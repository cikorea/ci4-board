<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class AuthController extends Controller
{
    public function login(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_idx')) {
            return redirect()->to('/');
        }
        return view('auth/login', ['title' => lang('App.login')]);
    }

    public function loginProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $loginId  = trim($this->request->getPost('login_id'));
        $password = $this->request->getPost('password');

        $model = new UserModel();
        $user  = $model->findByLoginId($loginId);

        if (! $user) {
            return redirect()->back()->with('error', lang('App.msg_login_fail'))->withInput();
        }

        $storedPw = $user['super_secured_password'] ?? '';
        if (! $storedPw || ! password_verify($password, $storedPw)) {
            return redirect()->back()->with('error', lang('App.msg_login_fail'))->withInput();
        }

        session()->set([
            'user_idx'   => $user['idx'],
            'user_id'    => $user['user_id'],
            'nickname'   => $user['nickname'],
            'email'      => $user['email'],
            'level'      => $user['level'],
            'group_idx'  => (int) $user['group_idx'],
            'group_name' => $user['group_name'] ?? '',
            'logged_in'  => true,
        ]);

        return redirect()->to('/');
    }

    public function register(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_idx')) {
            return redirect()->to('/');
        }
        return view('auth/register', ['title' => lang('App.register')]);
    }

    public function registerProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId   = trim($this->request->getPost('user_id'));
        $nickname = trim($this->request->getPost('nickname'));
        $email    = trim($this->request->getPost('email'));
        $password = $this->request->getPost('password');
        $password2 = $this->request->getPost('password2');

        $errors = [];
        if (mb_strlen($userId) < 3 || mb_strlen($userId) > 32) {
            $errors[] = lang('App.msg_user_id_length');
        }
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 64) {
            $errors[] = lang('App.msg_nickname_length');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = lang('App.msg_invalid_email');
        }
        if (strlen($password) < 6) {
            $errors[] = lang('App.msg_password_length');
        }
        if ($password !== $password2) {
            $errors[] = lang('App.msg_password_mismatch');
        }

        $model = new UserModel();
        if (! $errors) {
            if ($model->findByUserId($userId)) {
                $errors[] = lang('App.msg_user_id_taken');
            }
            if ($model->findByEmail($email)) {
                $errors[] = lang('App.msg_email_taken');
            }
        }

        if ($errors) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        $model->insert([
            'user_id'                 => $userId,
            'nickname'                => $nickname,
            'name'                    => $nickname,
            'email'                   => $email,
            'super_secured_password'  => password_hash($password, PASSWORD_BCRYPT),
            'level'                   => 1,
            'group_idx'               => 2,
            'status'                  => 1,
            'timezone'                => '+09',
            'timestamp_insert'        => time(),
            'client_ip_insert'        => $this->request->getIPAddress(),
        ]);

        return redirect()->to('/auth/login')->with('success', lang('App.msg_register_done'));
    }

    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/');
    }

    public function profile(): string
    {
        $model = new UserModel();
        $user  = $model->find(session()->get('user_idx'));

        return view('auth/profile', [
            'title' => lang('App.profile_title'),
            'user'  => $user,
        ]);
    }

    public function profileProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userIdx  = (int) session()->get('user_idx');
        $model    = new UserModel();
        $user     = $model->find($userIdx);

        $nickname    = trim($this->request->getPost('nickname'));
        $email       = trim($this->request->getPost('email'));
        $newPassword = $this->request->getPost('new_password');
        $newPassword2 = $this->request->getPost('new_password2');
        $currentPw   = $this->request->getPost('current_password');

        if (! password_verify($currentPw, $user['super_secured_password'])) {
            return redirect()->back()->with('error', lang('App.msg_wrong_current_pw'))->withInput();
        }

        $errors = [];
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 64) {
            $errors[] = lang('App.msg_nickname_length');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = lang('App.msg_invalid_email');
        }
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $errors[] = lang('App.msg_new_pw_length');
            }
            if ($newPassword !== $newPassword2) {
                $errors[] = lang('App.msg_new_pw_mismatch');
            }
        }

        if (! $errors) {
            $dup = $model->where('nickname', $nickname)->where('idx !=', $userIdx)->first();
            if ($dup) {
                $errors[] = lang('App.msg_nickname_taken');
            }
            $dup = $model->where('email', $email)->where('idx !=', $userIdx)->first();
            if ($dup) {
                $errors[] = lang('App.msg_email_taken');
            }
        }

        if ($errors) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        $updateData = [
            'nickname'         => $nickname,
            'name'             => $nickname,
            'email'            => $email,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ];

        if ($newPassword !== '') {
            $updateData['super_secured_password']    = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateData['timestamp_update_password'] = time();
            $updateData['client_ip_update_password'] = $this->request->getIPAddress();
        }

        $model->update($userIdx, $updateData);

        session()->set([
            'nickname' => $nickname,
            'email'    => $email,
        ]);

        return redirect()->to('/auth/profile')->with('success', lang('App.msg_profile_saved'));
    }

    public function withdrawProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userIdx   = (int) session()->get('user_idx');
        $model     = new UserModel();
        $user      = $model->find($userIdx);
        $currentPw = $this->request->getPost('withdraw_password');

        if (! password_verify($currentPw, $user['super_secured_password'])) {
            return redirect()->back()->with('error', lang('App.msg_withdraw_wrong_pw'));
        }

        $model->update($userIdx, [
            'status'           => 0,
            'timestamp_delete' => time(),
            'client_ip_delete' => $this->request->getIPAddress(),
        ]);

        session()->destroy();

        return redirect()->to('/')->with('success', lang('App.msg_withdraw_done'));
    }
}
