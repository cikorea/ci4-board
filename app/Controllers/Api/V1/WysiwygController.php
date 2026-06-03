<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * WYSIWYG 에디터 이미지 업로드 API (JWT 필요)
 *
 * POST /api/v1/files/wysiwyg
 *   Form-Data: image (file)
 *   Response:  { url: "http://host/uploads/wysiwyg/YYYY/MM/filename.ext" }
 */
class WysiwygController extends BaseApiController
{
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];
    private const MAX_MB = 5;

    public function upload(): ResponseInterface
    {
        $file = $this->request->getFile('image');

        if (! $file || ! $file->isValid()) {
            return $this->failValidation([], lang('Api.wysiwyg_image_required'));
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return $this->failValidation([], lang('Api.wysiwyg_invalid_mime'));
        }
        if ($file->getSizeByUnit('mb') > self::MAX_MB) {
            return $this->failValidation([], lang('Api.wysiwyg_size_exceeded'));
        }

        $subDir  = date('Y/m');
        $destDir = FCPATH . 'uploads/wysiwyg/' . $subDir;
        $name    = $file->getRandomName();

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (! $file->move($destDir, $name)) {
            return $this->fail(lang('Api.file_save_failed'), 500);
        }

        return $this->success([
            'url' => base_url('uploads/wysiwyg/' . $subDir . '/' . $name),
        ]);
    }
}
