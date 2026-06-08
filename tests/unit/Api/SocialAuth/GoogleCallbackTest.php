<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Google 소셜 로그인 엔드포인트 검증 테스트
 *
 * DB 불필요 — 환경변수·state·code 입력 검증만 수행.
 * 실제 Google OAuth2 토큰 교환은 502 에러로 검증한다.
 *
 * @internal
 */
final class GoogleCallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('jwt.secret=test-secret-google-minimum32chars!!');
        $_ENV['jwt.secret'] = 'test-secret-google-minimum32chars!!';
    }

    protected function tearDown(): void
    {
        foreach (['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
        putenv('jwt.secret=');
        unset($_ENV['jwt.secret']);
        parent::tearDown();
    }

    private function setGoogleEnv(): void
    {
        $_ENV['GOOGLE_CLIENT_ID']     = 'test_google_client_id';
        $_ENV['GOOGLE_CLIENT_SECRET'] = 'test_google_client_secret';
        $_ENV['GOOGLE_REDIRECT_URI']  = 'http://example.com/api/v1/auth/social/google/callback';
        putenv('GOOGLE_CLIENT_ID=test_google_client_id');
        putenv('GOOGLE_CLIENT_SECRET=test_google_client_secret');
        putenv('GOOGLE_REDIRECT_URI=http://example.com/api/v1/auth/social/google/callback');
    }

    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        $this->assertIsArray($data, "Response is not valid JSON: {$json}");
        return $data;
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google
    // ------------------------------------------------------------------ //

    public function testRedirectReturns500WhenEnvMissing(): void
    {
        $result = $this->get('api/v1/auth/social/google');

        $result->assertStatus(500);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('소셜 로그인 초기화에 실패했습니다', $body['message']);
    }

    public function testRedirectReturns500WhenOnlyClientIdSet(): void
    {
        $_ENV['GOOGLE_CLIENT_ID'] = 'only_id';
        putenv('GOOGLE_CLIENT_ID=only_id');

        $result = $this->get('api/v1/auth/social/google');

        $result->assertStatus(500);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
    }

    public function testRedirectReturnsRedirectUrlWhenEnvSet(): void
    {
        $this->setGoogleEnv();
        $result = $this->get('api/v1/auth/social/google');

        $result->assertStatus(200);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('redirect_url', $body['data']);
        $this->assertStringContainsString('accounts.google.com', $body['data']['redirect_url']);
    }

    public function testRedirectUrlContainsStateParam(): void
    {
        $this->setGoogleEnv();
        $result = $this->get('api/v1/auth/social/google');

        $body = $this->decodeJSON($result->getJSON());
        $this->assertStringContainsString('state=', $body['data']['redirect_url']);
    }

    public function testRedirectJsonStructureIsCorrect(): void
    {
        $this->setGoogleEnv();
        $result = $this->get('api/v1/auth/social/google');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $result->assertJSONFragment(['message' => 'Google 인증 URL을 발급했습니다.']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/google/callback — code 검증
    // ------------------------------------------------------------------ //

    public function testCallbackReturns400WhenCodeMissing(): void
    {
        $result = $this->get('api/v1/auth/social/google/callback');

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('인증 코드', $body['message']);
    }

    public function testCallbackReturns400WhenStateMissing(): void
    {
        $result = $this->get('api/v1/auth/social/google/callback', ['code' => 'anycode']);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns400WhenStateIsEmptyString(): void
    {
        $result = $this->get('api/v1/auth/social/google/callback', [
            'code'  => 'anycode',
            'state' => '',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
    }

    public function testCallbackReturns400WhenStateMismatch(): void
    {
        $this->setGoogleEnv();
        $this->get('api/v1/auth/social/google');

        $result = $this->get('api/v1/auth/social/google/callback', [
            'code'  => 'anycode',
            'state' => 'wrong_state_value',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns502WhenGoogleApiFailsWithValidState(): void
    {
        $this->setGoogleEnv();

        $fakeState = 'test_google_state_' . bin2hex(random_bytes(8));

        $result = $this->withSession(['oauth2_google_state' => $fakeState])
                       ->get('api/v1/auth/social/google/callback', [
                           'code'  => 'invalid_google_code_xyz',
                           'state' => $fakeState,
                       ]);

        $result->assertStatus(502);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Google 인증 처리 중', $body['message']);
    }

    public function testCallbackCodeMissingJsonStructureIsCorrect(): void
    {
        $result = $this->get('api/v1/auth/social/google/callback');

        $result->assertStatus(400);
        $result->assertJSONFragment(['success' => false]);
    }
}
