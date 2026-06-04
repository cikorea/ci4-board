<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use App\Models\FileLibraryModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 사용자 파일 라이브러리 API (본인 파일만 + 공용 목록)
 *
 * POST   /api/v1/cms/library/files           업로드 (is_public 선택)
 * GET    /api/v1/cms/library/files           내 파일 목록
 * GET    /api/v1/cms/library/files/public    공용 파일 목록 (is_public=1)
 * GET    /api/v1/cms/library/files/:idx      단건 조회 (본인 소유 확인)
 * PUT    /api/v1/cms/library/files/:idx      메타 수정 (alt_text, is_public — 본인 소유 확인)
 * DELETE /api/v1/cms/library/files/:idx      삭제 (본인 소유 확인 + 사용처 스캔)
 */
class LibraryController extends BaseApiController
{
    private const PER_PAGE = 20;
    private const MAX_MB   = 5;

    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/zip', 'application/x-zip-compressed',
    ];

    private FileLibraryModel $lib;

    public function __construct()
    {
        $this->lib = new FileLibraryModel();
    }

    public function upload(): ResponseInterface
    {
        $file = $this->request->getFile('file');

        if (! $file || ! $file->isValid()) {
            return $this->failValidation([], lang('Api.library_file_required'));
        }
        if ($file->getSizeByUnit('mb') > self::MAX_MB) {
            return $this->failValidation([], lang('Api.library_size_exceeded', [self::MAX_MB]));
        }

        $realMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getTempName());
        if (! in_array($realMime, self::ALLOWED_MIMES, true)) {
            return $this->failValidation([], lang('Api.library_invalid_mime'));
        }

        $ext      = strtolower($file->getClientExtension() ?: 'bin');
        $subDir   = 'library/' . date('Y/m');
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

        $idx = $this->lib->insert([
            'uploader_idx'     => $this->getUserIdx(),
            'source'           => 'direct',
            'original_name'    => $file->getClientName(),
            'stored_name'      => $stored,
            'file_path'        => $filePath,
            'mime_type'        => $realMime,
            'file_size'        => $file->getSize(),
            'is_public'        => $isPublic,
            'timestamp_insert' => time(),
        ], true);

        return $this->created([
            'idx'       => $idx,
            'url'       => FileLibraryModel::publicUrl($filePath),
            'is_public' => $isPublic,
        ], lang('Api.library_uploaded'));
    }

    public function index(): ResponseInterface
    {
        $userIdx = $this->getUserIdx();
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $total = $this->lib->where('uploader_idx', $userIdx)->countAllResults(false);
        $rows  = $this->lib
            ->select('idx, source, original_name, file_path, mime_type, file_size, alt_text, is_public, used_count, timestamp_insert')
            ->where('uploader_idx', $userIdx)
            ->orderBy('idx', 'DESC')
            ->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        foreach ($rows as &$row) {
            $row['url']       = FileLibraryModel::publicUrl($row['file_path']);
            $row['is_public'] = (int) $row['is_public'];
        }
        unset($row);

        return $this->success([
            'total'    => $total,
            'page'     => $page,
            'per_page' => self::PER_PAGE,
            'items'    => $rows,
        ]);
    }

    public function publicIndex(): ResponseInterface
    {
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $mime    = trim((string) ($this->request->getGet('mime') ?? ''));
        $keyword = trim((string) ($this->request->getGet('q')   ?? ''));

        $builder = $this->lib
            ->select('idx, uploader_idx, source, original_name, file_path, mime_type, file_size, alt_text, is_public, timestamp_insert')
            ->where('is_public', 1)
            ->orderBy('idx', 'DESC');

        if ($mime !== '') {
            $builder->like('mime_type', $mime, 'after');
        }
        if ($keyword !== '') {
            $builder->like('original_name', $keyword);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        foreach ($rows as &$row) {
            $row['url']       = FileLibraryModel::publicUrl($row['file_path']);
            $row['is_public'] = (int) $row['is_public'];
        }
        unset($row);

        return $this->success([
            'total'    => $total,
            'page'     => $page,
            'per_page' => self::PER_PAGE,
            'items'    => $rows,
        ]);
    }

    public function show(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
        }
        if ((int) $row['uploader_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.delete_forbidden'));
        }

        $row['url']       = FileLibraryModel::publicUrl($row['file_path']);
        $row['is_public'] = (int) $row['is_public'];

        return $this->success($row);
    }

    public function update(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
        }
        if ((int) $row['uploader_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.edit_forbidden'));
        }

        $body     = (array) $this->request->getJSON(true);
        $altText  = array_key_exists('alt_text', $body)
            ? (trim((string) $body['alt_text']) ?: null)
            : $row['alt_text'];
        $isPublic = array_key_exists('is_public', $body)
            ? (int) (bool) $body['is_public']
            : (int) $row['is_public'];

        $this->lib->update($idx, ['alt_text' => $altText, 'is_public' => $isPublic]);

        return $this->success(['idx' => $idx], lang('Api.library_updated'));
    }

    public function delete(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
        }
        if ((int) $row['uploader_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.delete_forbidden'));
        }

        $usages = $this->lib->findUsages($row);
        if ($usages) {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'data'    => null,
                'message' => lang('Api.library_delete_in_use', [count($usages)]),
                'usages'  => $usages,
            ]);
        }

        $this->lib->deleteFile($idx);

        return $this->success(null, lang('Api.library_deleted'));
    }
}
