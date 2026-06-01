<?php

namespace App\Controllers\Api\V1;

use App\Models\FileModel;
use CodeIgniter\HTTP\ResponseInterface;

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

    public function upload(): ResponseInterface
    {
        $bbsIdx     = (int) ($this->request->getPost('bbs_idx')     ?? 0);
        $articleIdx = (int) ($this->request->getPost('article_idx') ?? 0);
        $userIdx    = $this->getUserIdx();

        if (! $bbsIdx || ! $articleIdx) {
            return $this->failValidation([], 'bbs_idx, article_idx 가 필요합니다.');
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
                $errors[] = '최대 ' . self::MAX_COUNT . '개까지 업로드 가능합니다.';
                break;
            }

            $ext = strtolower($file->getClientExtension());
            if (! in_array($ext, self::ALLOWED_EXTS, true)) {
                $errors[] = "{$file->getClientName()}: 허용되지 않는 확장자입니다.";
                continue;
            }
            if ($file->getSize() > self::MAX_SIZE) {
                $errors[] = "{$file->getClientName()}: 파일 크기는 2MB 이하여야 합니다.";
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

    public function download(int $idx): mixed
    {
        $file = $this->file->find($idx);
        if (! $file) {
            return $this->failNotFound('파일을 찾을 수 없습니다.');
        }

        $path = FileModel::storagePath($file['conversion_filename']);
        if (! is_file($path)) {
            return $this->failNotFound('실제 파일이 존재하지 않습니다.');
        }

        return $this->response
            ->setHeader('Content-Type', $file['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($file['original_filename']) . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($path));
    }

    public function delete(int $idx): ResponseInterface
    {
        $file = $this->file->find($idx);
        if (! $file) {
            return $this->failNotFound('파일을 찾을 수 없습니다.');
        }
        if (! $this->isAdmin() && (int) $file['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden('삭제 권한이 없습니다.');
        }

        $this->file->deleteFile($idx);

        return $this->success(null, '파일이 삭제되었습니다.');
    }
}
