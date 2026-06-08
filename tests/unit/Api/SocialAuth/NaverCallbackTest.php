<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * 네이버 소셜 로그인 엔드포인트 검증 테스트
 *
 * DB 불필요 — state/code 입력 검증, 환경변수 검증만 테스트
 *
 * @internal
 */
final class NaverCallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('jwt.secret=test-secret-naver');
        $_ENV['jwt.secret'] = 'test-secret-naver';
    }

    protected function tearDown(): void
    {
        foreach (['NAVER_CLIENT_ID', 'NAVER_CLIENT_SECRET', 'NAVER_REDIRECT_URI'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
        putenv('jwt.secret');
        unset($_ENV['jwt.secret']);
        parent::tearDown();
    }

    private function setNaverEnv(): void
    {
        $_ENV['NAVER_CLIENT_ID']     = 'test_naver_id';
        $_ENV['NAVER_CLIENT_SECRET'] = 'test_naver_secret';
        $_ENV['NAVER_REDIRECT_URI']  = 'http://example.com/api/v1/auth/social/naver/callback';
        putenv('NAVER_CLIENT_ID=test_naver_id');
        putenv('NAVER_CLIENT_SECRET=test_naver_secret');
        putenv('NAVER_REDIRECT_URI=http://example.com/api/v1/auth/social/naver/callback');
    }

    private function decodeJSON(string $json): array
    {
        $data = json_decode($json, true);
        $this->assertIsArray($data, "Response is not valid JSON: {$json}");
        return $data;
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/naver
    // ------------------------------------------------------------------ //

    public function testRedirectReturns500WhenEnvMissing(): void
    {
        $result = $this->get('api/v1/auth/social/naver');

        $result->assertStatus(500);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('소셜 로그인 초기화에 실패했습니다', $body['message']);
    }

    public function testRedirectReturns500WhenOnlyClientIdSet(): void
    {
        $_ENV['NAVER_CLIENT_ID'] = 'only_id';
        putenv('NAVER_CLIENT_ID=only_id');

        $result = $this->get('api/v1/auth/social/naver');

        $result->assertStatus(500);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
    }

    public function testRedirectReturnsRedirectUrlWhenEnvSet(): void
    {
        $this->setNaverEnv();
        $result = $this->get('api/v1/auth/social/naver');

        $result->assertStatus(200);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('redirect_url', $body['data']);
        $this->assertStringContainsString('nid.naver.com', $body['data']['redirect_url']);
    }

    public function testRedirectUrlContainsStateParam(): void
    {
        $this->setNaverEnv();
        $result = $this->get('api/v1/auth/social/naver');

        $body = $this->decodeJSON($result->getJSON());
        $this->assertStringContainsString('state=', $body['data']['redirect_url']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/naver/callback — code 검증
    // ------------------------------------------------------------------ //

    public function testCallbackReturns400WhenCodeMissing(): void
    {
        $result = $this->get('api/v1/auth/social/naver/callback');

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('인증 코드', $body['message']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/social/naver/callback — state 검증
    // ------------------------------------------------------------------ //

    public function testCallbackReturns400WhenStateMissing(): void
    {
        $result = $this->get('api/v1/auth/social/naver/callback', ['code' => 'anycode']);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns400WhenStateIsEmptyString(): void
    {
        $result = $this->get('api/v1/auth/social/naver/callback', [
            'code'  => 'anycode',
            'state' => '',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
    }

    public function testCallbackReturns400WhenStateMismatch(): void
    {
        $this->setNaverEnv();
        $this->get('api/v1/auth/social/naver');

        $result = $this->get('api/v1/auth/social/naver/callback', [
            'code'  => 'anycode',
            'state' => 'wrong_state_value',
        ]);

        $result->assertStatus(400);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('state', $body['message']);
    }

    public function testCallbackReturns502WhenNaverApiFailsWithValidState(): void
    {
        $this->setNaverEnv();

        // FeatureTestTrait은 요청마다 $_SESSION을 초기화하므로
        // withSession()으로 state를 직접 주입한다
        $fakeState = 'test_valid_state_' . bin2hex(random_bytes(8));

        $result = $this->withSession(['oauth2_naver_state' => $fakeState])
                       ->get('api/v1/auth/social/naver/callback', [
                           'code'  => 'invalid_code_xyz',
                           'state' => $fakeState,
                       ]);

        $result->assertStatus(502);
        $body = $this->decodeJSON($result->getJSON());
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('네이버 인증 처리 중', $body['message']);
        // 내부 에러 상세 미노출 확인
        $this->assertStringNotContainsString('invalid_grant', $body['message']);
    }

    // ------------------------------------------------------------------ //
    // CI4 네이티브 assertJSONFragment 방식 추가 검증
    // ------------------------------------------------------------------ //

    public function testRedirectJsonStructureIsCorrect(): void
    {
        $this->setNaverEnv();
        $result = $this->get('api/v1/auth/social/naver');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $result->assertJSONFragment(['message' => '네이버 인증 URL을 발급했습니다.']);
    }

    public function testCallbackCodeMissingJsonStructureIsCorrect(): void
    {
        $result = $this->get('api/v1/auth/social/naver/callback');

        $result->assertStatus(400);
        $result->assertJSONFragment(['success' => false]);
    }
}
