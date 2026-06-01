<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 쪽지 API
 *
 * GET    /api/v1/messages/inbox
 * GET    /api/v1/messages/sent
 * GET    /api/v1/messages/:idx
 * POST   /api/v1/messages
 * DELETE /api/v1/messages/:idx
 */
class MessageController extends BaseApiController
{
    public function inbox(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function sent(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function show(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function send(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function delete(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
