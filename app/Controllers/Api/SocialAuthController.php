<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SocialUserModel;
use App\Models\UserModel;
use App\Services\GoogleOAuthService;
use App\Services\JwtService;
use App\Traits\ApiResponse;

class SocialAuthController extends BaseController
{
    use ApiResponse;

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google
    // ------------------------------------------------------------------ //

    public function googleRedirect(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $oauth = new GoogleOAuthService();
            ['url' => $url, 'state' => $state] = $oauth->getAuthorizationUrl();
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 500);
        }

        // state를 세션에 저장해 CSRF 방지
        session()->set('oauth2_state', $state);

        return $this->success(['redirect_url' => $url], 'Google 인증 URL을 발급했습니다.');
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google/callback
    // ------------------------------------------------------------------ //

    public function googleCallback(): \CodeIgniter\HTTP\ResponseInterface
    {
        $code  = $this->request->getGet('code');
        $state = $this->request->getGet('state');

        if (empty($code)) {
            return $this->fail('인증 코드가 없습니다.', 400);
        }

        // state 검증
        if ($state !== session()->get('oauth2_state')) {
            return $this->fail('유효하지 않은 state 값입니다.', 400);
        }
        session()->remove('oauth2_state');

        try {
            $oauth      = new GoogleOAuthService();
            $googleInfo = $oauth->getUserInfo($code);
        } catch (\Exception $e) {
            return $this->fail('Google 인증 처리 중 오류가 발생했습니다: ' . $e->getMessage(), 502);
        }

        $userModel   = new UserModel();
        $socialModel = new SocialUserModel();

        // 기존 소셜 연결 계정 확인
        $social = $socialModel->findByProvider('google', $googleInfo['provider_id']);

        if ($social) {
            // 기존 연결 계정 → 토큰 업데이트 후 JWT 발급
            $socialModel->upsert(
                $social['user_idx'],
                'google',
                $googleInfo['provider_id'],
                [
                    'email'        => $googleInfo['email'],
                    'nickname'     => $googleInfo['nickname'],
                    'access_token' => $googleInfo['access_token'],
                ]
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
                    'email'        => $googleInfo['email'],
                    'nickname'     => $googleInfo['nickname'],
                    'access_token' => $googleInfo['access_token'],
                ]
            );
        }

        if (! $user) {
            return $this->fail('사용자 정보를 불러올 수 없습니다.', 500);
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
        ], '소셜 로그인 성공');
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
