<?php

namespace App\Controllers;

use App\Models\BbsModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\FileModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

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

    /** 파일 업로드 처리 공통 메서드 */
    private function uploadFiles(int $bbsIdx, int $articleIdx, int $userIdx): array
    {
        $errors    = [];
        $uploaded  = $this->request->getFiles('attachments') ?? [];
        $files     = is_array($uploaded) ? $uploaded : [$uploaded];

        // 허용 확장자 목록
        $allowedExts = ['jpg','jpeg','gif','png','txt','doc','docx','xls','xlsx',
                        'pdf','ppt','pptx','zip','7z','alz','rar'];
        $maxSize     = 2 * 1024 * 1024; // 2MB
        $maxCount    = 5;

        $existing = count($this->file->getByArticle($articleIdx));
        $added    = 0;

        foreach ($files as $file) {
            if (! $file instanceof \CodeIgniter\HTTP\Files\UploadedFile) continue;
            if (! $file->isValid() || $file->hasMoved()) continue;
            if ($file->getError() === UPLOAD_ERR_NO_FILE) continue;

            if ($existing + $added >= $maxCount) {
                $errors[] = "파일은 최대 {$maxCount}개까지 첨부할 수 있습니다.";
                break;
            }

            $ext = strtolower($file->getClientExtension());
            if (! in_array($ext, $allowedExts, true)) {
                $errors[] = "'{$file->getClientFilename()}': 허용되지 않는 확장자입니다.";
                continue;
            }
            if ($file->getSize() > $maxSize) {
                $errors[] = "'{$file->getClientFilename()}': 파일 크기가 2MB를 초과합니다.";
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

    /** 게시판 목록 */
    public function index(string $bbsId): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException("게시판을 찾을 수 없습니다: {$bbsId}");
        }

        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
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

    /** 게시글 보기 */
    public function view(string $bbsId, int $articleIdx): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $post = $this->article->getArticleWithContents($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            throw new PageNotFoundException("게시글을 찾을 수 없습니다.");
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

    /** 글쓰기 폼 */
    public function write(string $bbsId): string|RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return redirect()->back()->with('error', '글쓰기 권한이 없습니다.');
        }

        return view('board/write', [
            'title' => '글쓰기',
            'board' => $board,
        ]);
    }

    /** 글쓰기 처리 */
    public function writeProcess(string $bbsId): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return redirect()->to('/')->with('error', '글쓰기 권한이 없습니다.');
        }

        $title    = trim($this->request->getPost('title'));
        $contents = $this->request->getPost('contents');

        if (! $title || ! $contents) {
            return redirect()->back()->with('error', '제목과 내용을 입력해주세요.')->withInput();
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

        $msg = '게시글이 등록되었습니다.';
        if ($fileErrors) {
            $msg .= ' (파일 오류: ' . implode(', ', $fileErrors) . ')';
        }

        clear_home_cache();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}")->with('success', $msg);
    }

    /** 글수정 폼 */
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
            return redirect()->back()->with('error', '수정 권한이 없습니다.');
        }

        return view('board/edit', [
            'title' => '글수정',
            'board' => $board,
            'post'  => $post,
            'tags'  => $this->article->getTagsByArticle($articleIdx),
            'urls'  => $this->article->getUrlsByArticle($articleIdx),
            'files' => $this->file->getByArticle($articleIdx),
        ]);
    }

    /** 글수정 처리 */
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
            return redirect()->back()->with('error', '수정 권한이 없습니다.');
        }

        $title    = trim($this->request->getPost('title'));
        $contents = $this->request->getPost('contents');

        if (! $title || ! $contents) {
            return redirect()->back()->with('error', '제목과 내용을 입력해주세요.')->withInput();
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

        // 삭제 요청된 첨부파일 처리
        $deleteFileIdxs = array_map('intval', (array) $this->request->getPost('delete_files'));
        foreach ($deleteFileIdxs as $fileIdx) {
            if ($fileIdx <= 0) continue;
            $f = $this->file->find($fileIdx);
            if ($f && $f['article_idx'] == $articleIdx) {
                $this->file->deleteFile($fileIdx);
            }
        }

        $fileErrors = $this->uploadFiles($board['idx'], $articleIdx, (int) session()->get('user_idx'));

        $msg = '게시글이 수정되었습니다.';
        if ($fileErrors) {
            $msg .= ' (파일 오류: ' . implode(', ', $fileErrors) . ')';
        }

        clear_home_cache();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}")->with('success', $msg);
    }

    /** 글삭제 */
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
            return redirect()->back()->with('error', '삭제 권한이 없습니다.');
        }

        $this->article->softDelete($articleIdx);
        clear_home_cache();

        return redirect()->to("/board/{$bbsId}")->with('success', '게시글이 삭제되었습니다.');
    }

    /** 댓글 작성 */
    public function commentWrite(string $bbsId, int $articleIdx): RedirectResponse
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            throw new PageNotFoundException();
        }

        if (! user_can_in_groups($board['permissions']['write_comment'] ?? [])) {
            return redirect()->back()->with('error', '댓글 작성 권한이 없습니다.');
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            throw new PageNotFoundException();
        }

        $comment = trim($this->request->getPost('comment'));
        if (! $comment) {
            return redirect()->back()->with('error', '댓글 내용을 입력해주세요.');
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

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comments")->with('success', '댓글이 등록되었습니다.');
    }

    /** 댓글 수정 */
    public function commentEdit(string $bbsId, int $articleIdx, int $commentIdx): RedirectResponse
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx || $c['is_deleted']) {
            throw new PageNotFoundException();
        }

        if ((int) $c['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', '수정 권한이 없습니다.');
        }

        $text = trim($this->request->getPost('comment'));
        if (! $text) {
            return redirect()->back()->with('error', '댓글 내용을 입력해주세요.');
        }

        $this->comment->update($commentIdx, [
            'comment'          => $text,
            'exec_user_idx'    => session()->get('user_idx'),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comment-{$commentIdx}")->with('success', '댓글이 수정되었습니다.');
    }

    /** 댓글 삭제 */
    public function commentDelete(string $bbsId, int $articleIdx, int $commentIdx): RedirectResponse
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx) {
            throw new PageNotFoundException();
        }

        if ((int) $c['user_idx'] !== (int) session()->get('user_idx')) {
            return redirect()->back()->with('error', '삭제 권한이 없습니다.');
        }

        $this->comment->softDelete($commentIdx);

        $this->article->set('comment_count', 'GREATEST(comment_count - 1, 0)', false)
                       ->where('idx', $articleIdx)
                       ->update();

        return redirect()->to("/board/{$bbsId}/view/{$articleIdx}#comments")->with('success', '댓글이 삭제되었습니다.');
    }
}
