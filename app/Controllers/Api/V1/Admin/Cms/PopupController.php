<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 CMS 팝업 API
 *
 * GET    /api/admin/v1/cms/popups
 * POST   /api/admin/v1/cms/popups
 * PUT    /api/admin/v1/cms/popups/:idx
 * DELETE /api/admin/v1/cms/popups/:idx
 */
class PopupController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/cms/popups',
        summary: '팝업 목록',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'is_used', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [
            new OA\Response(response: 200, description: '팝업 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_cms_popup')
            ->select('idx, title, position, start_at, end_at, is_used, timestamp_insert')
            ->orderBy('idx', 'DESC');

        $isUsed = $this->request->getGet('is_used');
        if ($isUsed !== null && $isUsed !== '') {
            $builder->where('is_used', (int) $isUsed);
        }

        $total  = $builder->countAllResults(false);
        $popups = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($popups, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
        ]);
    }

    #[OA\Post(
        path: '/api/admin/v1/cms/popups',
        summary: '팝업 생성',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'contents'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'position', type: 'string'),
                    new OA\Property(property: 'start_at', type: 'integer'),
                    new OA\Property(property: 'end_at', type: 'integer'),
                    new OA\Property(property: 'is_used', type: 'boolean', default: true),
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

        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || $contents === '') {
            return $this->failValidation([], lang('Api.cms_popup_required'));
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_popup')->insert([
            'title'            => $title,
            'contents'         => $contents,
            'position'         => trim((string) ($body['position'] ?? '')),
            'start_at'         => $this->parseTimestamp($body['start_at'] ?? null),
            'end_at'           => $this->parseTimestamp($body['end_at']   ?? null),
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : 1,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], lang('Api.cms_popup_created'));
    }

    public function show(int $idx): ResponseInterface
    {
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound(lang('Api.cms_popup_not_found'));
        }

        return $this->success($popup);
    }

    #[OA\Put(
        path: '/api/admin/v1/cms/popups/{idx}',
        summary: '팝업 수정',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                    new OA\Property(property: 'position', type: 'string'),
                    new OA\Property(property: 'start_at', type: 'integer'),
                    new OA\Property(property: 'end_at', type: 'integer'),
                    new OA\Property(property: 'is_used', type: 'boolean'),
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
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound(lang('Api.cms_popup_not_found'));
        }

        $body = (array) $this->request->getJSON(true);

        $title    = trim((string) ($body['title']    ?? $popup['title']));
        $contents = (string) ($body['contents'] ?? $popup['contents']);

        if (! $title || $contents === '') {
            return $this->failValidation([], lang('Api.cms_popup_required'));
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_popup')->where('idx', $idx)->update([
            'title'            => $title,
            'contents'         => $contents,
            'position'         => array_key_exists('position', $body)
                                    ? trim((string) $body['position'])
                                    : $popup['position'],
            'start_at'         => array_key_exists('start_at', $body)
                                    ? $this->parseTimestamp($body['start_at'])
                                    : $popup['start_at'],
            'end_at'           => array_key_exists('end_at', $body)
                                    ? $this->parseTimestamp($body['end_at'])
                                    : $popup['end_at'],
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : $popup['is_used'],
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, lang('Api.cms_popup_updated'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/cms/popups/{idx}',
        summary: '팝업 삭제',
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
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound(lang('Api.cms_popup_not_found'));
        }

        $db->table('tb_cms_popup')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.cms_popup_deleted'));
    }

    private function parseTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $ts = strtotime((string) $value);
        return $ts !== false ? $ts : null;
    }
}
