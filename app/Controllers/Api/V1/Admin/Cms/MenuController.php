<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;

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
            return $this->fail('순서 저장 중 오류가 발생했습니다.', 500);
        }

        return $this->success(null, lang('Api.cms_menu_reordered'));
    }

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
            return $this->fail('하위 메뉴가 있어 삭제할 수 없습니다. 하위 메뉴를 먼저 삭제해주세요.', 422);
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
