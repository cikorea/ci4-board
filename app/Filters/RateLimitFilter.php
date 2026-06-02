<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limiting 필터.
 *
 * 사용법 (Routes.php):
 *   'filter' => 'rate_limit:10'   → 분당 10회 (로그인용)
 *   'filter' => 'rate_limit:60'   → 분당 60회 (일반 API, 기본값)
 *
 * 초과 시 429 Too Many Requests JSON 반환.
 * 카운터는 CI4 Cache(file)에 저장되며 60초 후 자동 만료된다.
 */
class RateLimitFilter implements FilterInterface
{
    private const WINDOW = 60; // seconds

    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $limit = isset($arguments[0]) ? (int) $arguments[0] : 60;
        $ip    = $request->getIPAddress();
        $uri   = $request->getUri()->getPath();
        $key   = 'rl_' . md5($ip . $uri . $limit);

        $cache   = service('cache');
        $current = (int) ($cache->get($key) ?? 0);

        if ($current === 0) {
            $cache->save($key, 1, self::WINDOW);
        } elseif ($current < $limit) {
            $cache->save($key, $current + 1, self::WINDOW);
        } else {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'data'    => null,
                    'message' => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.',
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
