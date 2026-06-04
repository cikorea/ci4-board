<?php

namespace App\Controllers\Api\V1;

use App\Models\FileLibraryModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * WYSIWYG 에디터 이미지 업로드 API (JWT 필요)
 *
 * POST /api/v1/files/wysiwyg
 *   Form-Data: image (file)
 *   Response:  { url: "http://host/uploads/wysiwyg/YYYY/MM/filename.ext" }
 *
 * 업로드 성공 시 tb_file_library 에도 기록된다 (source=wysiwyg).
 * MIME 검증은 클라이언트 Content-Type이 아닌 finfo_file() 기반으로 수행한다.
 */
class WysiwygController extends BaseApiController
{
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];
    private const MAX_MB = 5;

    #[OA\Post(
        path: '/api/v1/files/wysiwyg',
        summary: 'WYSIWYG 이미지 업로드',
        tags: ['File'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'jpg·png·gif·webp, 5MB 이하'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '이미지 URL',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'url', type: 'string'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function upload(): ResponseInterface
    {
        $file = $this->request->getFile('image');

        if (! $file || ! $file->isValid()) {
            return $this->failValidation([], lang('Api.wysiwyg_image_required'));
        }
        if ($file->getSizeByUnit('mb') > self::MAX_MB) {
            return $this->failValidation([], lang('Api.wysiwyg_size_exceeded'));
        }

        $realMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getTempName());
        if (! in_array($realMime, self::ALLOWED_MIMES, true)) {
            return $this->failValidation([], lang('Api.wysiwyg_invalid_mime'));
        }

        $ext      = $file->getClientExtension() ?: 'bin';
        $subDir   = 'wysiwyg/' . date('Y/m');
        $destDir  = FCPATH . 'uploads/' . $subDir;
        $stored   = bin2hex(random_bytes(16)) . '.' . $ext;

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (! $file->move($destDir, $stored)) {
            return $this->fail(lang('Api.file_save_failed'), 500);
        }

        $filePath = $subDir . '/' . $stored;
        $isPublic = (int) (bool) $this->request->getPost('is_public');

        $model = new FileLibraryModel();
        $model->insert([
            'uploader_idx'     => $this->getUserIdx(),
            'source'           => 'wysiwyg',
            'original_name'    => $file->getClientName(),
            'stored_name'      => $stored,
            'file_path'        => $filePath,
            'mime_type'        => $realMime,
            'file_size'        => $file->getSize(),
            'is_public'        => $isPublic,
            'timestamp_insert' => time(),
        ]);

        return $this->success([
            'url'       => FileLibraryModel::publicUrl($filePath),
            'is_public' => $isPublic,
        ]);
    }
}
