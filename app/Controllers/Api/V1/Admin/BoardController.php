<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 게시판 API
 *
 * GET /api/admin/v1/boards
 * PUT /api/admin/v1/boards/:bbsId
 */
class BoardController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(string $bbsId): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
