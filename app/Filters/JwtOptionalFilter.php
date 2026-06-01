<?php

namespace App\Filters;

use App\Services\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JWT 인증 필터 (선택).
 * 토큰이 있으면 검증하고 사용자 정보를 주입한다.
 * 토큰이 없거나 유효하지 않아도 요청은 계속 진행한다 (비로그인 취급).
 */
class JwtOptionalFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $token = JwtService::extractFromRequest($request);

        if ($token) {
            try {
                $payload = JwtService::decode($token);
                JwtService::setCurrentUser($payload);
            } catch (\Exception) {
                // 유효하지 않은 토큰은 무시하고 비로그인으로 처리
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
