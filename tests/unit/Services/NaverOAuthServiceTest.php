<?php

use App\Services\NaverOAuthService;
use CodeIgniter\Test\CIUnitTestCase;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * NaverOAuthService 단위 테스트
 *
 * @internal
 */
final class NaverOAuthServiceTest extends CIUnitTestCase
{
    // ------------------------------------------------------------------ //
    // 헬퍼: 환경변수 설정 / 해제
    // ------------------------------------------------------------------ //

    private function setEnv(string $clientId = 'test_id', string $clientSecret = 'test_secret'): void
    {
        $_ENV['NAVER_CLIENT_ID']     = $clientId;
        $_ENV['NAVER_CLIENT_SECRET'] = $clientSecret;
        $_ENV['NAVER_REDIRECT_URI']  = 'http://localhost/callback';
        putenv("NAVER_CLIENT_ID={$clientId}");
        putenv("NAVER_CLIENT_SECRET={$clientSecret}");
        putenv('NAVER_REDIRECT_URI=http://localhost/callback');
    }

    private function clearEnv(): void
    {
        foreach (['NAVER_CLIENT_ID', 'NAVER_CLIENT_SECRET', 'NAVER_REDIRECT_URI'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        $this->clearEnv();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ //
    // 헬퍼: GenericProvider 목(mock) 주입
    // ------------------------------------------------------------------ //

    private function makeServiceWithMockProvider(array $resourceOwnerData): NaverOAuthService
    {
        $this->setEnv();
        $service = new NaverOAuthService();

        $accessToken = $this->createMock(AccessToken::class);

        $resourceOwner = $this->getMockBuilder(\League\OAuth2\Client\Provider\GenericResourceOwner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceOwner->method('toArray')->willReturn($resourceOwnerData);

        $provider = $this->createMock(GenericProvider::class);
        $provider->method('getAccessToken')->willReturn($accessToken);
        $provider->method('getResourceOwner')->willReturn($resourceOwner);

        $ref = new \ReflectionProperty(NaverOAuthService::class, 'provider');
        $ref->setAccessible(true);
        $ref->setValue($service, $provider);

        return $service;
    }

    // ------------------------------------------------------------------ //
    // 생성자 검증
    // ------------------------------------------------------------------ //

    public function testConstructorThrowsWhenClientIdMissing(): void
    {
        $this->setEnv('', 'secret');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/NAVER_CLIENT_ID/');
        new NaverOAuthService();
    }

    public function testConstructorThrowsWhenClientSecretMissing(): void
    {
        $this->setEnv('id', '');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/NAVER_CLIENT_SECRET/');
        new NaverOAuthService();
    }

    public function testConstructorThrowsWhenBothMissing(): void
    {
        $this->setEnv('', '');
        $this->expectException(\RuntimeException::class);
        new NaverOAuthService();
    }

    public function testConstructorSucceedsWithBothCredentials(): void
    {
        $this->setEnv();
        $service = new NaverOAuthService();
        $this->assertInstanceOf(NaverOAuthService::class, $service);
    }

    // ------------------------------------------------------------------ //
    // getAuthorizationUrl
    // ------------------------------------------------------------------ //

    public function testGetAuthorizationUrlReturnsUrlAndState(): void
    {
        $this->setEnv();
        $service = new NaverOAuthService();
        $result  = $service->getAuthorizationUrl();

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertStringContainsString('nid.naver.com', $result['url']);
        $this->assertNotEmpty($result['state']);
    }

    public function testGetAuthorizationUrlHasStateParam(): void
    {
        $this->setEnv();
        $service = new NaverOAuthService();
        $result  = $service->getAuthorizationUrl();

        $this->assertStringContainsString('state=', $result['url']);
    }

    // ------------------------------------------------------------------ //
    // getUserInfo — provider_id 검증
    // ------------------------------------------------------------------ //

    public function testGetUserInfoThrowsWhenProviderIdEmpty(): void
    {
        $service = $this->makeServiceWithMockProvider([
            'response' => ['id' => '', 'email' => 'a@b.com', 'nickname' => '닉네임'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/사용자 ID/');
        $service->getUserInfo('code');
    }

    public function testGetUserInfoThrowsWhenResponseMissing(): void
    {
        $service = $this->makeServiceWithMockProvider([]);

        $this->expectException(\RuntimeException::class);
        $service->getUserInfo('code');
    }

    // ------------------------------------------------------------------ //
    // getUserInfo — 정상 파싱
    // ------------------------------------------------------------------ //

    public function testGetUserInfoParsesAllFields(): void
    {
        $service = $this->makeServiceWithMockProvider([
            'response' => [
                'id'       => '12345678',
                'email'    => 'user@naver.com',
                'nickname' => '네이버닉네임',
            ],
        ]);

        $result = $service->getUserInfo('code');

        $this->assertSame('12345678', $result['provider_id']);
        $this->assertSame('user@naver.com', $result['email']);
        $this->assertSame('네이버닉네임', $result['nickname']);
    }

    public function testGetUserInfoFallsBackToNameWhenNicknameMissing(): void
    {
        $service = $this->makeServiceWithMockProvider([
            'response' => [
                'id'    => '99999',
                'email' => 'user@naver.com',
                'name'  => '홍길동',
            ],
        ]);

        $result = $service->getUserInfo('code');

        $this->assertSame('홍길동', $result['nickname']);
    }

    public function testGetUserInfoReturnsEmptyNicknameWhenBothMissing(): void
    {
        $service = $this->makeServiceWithMockProvider([
            'response' => ['id' => '111', 'email' => 'user@naver.com'],
        ]);

        $result = $service->getUserInfo('code');

        $this->assertSame('', $result['nickname']);
    }

    public function testGetUserInfoReturnsEmptyEmailWhenMissing(): void
    {
        $service = $this->makeServiceWithMockProvider([
            'response' => ['id' => '222', 'nickname' => '닉'],
        ]);

        $result = $service->getUserInfo('code');

        $this->assertSame('', $result['email']);
    }
}
