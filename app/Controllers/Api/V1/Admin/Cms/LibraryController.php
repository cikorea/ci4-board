<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use App\Models\FileLibraryModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 파일 라이브러리 API
 *
 * GET    /api/admin/v1/cms/library/files           전체 목록 (페이지네이션, MIME·source·날짜·업로더 필터)
 * POST   /api/admin/v1/cms/library/files           직접 업로드
 * GET    /api/admin/v1/cms/library/files/:idx      단건 조회
 * PUT    /api/admin/v1/cms/library/files/:idx      메타 수정 (alt_text)
 * DELETE /api/admin/v1/cms/library/files/:idx      삭제 (사용처 스캔 → 409 or 물리+DB 삭제)
 */
class LibraryController extends BaseAdminApiController
{
    private const PER_PAGE = 20;
    private const MAX_MB   = 10;

    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/zip', 'application/x-zip-compressed',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    private FileLibraryModel $lib;

    public function __construct()
    {
        $this->lib = new FileLibraryModel();
    }

    #[OA\Get(
        path: '/api/admin/v1/cms/library/files',
        summary: '파일 라이브러리 전체 목록',
        tags: ['AdminFileLibrary'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\QueryParameter(name: 'mime', in: 'query', description: 'MIME 타입 접두 일치', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'source', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'uploader_idx', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'date_from', in: 'query', description: '업로드 시작일', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'date_to', in: 'query', description: '업로드 종료일', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'is_public', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [
            new OA\Response(response: 200, description: '파일 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $page     = max(1, (int) ($this->request->getGet('page')         ?? 1));
        $mime     = trim((string) ($this->request->getGet('mime')         ?? ''));
        $source   = trim((string) ($this->request->getGet('source')       ?? ''));
        $uploader = (int) ($this->request->getGet('uploader_idx')          ?? 0);
        $dateFrom = trim((string) ($this->request->getGet('date_from')     ?? ''));
        $dateTo   = trim((string) ($this->request->getGet('date_to')       ?? ''));

        $isPublic = $this->request->getGet('is_public');

        $db      = \Config\Database::connect();
        $builder = $db->table('tb_file_library f')
            ->select('f.idx, f.uploader_idx, f.source, f.original_name, f.file_path,
                      f.mime_type, f.file_size, f.alt_text, f.is_public, f.used_count, f.timestamp_insert,
                      u.user_id AS uploader_user_id, u.nickname AS uploader_nickname')
            ->join('tb_users u', 'u.idx = f.uploader_idx', 'left')
            ->orderBy('f.idx', 'DESC');

        if ($mime !== '') {
            $builder->like('f.mime_type', $mime, 'after');
        }
        if ($source !== '') {
            $builder->where('f.source', $source);
        }
        if ($uploader > 0) {
            $builder->where('f.uploader_idx', $uploader);
        }
        if ($dateFrom !== '') {
            $builder->where('f.timestamp_insert >=', strtotime($dateFrom));
        }
        if ($dateTo !== '') {
            $builder->where('f.timestamp_insert <=', strtotime($dateTo . ' 23:59:59'));
        }
        if ($isPublic !== null) {
            $builder->where('f.is_public', (int) $isPublic);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
                         ->get()->getResultArray();

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

    #[OA\Post(
        path: '/api/admin/v1/cms/library/files',
        summary: '파일 직접 업로드',
        tags: ['AdminFileLibrary'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'is_public', type: 'integer', enum: [0, 1], default: 0),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '업로드 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/admin/v1/cms/library/files/{idx}',
        summary: '파일 단건 조회',
        tags: ['AdminFileLibrary'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '파일 상세', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function show(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
        }

        $row['url'] = FileLibraryModel::publicUrl($row['file_path']);

        return $this->success($row);
    }

    #[OA\Put(
        path: '/api/admin/v1/cms/library/files/{idx}',
        summary: '파일 메타 수정',
        tags: ['AdminFileLibrary'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'alt_text', type: 'string', nullable: true),
                    new OA\Property(property: 'is_public', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function update(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
        }

        $body    = (array) $this->request->getJSON(true);
        $altText = array_key_exists('alt_text', $body)
            ? (trim((string) $body['alt_text']) ?: null)
            : $row['alt_text'];
        $isPublic = array_key_exists('is_public', $body)
            ? (int) (bool) $body['is_public']
            : (int) $row['is_public'];

        $this->lib->update($idx, ['alt_text' => $altText, 'is_public' => $isPublic]);

        return $this->success(['idx' => $idx], lang('Api.library_updated'));
    }

    #[OA\Delete(
        path: '/api/admin/v1/cms/library/files/{idx}',
        summary: '파일 삭제',
        tags: ['AdminFileLibrary'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 409, description: '사용 중인 파일', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function delete(int $idx): ResponseInterface
    {
        $row = $this->lib->find($idx);
        if (! $row) {
            return $this->failNotFound(lang('Api.library_not_found'));
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
