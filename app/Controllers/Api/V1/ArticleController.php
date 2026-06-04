<?php

namespace App\Controllers\Api\V1;

use App\Models\ArticleModel;
use App\Models\BbsModel;
use App\Models\CommentModel;
use App\Models\FileModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/v1/boards/{bbsId}/articles',
        summary: '게시글 목록',
        tags: ['Article'],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '게시글 목록'),
        ]
    )]
    public function index(string $bbsId): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return $this->failForbidden(lang('Api.access_forbidden'));
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

    #[OA\Get(
        path: '/api/v1/boards/{bbsId}/articles/{idx}',
        summary: '게시글 상세',
        tags: ['Article'],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '게시글 상세'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return $this->failForbidden(lang('Api.access_forbidden'));
        }

        $post = $this->article->getArticleWithContents($idx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            return $this->failNotFound(lang('Api.article_not_found'));
        }

        $this->article->incrementHit($board['idx'], $idx);
        $post['hit_count']++;
        $post['tags']     = $this->article->getTagsByArticle($idx);
        $post['urls']     = $this->article->getUrlsByArticle($idx);
        $post['files']    = $this->file->getByArticle($idx);
        $post['comments'] = (new CommentModel())->getByArticle($idx);

        return $this->success($post);
    }

    #[OA\Post(
        path: '/api/v1/boards/{bbsId}/articles',
        summary: '게시글 작성',
        tags: ['Article'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'contents'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'is_secret', type: 'integer', enum: [0, 1]),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '게시글 작성 완료'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function create(string $bbsId): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['write_article'] ?? [])) {
            return $this->failForbidden(lang('Api.article_write_forbidden'));
        }

        $body     = (array) $this->request->getJSON(true);
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || ! $contents) {
            return $this->failValidation([], lang('Api.article_title_required'));
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

        return $this->created(['idx' => $articleIdx], lang('Api.article_created'));
    }

    #[OA\Put(
        path: '/api/v1/boards/{bbsId}/articles/{idx}',
        summary: '게시글 수정 (작성자만)',
        tags: ['Article'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'contents'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function update(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }

        $post = $this->article->find($idx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            return $this->failNotFound(lang('Api.article_not_found'));
        }
        if ((int) $post['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.edit_forbidden'));
        }

        $body     = (array) $this->request->getJSON(true);
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || ! $contents) {
            return $this->failValidation([], lang('Api.article_title_required'));
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

        return $this->success(null, lang('Api.article_updated'));
    }

    #[OA\Delete(
        path: '/api/v1/boards/{bbsId}/articles/{idx}',
        summary: '게시글 삭제 (작성자만)',
        tags: ['Article'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function delete(string $bbsId, int $idx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }

        $post = $this->article->find($idx);
        if (! $post || $post['bbs_idx'] != $board['idx']) {
            return $this->failNotFound(lang('Api.article_not_found'));
        }
        if ((int) $post['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.delete_forbidden'));
        }

        $this->article->softDelete($idx);
        clear_home_cache();

        return $this->success(null, lang('Api.article_deleted'));
    }
}
