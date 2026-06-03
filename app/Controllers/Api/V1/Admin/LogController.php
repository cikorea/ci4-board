<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 감사 로그 API
 *
 * GET /api/admin/v1/logs   감사 로그 목록
 */
class LogController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        $model   = new AdminLogModel();
        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $action  = trim($this->request->getGet('action') ?? '');

        $builder = $model->orderBy('idx', 'DESC');

        if ($action !== '') {
            $builder->like('action', $action);
        }

        $total = $builder->countAllResults(false);
        $logs  = $builder->limit($perPage, ($page - 1) * $perPage)->findAll();

        return $this->successList($logs, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }
}
