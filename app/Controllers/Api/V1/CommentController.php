<?php

namespace App\Controllers\Api\V1;

use App\Models\ArticleModel;
use App\Models\BbsModel;
use App\Models\CommentModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 댓글 API
 *
 * GET    /api/v1/boards/:bbsId/articles/:idx/comments
 * POST   /api/v1/boards/:bbsId/articles/:idx/comments
 * PUT    /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
 * DELETE /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
 */
class CommentController extends BaseApiController
{
    private BbsModel     $bbs;
    private ArticleModel $article;
    private CommentModel $comment;

    public function __construct()
    {
        $this->bbs     = new BbsModel();
        $this->article = new ArticleModel();
        $this->comment = new CommentModel();
    }

    public function index(string $bbsId, int $articleIdx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return $this->failForbidden('접근 권한이 없습니다.');
        }

        return $this->success($this->comment->getByArticle($articleIdx));
    }

    public function create(string $bbsId, int $articleIdx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['write_comment'] ?? [])) {
            return $this->failForbidden('댓글 작성 권한이 없습니다.');
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            return $this->failNotFound('게시글을 찾을 수 없습니다.');
        }

        $text = trim((string) ($this->request->getJSON(true)['comment'] ?? ''));
        if (! $text) {
            return $this->failValidation([], '댓글 내용을 입력해주세요.');
        }

        $userIdx    = $this->getUserIdx();
        $commentIdx = $this->comment->writeComment([
            'bbs_idx'          => $board['idx'],
            'article_idx'      => $articleIdx,
            'user_idx'         => $userIdx,
            'exec_user_idx'    => $userIdx,
            'comment'          => $text,
            'timestamp_insert' => time(),
            'client_ip_insert' => $this->request->getIPAddress(),
            'is_deleted'       => 0,
            'agent_insert'     => 'P',
        ]);

        $this->article->set('comment_count', 'comment_count + 1', false)
                       ->where('idx', $articleIdx)->update();

        return $this->created(['idx' => $commentIdx], '댓글이 작성되었습니다.');
    }

    public function update(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx || $c['is_deleted']) {
            return $this->failNotFound('댓글을 찾을 수 없습니다.');
        }
        if ((int) $c['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden('수정 권한이 없습니다.');
        }

        $text = trim((string) ($this->request->getJSON(true)['comment'] ?? ''));
        if (! $text) {
            return $this->failValidation([], '댓글 내용을 입력해주세요.');
        }

        $this->comment->update($commentIdx, [
            'comment'          => $text,
            'exec_user_idx'    => $this->getUserIdx(),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        return $this->success(null, '댓글이 수정되었습니다.');
    }

    public function delete(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx) {
            return $this->failNotFound('댓글을 찾을 수 없습니다.');
        }
        if ((int) $c['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden('삭제 권한이 없습니다.');
        }

        $this->comment->softDelete($commentIdx);
        $this->article->set('comment_count', 'GREATEST(comment_count - 1, 0)', false)
                       ->where('idx', $articleIdx)->update();

        return $this->success(null, '댓글이 삭제되었습니다.');
    }
}
