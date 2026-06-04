<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\StatsDailyModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 통계 API
 *
 * GET /api/admin/v1/stats   일별 통계 조회 (from / to 쿼리 파라미터)
 */
class StatsController extends BaseAdminApiController
{
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
