<?php

namespace App\Models;

use CodeIgniter\Model;

class CommentModel extends Model
{
    protected $table         = 'tb_bbs_comment';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'bbs_idx', 'article_idx', 'user_idx', 'exec_user_idx',
        'comment', 'timestamp_insert', 'timestamp_update',
        'client_ip_insert', 'client_ip_update',
        'is_deleted', 'agent_insert',
    ];

    public function getByArticle(int $articleIdx): array
    {
        return $this->db->table('tb_bbs_comment c')
            ->select('c.idx, c.user_idx, c.comment, c.vote_count, c.timestamp_insert, c.timestamp_update, c.is_deleted, u.nickname, u.user_id')
            ->join('tb_users u', 'u.idx = c.user_idx', 'left')
            ->where('c.article_idx', $articleIdx)
            ->where('c.is_deleted', 0)
            ->orderBy('c.idx', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function writeComment(array $data): int
    {
        $this->insert($data);
        return (int) $this->db->insertID();
    }

    public function softDelete(int $commentIdx): void
    {
        $this->update($commentIdx, [
            'is_deleted'       => 1,
            'timestamp_update' => time(),
        ]);
    }
}
