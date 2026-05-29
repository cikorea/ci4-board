<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @apiDefine CommentModel
 * @apiDescription tb_bbs_comment 를 관리하는 모델.
 */
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

    /**
     * @api {model} /model/CommentModel/getByArticle CommentModel::getByArticle
     * @apiName CommentModel_getByArticle
     * @apiDescription 게시글의 댓글 목록
     * @apiGroup CommentModel
     * @apiDescription 삭제되지 않은 댓글을 작성 순서(idx ASC)로 반환하며 작성자 정보를 JOIN한다.
     *
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {Array} rows        댓글 배열 (idx, user_idx, comment, timestamp_insert, nickname, user_id)
     */
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

    /**
     * @api {model} /model/CommentModel/writeComment CommentModel::writeComment
     * @apiName CommentModel_writeComment
     * @apiDescription 댓글 작성
     * @apiGroup CommentModel
     *
     * @apiParam  {Object} data  댓글 컬럼 맵 (article_idx, user_idx, comment 등)
     * @apiSuccess {Number} commentIdx 새로 생성된 댓글 idx
     */
    public function writeComment(array $data): int
    {
        $this->insert($data);
        return (int) $this->db->insertID();
    }

    /**
     * @api {model} /model/CommentModel/softDelete CommentModel::softDelete
     * @apiName CommentModel_softDelete
     * @apiDescription 댓글 소프트 삭제
     * @apiGroup CommentModel
     * @apiDescription is_deleted=1 로 플래그를 세우며 실제 행은 보존한다.
     *
     * @apiParam {Number} commentIdx  댓글 idx
     */
    public function softDelete(int $commentIdx): void
    {
        $this->update($commentIdx, [
            'is_deleted'       => 1,
            'timestamp_update' => time(),
        ]);
    }
}
