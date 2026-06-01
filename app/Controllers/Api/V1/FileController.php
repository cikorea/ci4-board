<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 파일 API
 *
 * POST   /api/v1/files
 * GET    /api/v1/files/:idx/download
 * DELETE /api/v1/files/:idx
 */
class FileController extends BaseApiController
{
    public function upload(): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function download(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }

    public function delete(int $idx): ResponseInterface
    {
        return $this->fail('Not Implemented', 501);
    }
}
