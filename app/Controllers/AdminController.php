<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class AdminController extends Controller
{
    private function db()
    {
        return \Config\Database::connect();
    }

    /** 게시판 설정 파라미터 목록 (관리 대상만) */
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

    /** 대시보드 */
    public function index(): RedirectResponse
    {
        return redirect()->to('/admin/boards');
    }

    /** 게시판 목록 */
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
            'title'  => '게시판 관리',
            'boards' => $rows,
            'groups' => $groups,
        ]);
    }

    /** 게시판 개별 설정 폼 */
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
            'title'   => '게시판 설정',
            'bbs'     => $bbs,
            'setting' => $settingMap,
            'groups'  => $groups,
        ]);
    }

    /** 게시판 설정 저장 */
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
                        'value'        => $value,
                        'exec_user_idx' => $userIdx,
                        'client_ip'    => $clientIp,
                    ]);
            } else {
                $db->table('tb_bbs_setting')->insert([
                    'bbs_idx'      => $bbs['idx'],
                    'parameter'    => $parameter,
                    'value'        => $value,
                    'exec_user_idx' => $userIdx,
                    'client_ip'    => $clientIp,
                ]);
            }
        }

        return redirect()->to('/admin/boards')->with('success', "'{$bbsId}' 게시판 설정이 저장되었습니다.");
    }

    /** 사이트 설정 폼 */
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
            'title'   => '사이트 설정',
            'setting' => $settingMap,
        ]);
    }

    /** 사이트 설정 저장 */
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

        return redirect()->to('/admin/setting')->with('success', '사이트 설정이 저장되었습니다.');
    }

    /* ------------------------------------------------------------------ */
    /* 회원 관리                                                             */
    /* ------------------------------------------------------------------ */

    public function members(): string
    {
        $db      = $this->db();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $status  = $this->request->getGet('status');   // '' | '0' | '1'
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
            'title'   => '회원 관리',
            'users'   => $users,
            'groups'  => $groups,
            'keyword' => $keyword,
            'status'  => $status,
            'pager'   => ['total' => $total, 'page' => $page, 'perPage' => $perPage],
        ]);
    }

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
            'title'  => '회원 정보 수정',
            'user'   => $user,
            'groups' => $groups,
        ]);
    }

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

        // 비밀번호 재설정 (입력된 경우만)
        $newPw = trim($post['new_password'] ?? '');
        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                return redirect()->back()->with('error', '비밀번호는 6자 이상이어야 합니다.');
            }
            $data['super_secured_password']    = password_hash($newPw, PASSWORD_BCRYPT);
            $data['timestamp_update_password'] = time();
            $data['client_ip_update_password'] = $clientIp;
        }

        // 닉네임·이메일 중복 체크 (본인 제외)
        if ($db->table('tb_users')->where('nickname', $data['nickname'])->where('idx !=', $idx)->countAllResults()) {
            return redirect()->back()->with('error', '이미 사용 중인 닉네임입니다.')->withInput();
        }
        if ($db->table('tb_users')->where('email', $data['email'])->where('idx !=', $idx)->countAllResults()) {
            return redirect()->back()->with('error', '이미 사용 중인 이메일입니다.')->withInput();
        }

        $db->table('tb_users')->where('idx', $idx)->update($data);

        return redirect()->to('/admin/members')->with('success', "회원 정보가 수정되었습니다. ({$user['user_id']})");
    }

    /* ------------------------------------------------------------------ */
    /* 게시물 관리                                                           */
    /* ------------------------------------------------------------------ */

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

        // 게시판 목록 (필터용)
        $boards = $db->table('tb_bbs b')
            ->select("b.bbs_id, COALESCE(sn.value, b.bbs_id) AS bbs_name")
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->orderBy('b.idx')
            ->get()->getResultArray();

        return view('admin/posts', [
            'title'   => '게시물 관리',
            'posts'   => $posts,
            'boards'  => $boards,
            'keyword' => $keyword,
            'bbsId'   => $bbsId,
            'pager'   => ['total' => $total, 'page' => $page, 'perPage' => $perPage],
        ]);
    }

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
            'title' => '게시물 수정',
            'post'  => $post,
        ]);
    }

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
            return redirect()->back()->with('error', '제목과 내용을 입력해주세요.')->withInput();
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

        // 돌아갈 URL 복원
        $back = $this->request->getPost('back') ?? '/admin/posts';
        return redirect()->to($back)->with('success', '게시물이 수정되었습니다.');
    }

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

        // 돌아갈 URL 복원
        $back = $this->request->getGet('back') ?? '/admin/posts';
        return redirect()->to($back)->with('success', '게시물이 삭제되었습니다.');
    }
}
