<?php

namespace App\Controllers\Api\V1;

use App\Services\JwtService;
use App\Traits\ApiResponse;
use CodeIgniter\Controller;

/**
 * API 컨트롤러 베이스.
 * 모든 Api/V1 컨트롤러는 이 클래스를 상속한다.
 */
abstract class BaseApiController extends Controller
{
    use ApiResponse;

    protected function getUserIdx(): int
    {
        return JwtService::getUserIdx();
    }

    protected function getGroupIdx(): int
    {
        return JwtService::getGroupIdx();
    }

    protected function isAdmin(): bool
    {
        return JwtService::isAdmin();
    }
}
