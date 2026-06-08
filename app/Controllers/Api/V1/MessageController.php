<?php

namespace App\Controllers\Api\V1;

use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/v1/messages/inbox',
        summary: '받은 쪽지함',
        tags: ['Message'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '받은 쪽지 목록'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/messages/sent',
        summary: '보낸 쪽지함',
        tags: ['Message'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '보낸 쪽지 목록'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/messages/{idx}',
        summary: '쪽지 상세 조회 (송·수신자만)',
        tags: ['Message'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '쪽지 상세'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(int $idx): ResponseInterface
    {
        $userIdx = $this->getUserIdx();
        $msg     = $this->model->getOne($idx, $userIdx);

        if (! $msg) {
            return $this->failNotFound(lang('Api.message_not_found'));
        }

        if ($msg['receiver_user_idx'] == $userIdx && ! $msg['is_read']) {
            $this->model->markRead($idx, $this->request->getIPAddress());
            $msg['is_read'] = 1;
        }

        $msg['is_sender'] = $msg['sender_user_idx'] == $userIdx;

        return $this->success($msg);
    }

    #[OA\Post(
        path: '/api/v1/messages',
        summary: '쪽지 보내기',
        tags: ['Message'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['to', 'contents'],
                properties: [
                    new OA\Property(property: 'to', type: 'string', description: '수신자 user_id 또는 nickname'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'contents', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '발송 완료'),
            new OA\Response(response: 404, description: '수신자 없음'),
            new OA\Response(response: 422, description: '자신에게 발송 불가 또는 필수값 누락'),
        ]
    )]
    public function send(): ResponseInterface
    {
        $body     = (array) $this->request->getJSON(true);
        $toId     = trim((string) ($body['to']       ?? ''));
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = trim((string) ($body['contents'] ?? ''));

        if (! $toId || ! $contents) {
            return $this->failValidation([], lang('Api.message_required'));
        }

        $userModel = new UserModel();
        $receiver  = $userModel->where('user_id', $toId)
                               ->orWhere('nickname', $toId)
                               ->where('status', 1)
                               ->first();

        if (! $receiver) {
            // "'{0}' 사용자를 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.user_not_found_by_id', [$toId]));
        }

        $userIdx = $this->getUserIdx();
        if ($receiver['idx'] == $userIdx) {
            return $this->failValidation([], lang('Api.message_self_send'));
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

        // "{0}님께 쪽지를 보냈습니다."
        return $this->created(null, lang('Api.message_sent_to', [esc_db($receiver['nickname'])]));
    }

    #[OA\Delete(
        path: '/api/v1/messages/{idx}',
        summary: '쪽지 삭제 (송·수신자만)',
        tags: ['Message'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '삭제 완료'),
        ]
    )]
    public function delete(int $idx): ResponseInterface
    {
        $userIdx = $this->getUserIdx();
        $msg     = $this->model->getOne($idx, $userIdx);

        if (! $msg) {
            return $this->failNotFound(lang('Api.message_not_found'));
        }

        $this->model->deleteForUser($idx, $userIdx);

        return $this->success(null, lang('Api.message_deleted'));
    }
}
