<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 감사 로그 API
 *
 * GET /api/admin/v1/logs   감사 로그 목록
 */
class LogController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/logs',
        summary: '감사 로그 목록',
        tags: ['AdminLog'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '감사 로그 목록', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminLog'))),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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
