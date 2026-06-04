<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 프론트 CMS 팝업 API
 *
 * GET /api/v1/cms/popups
 */
class PopupController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/cms/popups',
        summary: '팝업 목록',
        tags: ['CMS'],
        responses: [
            new OA\Response(response: 200, description: '활성 팝업 목록 (기간 필터 적용)'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $now = time();

        $popups = $db->table('tb_cms_popup')
            ->select('idx, title, contents, position')
            ->where('is_used', 1)
            ->groupStart()
                ->where('start_at IS NULL')
                ->orWhere('start_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('end_at IS NULL')
                ->orWhere('end_at >=', $now)
            ->groupEnd()
            ->orderBy('idx', 'DESC')
            ->get()->getResultArray();

        return $this->success($popups);
    }
}
