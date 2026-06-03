<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * 카카오 소셜 로그인 엔드포인트 검증 테스트
 *
 * DB 불필요 — 환경변수·state·code 입력 검증만 수행.
 * 실제 Kakao OAuth2 토큰 교환은 502 에러로 검증한다.
 *
 * @internal
 */
final class KakaoCallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('jwt.secret=test-secret-kakao-minimum32chars!!');
        $_ENV['jwt.secret'] = 'test-secret-kakao-minimum32chars!!';
    }

    protected function tearDown(): void
    {
        foreach (['KAKAO_CLIENT_ID', 'KAKAO_CLIENT_SECRET', 'KAKAO_REDIRECT_URI'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
        putenv('jwt.secret=');
        unset($_ENV['jwt.secret']);
        parent::tearDown();
    }

    private function setKakaoEnv(): void
    {
        $_ENV['KAKAO_CLIENT_ID']     = 'test_kakao_rest_api_key';
        $_ENV['KAKAO_CLIENT_SECRET'] = 'test_kakao_secret';
        $_ENV['KAKAO_REDIRECT_URI']  = 'http://example.com/api/v1/auth/social/kakao/callback';
        putenv('KAKAO_CLIENT_ID=test_kakao_rest_api_key');
        putenv('KAKAO_CLIENT_SECRET=test_kakao_secret');
        putenv('KAKAO_REDIRECT_URI=http://example.com/api/v1/auth/social/kakao/callback');
    }

    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        $this->assertIsArray($data, "Response is not valid JSON: {$json}");
        return $data;
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/kakao
    // ------------------------------------------------------------------ //

    public function testRedirectReturns500WhenEnvMissing(): void
    {
        $result = $this->get('api/v1/auth/social/kakao');

        $result->assertStatus(500);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('KAKAO_CLIENT_ID', $body['message']);
    }

    public function testRedirectReturnsRedirectUrlWhenEnvSet(): void
    {
        $this->setKakaoEnv();
        $result = $this->get('api/v1/auth/social/kakao');

        $result->assertStatus(200);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('redirect_url', $body['data']);
        $this->assertStringContainsString('kauth.kakao.com', $body['data']['redirect_url']);
    }

    public function testRedirectUrlContainsStateParam(): void
    {
        $this->setKakaoEnv();
        $result = $this->get('api/v1/auth/social/kakao');

        $body = $this->decodeJSON($result->getJSON());
        $this->assertStringContainsString('state=', $body['data']['redirect_url']);
    }

    public function testRedirectJsonStructureIsCorrect(): void
    {
        $this->setKakaoEnv();
        $result = $this->get('api/v1/auth/social/kakao');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $result->assertJSONFragment(['message' => '카카오 인증 URL을 발급했습니다.']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/kakao/callback — code 검증
    // ------------------------------------------------------------------ //

    public function testCallbackReturns400WhenCodeMissing(): void
    {
        $result = $this->get('api/v1/auth/social/kakao/callback');

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('인증 코드', $body['message']);
    }

    public function testCallbackReturns400WhenStateMissing(): void
    {
        $result = $this->get('api/v1/auth/social/kakao/callback', ['code' => 'anycode']);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns400WhenStateIsEmptyString(): void
    {
        $result = $this->get('api/v1/auth/social/kakao/callback', [
            'code'  => 'anycode',
            'state' => '',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
    }

    public function testCallbackReturns400WhenStateMismatch(): void
    {
        $this->setKakaoEnv();
        $this->get('api/v1/auth/social/kakao');

        $result = $this->get('api/v1/auth/social/kakao/callback', [
            'code'  => 'anycode',
            'state' => 'wrong_state_value',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns502WhenKakaoApiFailsWithValidState(): void
    {
        $this->setKakaoEnv();

        $fakeState = 'test_kakao_state_' . bin2hex(random_bytes(8));

        $result = $this->withSession(['oauth2_kakao_state' => $fakeState])
                       ->get('api/v1/auth/social/kakao/callback', [
                           'code'  => 'invalid_kakao_code_xyz',
                           'state' => $fakeState,
                       ]);

        $result->assertStatus(502);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('카카오 인증 처리 중', $body['message']);
    }

    public function testCallbackCodeMissingJsonStructureIsCorrect(): void
    {
        $result = $this->get('api/v1/auth/social/kakao/callback');

        $result->assertStatus(400);
        $result->assertJSONFragment(['success' => false]);
    }
}
