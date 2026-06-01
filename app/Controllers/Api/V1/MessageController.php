<?php

namespace App\Controllers\Api\V1;

use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 쪽지 API
 *
 * GET    /api/v1/messages/inbox
 * GET    /api/v1/messages/sent
 * GET    /api/v1/messages/:idx
 * POST   /api/v1/messages
 * DELETE /api/v1/messages/:idx
 */
class MessageController extends BaseApiController
{
    private MessageModel $model;

    public function __construct()
    {
        $this->model = new MessageModel();
    }

    public function inbox(): ResponseInterface
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getInbox($this->getUserIdx(), $page);

        return $this->successList($messages, [
            'page'      => $page,
            'per_page'  => $this->model->_perPage,
            'total'     => $this->model->_total,
            'last_page' => (int) ceil($this->model->_total / $this->model->_perPage),
        ]);
    }

    public function sent(): ResponseInterface
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getSent($this->getUserIdx(), $page);

        return $this->successList($messages, [
            'page'      => $page,
            'per_page'  => $this->model->_perPage,
            'total'     => $this->model->_total,
            'last_page' => (int) ceil($this->model->_total / $this->model->_perPage),
        ]);
    }

    public function show(int $idx): ResponseInterface
    {
        $userIdx = $this->getUserIdx();
        $msg     = $this->model->getOne($idx, $userIdx);

        if (! $msg) {
            return $this->failNotFound('쪽지를 찾을 수 없습니다.');
        }

        if ($msg['receiver_user_idx'] == $userIdx && ! $msg['is_read']) {
            $this->model->markRead($idx, $this->request->getIPAddress());
            $msg['is_read'] = 1;
        }

        $msg['is_sender'] = $msg['sender_user_idx'] == $userIdx;

        return $this->success($msg);
    }

    public function send(): ResponseInterface
    {
        $body     = (array) $this->request->getJSON(true);
        $toId     = trim((string) ($body['to']       ?? ''));
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = trim((string) ($body['contents'] ?? ''));

        if (! $toId || ! $contents) {
            return $this->failValidation([], '수신자와 내용을 입력해주세요.');
        }

        $userModel = new UserModel();
        $receiver  = $userModel->where('user_id', $toId)
                               ->orWhere('nickname', $toId)
                               ->where('status', 1)
                               ->first();

        if (! $receiver) {
            return $this->failNotFound("'{$toId}' 사용자를 찾을 수 없습니다.");
        }

        $userIdx = $this->getUserIdx();
        if ($receiver['idx'] == $userIdx) {
            return $this->fail('자기 자신에게 쪽지를 보낼 수 없습니다.', 422);
        }

        $this->model->send([
            'sender_user_idx'     => $userIdx,
            'receiver_user_idx'   => $receiver['idx'],
            'title'               => $title ?: null,
            'contents'            => $contents,
            'timestamp_send'      => time(),
            'client_ip_send'      => $this->request->getIPAddress(),
            'is_read'             => 0,
            'is_deleted_sender'   => 0,
            'is_deleted_receiver' => 0,
        ]);

        return $this->created(null, esc_db($receiver['nickname']) . '님께 쪽지를 보냈습니다.');
    }

    public function delete(int $idx): ResponseInterface
    {
        $userIdx = $this->getUserIdx();
        $msg     = $this->model->getOne($idx, $userIdx);

        if (! $msg) {
            return $this->failNotFound('쪽지를 찾을 수 없습니다.');
        }

        $this->model->deleteForUser($idx, $userIdx);

        return $this->success(null, '쪽지가 삭제되었습니다.');
    }
}
