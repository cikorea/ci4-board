<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 게시글 API
 *
 * GET    /api/admin/v1/articles
 * PUT    /api/admin/v1/articles/:idx
 * DELETE /api/admin/v1/articles/:idx
 */
class ArticleController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function delete(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
