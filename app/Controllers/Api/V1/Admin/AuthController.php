<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 인증 API
 *
 * POST /api/admin/v1/auth/login
 * POST /api/admin/v1/auth/logout
 */
class AuthController extends BaseAdminApiController
{
    public function login(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function logout(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
