<?php

namespace App\Controllers\Api\V1;

use App\Models\UserModel;
use App\Models\UserTokenModel;
use App\Services\JwtService;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

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

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: '로그인',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login_id', 'password'],
                properties: [
                    new OA\Property(property: 'login_id', type: 'string', example: 'testuser'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '로그인 성공 → JWT 발급', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
            new OA\Response(response: 401, description: '잘못된 자격증명'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: '회원가입',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'nickname', 'email', 'password', 'password2'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'string'),
                    new OA\Property(property: 'nickname', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'password2', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: '회원가입 성공'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: '로그아웃',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '로그아웃 성공'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function logout(): ResponseInterface
    {
        $refreshToken = (string) ($this->request->getJSON(true)['refresh_token'] ?? '');

        if ($refreshToken) {
            $this->tokens->revoke($refreshToken);
        }

        return $this->success(null, lang('Api.auth_logout_success'));
    }

    // ------------------------------------------------------------------ //

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: '액세스 토큰 갱신',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '토큰 갱신 성공'),
            new OA\Response(response: 401, description: '유효하지 않은 Refresh Token'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: '내 정보 조회',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '사용자 정보', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/v1/auth/profile',
        summary: '프로필 수정',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nickname', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/v1/auth/withdraw',
        summary: '회원 탈퇴',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '탈퇴 완료'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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
