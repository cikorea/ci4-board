<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 CMS 메뉴 API
 *
 * GET  /api/admin/v1/cms/menus          — 전체 메뉴 트리 조회
 * POST /api/admin/v1/cms/menus          — 메뉴 항목 생성
 * PUT  /api/admin/v1/cms/menus/reorder  — 순서 일괄 변경 (드래그앤드롭)
 * PUT  /api/admin/v1/cms/menus/:idx     — 메뉴 항목 수정
 * DELETE /api/admin/v1/cms/menus/:idx   — 메뉴 항목 삭제
 */
class MenuController extends BaseAdminApiController
{
    private const ALLOWED_TARGETS = ['_self', '_blank'];

    #[OA\Get(
        path: '/api/admin/v1/cms/menus',
        summary: '메뉴 트리 조회',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '메뉴 트리', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tb_cms_menu')
            ->select('idx, parent_idx, label, url, target, sequence, is_used')
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();

        return $this->success($this->buildTree($rows, null));
    }

    #[OA\Post(
        path: '/api/admin/v1/cms/menus',
        summary: '메뉴 생성',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label'],
                properties: [
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'target', type: 'string', enum: ['_self', '_blank'], default: '_self'),
                    new OA\Property(property: 'parent_idx', type: 'integer', nullable: true),
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

        $label = trim((string) ($body['label'] ?? ''));
        if (! $label) {
            return $this->failValidation([], lang('Api.cms_menu_label_required'));
        }

        $parentIdx = isset($body['parent_idx']) && $body['parent_idx'] !== null
            ? (int) $body['parent_idx']
            : null;

        if ($parentIdx !== null) {
            $exists = $db->table('tb_cms_menu')->where('idx', $parentIdx)->countAllResults();
            if (! $exists) {
                return $this->failValidation([], lang('Api.cms_menu_parent_not_found'));
            }
        }

        $target = (string) ($body['target'] ?? '_self');
        if (! in_array($target, self::ALLOWED_TARGETS, true)) {
            $target = '_self';
        }

        $db->table('tb_cms_menu')->insert([
            'parent_idx'       => $parentIdx,
            'label'            => $label,
            'url'              => trim((string) ($body['url'] ?? '')),
            'target'           => $target,
            'sequence'         => max(0, (int) ($body['sequence'] ?? 0)),
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : 1,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], lang('Api.cms_menu_created'));
    }

    /**
     * 드래그앤드롭 순서 일괄 저장
     *
     * Request Body: [{ "idx": 1, "sequence": 0, "parent_idx": null }, ...]
     */
    #[OA\Put(
        path: '/api/admin/v1/cms/menus/reorder',
        summary: '메뉴 순서 일괄 변경',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'idx', type: 'integer'),
                        new OA\Property(property: 'sequence', type: 'integer'),
                        new OA\Property(property: 'parent_idx', type: 'integer', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '순서 저장 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function reorder(): ResponseInterface
    {
        $db    = \Config\Database::connect();
        $items = (array) $this->request->getJSON(true);

        if (empty($items)) {
            return $this->failValidation([], lang('Api.cms_menu_reorder_required'));
        }

        $db->transStart();
        foreach ($items as $item) {
            $idx = (int) ($item['idx'] ?? 0);
            if (! $idx) {
                continue;
            }
            $parentIdx = isset($item['parent_idx']) && $item['parent_idx'] !== null
                ? (int) $item['parent_idx']
                : null;

            $db->table('tb_cms_menu')->where('idx', $idx)->update([
                'parent_idx'       => $parentIdx,
                'sequence'         => max(0, (int) ($item['sequence'] ?? 0)),
                'exec_user_idx'    => $this->getUserIdx(),
                'client_ip'        => $this->request->getIPAddress(),
                'timestamp_update' => time(),
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->failServerError(lang('Api.cms_menu_reorder_failed'));
        }

        return $this->success(null, lang('Api.cms_menu_reordered'));
    }

    #[OA\Put(
        path: '/api/admin/v1/cms/menus/{idx}',
        summary: '메뉴 수정',
        tags: ['AdminCMS'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'target', type: 'string', enum: ['_self', '_blank']),
                    new OA\Property(property: 'parent_idx', type: 'integer', nullable: true),
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
        $db   = \Config\Database::connect();
        $menu = $db->table('tb_cms_menu')->where('idx', $idx)->get()->getRowArray();
        if (! $menu) {
            return $this->failNotFound(lang('Api.cms_menu_not_found'));
        }

        $body  = (array) $this->request->getJSON(true);
        $label = trim((string) ($body['label'] ?? $menu['label']));
        if (! $label) {
            return $this->failValidation([], lang('Api.cms_menu_label_required'));
        }

        $parentIdx = array_key_exists('parent_idx', $body)
            ? ($body['parent_idx'] !== null ? (int) $body['parent_idx'] : null)
            : ($menu['parent_idx'] !== null ? (int) $menu['parent_idx'] : null);

        // 자기 자신을 부모로 설정하는 것 방지
        if ($parentIdx === $idx) {
            return $this->failValidation([], lang('Api.cms_menu_self_parent'));
        }
        if ($parentIdx !== null) {
            $exists = $db->table('tb_cms_menu')->where('idx', $parentIdx)->countAllResults();
            if (! $exists) {
                return $this->failValidation([], lang('Api.cms_menu_parent_not_found'));
            }
        }

        $target = isset($body['target']) ? (string) $body['target'] : $menu['target'];
        if (! in_array($target, self::ALLOWED_TARGETS, true)) {
            $target = '_self';
        }

        $db->table('tb_cms_menu')->where('idx', $idx)->update([
            'parent_idx'       => $parentIdx,
            'label'            => $label,
            'url'              => array_key_exists('url', $body)
                                    ? trim((string) $body['url'])
                                    : $menu['url'],
            'target'           => $target,
            'sequence'         => isset($body['sequence']) ? max(0, (int) $body['sequence']) : $menu['sequence'],
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : $menu['is_used'],
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, lang('Api.cms_menu_updated'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/cms/menus/{idx}',
        summary: '메뉴 삭제',
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
        $menu = $db->table('tb_cms_menu')->where('idx', $idx)->get()->getRowArray();
        if (! $menu) {
            return $this->failNotFound(lang('Api.cms_menu_not_found'));
        }

        // 하위 메뉴가 있으면 삭제 거부
        $childCount = $db->table('tb_cms_menu')->where('parent_idx', $idx)->countAllResults();
        if ($childCount > 0) {
            return $this->failConflict(lang('Api.cms_menu_has_children'));
        }

        $db->table('tb_cms_menu')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.cms_menu_deleted'));
    }

    private function buildTree(array $rows, ?int $parentIdx): array
    {
        $tree = [];
        foreach ($rows as $row) {
            $rowParent = $row['parent_idx'] !== null ? (int) $row['parent_idx'] : null;
            if ($rowParent === $parentIdx) {
                $row['children'] = $this->buildTree($rows, (int) $row['idx']);
                $tree[]          = $row;
            }
        }
        return $tree;
    }
}
