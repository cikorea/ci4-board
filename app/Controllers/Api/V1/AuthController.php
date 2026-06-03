<?php

namespace App\Controllers\Api\V1;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Services\JwtService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 사용자 인증 API
 *
 * POST   /api/v1/auth/login      로그인
 * POST   /api/v1/auth/register   회원가입
 * POST   /api/v1/auth/logout     로그아웃 [jwt]
 * POST   /api/v1/auth/refresh    토큰 갱신
 * GET    /api/v1/auth/me         내 정보 [jwt]
 * PUT    /api/v1/auth/profile    프로필 수정 [jwt]
 * DELETE /api/v1/auth/withdraw   회원 탈퇴 [jwt]
 */
class AuthController extends BaseApiController
{
    private UserModel      $users;
    private UserTokenModel $tokens;

    public function __construct()
    {
        $this->users  = new UserModel();
        $this->tokens = new UserTokenModel();
    }

    // ------------------------------------------------------------------ //

    public function login(): ResponseInterface
    {
        $body     = $this->request->getJSON(true) ?? [];
        $loginId  = trim((string) ($body['login_id'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if (! $loginId || ! $password) {
            return $this->failValidation([], lang('Api.auth_credentials_required'));
        }

        $user = $this->users->findByLoginId($loginId);

        if (! $user || ! password_verify($password, $user['super_secured_password'] ?? '')) {
            return $this->fail(lang('Api.auth_invalid_credentials'), 401);
        }

        return $this->issueTokenResponse($user);
    }

    // ------------------------------------------------------------------ //

    public function register(): ResponseInterface
    {
        $body      = (array) $this->request->getJSON(true);
        $userId    = trim((string) ($body['user_id']    ?? ''));
        $nickname  = trim((string) ($body['nickname']   ?? ''));
        $email     = trim((string) ($body['email']      ?? ''));
        $password  = (string) ($body['password']  ?? '');
        $password2 = (string) ($body['password2'] ?? '');

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

        if (! $errors) {
            if ($this->users->findByUserId($userId)) {
                $errors[] = lang('App.msg_user_id_taken');
            }
            if ($this->users->findByEmail($email)) {
                $errors[] = lang('App.msg_email_taken');
            }
        }

        if ($errors) {
            return $this->failValidation($errors);
        }

        $this->users->insert([
            'user_id'                => $userId,
            'nickname'               => $nickname,
            'name'                   => $nickname,
            'email'                  => $email,
            'super_secured_password' => password_hash($password, PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'status'                 => 1,
            'timezone'               => '+09',
            'timestamp_insert'       => time(),
            'client_ip_insert'       => $this->request->getIPAddress(),
        ]);

        return $this->created(null, lang('App.msg_register_done'));
    }

    // ------------------------------------------------------------------ //

    public function logout(): ResponseInterface
    {
        $refreshToken = (string) ($this->request->getJSON(true)['refresh_token'] ?? '');

        if ($refreshToken) {
            $this->tokens->revoke($refreshToken);
        }

        return $this->success(null, lang('Api.auth_logout_success'));
    }

    // ------------------------------------------------------------------ //

    public function refresh(): ResponseInterface
    {
        $refreshToken = (string) ($this->request->getJSON(true)['refresh_token'] ?? '');

        if (! $refreshToken) {
            return $this->failValidation([], lang('Api.auth_refresh_token_required'));
        }

        $record = $this->tokens->findValid($refreshToken);
        if (! $record) {
            return $this->fail(lang('Api.auth_refresh_token_invalid'), 401);
        }

        try {
            $payload = JwtService::decode($refreshToken);
        } catch (\Exception) {
            return $this->fail(lang('Api.auth_refresh_token_expired'), 401);
        }

        $user = $this->users->find((int) $payload->sub);
        if (! $user || (int) $user['status'] !== 1) {
            return $this->fail(lang('Api.auth_account_inactive'), 401);
        }

        $userWithGroup = $this->users->findByLoginId($user['user_id']);
        $accessToken   = JwtService::issueAccess($userWithGroup);

        return $this->success([
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'expires_in'   => JwtService::accessTtl(),
        ]);
    }

    // ------------------------------------------------------------------ //

    public function me(): ResponseInterface
    {
        $user = $this->users->find($this->getUserIdx());
        if (! $user) {
            return $this->failNotFound(lang('Api.user_not_found'));
        }

        unset($user['super_secured_password'], $user['new_password']);

        return $this->success($user);
    }

    // ------------------------------------------------------------------ //

    public function updateProfile(): ResponseInterface
    {
        $body        = (array) $this->request->getJSON(true);
        $nickname    = trim((string) ($body['nickname']         ?? ''));
        $email       = trim((string) ($body['email']           ?? ''));
        $currentPw   = (string) ($body['current_password'] ?? '');
        $newPw       = (string) ($body['new_password']      ?? '');
        $newPw2      = (string) ($body['new_password2']     ?? '');

        $userIdx = $this->getUserIdx();
        $user    = $this->users->find($userIdx);

        if (! password_verify($currentPw, $user['super_secured_password'] ?? '')) {
            return $this->fail(lang('App.msg_wrong_current_pw'), 422);
        }

        $errors = [];
        if (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 64) {
            $errors[] = lang('App.msg_nickname_length');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = lang('App.msg_invalid_email');
        }
        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                $errors[] = lang('App.msg_new_pw_length');
            }
            if ($newPw !== $newPw2) {
                $errors[] = lang('App.msg_new_pw_mismatch');
            }
        }
        if (! $errors) {
            if ($this->users->where('nickname', $nickname)->where('idx !=', $userIdx)->first()) {
                $errors[] = lang('App.msg_nickname_taken');
            }
            if ($this->users->where('email', $email)->where('idx !=', $userIdx)->first()) {
                $errors[] = lang('App.msg_email_taken');
            }
        }

        if ($errors) {
            return $this->failValidation($errors);
        }

        $data = [
            'nickname'         => $nickname,
            'name'             => $nickname,
            'email'            => $email,
            'timestamp_update' => time(),
            'client_ip_update' => $this->request->getIPAddress(),
        ];
        if ($newPw !== '') {
            $data['super_secured_password']    = password_hash($newPw, PASSWORD_BCRYPT);
            $data['timestamp_update_password'] = time();
            $data['client_ip_update_password'] = $this->request->getIPAddress();
        }

        $this->users->update($userIdx, $data);

        return $this->success(null, lang('App.msg_profile_saved'));
    }

    // ------------------------------------------------------------------ //

    public function withdraw(): ResponseInterface
    {
        $password = (string) ($this->request->getJSON(true)['password'] ?? '');
        $userIdx  = $this->getUserIdx();
        $user     = $this->users->find($userIdx);

        if (! password_verify($password, $user['super_secured_password'] ?? '')) {
            return $this->fail(lang('App.msg_withdraw_wrong_pw'), 422);
        }

        $this->users->update($userIdx, [
            'status'           => 0,
            'timestamp_delete' => time(),
            'client_ip_delete' => $this->request->getIPAddress(),
        ]);

        $this->tokens->revokeAll($userIdx);

        return $this->success(null, lang('App.msg_withdraw_done'));
    }

    // ------------------------------------------------------------------ //
    // private
    // ------------------------------------------------------------------ //

    private function issueTokenResponse(array $user): ResponseInterface
    {
        $accessToken  = JwtService::issueAccess($user);
        $refreshToken = JwtService::issueRefresh((int) $user['idx']);
        $ip           = $this->request->getIPAddress();

        $this->tokens->store(
            (int) $user['idx'],
            $refreshToken,
            time() + JwtService::refreshTtl(),
            $ip
        );

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => JwtService::accessTtl(),
            'user'          => [
                'idx'        => (int) $user['idx'],
                'user_id'    => $user['user_id'],
                'nickname'   => $user['nickname'],
                'email'      => $user['email'],
                'group_idx'  => (int) $user['group_idx'],
                'group_name' => $user['group_name'] ?? '',
            ],
        ]);
    }
}
