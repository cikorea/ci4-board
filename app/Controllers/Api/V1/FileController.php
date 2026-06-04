<?php

namespace App\Controllers\Api\V1;

use App\Models\FileModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 파일 API
 *
 * POST   /api/v1/files               업로드
 * GET    /api/v1/files/:idx/download 다운로드
 * DELETE /api/v1/files/:idx          삭제
 */
class FileController extends BaseApiController
{
    private const ALLOWED_EXTS = ['jpg','jpeg','gif','png','txt','doc','docx',
                                  'xls','xlsx','pdf','ppt','pptx','zip','7z','alz','rar'];
    private const MAX_SIZE  = 2 * 1024 * 1024;
    private const MAX_COUNT = 5;

    private FileModel $file;

    public function __construct()
    {
        $this->file = new FileModel();
    }

    #[OA\Post(
        path: '/api/v1/files',
        summary: '파일 업로드',
        tags: ['File'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['bbs_idx', 'article_idx'],
                    properties: [
                        new OA\Property(property: 'bbs_idx', type: 'integer'),
                        new OA\Property(property: 'article_idx', type: 'integer'),
                        new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), description: '최대 5개, 2MB/개'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '업로드 결과'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, description: '허용되지 않는 파일'),
        ]
    )]
    public function upload(): ResponseInterface
    {
        $bbsIdx     = (int) ($this->request->getPost('bbs_idx')     ?? 0);
        $articleIdx = (int) ($this->request->getPost('article_idx') ?? 0);
        $userIdx    = $this->getUserIdx();

        if (! $bbsIdx || ! $articleIdx) {
            return $this->failValidation([], lang('Api.file_params_required'));
        }

        // getFileMultiple: name="attachments[]" 다중 파일
        // getFile: name="attachments" 단일 파일
        $multi = $this->request->getFileMultiple('attachments');
        if ($multi !== null) {
            $files = $multi;
        } elseif ($this->request->getFile('attachments') !== null) {
            $files = [$this->request->getFile('attachments')];
        } else {
            $files = [];
        }
        $existing = count($this->file->getByArticle($articleIdx));
        $added    = 0;
        $errors   = [];
        $result   = [];

        foreach ($files as $file) {
            if (! $file instanceof \CodeIgniter\HTTP\Files\UploadedFile) continue;
            if (! $file->isValid() || $file->hasMoved()) continue;
            if ($file->getError() === UPLOAD_ERR_NO_FILE) continue;

            if ($existing + $added >= self::MAX_COUNT) {
                $errors[] = lang('Api.file_max_count', [self::MAX_COUNT]);
                break;
            }

            $ext = strtolower($file->getClientExtension());
            if (! in_array($ext, self::ALLOWED_EXTS, true)) {
                // "{0}: 허용되지 않는 확장자입니다."
                $errors[] = lang('Api.file_invalid_ext', [$file->getClientName()]);
                continue;
            }
            if ($file->getSize() > self::MAX_SIZE) {
                // "{0}: 파일 크기는 2MB 이하여야 합니다."
                $errors[] = lang('Api.file_size_exceeded', [$file->getClientName()]);
                continue;
            }

            $datePath = $bbsIdx . '/' . date('Ymd');
            $destDir  = WRITEPATH . 'uploads/' . $datePath;
            if (! is_dir($destDir)) mkdir($destDir, 0755, true);

            $newName = bin2hex(random_bytes(16)) . '.' . $ext;
            $file->move($destDir, $newName);

            $fileIdx = $this->file->insert([
                'bbs_idx'             => $bbsIdx,
                'article_idx'         => $articleIdx,
                'user_idx'            => $userIdx,
                'is_wysiwyg'          => 0,
                'original_filename'   => $file->getClientName(),
                'conversion_filename' => $datePath . '/' . $newName,
                'mime'                => $file->getClientMimeType(),
                'capacity'            => $file->getSize(),
                'sequence'            => $existing + $added + 1,
            ], true);

            $result[] = ['idx' => $fileIdx, 'original_filename' => $file->getClientName(), 'capacity' => $file->getSize()];
            $added++;
        }

        if (empty($result) && $errors) {
            return $this->fail(implode(' ', $errors), 422);
        }

        return $this->created(['uploaded' => $result, 'errors' => $errors ?: null]);
    }

    #[OA\Get(
        path: '/api/v1/files/{idx}/download',
        summary: '파일 다운로드 (인증 불필요)',
        tags: ['File'],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '파일 스트림', content: new OA\MediaType(mediaType: 'application/octet-stream', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function download(int $idx): mixed
    {
        $file = $this->file->find($idx);
        if (! $file) {
            return $this->failNotFound(lang('Api.file_not_found'));
        }

        $path = FileModel::storagePath($file['conversion_filename']);
        if (! is_file($path)) {
            return $this->failNotFound(lang('Api.file_physical_not_found'));
        }

        return $this->response
            ->setHeader('Content-Type', $file['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($file['original_filename']) . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($path));
    }

    #[OA\Delete(
        path: '/api/v1/files/{idx}',
        summary: '파일 삭제 (소유자만)',
        tags: ['File'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
    public function delete(int $idx): ResponseInterface
    {
        $file = $this->file->find($idx);
        if (! $file) {
            return $this->failNotFound(lang('Api.file_not_found'));
        }
        if (! $this->isAdmin() && (int) $file['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.delete_forbidden'));
        }

        $this->file->deleteFile($idx);

        return $this->success(null, lang('Api.file_deleted'));
    }
}
