<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * @api {group} Admin 관리자
 * @apiGroup Admin
 * @apiPermission 최고관리자
 * @apiDescription 게시판 설정·사이트 설정·회원 관리·게시글 관리를 처리한다.
 *   모든 메소드는 AdminFilter를 통해 최고관리자 권한을 검사한다.
 */
class AdminController extends Controller
{
    private function db()
    {
        return \Config\Database::connect();
    }

    private const BBS_PERM_PARAMS = [
        'bbs_allow_group_view_list',
        'bbs_allow_group_view_article',
        'bbs_allow_group_write_article',
        'bbs_allow_group_write_comment',
    ];

    private const BBS_EDIT_PARAMS = [
        'bbs_name', 'bbs_used', 'bbs_count_list_article', 'bbs_comment_used',
        'bbs_allow_group_view_list', 'bbs_allow_group_view_article',
        'bbs_allow_group_write_article', 'bbs_allow_group_write_comment',
    ];

    private const SITE_PARAMS = [
        'browser_title_fix_value', 'join_used',
        'site_block_used', 'site_block_contents',
    ];

    /**
     * @api {get} /admin 관리자 홈
     * @apiGroup Admin
     * @apiName AdminIndex
     * @apiSuccess {String} redirect /admin/boards 로 이동
     */
    public function index(): RedirectResponse
    {
        return redirect()->to('/admin/boards');
    }

    /**
     * @api {get} /admin/boards 게시판 목록
     * @apiGroup Admin
     * @apiName AdminBoards
     * @apiDescription 전체 게시판과 각 게시판의 주요 설정값을 집계하여 표시한다.
     *
     * @apiSuccess {Array}  boards  게시판 행 (bbs_name, bbs_used, list_count, perm_view_list 등)
     * @apiSuccess {Array}  groups  사용 가능한 사용자 그룹 목록
     */
    public function boards(): string
    {
        $db = $this->db();

        $rows = $db->table('tb_bbs b')
            ->select("b.idx, b.bbs_id,
                MAX(CASE WHEN s.parameter='bbs_name'         THEN s.value END) AS bbs_name,
                MAX(CASE WHEN s.parameter='bbs_used'         THEN s.value END) AS bbs_used,
                MAX(CASE WHEN s.parameter='bbs_count_list_article' THEN s.value END) AS list_count,
                MAX(CASE WHEN s.parameter='bbs_comment_used' THEN s.value END) AS comment_used,
                MAX(CASE WHEN s.parameter='bbs_allow_group_view_list'    THEN s.value END) AS perm_view_list,
                MAX(CASE WHEN s.parameter='bbs_allow_group_write_article' THEN s.value END) AS perm_write_article")
            ->join('tb_bbs_setting s', 's.bbs_idx = b.idx')
            ->groupBy('b.idx, b.bbs_id')
            ->orderBy('b.idx', 'ASC')
            ->get()->getResultArray();

        $groups = $db->table('tb_users_group')->where('is_used', 1)->orderBy('idx')->get()->getResultArray();

        return view('admin/boards', [
            'title'  => lang('App.admin_boards'),
            'boards' => $rows,
            'groups' => $groups,
        ]);
    }

    /**
     * @api {get} /admin/boards/:bbsId/edit 게시판 설정 폼
     * @apiGroup Admin
     * @apiName AdminBoardEdit
     *
     * @apiParam  {String} bbsId    게시판 슬러그
     * @apiSuccess {Object} bbs     게시판 기본 정보
     * @apiSuccess {Object} setting 설정 파라미터 맵 (parameter → value)
     * @apiSuccess {Array}  groups  사용자 그룹 목록
     */
    public function boardEdit(string $bbsId): string|RedirectResponse
    {
        $db  = $this->db();
        $bbs = $db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
        if (! $bbs) {
            throw new PageNotFoundException();
        }

        $settings = $db->table('tb_bbs_setting')
            ->whereIn('parameter', self::BBS_EDIT_PARAMS)
            ->where('bbs_idx', $bbs['idx'])
            ->get()->getResultArray();

        $settingMap = [];
        foreach ($settings as $s) {
            $settingMap[$s['parameter']] = $s['value'];
        }

        $groups = $db->table('tb_users_group')->where('is_used', 1)->orderBy('idx')->get()->getResultArray();

        return view('admin/board_edit', [
            'title'   => lang('App.board_basic_settings'),
            'bbs'     => $bbs,
            'setting' => $settingMap,
            'groups'  => $groups,
        ]);
    }

    /**
     * @api {post} /admin/boards/:bbsId/edit 게시판 설정 저장
     * @apiGroup Admin
     * @apiName AdminBoardEditProcess
     * @apiDescription tb_bbs_setting 행이 존재하면 UPDATE, 없으면 INSERT(upsert)한다.
     *   권한 파라미터 값은 PHP serialize() 형식으로 저장된다.
     *
     * @apiParam  {String}   bbsId                    게시판 슬러그
     * @apiBody   {String}   bbs_name                 게시판 이름
     * @apiBody   {Boolean}  [bbs_used]               활성화 여부
     * @apiBody   {Number}   bbs_count_list_article   목록 표시 수 (최소 1)
     * @apiBody   {Boolean}  [bbs_comment_used]       댓글 기능 여부
     * @apiBody   {Number[]} [view_list]              view_list 허용 그룹 idx 배열
     * @apiBody   {Number[]} [view_article]           view_article 허용 그룹 idx 배열
     * @apiBody   {Number[]} [write_article]          write_article 허용 그룹 idx 배열
     * @apiBody   {Number[]} [write_comment]          write_comment 허용 그룹 idx 배열
     * @apiSuccess {String} redirect                   /admin/boards 로 이동
     */
    public function boardEditProcess(string $bbsId): RedirectResponse
    {
        $db  = $this->db();
        $bbs = $db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
        if (! $bbs) {
            throw new PageNotFoundException();
        }

        $post     = $this->request->getPost();
        $userIdx  = (int) session()->get('user_idx');
        $clientIp = $this->request->getIPAddress();

        $updates = [
            'bbs_name'               => trim($post['bbs_name'] ?? ''),
            'bbs_used'               => isset($post['bbs_used']) ? '1' : '0',
            'bbs_count_list_article' => (string) max(1, (int) ($post['bbs_count_list_article'] ?? 15)),
            'bbs_comment_used'       => isset($post['bbs_comment_used']) ? '1' : '0',
        ];

        foreach (self::BBS_PERM_PARAMS as $param) {
            $key = str_replace('bbs_allow_group_', '', $param);
            $selected = array_map('strval', (array) ($post[$key] ?? []));
            $updates[$param] = $selected ? serialize(array_values($selected)) : serialize([]);
        }

        foreach ($updates as $parameter => $value) {
            $exists = $db->table('tb_bbs_setting')
                ->where('bbs_idx', $bbs['idx'])
                ->where('parameter', $parameter)
                ->get()->getRowArray();

            if ($exists) {
                $db->table('tb_bbs_setting')
                    ->where('bbs_idx', $bbs['idx'])
                    ->where('parameter', $parameter)
                    ->update([
                        'value'         => $value,
                        'exec_user_idx' => $userIdx,
                        'client_ip'     => $clientIp,
                    ]);
            } else {
                $db->table('tb_bbs_setting')->insert([
                    'bbs_idx'       => $bbs['idx'],
                    'parameter'     => $parameter,
                    'value'         => $value,
                    'exec_user_idx' => $userIdx,
                    'client_ip'     => $clientIp,
                ]);
            }
        }

        return redirect()->to('/admin/boards')->with('success', lang('App.msg_board_setting_saved', [$bbsId]));
    }

    /**
     * @api {get} /admin/setting 사이트 설정 폼
     * @apiGroup Admin
     * @apiName AdminSetting
     *
     * @apiSuccess {Object} setting  설정 파라미터 맵
     *   (browser_title_fix_value, join_used, site_block_used, site_block_contents)
     */
    public function setting(): string
    {
        $db = $this->db();

        $rows = $db->table('tb_setting')
            ->whereIn('parameter', self::SITE_PARAMS)
            ->get()->getResultArray();

        $settingMap = [];
        foreach ($rows as $r) {
            $settingMap[$r['parameter']] = $r['value'];
        }

        return view('admin/setting', [
            'title'   => lang('App.admin_setting'),
            'setting' => $settingMap,
        ]);
    }

    /**
     * @api {post} /admin/setting 사이트 설정 저장
     * @apiGroup Admin
     * @apiName AdminSettingProcess
     *
     * @apiBody  {String}  browser_title_fix_value  브라우저 타이틀 접미어
     * @apiBody  {Boolean} [join_used]              회원가입 허용 여부
     * @apiBody  {Boolean} [site_block_used]        사이트 차단 여부
     * @apiBody  {String}  [site_block_contents]    차단 시 표시할 메시지
     * @apiSuccess {String} redirect                 /admin/setting 으로 이동
     */
    public function settingProcess(): RedirectResponse
    {
        $db       = $this->db();
        $post     = $this->request->getPost();
        $userIdx  = (int) session()->get('user_idx');
        $clientIp = $this->request->getIPAddress();

        $updates = [
            'browser_title_fix_value' => trim($post['browser_title_fix_value'] ?? ''),
            'join_used'               => isset($post['join_used']) ? '1' : '0',
            'site_block_used'         => isset($post['site_block_used']) ? '1' : '0',
            'site_block_contents'     => trim($post['site_block_contents'] ?? ''),
        ];

        foreach ($updates as $parameter => $value) {
            $db->table('tb_setting')
                ->where('parameter', $parameter)
                ->update([
                    'value'         => $value,
                    'exec_user_idx' => $userIdx,
                    'client_ip'     => $clientIp,
                ]);
        }

        return redirect()->to('/admin/setting')->with('success', lang('App.msg_site_setting_saved'));
    }

    /**
     * @api {get} /admin/members 회원 목록
     * @apiGroup Admin
     * @apiName AdminMembers
     *
     * @apiQuery {String} [keyword]  아이디·닉네임·이메일 검색어
     * @apiQuery {Number} [status]   회원 상태 필터 (0=탈퇴, 1=정상)
     * @apiQuery {Number} [page=1]   페이지 번호
     * @apiSuccess {Array}  users    회원 목록 (group_name JOIN 포함)
     * @apiSuccess {Array}  groups   전체 그룹 목록
     * @apiSuccess {Object} pager    페이지네이션 메타
     */
    public function members(): string
    {
        $db      = $this->db();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $status  = $this->request->getGet('status');
        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_users u')
            ->select('u.idx, u.user_id, u.nickname, u.email, u.status, u.timestamp_insert,
                      u.article_count, u.comment_count, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->orderBy('u.idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.user_id', $keyword)
                ->orLike('u.nickname', $keyword)
                ->orLike('u.email', $keyword)
                ->groupEnd();
        }
        if ($status !== null && $status !== '') {
            $builder->where('u.status', (int) $status);
        }

        $total  = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;
        $users  = $builder->limit($perPage, $offset)->get()->getResultArray();
        $groups = $db->table('tb_users_group')->orderBy('idx')->get()->getResultArray();

        return view('admin/members', [
            'title'   => lang('App.admin_members'),
            'users'   => $users,
            'groups'  => $groups,
            'keyword' => $keyword,
            'status'  => $status,
            'pager'   => ['total' => $total, 'page' => $page, 'perPage' => $perPage],
        ]);
    }

    /**
     * @api {get} /admin/members/:idx/edit 회원 수정 폼
     * @apiGroup Admin
     * @apiName AdminMemberEdit
     *
     * @apiParam  {Number} idx   회원 idx
     * @apiSuccess {Object} user   회원 정보 (group_name 포함)
     * @apiSuccess {Array}  groups 전체 그룹 목록
     */
    public function memberEdit(int $idx): string|RedirectResponse
    {
        $db   = $this->db();
        $user = $db->table('tb_users u')
            ->select('u.*, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->where('u.idx', $idx)
            ->get()->getRowArray();

        if (! $user) {
            throw new PageNotFoundException();
        }

        $groups = $db->table('tb_users_group')->orderBy('idx')->get()->getResultArray();

        return view('admin/member_edit', [
            'title'  => lang('App.admin_member_info'),
            'user'   => $user,
            'groups' => $groups,
        ]);
    }

    /**
     * @api {post} /admin/members/:idx/edit 회원 수정 저장
     * @apiGroup Admin
     * @apiName AdminMemberEditProcess
     * @apiDescription 닉네임·이메일 중복 여부를 검사한 뒤 저장한다. 비밀번호는 입력 시에만 변경된다.
     *
     * @apiParam  {Number} idx             회원 idx
     * @apiBody   {Number} group_idx       그룹 idx
     * @apiBody   {Number} status          상태 (0=탈퇴, 1=정상)
     * @apiBody   {String} nickname        닉네임
     * @apiBody   {String} email           이메일
     * @apiBody   {String} [new_password]  새 비밀번호 (최소 6자, 생략 시 변경 안 함)
     * @apiSuccess {String} redirect        /admin/members 로 이동
     */
    public function memberEditProcess(int $idx): RedirectResponse
    {
        $db   = $this->db();
        $user = $db->table('tb_users')->where('idx', $idx)->get()->getRowArray();
        if (! $user) {
            throw new PageNotFoundException();
        }

        $post     = $this->request->getPost();
        $clientIp = $this->request->getIPAddress();

        $data = [
            'group_idx'        => (int) $post['group_idx'],
            'status'           => (int) $post['status'],
            'nickname'         => trim($post['nickname']),
            'name'             => trim($post['nickname']),
            'email'            => trim($post['email']),
            'timestamp_update' => time(),
            'client_ip_update' => $clientIp,
        ];

        $newPw = trim($post['new_password'] ?? '');
        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                return redirect()->back()->with('error', lang('App.msg_password_length'));
            }
            $data['super_secured_password']    = password_hash($newPw, PASSWORD_BCRYPT);
            $data['timestamp_update_password'] = time();
            $data['client_ip_update_password'] = $clientIp;
        }

        if ($db->table('tb_users')->where('nickname', $data['nickname'])->where('idx !=', $idx)->countAllResults()) {
            return redirect()->back()->with('error', lang('App.msg_nickname_taken'))->withInput();
        }
        if ($db->table('tb_users')->where('email', $data['email'])->where('idx !=', $idx)->countAllResults()) {
            return redirect()->back()->with('error', lang('App.msg_email_taken'))->withInput();
        }

        $db->table('tb_users')->where('idx', $idx)->update($data);

        return redirect()->to('/admin/members')->with('success', lang('App.msg_profile_saved'));
    }

    /**
     * @api {get} /admin/posts 게시글 관리 목록
     * @apiGroup Admin
     * @apiName AdminPosts
     *
     * @apiQuery {String} [keyword]  제목·닉네임 검색어
     * @apiQuery {String} [bbs_id]   게시판 슬러그 필터
     * @apiQuery {Number} [page=1]   페이지 번호
     * @apiSuccess {Array}  posts    게시글 목록 (bbs_name·nickname·hit_count 포함)
     * @apiSuccess {Array}  boards   전체 게시판 목록 (필터용)
     * @apiSuccess {Object} pager    페이지네이션 메타
     */
    public function posts(): string
    {
        $db      = $this->db();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $bbsId   = trim($this->request->getGet('bbs_id') ?? '');
        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.is_notice, a.is_deleted, a.timestamp_insert,
                      a.comment_count, a.vote_count, b.bbs_id, b.idx as bbs_idx,
                      COALESCE(sn.value, b.bbs_id) AS bbs_name,
                      u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_bbs b', 'b.idx = a.bbs_idx')
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.is_deleted', 0)
            ->orderBy('a.idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('a.title', $keyword)
                ->orLike('u.nickname', $keyword)
                ->groupEnd();
        }
        if ($bbsId !== '') {
            $builder->where('b.bbs_id', $bbsId);
        }

        $total  = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;
        $posts  = $builder->limit($perPage, $offset)->get()->getResultArray();

        $boards = $db->table('tb_bbs b')
            ->select("b.bbs_id, COALESCE(sn.value, b.bbs_id) AS bbs_name")
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->orderBy('b.idx')
            ->get()->getResultArray();

        return view('admin/posts', [
            'title'   => lang('App.admin_posts_title'),
            'posts'   => $posts,
            'boards'  => $boards,
            'keyword' => $keyword,
            'bbsId'   => $bbsId,
            'pager'   => ['total' => $total, 'page' => $page, 'perPage' => $perPage],
        ]);
    }

    /**
     * @api {get} /admin/posts/:idx/edit 게시글 수정 폼 (관리자)
     * @apiGroup Admin
     * @apiName AdminPostEdit
     *
     * @apiParam  {Number} idx   게시글 idx
     * @apiSuccess {Object} post  게시글 (본문·게시판명 포함)
     */
    public function postEdit(int $idx): string|RedirectResponse
    {
        $db   = $this->db();
        $post = $db->table('tb_bbs_article a')
            ->select('a.*, c.contents, b.bbs_id, COALESCE(sn.value, b.bbs_id) AS bbs_name')
            ->join('tb_bbs b', 'b.idx = a.bbs_idx')
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->join('tb_bbs_contents c', 'c.article_idx = a.idx', 'left')
            ->where('a.idx', $idx)
            ->where('a.is_deleted', 0)
            ->get()->getRowArray();

        if (! $post) {
            throw new PageNotFoundException();
        }

        return view('admin/post_edit', [
            'title' => lang('App.admin_post_edit'),
            'post'  => $post,
        ]);
    }

    /**
     * @api {post} /admin/posts/:idx/edit 게시글 수정 저장 (관리자)
     * @apiGroup Admin
     * @apiName AdminPostEditProcess
     * @apiDescription article + contents 를 트랜잭션으로 함께 업데이트한다. is_notice 토글 가능.
     *
     * @apiParam  {Number}  idx         게시글 idx
     * @apiBody   {String}  title       수정할 제목
     * @apiBody   {String}  contents    수정할 본문
     * @apiBody   {Boolean} [is_notice] 공지 여부
     * @apiBody   {String}  [back]      완료 후 리다이렉트 URL (기본: /admin/posts)
     * @apiSuccess {String} redirect     back 파라미터 URL 또는 /admin/posts 로 이동
     */
    public function postEditProcess(int $idx): RedirectResponse
    {
        $db   = $this->db();
        $post = $db->table('tb_bbs_article')->where('idx', $idx)->where('is_deleted', 0)->get()->getRowArray();
        if (! $post) {
            throw new PageNotFoundException();
        }

        $title    = trim($this->request->getPost('title'));
        $contents = $this->request->getPost('contents');
        $isNotice = (int) (bool) $this->request->getPost('is_notice');

        if (! $title || ! $contents) {
            return redirect()->back()->with('error', lang('App.msg_admin_title_required'))->withInput();
        }

        $db->transStart();

        $db->table('tb_bbs_article')->where('idx', $idx)->update([
            'title'            => $title,
            'is_notice'        => $isNotice,
            'exec_user_idx'    => session()->get('user_idx'),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        $db->table('tb_bbs_contents')->where('article_idx', $idx)->update([
            'contents'      => $contents,
            'exec_user_idx' => session()->get('user_idx'),
            'client_ip'     => $this->request->getIPAddress(),
        ]);

        $db->transComplete();

        clear_home_cache();

        $back = $this->request->getPost('back') ?? '/admin/posts';
        return redirect()->to($back)->with('success', lang('App.msg_admin_post_updated'));
    }

    /**
     * @api {get} /admin/posts/:idx/delete 게시글 소프트 삭제 (관리자)
     * @apiGroup Admin
     * @apiName AdminPostDelete
     *
     * @apiParam  {Number} idx    게시글 idx
     * @apiQuery  {String} [back] 완료 후 리다이렉트 URL (기본: /admin/posts)
     * @apiSuccess {String} redirect
     */
    public function postDelete(int $idx): RedirectResponse
    {
        $db   = $this->db();
        $post = $db->table('tb_bbs_article')->where('idx', $idx)->get()->getRowArray();
        if (! $post) {
            throw new PageNotFoundException();
        }

        $db->table('tb_bbs_article')->where('idx', $idx)->update([
            'is_deleted'       => 1,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
            'exec_user_idx'    => session()->get('user_idx'),
        ]);

        clear_home_cache();

        $back = $this->request->getGet('back') ?? '/admin/posts';
        return redirect()->to($back)->with('success', lang('App.msg_admin_post_deleted'));
    }
}
