<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 내부 공지 API
 *
 * GET    /api/admin/v1/notices        공지 목록
 * POST   /api/admin/v1/notices        공지 등록
 * DELETE /api/admin/v1/notices/:idx   공지 삭제
 */
class NoticeController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/notices',
        summary: '관리자 내부 공지 목록',
        tags: ['AdminNotice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '공지 목록', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminNotice'))),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect('admin');
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_admin_notice')->orderBy('is_pinned', 'DESC')->orderBy('idx', 'DESC');

        $total   = $builder->countAllResults(false);
        $notices = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($notices, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    #[OA\Post(
        path: '/api/admin/v1/notices',
        summary: '관리자 내부 공지 등록',
        tags: ['AdminNotice'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'is_pinned', type: 'boolean', default: false),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '등록 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function create(): ResponseInterface
    {
        $db   = \Config\Database::connect('admin');
        $body = (array) $this->request->getJSON(true);

        $title    = trim((string) ($body['title']    ?? ''));
        $contents = trim((string) ($body['contents'] ?? ''));

        if (! $title) {
            return $this->failValidation([], lang('Api.article_title_required'));
        }

        $db->table('tb_admin_notice')->insert([
            'title'            => $title,
            'contents'         => $contents,
            'author_idx'       => $this->getUserIdx(),
            'is_pinned'        => (int) (bool) ($body['is_pinned'] ?? false),
            'timestamp_insert' => time(),
        ]);

        return $this->success(['idx' => $db->insertID()], lang('Api.created'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/notices/{idx}',
        summary: '관리자 내부 공지 삭제',
        tags: ['AdminNotice'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function delete(int $idx): ResponseInterface
    {
        $db     = \Config\Database::connect('admin');
        $notice = $db->table('tb_admin_notice')->where('idx', $idx)->get()->getRowArray();

        if (! $notice) {
            return $this->failNotFound(lang('Api.not_found'));
        }

        $db->table('tb_admin_notice')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.deleted'));
    }
}
