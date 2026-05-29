<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @apiDefine FileModel
 * @apiDescription tb_bbs_file 을 관리하는 모델. 실제 파일은 WRITEPATH/uploads/{bbs_idx}/{Ymd}/ 에 저장된다.
 */
class FileModel extends Model
{
    protected $table         = 'tb_bbs_file';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'bbs_idx', 'article_idx', 'user_idx', 'is_wysiwyg',
        'original_filename', 'conversion_filename', 'mime', 'capacity', 'sequence',
    ];

    /**
     * @api {model} /model/FileModel/getByArticle FileModel::getByArticle
     * @apiName FileModel_getByArticle
     * @apiDescription 게시글 첨부파일 목록
     * @apiGroup FileModel
     * @apiDescription WYSIWYG 이미지(is_wysiwyg=1)를 제외한 일반 첨부파일만 반환한다.
     *
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {Array} rows        파일 배열 (sequence ASC 정렬)
     */
    public function getByArticle(int $articleIdx): array
    {
        return $this->where('article_idx', $articleIdx)
                    ->where('is_wysiwyg', 0)
                    ->orderBy('sequence', 'ASC')
                    ->orderBy('idx', 'ASC')
                    ->findAll();
    }

    /**
     * @api {model} /model/FileModel/storagePath FileModel::storagePath
     * @apiName FileModel_storagePath
     * @apiDescription 파일 절대 경로 반환
     * @apiGroup FileModel
     * @apiDescription conversion_filename 을 받아 WRITEPATH 기반의 절대 경로를 반환하는 정적 헬퍼.
     *
     * @apiParam  {String} conversionFilename  DB에 저장된 상대 경로 (예: 1/20260529/abc123.jpg)
     * @apiSuccess {String} path 절대 파일 경로
     */
    public static function storagePath(string $conversionFilename): string
    {
        return WRITEPATH . 'uploads/' . $conversionFilename;
    }

    /**
     * @api {model} /model/FileModel/deleteFile FileModel::deleteFile
     * @apiName FileModel_deleteFile
     * @apiDescription DB 레코드 + 실제 파일 삭제
     * @apiGroup FileModel
     * @apiDescription 파일이 실제로 존재하면 unlink 후 DB 레코드를 삭제한다.
     *
     * @apiParam {Number} idx  파일 idx
     */
    public function deleteFile(int $idx): void
    {
        $file = $this->find($idx);
        if (! $file) return;

        $path = self::storagePath($file['conversion_filename']);
        if (is_file($path)) {
            @unlink($path);
        }

        $this->delete($idx);
    }

    /**
     * @api {model} /model/FileModel/deleteByArticle FileModel::deleteByArticle
     * @apiName FileModel_deleteByArticle
     * @apiDescription 게시글의 모든 첨부파일 삭제
     * @apiGroup FileModel
     * @apiDescription getByArticle() 결과를 순회하며 deleteFile() 을 호출한다.
     *
     * @apiParam {Number} articleIdx  게시글 idx
     */
    public function deleteByArticle(int $articleIdx): void
    {
        $files = $this->getByArticle($articleIdx);
        foreach ($files as $f) {
            $this->deleteFile($f['idx']);
        }
    }
}
