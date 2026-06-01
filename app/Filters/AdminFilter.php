<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/auth/login')->with('error', '로그인이 필요합니다.');
        }

        if (session()->get('group_name') !== '최고관리자') {
            return redirect()->to('/')->with('error', '관리자만 접근할 수 있습니다.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
