<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 게시글 API
 *
 * GET    /api/admin/v1/articles
 * PUT    /api/admin/v1/articles/:idx
 * DELETE /api/admin/v1/articles/:idx
 */
class ArticleController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $bbsId   = trim($this->request->getGet('bbs_id')  ?? '');
        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.is_notice, a.is_deleted, a.timestamp_insert,
                      a.comment_count, a.vote_count, b.bbs_id,
                      COALESCE(sn.value, b.bbs_id) AS bbs_name,
                      u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_bbs b', 'b.idx = a.bbs_idx')
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.is_deleted', 0)
            ->orderBy('a.idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('a.title', $keyword)
                ->orLike('u.nickname', $keyword)
                ->groupEnd();
        }
        if ($bbsId !== '') {
            $builder->where('b.bbs_id', $bbsId);
        }

        $total    = $builder->countAllResults(false);
        $articles = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($articles, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function update(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $post = $db->table('tb_bbs_article')->where('idx', $idx)->where('is_deleted', 0)->get()->getRowArray();
        if (! $post) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }

        $body     = (array) $this->request->getJSON(true);
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');
        $isNotice = (int) (bool) ($body['is_notice'] ?? false);

        if (! $title || ! $contents) {
            return $this->failValidation([], '제목과 내용을 입력해주세요.');
        }

        $db->transStart();

        $db->table('tb_bbs_article')->where('idx', $idx)->update([
            'title'            => $title,
            'is_notice'        => $isNotice,
            'exec_user_idx'    => $this->getUserIdx(),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        $db->table('tb_bbs_contents')->where('article_idx', $idx)->update([
            'contents'      => $contents,
            'exec_user_idx' => $this->getUserIdx(),
            'client_ip'     => $this->request->getIPAddress(),
        ]);

        $db->transComplete();
        clear_home_cache();

        return $this->success(null, '게시글이 수정되었습니다.');
    }

    public function delete(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $post = $db->table('tb_bbs_article')->where('idx', $idx)->get()->getRowArray();
        if (! $post) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }

        $db->table('tb_bbs_article')->where('idx', $idx)->update([
            'is_deleted'       => 1,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
            'exec_user_idx'    => $this->getUserIdx(),
        ]);

        clear_home_cache();

        return $this->success(null, '게시글이 삭제되었습니다.');
    }
}
