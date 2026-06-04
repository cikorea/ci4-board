<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 JWT 인증 필터.
 * type === 'admin' && role ∈ {superadmin, manager} 인 토큰만 허용한다.
 */
class AdminJwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $token = JwtService::extractFromRequest($request);

        if (! $token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['success' => false, 'data' => null, 'message' => '인증이 필요합니다.']);
        }

        try {
            $payload = JwtService::decode($token);
        } catch (\Exception) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['success' => false, 'data' => null, 'message' => '유효하지 않은 토큰입니다.']);
        }

        $allowedRoles = ['superadmin', 'manager'];
        if (($payload->type ?? '') !== 'admin' || ! in_array($payload->role ?? '', $allowedRoles, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['success' => false, 'data' => null, 'message' => '관리자 권한이 필요합니다.']);
        }

        JwtService::setCurrentUser($payload);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
