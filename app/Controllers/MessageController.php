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

    public function write(): string
    {
        $to = trim($this->request->getGet('to') ?? '');

        return view('message/write', [
            'title' => lang('App.write_message'),
            'to'    => $to,
        ]);
    }

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
