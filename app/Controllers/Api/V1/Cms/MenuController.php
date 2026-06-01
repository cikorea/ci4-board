<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 프론트 CMS 메뉴 API
 *
 * GET /api/v1/cms/menus
 */
class MenuController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tb_cms_menu')
            ->select('idx, parent_idx, label, url, target, sequence')
            ->where('is_used', 1)
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();

        return $this->success($this->buildTree($rows, null));
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
