<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 댓글 API
 *
 * GET    /api/v1/boards/:bbsId/articles/:idx/comments
 * POST   /api/v1/boards/:bbsId/articles/:idx/comments
 * PUT    /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
 * DELETE /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
 */
class CommentController extends BaseApiController
{
    public function index(string $bbsId, int $articleIdx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function create(string $bbsId, int $articleIdx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function update(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function delete(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
