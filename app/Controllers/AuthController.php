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
        return view('auth/login', ['title' => '로그인']);
    }

    public function loginProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $loginId  = trim($this->request->getPost('login_id'));
        $password = $this->request->getPost('password');

        $model = new UserModel();
        $user  = $model->findByLoginId($loginId);

        if (! $user) {
            return redirect()->back()->with('error', '아이디(이메일) 또는 비밀번호가 올바르지 않습니다.')->withInput();
        }

        // bcrypt($2a$/$2y$) 형식 검증
        $storedPw = $user['super_secured_password'] ?? '';
        if (! $storedPw || ! password_verify($password, $storedPw)) {
            return redirect()->back()->with('error', '아이디(이메일) 또는 비밀번호가 올바르지 않습니다.')->withInput();
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

        return redirect()->to('/')->with('success', $user['nickname'] . '님, 환영합니다!');
    }

    public function register(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_idx')) {
            return redirect()->to('/');
        }
        return view('auth/register', ['title' => '회원가입']);
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
            $errors[] = '아이디는 3~32자여야 합니다.';
        }
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 64) {
            $errors[] = '닉네임은 2~64자여야 합니다.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '올바른 이메일 형식이 아닙니다.';
        }
        if (strlen($password) < 6) {
            $errors[] = '비밀번호는 6자 이상이어야 합니다.';
        }
        if ($password !== $password2) {
            $errors[] = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
        }

        $model = new UserModel();
        if (! $errors) {
            if ($model->findByUserId($userId)) {
                $errors[] = '이미 사용 중인 아이디입니다.';
            }
            if ($model->findByEmail($email)) {
                $errors[] = '이미 사용 중인 이메일입니다.';
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

        return redirect()->to('/auth/login')->with('success', '회원가입이 완료되었습니다. 로그인해주세요.');
    }

    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/')->with('success', '로그아웃 되었습니다.');
    }

    public function profile(): string
    {
        $model = new UserModel();
        $user  = $model->find(session()->get('user_idx'));

        return view('auth/profile', [
            'title' => '회원정보 수정',
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

        // 현재 비밀번호 확인
        if (! password_verify($currentPw, $user['super_secured_password'])) {
            return redirect()->back()->with('error', '현재 비밀번호가 올바르지 않습니다.')->withInput();
        }

        $errors = [];
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 64) {
            $errors[] = '닉네임은 2~64자여야 합니다.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '올바른 이메일 형식이 아닙니다.';
        }
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $errors[] = '새 비밀번호는 6자 이상이어야 합니다.';
            }
            if ($newPassword !== $newPassword2) {
                $errors[] = '새 비밀번호와 확인이 일치하지 않습니다.';
            }
        }

        // 닉네임/이메일 중복 (본인 제외)
        if (! $errors) {
            $dup = $model->where('nickname', $nickname)->where('idx !=', $userIdx)->first();
            if ($dup) {
                $errors[] = '이미 사용 중인 닉네임입니다.';
            }
            $dup = $model->where('email', $email)->where('idx !=', $userIdx)->first();
            if ($dup) {
                $errors[] = '이미 사용 중인 이메일입니다.';
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
            $updateData['super_secured_password']   = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateData['timestamp_update_password'] = time();
            $updateData['client_ip_update_password'] = $this->request->getIPAddress();
        }

        $model->update($userIdx, $updateData);

        // 세션 동기화
        session()->set([
            'nickname' => $nickname,
            'email'    => $email,
        ]);

        return redirect()->to('/auth/profile')->with('success', '회원정보가 수정되었습니다.');
    }

    public function withdrawProcess(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userIdx   = (int) session()->get('user_idx');
        $model     = new UserModel();
        $user      = $model->find($userIdx);
        $currentPw = $this->request->getPost('withdraw_password');

        if (! password_verify($currentPw, $user['super_secured_password'])) {
            return redirect()->back()->with('error', '비밀번호가 올바르지 않습니다. 탈퇴가 취소되었습니다.');
        }

        $model->update($userIdx, [
            'status'           => 0,
            'timestamp_delete' => time(),
            'client_ip_delete' => $this->request->getIPAddress(),
        ]);

        session()->destroy();

        return redirect()->to('/')->with('success', '탈퇴가 완료되었습니다. 이용해주셔서 감사합니다.');
    }
}
