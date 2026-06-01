<?php

namespace App\Controllers\Api\V1;

use App\Models\ArticleModel;
use App\Models\BbsModel;
use App\Models\CommentModel;
use App\Models\FileModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 게시글 API
 *
 * GET    /api/v1/boards/:bbsId/articles
 * POST   /api/v1/boards/:bbsId/articles
 * GET    /api/v1/boards/:bbsId/articles/:idx
 * PUT    /api/v1/boards/:bbsId/articles/:idx
 * DELETE /api/v1/boards/:bbsId/articles/:idx
 */
class ArticleController extends BaseApiController
{
    private BbsModel     $bbs;
    private ArticleModel $article;
    private FileModel    $file;

    public function __construct()
    {
        $this->bbs     = new BbsModel();
        $this->article = new ArticleModel();
        $this->file    = new FileModel();
    }

    public function index(string $bbsId): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return $this->failForbidden('접근 권한이 없습니다.');
        }

        $keyword = $this->request->getGet('keyword');
        $perPage = max(1, min(50, (int) ($this->request->getGet('per_page') ?? 15)));
        $articles = $this->article->getList($board['idx'], $keyword, $perPage);

        return $this->successList($articles, [
            'page'      => $this->article->_pagerPage,
            'per_page'  => $this->article->_pagerPerPage,
            'total'     => $this->article->_pagerTotal,
            'last_page' => (int) ceil($this->article->_pagerTotal / max(1, $this->article->_pagerPerPage)),
        ]);
    }

    public function show(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return $this->failForbidden('접근 권한이 없습니다.');
        }

        $post = $this->article->getArticleWithContents($idx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }

        $this->article->incrementHit($board['idx'], $idx);
        $post['hit_count']++;
        $post['tags']     = $this->article->getTagsByArticle($idx);
        $post['urls']     = $this->article->getUrlsByArticle($idx);
        $post['files']    = $this->file->getByArticle($idx);
        $post['comments'] = (new CommentModel())->getByArticle($idx);

        return $this->success($post);
    }

    public function create(string $bbsId): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return $this->failForbidden('글 작성 권한이 없습니다.');
        }

        $body     = (array) $this->request->getJSON(true);
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || ! $contents) {
            return $this->failValidation([], '제목과 내용을 입력해주세요.');
        }

        $userIdx    = $this->getUserIdx();
        $articleIdx = $this->article->writeArticle([
            'bbs_idx'          => $board['idx'],
            'user_idx'         => $userIdx,
            'exec_user_idx'    => $userIdx,
            'title'            => $title,
            'html_used'        => 0,
            'is_notice'        => 0,
            'is_secret'        => (int) ($body['is_secret'] ?? 0),
            'is_deleted'       => 0,
            'timestamp_insert' => time(),
            'client_ip_insert' => $this->request->getIPAddress(),
            'agent_insert'     => 'P',
        ], $contents);

        $tags = array_filter(array_map('trim', (array) ($body['tags'] ?? [])));
        $urls = array_filter(array_map('trim', (array) ($body['urls'] ?? [])));
        if ($tags) $this->article->saveTagsForArticle($board['idx'], $articleIdx, $tags);
        if ($urls) $this->article->saveUrlsForArticle($board['idx'], $articleIdx, $urls);

        clear_home_cache();

        return $this->created(['idx' => $articleIdx], '게시글이 작성되었습니다.');
    }

    public function update(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }

        $post = $this->article->find($idx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }
        if ((int) $post['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden('수정 권한이 없습니다.');
        }

        $body     = (array) $this->request->getJSON(true);
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || ! $contents) {
            return $this->failValidation([], '제목과 내용을 입력해주세요.');
        }

        $this->article->updateArticle($idx, [
            'title'            => $title,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ], $contents);

        $tags = array_filter(array_map('trim', (array) ($body['tags'] ?? [])));
        $urls = array_filter(array_map('trim', (array) ($body['urls'] ?? [])));
        $this->article->saveTagsForArticle($board['idx'], $idx, $tags);
        $this->article->saveUrlsForArticle($board['idx'], $idx, $urls);

        clear_home_cache();

        return $this->success(null, '게시글이 수정되었습니다.');
    }

    public function delete(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }

        $post = $this->article->find($idx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }
        if ((int) $post['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden('삭제 권한이 없습니다.');
        }

        $this->article->softDelete($idx);
        clear_home_cache();

        return $this->success(null, '게시글이 삭제되었습니다.');
    }
}
