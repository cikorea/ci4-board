<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminUserModel;
use App\Models\Admin\AdminSessionModel;
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
    private AdminUserModel    $users;
    private AdminSessionModel $sessions;

    public function __construct()
    {
        $this->users    = new AdminUserModel();
        $this->sessions = new AdminSessionModel();
    }

    // ------------------------------------------------------------------ //

    public function login(): ResponseInterface
    {
        $body     = (array) $this->request->getJSON(true);
        $loginId  = trim((string) ($body['login_id']  ?? ''));
        $password = (string) ($body['password'] ?? '');

        if (! $loginId || ! $password) {
            return $this->failValidation([], lang('Api.auth_credentials_required'));
        }

        $user = $this->users->findByLoginId($loginId);

        if (! $user || ! password_verify($password, $user['super_secured_password'] ?? '')) {
            return $this->fail(lang('Api.auth_invalid_credentials'), 401);
        }

        $ip           = $this->request->getIPAddress();
        $accessToken  = JwtService::issueAdminAccess($user);
        $refreshToken = JwtService::issueRefresh((int) $user['idx']);

        $this->sessions->store(
            (int) $user['idx'],
            $accessToken,
            $refreshToken,
            time() + JwtService::refreshTtl(),
            $ip
        );

        $this->users->updateLoginTimestamp((int) $user['idx'], $ip);

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => JwtService::accessTtl(),
            'user'          => [
                'idx'      => (int) $user['idx'],
                'user_id'  => $user['user_id'],
                'nickname' => $user['nickname'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ]);
    }

    // ------------------------------------------------------------------ //

    public function logout(): ResponseInterface
    {
        $refreshToken = (string) ($this->request->getJSON(true)['refresh_token'] ?? '');

        if ($refreshToken) {
            $this->sessions->revoke($refreshToken);
        }

        return $this->success(null, lang('Api.auth_logout_success'));
    }
}
