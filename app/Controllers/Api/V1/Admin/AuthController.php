<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Services\JwtService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 인증 API
 *
 * POST /api/admin/v1/auth/login   관리자 로그인 (공개)
 * POST /api/admin/v1/auth/logout  로그아웃 [admin_jwt]
 */
class AuthController extends BaseAdminApiController
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
        $body     = (array) $this->request->getJSON(true);
        $loginId  = trim((string) ($body['login_id']  ?? ''));
        $password = (string) ($body['password'] ?? '');

        if (! $loginId || ! $password) {
            return $this->failValidation([], '아이디와 비밀번호를 입력해주세요.');
        }

        $user = $this->users->findByLoginId($loginId);

        if (! $user || ! password_verify($password, $user['super_secured_password'] ?? '')) {
            return $this->fail('아이디 또는 비밀번호가 올바르지 않습니다.', 401);
        }

        if ((int) $user['group_idx'] !== 1) {
            return $this->fail('관리자 계정이 아닙니다.', 403);
        }

        $accessToken  = JwtService::issueAdminAccess($user);
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

    // ------------------------------------------------------------------ //

    public function logout(): ResponseInterface
    {
        $refreshToken = (string) ($this->request->getJSON(true)['refresh_token'] ?? '');

        if ($refreshToken) {
            $this->tokens->revoke($refreshToken);
        }

        return $this->success(null, '로그아웃 되었습니다.');
    }
}
