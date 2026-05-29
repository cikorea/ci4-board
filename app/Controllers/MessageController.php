<?php

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * @api {group} Message 쪽지
 * @apiGroup Message
 * @apiPermission 로그인
 * @apiDescription 받은 쪽지함·보낸 쪽지함·쪽지 읽기·작성·삭제를 처리한다.
 */
class MessageController extends Controller
{
    protected MessageModel $model;
    protected int $userIdx;

    public function __construct()
    {
        $this->model   = new MessageModel();
        $this->userIdx = (int) session()->get('user_idx');
    }

    /**
     * @api {get} /message 받은 쪽지함
     * @apiGroup Message
     * @apiName MessageInbox
     *
     * @apiQuery  {Number} [page=1]  페이지 번호
     * @apiSuccess {Array}  messages  받은 쪽지 목록 (sender 정보 포함)
     * @apiSuccess {Object} pager     페이지네이션 메타 {total, page, perPage}
     */
    public function inbox(): string
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getInbox($this->userIdx, $page);

        return view('message/inbox', [
            'title'    => lang('App.inbox'),
            'messages' => $messages,
            'pager'    => ['total' => $this->model->_total, 'page' => $page, 'perPage' => $this->model->_perPage],
        ]);
    }

    /**
     * @api {get} /message/sent 보낸 쪽지함
     * @apiGroup Message
     * @apiName MessageSent
     *
     * @apiQuery  {Number} [page=1]  페이지 번호
     * @apiSuccess {Array}  messages  보낸 쪽지 목록 (receiver 정보 포함)
     * @apiSuccess {Object} pager     페이지네이션 메타 {total, page, perPage}
     */
    public function sent(): string
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getSent($this->userIdx, $page);

        return view('message/sent', [
            'title'    => lang('App.sent_box'),
            'messages' => $messages,
            'pager'    => ['total' => $this->model->_total, 'page' => $page, 'perPage' => $this->model->_perPage],
        ]);
    }

    /**
     * @api {get} /message/:idx 쪽지 읽기
     * @apiGroup Message
     * @apiName MessageView
     * @apiDescription 수신자가 처음 열면 is_read=1 로 갱신된다. 발신자 또는 수신자만 접근 가능하다.
     *
     * @apiParam  {Number} idx      쪽지 idx
     * @apiSuccess {Object} msg     쪽지 상세 (sender·receiver 정보 포함)
     * @apiSuccess {Boolean} isSender 현재 사용자가 발신자인지 여부
     */
    public function view(int $idx): string|RedirectResponse
    {
        $msg = $this->model->getOne($idx, $this->userIdx);
        if (! $msg) {
            throw new PageNotFoundException();
        }

        if ($msg['receiver_user_idx'] == $this->userIdx && ! $msg['is_read']) {
            $this->model->markRead($idx, $this->request->getIPAddress());
            $msg['is_read'] = 1;
        }

        $isSender = $msg['sender_user_idx'] == $this->userIdx;

        return view('message/view', [
            'title'    => lang('App.read_message'),
            'msg'      => $msg,
            'isSender' => $isSender,
        ]);
    }

    /**
     * @api {get} /message/write 쪽지 작성 폼
     * @apiGroup Message
     * @apiName MessageWrite
     *
     * @apiQuery  {String} [to]   수신자 user_id 또는 닉네임 (폼 초기값)
     * @apiSuccess {String} to    수신자 초기값
     */
    public function write(): string
    {
        $to = trim($this->request->getGet('to') ?? '');

        return view('message/write', [
            'title' => lang('App.write_message'),
            'to'    => $to,
        ]);
    }

    /**
     * @api {post} /message/send 쪽지 전송
     * @apiGroup Message
     * @apiName MessageSend
     * @apiDescription to 는 user_id 또는 닉네임으로 검색한다. 자기 자신에게 전송은 불가하다.
     *
     * @apiBody  {String} to        수신자 user_id 또는 닉네임
     * @apiBody  {String} [title]   제목 (생략 가능)
     * @apiBody  {String} contents  본문 (필수)
     * @apiSuccess {String} redirect 받은 쪽지함으로 이동
     * @apiError {String} error  작성 폼으로 리다이렉트 (error 플래시 포함)
     */
    public function send(): RedirectResponse
    {
        $toId     = trim($this->request->getPost('to') ?? '');
        $title    = trim($this->request->getPost('title') ?? '');
        $contents = trim($this->request->getPost('contents') ?? '');

        if (! $toId || ! $contents) {
            return redirect()->back()->with('error', lang('App.msg_comment_empty'))->withInput();
        }

        $userModel = new UserModel();
        $receiver  = $userModel->where('user_id', $toId)
                               ->orWhere('nickname', $toId)
                               ->where('status', 1)
                               ->first();

        if (! $receiver) {
            return redirect()->back()->with('error', lang('App.msg_message_user_not_found', [$toId]))->withInput();
        }

        if ($receiver['idx'] == $this->userIdx) {
            return redirect()->back()->with('error', lang('App.msg_message_self'))->withInput();
        }

        $this->model->send([
            'sender_user_idx'     => $this->userIdx,
            'receiver_user_idx'   => $receiver['idx'],
            'title'               => $title ?: null,
            'contents'            => $contents,
            'timestamp_send'      => time(),
            'client_ip_send'      => $this->request->getIPAddress(),
            'is_read'             => 0,
            'is_deleted_sender'   => 0,
            'is_deleted_receiver' => 0,
        ]);

        return redirect()->to('/message')->with('success',
            lang('App.msg_message_sent', [esc_db($receiver['nickname'])]));
    }

    /**
     * @api {get} /message/:idx/delete 쪽지 소프트 삭제
     * @apiGroup Message
     * @apiName MessageDelete
     * @apiDescription 발신자라면 is_deleted_sender=1, 수신자라면 is_deleted_receiver=1 로 처리한다.
     *   양쪽 모두 삭제해도 DB 레코드는 남는다.
     *
     * @apiParam  {Number} idx       쪽지 idx
     * @apiQuery  {String} [from]    'sent' 이면 보낸 쪽지함으로, 그 외엔 받은 쪽지함으로 이동
     * @apiSuccess {String} redirect
     */
    public function delete(int $idx): RedirectResponse
    {
        $msg = $this->model->getOne($idx, $this->userIdx);
        if (! $msg) {
            throw new PageNotFoundException();
        }

        $this->model->deleteForUser($idx, $this->userIdx);

        $back = $this->request->getGet('from') === 'sent' ? '/message/sent' : '/message';
        return redirect()->to($back)->with('success', lang('App.msg_message_deleted'));
    }
}
