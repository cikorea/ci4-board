<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 CMS 페이지 API
 *
 * GET    /api/admin/v1/cms/pages
 * POST   /api/admin/v1/cms/pages
 * PUT    /api/admin/v1/cms/pages/:idx
 * DELETE /api/admin/v1/cms/pages/:idx
 */
class PageController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/cms/pages',
        summary: '페이지 목록',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '페이지 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $status  = $this->request->getGet('status');
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_cms_page')
            ->select('idx, slug, title, status, timestamp_insert, timestamp_update')
            ->orderBy('idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('title', $keyword)
                ->orLike('slug', $keyword)
                ->groupEnd();
        }
        if ($status !== null && $status !== '') {
            $builder->where('status', (int) $status);
        }

        $total = $builder->countAllResults(false);
        $pages = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($pages, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/v1/cms/pages',
        summary: '페이지 생성',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['slug', 'title', 'contents'],
                properties: [
                    new OA\Property(property: 'slug', type: 'string', description: '영문 소문자·숫자·하이픈', example: 'about-us'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1], default: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '생성 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function create(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $body = (array) $this->request->getJSON(true);

        $slug     = trim((string) ($body['slug']     ?? ''));
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');
        $status   = (int) (bool) ($body['status']   ?? false);

        if (! $slug || ! $title || $contents === '') {
            return $this->failValidation([], lang('Api.cms_page_required'));
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], lang('Api.cms_page_slug_invalid'));
        }

        $exists = $db->table('tb_cms_page')->where('slug', $slug)->countAllResults();
        if ($exists) {
            return $this->failValidation([], lang('Api.cms_page_slug_duplicate'));
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_page')->insert([
            'slug'             => $slug,
            'title'            => $title,
            'contents'         => $contents,
            'status'           => $status,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], lang('Api.cms_page_created'));
    }

    #[OA\Get(
        path: '/api/admin/v1/cms/pages/{idx}',
        summary: '페이지 단건 조회',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '페이지 상세', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    #[OA\Put(
        path: '/api/admin/v1/cms/pages/{idx}',
        summary: '페이지 수정',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function update(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound(lang('Api.cms_page_not_found'));
        }

        $body = (array) $this->request->getJSON(true);

        $slug     = trim((string) ($body['slug']     ?? $page['slug']));
        $title    = trim((string) ($body['title']    ?? $page['title']));
        $contents = (string) ($body['contents'] ?? $page['contents']);
        $status   = isset($body['status']) ? (int) (bool) $body['status'] : (int) $page['status'];

        if (! $slug || ! $title || $contents === '') {
            return $this->failValidation([], lang('Api.cms_page_required'));
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], lang('Api.cms_page_slug_invalid'));
        }
        if ($slug !== $page['slug']) {
            $exists = $db->table('tb_cms_page')->where('slug', $slug)->where('idx !=', $idx)->countAllResults();
            if ($exists) {
                return $this->failValidation([], lang('Api.cms_page_slug_duplicate'));
            }
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_page')->where('idx', $idx)->update([
            'slug'             => $slug,
            'title'            => $title,
            'contents'         => $contents,
            'status'           => $status,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, lang('Api.cms_page_updated'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/cms/pages/{idx}',
        summary: '페이지 삭제',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function delete(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound(lang('Api.cms_page_not_found'));
        }

        $db->table('tb_cms_page')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.cms_page_deleted'));
    }
}
