<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 게시판 API
 *
 * GET /api/v1/boards
 * GET /api/v1/boards/:bbsId
 */
class BoardController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function show(string $bbsId): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
