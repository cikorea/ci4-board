<?php

namespace App\Services;

use League\OAuth2\Client\Provider\Google;

class GoogleOAuthService
{
    private Google $provider;

    public function __construct()
    {
        $clientId     = env('GOOGLE_CLIENT_ID', '');
        $clientSecret = env('GOOGLE_CLIENT_SECRET', '');
        $redirectUri  = env('GOOGLE_REDIRECT_URI', base_url('api/v1/auth/social/google/callback'));

        if (empty($clientId) || empty($clientSecret)) {
            throw new \RuntimeException('GOOGLE_CLIENT_ID 또는 GOOGLE_CLIENT_SECRET이 .env에 설정되지 않았습니다.');
        }

        $this->provider = new Google([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri'  => $redirectUri,
        ]);
    }

    public function getAuthorizationUrl(): array
    {
        $url   = $this->provider->getAuthorizationUrl([
            'scope' => ['openid', 'profile', 'email'],
        ]);
        $state = $this->provider->getState();

        return ['url' => $url, 'state' => $state];
    }

    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getUserInfo(string $code): array
    {
        $token      = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $googleUser = $this->provider->getResourceOwner($token);

        return [
            'provider_id'  => (string) $googleUser->getId(),
            'email'        => $googleUser->getEmail() ?? '',
            'nickname'     => $googleUser->getName() ?? '',
            'access_token' => $token->getToken(),
        ];
    }
}
