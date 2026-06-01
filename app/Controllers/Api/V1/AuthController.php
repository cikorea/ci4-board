<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 인증 API (로그인, 회원가입, 토큰 갱신, 내 정보)
 *
 * POST   /api/v1/auth/login
 * POST   /api/v1/auth/register
 * POST   /api/v1/auth/logout
 * POST   /api/v1/auth/refresh
 * GET    /api/v1/auth/me
 * PUT    /api/v1/auth/profile
 * DELETE /api/v1/auth/withdraw
 */
class AuthController extends BaseApiController
{
    public function login(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function register(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function logout(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function refresh(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function me(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function updateProfile(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function withdraw(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
