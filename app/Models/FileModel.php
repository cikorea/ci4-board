<?php

namespace App\Models;

use CodeIgniter\Model;

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

    /** 게시물 첨부파일 목록 (is_wysiwyg=0만) */
    public function getByArticle(int $articleIdx): array
    {
        return $this->where('article_idx', $articleIdx)
                    ->where('is_wysiwyg', 0)
                    ->orderBy('sequence', 'ASC')
                    ->orderBy('idx', 'ASC')
                    ->findAll();
    }

    /** 파일 저장 경로 반환 */
    public static function storagePath(string $conversionFilename): string
    {
        return WRITEPATH . 'uploads/' . $conversionFilename;
    }

    /** DB 레코드 + 실제 파일 삭제 */
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

    /** 게시물의 모든 첨부파일 삭제 */
    public function deleteByArticle(int $articleIdx): void
    {
        $files = $this->getByArticle($articleIdx);
        foreach ($files as $f) {
            $this->deleteFile($f['idx']);
        }
    }
}
