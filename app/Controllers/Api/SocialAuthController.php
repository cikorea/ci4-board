<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SocialUserModel;
use App\Models\UserModel;
use App\Services\GoogleOAuthService;
use App\Services\KakaoOAuthService;
use App\Services\NaverOAuthService;
use App\Services\JwtService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class SocialAuthController extends BaseController
{
    use ApiResponse;

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/google',
        summary: 'Google OAuth2 인증 시작',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 302, description: 'Google 인증 페이지로 리다이렉트'),
        ]
    )]
    public function googleRedirect(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $oauth = new GoogleOAuthService();
            ['url' => $url, 'state' => $state] = $oauth->getAuthorizationUrl();
        } catch (\RuntimeException $e) {
            log_message('error', '[GoogleOAuth] init error: ' . $e->getMessage());
            return $this->failServerError(lang('Api.social_init_failed'));
        }

        session()->set('oauth2_google_state', $state);

        return $this->success(['redirect_url' => $url], lang('Api.social_redirect_issued', ['Google']));
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google/callback
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/google/callback',
        summary: 'Google OAuth2 콜백',
        tags: ['Auth'],
        parameters: [
            new OA\QueryParameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'state', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '소셜 로그인 성공 → JWT 발급', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
        ]
    )]
    public function googleCallback(): \CodeIgniter\HTTP\ResponseInterface
    {
        $code  = $this->request->getGet('code');
        $state = $this->request->getGet('state');

        if (empty($code)) {
            return $this->fail(lang('Api.social_code_missing'), 400);
        }

        if (empty($state) || $state !== session()->get('oauth2_google_state')) {
            return $this->fail(lang('Api.social_state_invalid'), 400);
        }
        session()->remove('oauth2_google_state');

        try {
            $oauth      = new GoogleOAuthService();
            $googleInfo = $oauth->getUserInfo($code);
        } catch (\Exception $e) {
            log_message('error', '[GoogleOAuth] callback error: ' . $e->getMessage());
            return $this->fail(lang('Api.social_auth_error', ['Google']), 502);
        }

        $userModel   = new UserModel();
        $socialModel = new SocialUserModel();

        // 기존 소셜 연결 계정 확인
        $social = $socialModel->findByProvider('google', $googleInfo['provider_id']);

        if ($social) {
            // 기존 연결 계정 → 프로필 업데이트 후 JWT 발급 ($social을 전달해 findByProvider 재조회 생략)
            $socialModel->upsert(
                $social['user_idx'],
                'google',
                $googleInfo['provider_id'],
                [
                    'email'    => $googleInfo['email'],
                    'nickname' => $googleInfo['nickname'],
                ],
                $social
            );

            $user = $this->getUserWithGroup($userModel, $social['user_idx']);
        } else {
            // 이메일로 기존 회원 조회
            $user = $userModel->findByEmail($googleInfo['email']);

            if (! $user) {
                // 신규 회원 자동 생성
                $userId   = 'google_' . substr($googleInfo['provider_id'], 0, 20);
                $nickname = $this->resolveNickname($userModel, $googleInfo['nickname']);

                $newIdx = $userModel->insert([
                    'user_id'                => $userId,
                    'super_secured_password' => null,
                    'email'                  => $googleInfo['email'],
                    'nickname'               => $nickname,
                    'name'                   => $googleInfo['nickname'],
                    'level'                  => 1,
                    'group_idx'              => 2,
                    'status'                 => 1,
                    'timestamp_insert'       => time(),
                ], true);

                $user = $this->getUserWithGroup($userModel, $newIdx);
            }

            // tb_users_social 연결
            $socialModel->upsert(
                (int) $user['idx'],
                'google',
                $googleInfo['provider_id'],
                [
                    'email'    => $googleInfo['email'],
                    'nickname' => $googleInfo['nickname'],
                ]
            );
        }

        if (! $user) {
            return $this->fail(lang('Api.social_user_info_error'), 500);
        }

        $accessToken  = JwtService::issueAccess($user);
        $refreshToken = JwtService::issueRefresh((int) $user['idx']);

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
        ], lang('Api.social_login_success'));
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/kakao
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/kakao',
        summary: '카카오 OAuth2 인증 시작',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 302, description: '카카오 인증 페이지로 리다이렉트'),
        ]
    )]
    public function kakaoRedirect(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $oauth = new KakaoOAuthService();
            ['url' => $url, 'state' => $state] = $oauth->getAuthorizationUrl();
        } catch (\RuntimeException $e) {
            log_message('error', '[KakaoOAuth] init error: ' . $e->getMessage());
            return $this->failServerError(lang('Api.social_init_failed'));
        }

        session()->set('oauth2_kakao_state', $state);

        return $this->success(['redirect_url' => $url], lang('Api.social_redirect_issued', ['카카오']));
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/kakao/callback
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/kakao/callback',
        summary: '카카오 OAuth2 콜백',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '소셜 로그인 성공 → JWT 발급'),
        ]
    )]
    public function kakaoCallback(): \CodeIgniter\HTTP\ResponseInterface
    {
        $code  = $this->request->getGet('code');
        $state = $this->request->getGet('state');

        if (empty($code)) {
            return $this->fail(lang('Api.social_code_missing'), 400);
        }

        if (empty($state) || $state !== session()->get('oauth2_kakao_state')) {
            return $this->fail(lang('Api.social_state_invalid'), 400);
        }
        session()->remove('oauth2_kakao_state');

        try {
            $oauth      = new KakaoOAuthService();
            $kakaoInfo  = $oauth->getUserInfo($code);
        } catch (\Exception $e) {
            log_message('error', '[KakaoOAuth] callback error: ' . $e->getMessage());
            return $this->fail(lang('Api.social_auth_error', ['카카오']), 502);
        }

        $userModel   = new UserModel();
        $socialModel = new SocialUserModel();

        $social = $socialModel->findByProvider('kakao', $kakaoInfo['provider_id']);

        if ($social) {
            $socialModel->upsert(
                $social['user_idx'],
                'kakao',
                $kakaoInfo['provider_id'],
                [
                    'email'    => $kakaoInfo['email'],
                    'nickname' => $kakaoInfo['nickname'],
                ],
                $social
            );

            $user = $this->getUserWithGroup($userModel, $social['user_idx']);
        } else {
            // 이메일이 있을 때만 기존 회원 매핑 (카카오는 이메일 동의가 선택 사항)
            $user = $kakaoInfo['email'] !== '' ? $userModel->findByEmail($kakaoInfo['email']) : null;

            if (! $user) {
                $userId   = 'kakao_' . substr($kakaoInfo['provider_id'], 0, 20);
                $nickname = $this->resolveNickname($userModel, $kakaoInfo['nickname']);

                $newIdx = $userModel->insert([
                    'user_id'                => $userId,
                    'super_secured_password' => null,
                    'email'                  => $kakaoInfo['email'],
                    'nickname'               => $nickname,
                    'name'                   => $kakaoInfo['nickname'],
                    'level'                  => 1,
                    'group_idx'              => 2,
                    'status'                 => 1,
                    'timestamp_insert'       => time(),
                ], true);

                $user = $this->getUserWithGroup($userModel, $newIdx);
            }

            $socialModel->upsert(
                (int) $user['idx'],
                'kakao',
                $kakaoInfo['provider_id'],
                [
                    'email'    => $kakaoInfo['email'],
                    'nickname' => $kakaoInfo['nickname'],
                ]
            );
        }

        if (! $user) {
            return $this->fail(lang('Api.social_user_info_error'), 500);
        }

        $accessToken  = JwtService::issueAccess($user);
        $refreshToken = JwtService::issueRefresh((int) $user['idx']);

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
        ], lang('Api.social_login_success'));
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/naver
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/naver',
        summary: '네이버 OAuth2 인증 시작',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 302, description: '네이버 인증 페이지로 리다이렉트'),
        ]
    )]
    public function naverRedirect(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $oauth = new NaverOAuthService();
            ['url' => $url, 'state' => $state] = $oauth->getAuthorizationUrl();
        } catch (\RuntimeException $e) {
            log_message('error', '[NaverOAuth] init error: ' . $e->getMessage());
            return $this->failServerError(lang('Api.social_init_failed'));
        }

        session()->set('oauth2_naver_state', $state);

        return $this->success(['redirect_url' => $url], lang('Api.social_redirect_issued', ['네이버']));
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/naver/callback
    // ------------------------------------------------------------------ //

    #[OA\Get(
        path: '/api/v1/auth/social/naver/callback',
        summary: '네이버 OAuth2 콜백',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: '소셜 로그인 성공 → JWT 발급'),
        ]
    )]
    public function naverCallback(): \CodeIgniter\HTTP\ResponseInterface
    {
        $code  = $this->request->getGet('code');
        $state = $this->request->getGet('state');

        if (empty($code)) {
            return $this->fail(lang('Api.social_code_missing'), 400);
        }

        if (empty($state) || $state !== session()->get('oauth2_naver_state')) {
            return $this->fail(lang('Api.social_state_invalid'), 400);
        }
        session()->remove('oauth2_naver_state');

        try {
            $oauth      = new NaverOAuthService();
            $naverInfo  = $oauth->getUserInfo($code);
        } catch (\Exception $e) {
            log_message('error', '[NaverOAuth] callback error: ' . $e->getMessage());
            return $this->fail(lang('Api.social_auth_error', ['네이버']), 502);
        }

        $userModel   = new UserModel();
        $socialModel = new SocialUserModel();

        $social = $socialModel->findByProvider('naver', $naverInfo['provider_id']);

        if ($social) {
            $socialModel->upsert(
                $social['user_idx'],
                'naver',
                $naverInfo['provider_id'],
                [
                    'email'    => $naverInfo['email'],
                    'nickname' => $naverInfo['nickname'],
                ],
                $social
            );

            $user = $this->getUserWithGroup($userModel, $social['user_idx']);
        } else {
            $user = $naverInfo['email'] !== '' ? $userModel->findByEmail($naverInfo['email']) : null;

            if (! $user) {
                $userId   = 'naver_' . substr($naverInfo['provider_id'], 0, 20);
                $nickname = $this->resolveNickname($userModel, $naverInfo['nickname']);

                $newIdx = $userModel->insert([
                    'user_id'                => $userId,
                    'super_secured_password' => null,
                    'email'                  => $naverInfo['email'],
                    'nickname'               => $nickname,
                    'name'                   => $naverInfo['nickname'],
                    'level'                  => 1,
                    'group_idx'              => 2,
                    'status'                 => 1,
                    'timestamp_insert'       => time(),
                ], true);

                $user = $this->getUserWithGroup($userModel, $newIdx);
            }

            $socialModel->upsert(
                (int) $user['idx'],
                'naver',
                $naverInfo['provider_id'],
                [
                    'email'    => $naverInfo['email'],
                    'nickname' => $naverInfo['nickname'],
                ]
            );
        }

        if (! $user) {
            return $this->fail(lang('Api.social_user_info_error'), 500);
        }

        $accessToken  = JwtService::issueAccess($user);
        $refreshToken = JwtService::issueRefresh((int) $user['idx']);

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
        ], lang('Api.social_login_success'));
    }

    // ------------------------------------------------------------------ //
    // 내부 헬퍼
    // ------------------------------------------------------------------ //

    private function getUserWithGroup(UserModel $model, int $userIdx): ?array
    {
        $row = $model->db->table('tb_users u')
            ->select('u.*, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->where('u.idx', $userIdx)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function resolveNickname(UserModel $model, string $base): string
    {
        $candidate = mb_substr($base, 0, 60);
        $suffix    = 0;

        while ($model->where('nickname', $candidate)->countAllResults() > 0) {
            $suffix++;
            $candidate = mb_substr($base, 0, 57) . '_' . $suffix;
        }

        return $candidate;
    }
}
