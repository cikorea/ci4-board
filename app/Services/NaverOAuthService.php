<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;

class NaverOAuthService
{
    private GenericProvider $provider;

    public function __construct()
    {
        $clientId     = env('NAVER_CLIENT_ID', '');
        $clientSecret = env('NAVER_CLIENT_SECRET', '');
        $redirectUri  = env('NAVER_REDIRECT_URI', base_url('api/v1/auth/social/naver/callback'));

        if (empty($clientId) || empty($clientSecret)) {
            throw new \RuntimeException('NAVER_CLIENT_ID 또는 NAVER_CLIENT_SECRET이 .env에 설정되지 않았습니다.');
        }

        $this->provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => $redirectUri,
            'urlAuthorize'            => 'https://nid.naver.com/oauth2.0/authorize',
            'urlAccessToken'          => 'https://nid.naver.com/oauth2.0/token',
            'urlResourceOwnerDetails' => 'https://openapi.naver.com/v1/nid/me',
        ]);
    }

    public function getAuthorizationUrl(): array
    {
        $url   = $this->provider->getAuthorizationUrl();
        $state = $this->provider->getState();

        return ['url' => $url, 'state' => $state];
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getUserInfo(string $code): array
    {
        $token     = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $response  = $this->provider->getResourceOwner($token)->toArray();

        $profile    = $response['response'] ?? [];
        $providerId = (string) ($profile['id'] ?? '');

        if ($providerId === '') {
            throw new \RuntimeException('네이버 응답에서 사용자 ID를 확인할 수 없습니다.');
        }

        return [
            'provider_id' => $providerId,
            'email'       => $profile['email'] ?? '',
            'nickname'    => $profile['nickname'] ?? $profile['name'] ?? '',
        ];
    }
}
