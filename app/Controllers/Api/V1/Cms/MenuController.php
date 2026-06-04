<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 프론트 CMS 메뉴 API
 *
 * GET /api/v1/cms/menus
 */
class MenuController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/cms/menus',
        summary: '메뉴 트리 조회',
        tags: ['CMS'],
        responses: [
            new OA\Response(response: 200, description: '메뉴 트리 (children 포함)'),
        ]
    )]
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
