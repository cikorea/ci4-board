<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 게시글 API
 *
 * GET    /api/v1/boards/:bbsId/articles
 * POST   /api/v1/boards/:bbsId/articles
 * GET    /api/v1/boards/:bbsId/articles/:idx
 * PUT    /api/v1/boards/:bbsId/articles/:idx
 * DELETE /api/v1/boards/:bbsId/articles/:idx
 */
class ArticleController extends BaseApiController
{
    public function index(string $bbsId): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function show(string $bbsId, int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function create(string $bbsId): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(string $bbsId, int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function delete(string $bbsId, int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
