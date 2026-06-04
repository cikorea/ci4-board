<?php

namespace App\Models;

use CodeIgniter\Model;

class FileLibraryModel extends Model
{
    protected $table         = 'tb_file_library';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'uploader_idx', 'source', 'original_name', 'stored_name',
        'file_path', 'mime_type', 'file_size', 'alt_text',
        'is_public', 'used_count', 'timestamp_insert',
    ];

    public static function publicUrl(string $filePath): string
    {
        return base_url('uploads/' . $filePath);
    }

    public static function absolutePath(string $filePath): string
    {
        return FCPATH . 'uploads/' . $filePath;
    }

    /**
     * 파일이 실제로 참조된 위치를 스캔한다.
     * 게시글 본문(tb_bbs_contents), CMS 페이지(tb_cms_page), CMS 배너(tb_cms_banner)를 검사한다.
     *
     * @return array<int, array{type: string, idx: int, hint: string}>
     */
    public function findUsages(array $row): array
    {
        $db     = \Config\Database::connect();
        $needle = $row['file_path'];
        $usages = [];

        // 게시글 본문
        $articles = $db->table('tb_bbs_contents c')
            ->select('c.article_idx, b.bbs_id')
            ->join('tb_bbs b', 'b.idx = c.bbs_idx')
            ->like('c.contents', $needle)
            ->get()->getResultArray();
        foreach ($articles as $a) {
            $usages[] = [
                'type' => 'article',
                'idx'  => (int) $a['article_idx'],
                'hint' => "게시글 #{$a['article_idx']} (게시판: {$a['bbs_id']})",
            ];
        }

        // CMS 페이지
        $pages = $db->table('tb_cms_page')
            ->select('idx, slug, title')
            ->like('contents', $needle)
            ->get()->getResultArray();
        foreach ($pages as $p) {
            $usages[] = [
                'type' => 'cms_page',
                'idx'  => (int) $p['idx'],
                'hint' => "CMS 페이지 '{$p['title']}' (/{$p['slug']})",
            ];
        }

        // CMS 배너
        $banners = $db->table('tb_cms_banner')
            ->select('idx, position')
            ->like('image_path', $needle)
            ->get()->getResultArray();
        foreach ($banners as $b) {
            $usages[] = [
                'type' => 'cms_banner',
                'idx'  => (int) $b['idx'],
                'hint' => "CMS 배너 #{$b['idx']} (위치: {$b['position']})",
            ];
        }

        return $usages;
    }

    public function deleteFile(int $idx): void
    {
        $row = $this->find($idx);
        if (! $row) return;

        $path = self::absolutePath($row['file_path']);
        if (is_file($path)) {
            @unlink($path);
        }

        $this->delete($idx);
    }
}
