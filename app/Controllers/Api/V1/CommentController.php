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
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['view_article'] ?? [])) {
            return $this->failForbidden(lang('Api.access_forbidden'));
        }

        return $this->success($this->comment->getByArticle($articleIdx));
    }

    public function create(string $bbsId, int $articleIdx): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['write_comment'] ?? [])) {
            return $this->failForbidden(lang('Api.comment_write_forbidden'));
        }

        $post = $this->article->find($articleIdx);
        if (! $post || $post['bbs_idx'] != $board['idx'] || $post['is_deleted']) {
            return $this->failNotFound(lang('Api.article_not_found'));
        }

        $text = trim((string) ($this->request->getJSON(true)['comment'] ?? ''));
        if (! $text) {
            return $this->failValidation([], lang('Api.comment_required'));
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

        return $this->created(['idx' => $commentIdx], lang('Api.comment_created'));
    }

    public function update(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx || $c['is_deleted']) {
            return $this->failNotFound(lang('Api.comment_not_found'));
        }
        if ((int) $c['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.edit_forbidden'));
        }

        $text = trim((string) ($this->request->getJSON(true)['comment'] ?? ''));
        if (! $text) {
            return $this->failValidation([], lang('Api.comment_required'));
        }

        $this->comment->update($commentIdx, [
            'comment'          => $text,
            'exec_user_idx'    => $this->getUserIdx(),
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ]);

        return $this->success(null, lang('Api.comment_updated'));
    }

    public function delete(string $bbsId, int $articleIdx, int $commentIdx): ResponseInterface
    {
        $c = $this->comment->find($commentIdx);
        if (! $c || $c['article_idx'] != $articleIdx) {
            return $this->failNotFound(lang('Api.comment_not_found'));
        }
        if ((int) $c['user_idx'] !== $this->getUserIdx()) {
            return $this->failForbidden(lang('Api.delete_forbidden'));
        }

        $this->comment->softDelete($commentIdx);
        $this->article->set('comment_count', 'GREATEST(comment_count - 1, 0)', false)
                       ->where('idx', $articleIdx)->update();

        return $this->success(null, lang('Api.comment_deleted'));
    }
}
