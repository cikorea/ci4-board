<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;

class KakaoOAuthService
{
    private GenericProvider $provider;

    public function __construct()
    {
        $clientId     = env('KAKAO_CLIENT_ID', '');
        $clientSecret = env('KAKAO_CLIENT_SECRET', '');
        $redirectUri  = env('KAKAO_REDIRECT_URI', base_url('api/v1/auth/social/kakao/callback'));

        if (empty($clientId)) {
            throw new \RuntimeException('KAKAO_CLIENT_ID가 .env에 설정되지 않았습니다.');
        }

        $this->provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => $redirectUri,
            'urlAuthorize'            => 'https://kauth.kakao.com/oauth/authorize',
            'urlAccessToken'          => 'https://kauth.kakao.com/oauth/token',
            'urlResourceOwnerDetails' => 'https://kapi.kakao.com/v2/user/me',
        ]);
    }

    public function getAuthorizationUrl(): array
    {
        $url   = $this->provider->getAuthorizationUrl([
            'scope' => ['account_email', 'profile_nickname'],
        ]);
        $state = $this->provider->getState();

        return ['url' => $url, 'state' => $state];
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getUserInfo(string $code): array
    {
        $token     = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $kakaoUser = $this->provider->getResourceOwner($token)->toArray();

        $account  = $kakaoUser['kakao_account'] ?? [];
        $email    = $account['email'] ?? '';
        $nickname = $account['profile']['nickname']
                 ?? $kakaoUser['properties']['nickname']
                 ?? '';

        return [
            'provider_id' => (string) ($kakaoUser['id'] ?? ''),
            'email'       => $email,
            'nickname'    => $nickname,
        ];
    }
}
