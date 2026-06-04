<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API 서버 랜딩 페이지.
 * GET / → Swagger UI 리다이렉트 또는 API 정보 JSON 반환.
 */
class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        $accept = $this->request->getHeaderLine('Accept');

        if (str_contains($accept, 'application/json') || str_contains($accept, 'text/plain')) {
            return $this->response
                ->setStatusCode(200)
                ->setContentType('application/json')
                ->setBody(json_encode([
                    'name'    => 'CI4 Board API',
                    'version' => 'v1',
                    'docs'    => base_url('docs/swagger.html'),
                    'spec'    => base_url('docs/openapi.yaml'),
                    'endpoints' => [
                        'user'  => base_url('api/v1'),
                        'admin' => base_url('api/admin/v1'),
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }

        return redirect()->to(base_url('docs/swagger.html'));
    }
}
