<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 회원 API
 *
 * GET /api/admin/v1/members
 * PUT /api/admin/v1/members/:idx
 */
class MemberController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
