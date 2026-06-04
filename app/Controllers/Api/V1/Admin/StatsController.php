<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\StatsDailyModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 통계 API
 *
 * GET /api/admin/v1/stats   일별 통계 조회 (from / to 쿼리 파라미터)
 */
class StatsController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/stats',
        summary: '일별 통계 조회',
        tags: ['AdminStats'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '일별 통계 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $model = new StatsDailyModel();
        $to    = $this->request->getGet('to')   ?? date('Y-m-d');
        $from  = $this->request->getGet('from') ?? date('Y-m-d', strtotime('-29 days'));

        $stats = $model->getRange($from, $to);

        return $this->success([
            'from'  => $from,
            'to'    => $to,
            'stats' => $stats,
        ]);
    }
}
