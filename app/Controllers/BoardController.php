<?php

namespace App\Controllers;

use App\Models\BbsModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\FileModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * @api {group} Board 게시판
 * @apiGroup Board
 * @apiDescription 게시판 목록·상세·작성·수정·삭제 및 댓글 CRUD를 처리한다.
 */
class BoardController extends Controller
{
    protected BbsModel     $bbs;
    protected ArticleModel $article;
    protected CommentModel $comment;
    protected FileModel    $file;

    public function __construct()
    {
        $this->bbs     = new BbsModel();
        $this->article = new ArticleModel();
        $this->comment = new CommentModel();
        $this->file    = new FileModel();
    }

    /**
     * @api {post} /board/:bbsId/write/process 게시글 첨부파일 업로드 (내부)
     * @apiGroup Board
     * @apiPrivate
     * @apiDescription 폼에서 전송된 attachments[] 파일을 검증하고 저장한다.
     *   최대 5개, 개당 2MB, 허용 확장자 목록 이외는 무시된다.
     *
     * @apiParam  {Number} bbsIdx      게시판 idx
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiParam  {Number} userIdx     업로드한 사용자 idx
     * @apiSuccess {String[]} errors   실패한 파일에 대한 오류 메시지 배열 (비어 있으면 전부 성공)
     */
    private function uploadFiles(int $bbsIdx, int $articleIdx, int $userIdx): array
    {
        $errors    = [];
        $uploaded  = $this->request->getFiles('attachments') ?? [];
        $files     = is_array($uploaded) ? $uploaded : [$uploaded];

        $allowedExts = ['jpg','jpeg','gif','png','txt','doc','docx','xls','xlsx',
                        'pdf','ppt','pptx','zip','7z','alz','rar'];
        $maxSize     = 2 * 1024 * 1024;
        $maxCount    = 5;

        $existing = count($this->file->getByArticle($articleIdx));
        $added    = 0;

        foreach ($files as $file) {
            if (! $file instanceof \CodeIgniter\HTTP\Files\UploadedFile) continue;
            if (! $file->isValid() || $file->hasMoved()) continue;
            if ($file->getError() === UPLOAD_ERR_NO_FILE) continue;

            if ($existing + $added >= $maxCount) {
                $errors[] = lang('App.msg_file_max', [$maxCount]);
                break;
            }

            $ext = strtolower($file->getClientExtension());
            if (! in_array($ext, $allowedExts, true)) {
                continue;
            }
            if ($file->getSize() > $maxSize) {
                continue;
            }

            $datePath = $bbsIdx . '/' . date('Ymd');
            $destDir  = WRITEPATH . 'uploads/' . $datePath;
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $newName = bin2hex(random_bytes(16)) . '.' . $ext;
            $file->move($destDir, $newName);

            $this->file->insert([
                'bbs_idx'             => $bbsIdx,
                'article_idx'         => $articleIdx,
                'user_idx'            => $userIdx,
                'is_wysiwyg'          => 0,
                'original_filename'   => $file->getClientFilename(),
                'conversion_filename' => $datePath . '/' . $newName,
                'mime'                => $file->getClientMimeType(),
                'capacity'            => $file->getSize(),
                'sequence'            => $existing + $added + 1,
            ]);

            $added++;
        }

        return $errors;
    }

    /**
     * @api {get} /board/:bbsId 게시글 목록
     * @apiGroup Board
     * @apiName BoardIndex
     * @apiDescription 게시판 글 목록을 페이지네이션과 함께 반환한다. view_list 권한이 필요하다.
     *
     * @apiParam  {String} bbsId         게시판 슬러그 (URL)
     * @apiQuery  {String} [keyword]     제목 검색어
     * @apiQuery  {Number} [page=1]      페이지 번호
     * @apiSuccess {String} title        페이지 타이틀
     * @apiSuccess {Object} board        게시판 정보 + 권한 맵
     * @apiSuccess {Array}  articles     게시글 목록
     * @apiSuccess {Object} pager        페이지네이션 메타 {total, page, perPage}
     */
    public function index(string $bbsId): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException(lang('App.msg_board_not_found', [$bbsId]));
        }

        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return redirect()->to('/')->with('error', lang('App.msg_no_permission'));
        }

        $keyword  = $this->request->getGet('keyword');
        $articles = $this->article->getList($board['idx'], $keyword, 15);

        return view('board/list', [
            'title'    => $board['bbs_name'] ?? $bbsId,
            'board'    => $board,
            'articles' => $articles,
            'keyword'  => $keyword,
            'pager'    => [
                'total'   => $this->article->_pagerTotal,
                'page'    => $this->article->_pagerPage,
                'perPage' => $this->article->_pagerPerPage,
            ],
        ]);
    }

    /**
     * @api {get} /board/:bbsId/view/:articleIdx 게시글 상세
     * @apiGroup Board
     * @apiName BoardView
     * @apiDescription 게시글 본문·댓글·태그·URL·첨부파일을 함께 반환한다. 조회수가 1 증가한다.
     *
     * @apiParam  {String} bbsId        게시판 슬러그
     * @apiParam  {Number} articleIdx   게시글 idx
     * @apiSuccess {Object} post        게시글 (본문 포함)
     * @apiSuccess {Array}  comments    댓글 목록
     * @apiSuccess {Array}  tags        태그 목록
     * @apiSuccess {Array}  urls        관련 URL 목록
     * @apiSuccess {Array}  files       첨부파일 목록
     */
    public function view(string $bbsId, int $articleIdx): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return redirect()->to('/')->with('error', lang('App.msg_no_permission'));
        }

        $post = $this->article->getArticleWithContents($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            throw new PageNotFoundException(lang('App.msg_post_not_found'));
        }

        $this->article->incrementHit($board['idx'], $articleIdx);
        $post['hit_count']++;

        $comments = $this->comment->getByArticle($articleIdx);
        $tags     = $this->article->getTagsByArticle($articleIdx);
        $urls     = $this->article->getUrlsByArticle($articleIdx);
        $files    = $this->file->getByArticle($articleIdx);

        return view('board/view', [
            'title'    => $post['title'],
            'board'    => $board,
            'post'     => $post,
            'comments' => $comments,
            'tags'     => $tags,
            'urls'     => $urls,
            'files'    => $files,
        ]);
    }

    /**
     * @api {get} /board/:bbsId/write 게시글 작성 폼
     * @apiGroup Board
     * @apiName BoardWrite
     * @apiPermission write_article
     *
     * @apiParam  {String} bbsId  게시판 슬러그
     * @apiSuccess {Object} board  게시판 정보
     */
    public function write(string $bbsId): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return redirect()->back()->with('error', lang('App.msg_no_write_permission'));
        }

        return view('board/write', [
            'title' => lang('App.write_post'),
            'board' => $board,
        ]);
    }

    /**
     * @api {post} /board/:bbsId/write 게시글 저장
     * @apiGroup Board
     * @apiName BoardWriteProcess
     * @apiPermission write_article
     *
     * @apiParam   {String} bbsId          게시판 슬러그
     * @apiBody    {String} title           제목 (필수)
     * @apiBody    {String} contents        본문 (필수)
     * @apiBody    {String[]} [tags]        태그 배열
     * @apiBody    {String[]} [urls]        관련 URL 배열
     * @apiBody    {File[]}   [attachments] 첨부파일 (최대 5개, 개당 2MB)
     * @apiSuccess {String} redirect         작성된 게시글 상세 페이지로 이동
     */
    public function writeProcess(string $bbsId): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return redirect()->to('/')->with('error', lang('App.msg_no_write_permission'));
        }

        $title    = trim($this->request->getPost('title'));
        $contents = $this->request->getPost('contents');

        if (! $title || ! $contents) {
            return redirect()->back()->with('error', lang('App.msg_admin_title_required'))->withInput();
        }

        $userIdx  = session()->get('user_idx');
        $clientIp = $this->request->getIPAddress();

        $articleIdx = $this->article->writeArticle([
            'bbs_idx'          => $board['idx'],
            'user_idx'         => $userIdx,
            'exec_user_idx'    => $userIdx,
            'title'            => $title,
            'html_used'        => 0,
            'is_notice'        => 0,
            'is_secret'        => 0,
            'is_deleted'       => 0,
            'timestamp_insert' => time(),
            'client_ip_insert' => $clientIp,
            'agent_insert'     => 'P',
        ], $contents);

        $tags = array_filter(array_map('trim', (array) $this->request->getPost('tags')));
        $urls = array_filter(array_map('trim', (array) $this->request->getPost('urls')));
        if ($tags) $this->article->saveTagsForArticle($board['idx'], $articleIdx, $tags);
        if ($urls) $this->article->saveUrlsForArticle($board['idx'], $articleIdx, $urls);

        $fileErrors = $this->uploadFiles($board['idx'], $articleIdx, $userIdx);

        $msg = lang('App.msg_post_created');
        if ($fileErrors) {
            $msg .= lang('App.msg_file_error', [implode(', ', $fileErrors)]);
        }

        clear_home_cache();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}")->with('success', $msg);
    }

    /**
     * @api {get} /board/:bbsId/edit/:articleIdx 게시글 수정 폼
     * @apiGroup Board
     * @apiName BoardEdit
     * @apiPermission 본인
     *
     * @apiParam  {String} bbsId        게시판 슬러그
     * @apiParam  {Number} articleIdx   게시글 idx
     * @apiSuccess {Object} post        게시글 (본문 포함)
     * @apiSuccess {Array}  tags        기존 태그 목록
     * @apiSuccess {Array}  urls        기존 URL 목록
     * @apiSuccess {Array}  files       기존 첨부파일 목록
     */
    public function edit(string $bbsId, int $articleIdx): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        $post = $this->article->getArticleWithContents($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            throw new PageNotFoundException();
        }

        if ((int) $post['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', lang('App.msg_no_edit_permission'));
        }

        return view('board/edit', [
            'title' => lang('App.edit_post'),
            'board' => $board,
            'post'  => $post,
            'tags'  => $this->article->getTagsByArticle($articleIdx),
            'urls'  => $this->article->getUrlsByArticle($articleIdx),
            'files' => $this->file->getByArticle($articleIdx),
        ]);
    }

    /**
     * @api {post} /board/:bbsId/edit/:articleIdx 게시글 수정 저장
     * @apiGroup Board
     * @apiName BoardEditProcess
     * @apiPermission 본인
     *
     * @apiParam   {String} bbsId          게시판 슬러그
     * @apiParam   {Number} articleIdx     게시글 idx
     * @apiBody    {String} title           수정할 제목
     * @apiBody    {String} contents        수정할 본문
     * @apiBody    {String[]} [tags]        태그 배열 (전체 교체)
     * @apiBody    {String[]} [urls]        URL 배열 (전체 교체)
     * @apiBody    {Number[]} [delete_files] 삭제할 파일 idx 배열
     * @apiBody    {File[]}   [attachments]  추가 첨부파일
     * @apiSuccess {String} redirect          수정된 게시글 상세 페이지로 이동
     */
    public function editProcess(string $bbsId, int $articleIdx): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            throw new PageNotFoundException();
        }

        if ((int) $post['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', lang('App.msg_no_edit_permission'));
        }

        $title    = trim($this->request->getPost('title'));
        $contents = $this->request->getPost('contents');

        if (! $title || ! $contents) {
            return redirect()->back()->with('error', lang('App.msg_admin_title_required'))->withInput();
        }

        $this->article->updateArticle($articleIdx, [
            'title'            => $title,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ], $contents);

        $tags = array_filter(array_map('trim', (array) $this->request->getPost('tags')));
        $urls = array_filter(array_map('trim', (array) $this->request->getPost('urls')));
        $this->article->saveTagsForArticle($board['idx'], $articleIdx, $tags);
        $this->article->saveUrlsForArticle($board['idx'], $articleIdx, $urls);

        $deleteFileIdxs = array_map('intval', (array) $this->request->getPost('delete_files'));
        foreach ($deleteFileIdxs as $fileIdx) {
            if ($fileIdx <= 0) continue;
            $f = $this->file->find($fileIdx);
            if ($f && $f['article_idx'] == $articleIdx) {
                $this->file->deleteFile($fileIdx);
            }
        }

        $fileErrors = $this->uploadFiles($board['idx'], $articleIdx, (int) session()->get('user_idx'));

        $msg = lang('App.msg_post_updated');
        if ($fileErrors) {
            $msg .= lang('App.msg_file_error', [implode(', ', $fileErrors)]);
        }

        clear_home_cache();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}")->with('success', $msg);
    }

    /**
     * @api {get} /board/:bbsId/delete/:articleIdx 게시글 소프트 삭제
     * @apiGroup Board
     * @apiName BoardDelete
     * @apiPermission 본인
     *
     * @apiParam  {String} bbsId       게시판 슬러그
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {String} redirect    게시판 목록으로 이동
     */
    public function delete(string $bbsId, int $articleIdx): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            throw new PageNotFoundException();
        }

        if ((int) $post['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', lang('App.msg_no_delete_permission'));
        }

        $this->article->softDelete($articleIdx);
        clear_home_cache();

        return redirect()->to("/board/{$bbsId}")->with('success', lang('App.msg_post_deleted'));
    }

    /**
     * @api {post} /board/:bbsId/view/:articleIdx/comment 댓글 작성
     * @apiGroup Board
     * @apiName CommentWrite
     * @apiPermission write_comment
     *
     * @apiParam  {String} bbsId       게시판 슬러그
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiBody   {String} comment     댓글 본문 (필수)
     * @apiSuccess {String} redirect    게시글 상세 #comments 앵커로 이동
     */
    public function commentWrite(string $bbsId, int $articleIdx): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_comment'] ?? [])) {
            return redirect()->back()->with('error', lang('App.msg_no_comment_permission'));
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            throw new PageNotFoundException();
        }

        $comment = trim($this->request->getPost('comment'));
        if (! $comment) {
            return redirect()->back()->with('error', lang('App.msg_comment_empty'));
        }

        $userIdx = session()->get('user_idx');
        $this->comment->writeComment([
            'bbs_idx'          => $board['idx'],
            'article_idx'      => $articleIdx,
            'user_idx'         => $userIdx,
            'exec_user_idx'    => $userIdx,
            'comment'          => $comment,
            'timestamp_insert' => time(),
            'client_ip_insert' => $this->request->getIPAddress(),
            'is_deleted'       => 0,
            'agent_insert'     => 'P',
        ]);

        $this->article->set('comment_count', 'comment_count + 1', false)
                       ->where('idx', $articleIdx)
                       ->update();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comments");
    }

    /**
     * @api {post} /board/:bbsId/view/:articleIdx/comment/:commentIdx/edit 댓글 수정
     * @apiGroup Board
     * @apiName CommentEdit
     * @apiPermission 본인
     *
     * @apiParam  {String} bbsId        게시판 슬러그
     * @apiParam  {Number} articleIdx   게시글 idx
     * @apiParam  {Number} commentIdx   댓글 idx
     * @apiBody   {String} comment      수정할 댓글 본문
     * @apiSuccess {String} redirect     해당 댓글 앵커로 이동
     */
    public function commentEdit(string $bbsId, int $articleIdx, int $commentIdx): RedirectResponse
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx || $c['is_deleted']) {
            throw new PageNotFoundException();
        }

        if ((int) $c['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', lang('App.msg_no_edit_permission'));
        }

        $text = trim($this->request->getPost('comment'));
        if (! $text) {
            return redirect()->back()->with('error', lang('App.msg_comment_empty'));
        }

        $this->comment->update($commentIdx, [
            'comment'          => $text,
            'exec_user_idx'    => session()->get('user_idx'),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comment-{$commentIdx}");
    }

    /**
     * @api {get} /board/:bbsId/view/:articleIdx/comment/:commentIdx/delete 댓글 소프트 삭제
     * @apiGroup Board
     * @apiName CommentDelete
     * @apiPermission 본인
     *
     * @apiParam  {String} bbsId        게시판 슬러그
     * @apiParam  {Number} articleIdx   게시글 idx
     * @apiParam  {Number} commentIdx   댓글 idx
     * @apiSuccess {String} redirect     게시글 상세 #comments 앵커로 이동
     */
    public function commentDelete(string $bbsId, int $articleIdx, int $commentIdx): RedirectResponse
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx) {
            throw new PageNotFoundException();
        }

        if ((int) $c['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', lang('App.msg_no_delete_permission'));
        }

        $this->comment->softDelete($commentIdx);

        $this->article->set('comment_count', 'GREATEST(comment_count - 1, 0)', false)
                       ->where('idx', $articleIdx)
                       ->update();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comments");
    }
}
