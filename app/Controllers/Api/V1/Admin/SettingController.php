<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 사이트 설정 API
 *
 * GET /api/admin/v1/setting
 * PUT /api/admin/v1/setting
 */
class SettingController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
