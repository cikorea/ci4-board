<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * @api {group} Auth 인증
 * @apiGroup Auth
 * @apiDescription 로그인·로그아웃·회원가입·프로필·회원탈퇴를 처리한다.
 */
class AuthController extends Controller
{
    /**
     * @api {get} /auth/login 로그인 폼
     * @apiGroup Auth
     * @apiName LoginForm
     * @apiDescription 이미 로그인된 경우 홈으로 리다이렉트한다.
     */
    public function login(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_idx')) {
            return redirect()->to('/');
        }
        return view('auth/login', ['title' => lang('App.login')]);
    }

    /**
     * @api {post} /auth/login 로그인 처리
     * @apiGroup Auth
     * @apiName LoginProcess
     * @apiDescription user_id 또는 email + 비밀번호로 인증한다. 성공 시 세션을 초기화하고 홈으로 이동한다.
     *
     * @apiBody  {String} login_id   아이디 또는 이메일
     * @apiBody  {String} password   비밀번호
     * @apiSuccess {String} redirect  홈으로 이동
     * @apiError {String} error   로그인 폼으로 리다이렉트 (error 플래시 메시지 포함)
     */
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

    /**
     * @api {get} /auth/register 회원가입 폼
     * @apiGroup Auth
     * @apiName RegisterForm
     * @apiDescription 이미 로그인된 경우 홈으로 리다이렉트한다.
     */
    public function register(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_idx')) {
            return redirect()->to('/');
        }
        return view('auth/register', ['title' => lang('App.register')]);
    }

    /**
     * @api {post} /auth/register 회원가입 처리
     * @apiGroup Auth
     * @apiName RegisterProcess
     * @apiDescription 유효성 검사 후 회원을 생성한다. 기본 그룹(group_idx=2), 레벨 1로 등록된다.
     *
     * @apiBody  {String} user_id    아이디 (3–32자)
     * @apiBody  {String} nickname   닉네임 (2–64자)
     * @apiBody  {String} email      이메일
     * @apiBody  {String} password   비밀번호 (최소 6자)
     * @apiBody  {String} password2  비밀번호 확인
     * @apiSuccess {String} redirect  로그인 페이지로 이동
     * @apiError {String} error   회원가입 폼으로 리다이렉트 (errors 플래시 배열 포함)
     */
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

    /**
     * @api {get} /auth/logout 로그아웃
     * @apiGroup Auth
     * @apiName Logout
     * @apiDescription 세션을 파기하고 홈으로 이동한다.
     */
    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/');
    }

    /**
     * @api {get} /auth/profile 프로필 페이지
     * @apiGroup Auth
     * @apiName Profile
     * @apiPermission 로그인
     *
     * @apiSuccess {Object} user  현재 로그인 사용자 정보
     */
    public function profile(): string
    {
        $model = new UserModel();
        $user  = $model->find(session()->get('user_idx'));

        return view('auth/profile', [
            'title' => lang('App.profile_title'),
            'user'  => $user,
        ]);
    }

    /**
     * @api {post} /auth/profile 프로필 수정
     * @apiGroup Auth
     * @apiName ProfileProcess
     * @apiPermission 로그인
     * @apiDescription 현재 비밀번호 확인 후 닉네임·이메일·비밀번호를 수정한다.
     *
     * @apiBody  {String} current_password  현재 비밀번호 (필수)
     * @apiBody  {String} nickname          새 닉네임 (2–64자)
     * @apiBody  {String} email             새 이메일
     * @apiBody  {String} [new_password]    새 비밀번호 (최소 6자, 변경 시에만)
     * @apiBody  {String} [new_password2]   새 비밀번호 확인
     * @apiSuccess {String} redirect         프로필 페이지로 이동
     */
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

    /**
     * @api {post} /auth/withdraw 회원 탈퇴
     * @apiGroup Auth
     * @apiName WithdrawProcess
     * @apiPermission 로그인
     * @apiDescription 비밀번호 확인 후 status=0으로 소프트 삭제하고 세션을 파기한다.
     *
     * @apiBody  {String} withdraw_password  현재 비밀번호
     * @apiSuccess {String} redirect          홈으로 이동
     */
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
