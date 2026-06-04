<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 CMS 배너 API
 *
 * GET    /api/admin/v1/cms/banners
 * POST   /api/admin/v1/cms/banners
 * PUT    /api/admin/v1/cms/banners/:idx
 * DELETE /api/admin/v1/cms/banners/:idx
 */
class BannerController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/cms/banners',
        summary: '배너 목록',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'position', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '배너 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db       = \Config\Database::connect();
        $position = trim($this->request->getGet('position') ?? '');

        $builder = $db->table('tb_cms_banner')
            ->select('idx, position, image_path, link_url, start_at, end_at, sequence, is_used, timestamp_insert')
            ->orderBy('position', 'ASC')
            ->orderBy('sequence', 'ASC');

        if ($position !== '') {
            $builder->where('position', $position);
        }

        return $this->success($builder->get()->getResultArray());
    }

    #[OA\Post(
        path: '/api/admin/v1/cms/banners',
        summary: '배너 생성',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['position', 'image_path'],
                properties: [
                    new OA\Property(property: 'position', type: 'string'),
                    new OA\Property(property: 'image_path', type: 'string'),
                    new OA\Property(property: 'link_url', type: 'string'),
                    new OA\Property(property: 'start_at', type: 'integer', description: '노출 시작 time()'),
                    new OA\Property(property: 'end_at', type: 'integer', description: '노출 종료 time()'),
                    new OA\Property(property: 'sequence', type: 'integer', default: 0),
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

        $position   = trim((string) ($body['position']   ?? ''));
        $imagePath  = trim((string) ($body['image_path'] ?? ''));

        if (! $position || ! $imagePath) {
            return $this->failValidation([], lang('Api.cms_banner_required'));
        }

        $db->table('tb_cms_banner')->insert([
            'position'         => $position,
            'image_path'       => $imagePath,
            'link_url'         => trim((string) ($body['link_url'] ?? '')) ?: null,
            'start_at'         => $this->parseTimestamp($body['start_at'] ?? null),
            'end_at'           => $this->parseTimestamp($body['end_at']   ?? null),
            'sequence'         => max(0, (int) ($body['sequence'] ?? 0)),
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : 1,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], lang('Api.cms_banner_created'));
    }

    #[OA\Put(
        path: '/api/admin/v1/cms/banners/{idx}',
        summary: '배너 수정',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'position', type: 'string'),
                    new OA\Property(property: 'image_path', type: 'string'),
                    new OA\Property(property: 'link_url', type: 'string'),
                    new OA\Property(property: 'start_at', type: 'integer'),
                    new OA\Property(property: 'end_at', type: 'integer'),
                    new OA\Property(property: 'sequence', type: 'integer'),
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
        $db     = \Config\Database::connect();
        $banner = $db->table('tb_cms_banner')->where('idx', $idx)->get()->getRowArray();
        if (! $banner) {
            return $this->failNotFound(lang('Api.cms_banner_not_found'));
        }

        $body = (array) $this->request->getJSON(true);

        $position  = trim((string) ($body['position']   ?? $banner['position']));
        $imagePath = trim((string) ($body['image_path'] ?? $banner['image_path']));

        if (! $position || ! $imagePath) {
            return $this->failValidation([], lang('Api.cms_banner_required'));
        }

        $db->table('tb_cms_banner')->where('idx', $idx)->update([
            'position'         => $position,
            'image_path'       => $imagePath,
            'link_url'         => array_key_exists('link_url', $body)
                                    ? (trim((string) $body['link_url']) ?: null)
                                    : $banner['link_url'],
            'start_at'         => array_key_exists('start_at', $body)
                                    ? $this->parseTimestamp($body['start_at'])
                                    : $banner['start_at'],
            'end_at'           => array_key_exists('end_at', $body)
                                    ? $this->parseTimestamp($body['end_at'])
                                    : $banner['end_at'],
            'sequence'         => isset($body['sequence']) ? max(0, (int) $body['sequence']) : $banner['sequence'],
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : $banner['is_used'],
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, lang('Api.cms_banner_updated'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/cms/banners/{idx}',
        summary: '배너 삭제',
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
        $db     = \Config\Database::connect();
        $banner = $db->table('tb_cms_banner')->where('idx', $idx)->get()->getRowArray();
        if (! $banner) {
            return $this->failNotFound(lang('Api.cms_banner_not_found'));
        }

        $db->table('tb_cms_banner')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.cms_banner_deleted'));
    }

    private function parseTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Unix timestamp 정수로 전달되는 경우
        if (is_numeric($value)) {
            return (int) $value;
        }
        // ISO 8601 문자열 (e.g. "2026-07-01T00:00:00") 처리
        $ts = strtotime((string) $value);
        return $ts !== false ? $ts : null;
    }
}
