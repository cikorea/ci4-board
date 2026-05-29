<?php

namespace App\Controllers;

use App\Models\MessageModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class MessageController extends Controller
{
    protected MessageModel $model;
    protected int $userIdx;

    public function __construct()
    {
        $this->model   = new MessageModel();
        $this->userIdx = (int) session()->get('user_idx');
    }

    /** 받은 쪽지함 */
    public function inbox(): string
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getInbox($this->userIdx, $page);

        return view('message/inbox', [
            'title'    => '받은 쪽지함',
            'messages' => $messages,
            'pager'    => ['total' => $this->model->_total, 'page' => $page, 'perPage' => $this->model->_perPage],
        ]);
    }

    /** 보낸 쪽지함 */
    public function sent(): string
    {
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $messages = $this->model->getSent($this->userIdx, $page);

        return view('message/sent', [
            'title'    => '보낸 쪽지함',
            'messages' => $messages,
            'pager'    => ['total' => $this->model->_total, 'page' => $page, 'perPage' => $this->model->_perPage],
        ]);
    }

    /** 쪽지 읽기 */
    public function view(int $idx): string|RedirectResponse
    {
        $msg = $this->model->getOne($idx, $this->userIdx);
        if (! $msg) {
            throw new PageNotFoundException();
        }

        // 수신자가 읽는 경우 읽음 처리
        if ($msg['receiver_user_idx'] == $this->userIdx && ! $msg['is_read']) {
            $this->model->markRead($idx, $this->request->getIPAddress());
            $msg['is_read'] = 1;
        }

        $isSender = $msg['sender_user_idx'] == $this->userIdx;

        return view('message/view', [
            'title'    => '쪽지 읽기',
            'msg'      => $msg,
            'isSender' => $isSender,
        ]);
    }

    /** 쪽지 쓰기 폼 */
    public function write(): string
    {
        $to = trim($this->request->getGet('to') ?? '');

        return view('message/write', [
            'title' => '쪽지 쓰기',
            'to'    => $to,
        ]);
    }

    /** 쪽지 전송 처리 */
    public function send(): RedirectResponse
    {
        $toId     = trim($this->request->getPost('to') ?? '');
        $title    = trim($this->request->getPost('title') ?? '');
        $contents = trim($this->request->getPost('contents') ?? '');

        if (! $toId || ! $contents) {
            return redirect()->back()->with('error', '받는 사람과 내용을 입력해주세요.')->withInput();
        }

        $userModel = new UserModel();
        $receiver  = $userModel->where('user_id', $toId)
                               ->orWhere('nickname', $toId)
                               ->where('status', 1)
                               ->first();

        if (! $receiver) {
            return redirect()->back()->with('error', "'{$toId}' 회원을 찾을 수 없습니다.")->withInput();
        }

        if ($receiver['idx'] == $this->userIdx) {
            return redirect()->back()->with('error', '자기 자신에게 쪽지를 보낼 수 없습니다.')->withInput();
        }

        $this->model->send([
            'sender_user_idx'   => $this->userIdx,
            'receiver_user_idx' => $receiver['idx'],
            'title'             => $title ?: null,
            'contents'          => $contents,
            'timestamp_send'    => time(),
            'client_ip_send'    => $this->request->getIPAddress(),
            'is_read'           => 0,
            'is_deleted_sender'   => 0,
            'is_deleted_receiver' => 0,
        ]);

        return redirect()->to('/message')->with('success',
            esc_db($receiver['nickname']) . '님께 쪽지를 보냈습니다.');
    }

    /** 쪽지 삭제 */
    public function delete(int $idx): RedirectResponse
    {
        $msg = $this->model->getOne($idx, $this->userIdx);
        if (! $msg) {
            throw new PageNotFoundException();
        }

        $this->model->deleteForUser($idx, $this->userIdx);

        $back = $this->request->getGet('from') === 'sent' ? '/message/sent' : '/message';
        return redirect()->to($back)->with('success', '쪽지가 삭제되었습니다.');
    }
}
