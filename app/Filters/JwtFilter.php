<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JWT 인증 필터 (필수).
 * Authorization: Bearer {token} 헤더가 없거나 유효하지 않으면 401을 반환한다.
 */
class JwtFilter implements FilterInterface
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
            JwtService::setCurrentUser($payload);
        } catch (\Exception) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['success' => false, 'data' => null, 'message' => '유효하지 않은 토큰입니다.']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
