<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 프론트 CMS 배너 API
 *
 * GET /api/v1/cms/banners
 */
class BannerController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/cms/banners',
        summary: '배너 목록',
        tags: ['CMS'],
        parameters: [
            new OA\QueryParameter(name: 'position', in: 'query', description: '배너 위치 코드', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '활성 배너 목록 (기간 필터 적용)'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db       = \Config\Database::connect();
        $now      = time();
        $position = trim($this->request->getGet('position') ?? '');

        $builder = $db->table('tb_cms_banner')
            ->select('idx, position, image_path, link_url, sequence')
            ->where('is_used', 1)
            ->groupStart()
                ->where('start_at IS NULL')
                ->orWhere('start_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('end_at IS NULL')
                ->orWhere('end_at >=', $now)
            ->groupEnd()
            ->orderBy('position', 'ASC')
            ->orderBy('sequence', 'ASC');

        if ($position !== '') {
            $builder->where('position', $position);
        }

        return $this->success($builder->get()->getResultArray());
    }
}
