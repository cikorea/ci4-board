<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table         = 'tb_bbs_article';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'bbs_idx', 'category_idx', 'user_idx', 'exec_user_idx',
        'title', 'comment_count', 'vote_count', 'scrap_count',
        'timestamp_insert', 'timestamp_update',
        'client_ip_insert', 'client_ip_update',
        'html_used', 'is_notice', 'is_secret', 'is_deleted', 'agent_insert',
    ];

    /** 게시판 글 목록 (페이지네이션, 조회수 JOIN) */
    public function getList(int $bbsIdx, ?string $keyword = null, int $perPage = 15): array
    {
        $builder = $this->db->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.comment_count, a.vote_count, a.is_notice, a.is_secret, a.timestamp_insert, u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.bbs_idx', $bbsIdx)
            ->where('a.is_deleted', 0)
            ->orderBy('a.is_notice', 'DESC')
            ->orderBy('a.idx', 'DESC');

        if ($keyword) {
            $builder->groupStart()
                ->like('a.title', $keyword)
                ->groupEnd();
        }

        $total   = $builder->countAllResults(false);
        $page    = (int) ($_GET['page'] ?? 1);
        $offset  = ($page - 1) * $perPage;
        $rows    = $builder->limit($perPage, $offset)->get()->getResultArray();

        $this->_pagerTotal   = $total;
        $this->_pagerPage    = $page;
        $this->_pagerPerPage = $perPage;

        return $rows;
    }

    public int $_pagerTotal   = 0;
    public int $_pagerPage    = 1;
    public int $_pagerPerPage = 15;

    /** 단일 글 조회 (본문 포함) */
    public function getArticleWithContents(int $articleIdx): ?array
    {
        $row = $this->db->table('tb_bbs_article a')
            ->select('a.*, c.contents, COALESCE(h.hit, 0) AS hit_count, u.nickname, u.user_id')
            ->join('tb_bbs_contents c', 'c.article_idx = a.idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->where('a.idx', $articleIdx)
            ->where('a.is_deleted', 0)
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    /** 메인용 최신글 N개 */
    public function getLatest(int $bbsIdx, int $limit = 10): array
    {
        return $this->db->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.timestamp_insert, a.is_notice, a.comment_count, u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.bbs_idx', $bbsIdx)
            ->where('a.is_deleted', 0)
            ->orderBy('a.idx', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /** 조회수 증가 */
    public function incrementHit(int $bbsIdx, int $articleIdx): void
    {
        $existing = $this->db->table('tb_bbs_hit')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->get()->getRowArray();

        if ($existing) {
            $this->db->table('tb_bbs_hit')
                ->where('bbs_idx', $bbsIdx)
                ->where('article_idx', $articleIdx)
                ->set('hit', 'hit + 1', false)
                ->update();
        } else {
            $this->db->table('tb_bbs_hit')->insert([
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'hit'         => 1,
            ]);
        }
    }

    /** 글쓰기: article + contents + hit 동시 삽입 */
    public function writeArticle(array $articleData, string $contents): int
    {
        $this->db->transStart();

        $articleIdx = $this->insert($articleData, true);

        $this->db->table('tb_bbs_contents')->insert([
            'bbs_idx'       => $articleData['bbs_idx'],
            'article_idx'   => $articleIdx,
            'exec_user_idx' => $articleData['user_idx'],
            'contents'      => $contents,
            'client_ip'     => $articleData['client_ip_insert'],
        ]);

        $this->db->table('tb_bbs_hit')->insert([
            'bbs_idx'     => $articleData['bbs_idx'],
            'article_idx' => $articleIdx,
            'hit'         => 0,
        ]);

        $this->db->transComplete();

        return (int) $articleIdx;
    }

    /** 글수정: article + contents 업데이트 */
    public function updateArticle(int $articleIdx, array $articleData, string $contents): void
    {
        $this->db->transStart();

        $this->update($articleIdx, $articleData);

        $this->db->table('tb_bbs_contents')
            ->where('article_idx', $articleIdx)
            ->update(['contents' => $contents]);

        $this->db->transComplete();
    }

    /** 태그 저장 (기존 삭제 후 재삽입) */
    public function saveTagsForArticle(int $bbsIdx, int $articleIdx, array $tags): void
    {
        $this->db->table('tb_bbs_tag')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->delete();

        foreach (array_values($tags) as $seq => $tag) {
            $tag = trim($tag);
            if ($tag === '') continue;
            $this->db->table('tb_bbs_tag')->insert([
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'tag'         => $tag,
                'sequence'    => $seq + 1,
            ]);
        }
    }

    /** URL 저장 (기존 삭제 후 재삽입) */
    public function saveUrlsForArticle(int $bbsIdx, int $articleIdx, array $urls): void
    {
        $this->db->table('tb_bbs_url')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->delete();

        foreach (array_values($urls) as $seq => $url) {
            $url = trim($url);
            if ($url === '') continue;
            $this->db->table('tb_bbs_url')->insert([
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'url'         => $url,
                'sequence'    => $seq + 1,
            ]);
        }
    }

    /** 태그 목록 */
    public function getTagsByArticle(int $articleIdx): array
    {
        return $this->db->table('tb_bbs_tag')
            ->where('article_idx', $articleIdx)
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();
    }

    /** URL 목록 */
    public function getUrlsByArticle(int $articleIdx): array
    {
        return $this->db->table('tb_bbs_url')
            ->where('article_idx', $articleIdx)
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();
    }

    /** 소프트 삭제 */
    public function softDelete(int $articleIdx): void
    {
        $this->update($articleIdx, [
            'is_deleted'       => 1,
            'timestamp_update' => time(),
        ]);
    }
}
