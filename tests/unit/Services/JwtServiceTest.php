<?php

use App\Services\JwtService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * JwtService 단위 테스트
 *
 * @internal
 */
final class JwtServiceTest extends CIUnitTestCase
{
    private string $secret = 'test-secret-key-for-unit-testing';

    private array $fakeUser = [
        'idx'        => 42,
        'user_id'    => 'testuser',
        'nickname'   => '테스트유저',
        'email'      => 'test@example.com',
        'group_idx'  => 2,
        'group_name' => '일반회원',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        putenv("jwt.secret={$this->secret}");
        $_ENV['jwt.secret'] = $this->secret;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('jwt.secret');
        unset($_ENV['jwt.secret']);
        JwtService::reset();
    }

    // ------------------------------------------------------------------ //
    // Access Token
    // ------------------------------------------------------------------ //

    public function testIssueAccessReturnsString(): void
    {
        $token = JwtService::issueAccess($this->fakeUser);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testAccessTokenPayloadContainsCorrectClaims(): void
    {
        $token   = JwtService::issueAccess($this->fakeUser);
        $payload = JwtService::decode($token);

        $this->assertSame(42, $payload->sub);
        $this->assertSame('user', $payload->type);
        $this->assertSame('testuser', $payload->user_id);
        $this->assertSame(2, $payload->group_idx);
        $this->assertSame('ci4-board', $payload->iss);
    }

    public function testAccessTokenExpiresInOneHour(): void
    {
        $before = time();
        $token  = JwtService::issueAccess($this->fakeUser);
        $after  = time();

        $payload = JwtService::decode($token);

        $this->assertGreaterThanOrEqual($before + 3600, $payload->exp);
        $this->assertLessThanOrEqual($after + 3600, $payload->exp);
    }

    // ------------------------------------------------------------------ //
    // Admin Access Token
    // ------------------------------------------------------------------ //

    public function testIssueAdminAccessHasAdminType(): void
    {
        $adminUser = array_merge($this->fakeUser, ['role' => 'superadmin']);
        $token     = JwtService::issueAdminAccess($adminUser);
        $payload   = JwtService::decode($token);

        $this->assertSame('admin', $payload->type);
        $this->assertSame('superadmin', $payload->role);
        $this->assertFalse(isset($payload->group_idx));
    }

    // ------------------------------------------------------------------ //
    // Refresh Token
    // ------------------------------------------------------------------ //

    public function testIssueRefreshHasRefreshType(): void
    {
        $token   = JwtService::issueRefresh(42);
        $payload = JwtService::decode($token);

        $this->assertSame('refresh', $payload->type);
        $this->assertSame(42, $payload->sub);
    }

    public function testRefreshTokenExpiresIn30Days(): void
    {
        $before = time();
        $token  = JwtService::issueRefresh(42);
        $after  = time();

        $payload = JwtService::decode($token);

        $this->assertGreaterThanOrEqual($before + 2592000, $payload->exp);
        $this->assertLessThanOrEqual($after + 2592000, $payload->exp);
    }

    // ------------------------------------------------------------------ //
    // Decode
    // ------------------------------------------------------------------ //

    public function testDecodeThrowsOnInvalidToken(): void
    {
        $this->expectException(\Exception::class);
        JwtService::decode('invalid.token.here');
    }

    public function testDecodeThrowsOnWrongSecret(): void
    {
        $token = JwtService::issueAccess($this->fakeUser);

        putenv('jwt.secret=wrong-secret');
        $_ENV['jwt.secret'] = 'wrong-secret';

        $this->expectException(\Exception::class);
        JwtService::decode($token);
    }

    // ------------------------------------------------------------------ //
    // 현재 사용자 주입
    // ------------------------------------------------------------------ //

    public function testSetAndGetCurrentUser(): void
    {
        $payload = (object) ['sub' => 99, 'group_idx' => 2];
        JwtService::setCurrentUser($payload);

        $this->assertTrue(JwtService::isLoggedIn());
        $this->assertSame(99, JwtService::getUserIdx());
        $this->assertSame(2, JwtService::getGroupIdx());
        $this->assertFalse(JwtService::isAdmin());
    }

    public function testIsAdminReturnsTrueForGroupIdx1(): void
    {
        JwtService::setCurrentUser((object) ['sub' => 1, 'group_idx' => 1]);
        $this->assertTrue(JwtService::isAdmin());
    }

    public function testIsLoggedInFalseByDefault(): void
    {
        // tearDown에서 초기화했으므로 기본 상태
        $this->assertFalse(JwtService::isLoggedIn());
    }

    // ------------------------------------------------------------------ //
    // TTL
    // ------------------------------------------------------------------ //

    public function testAccessTtlIs3600(): void
    {
        $this->assertSame(3600, JwtService::accessTtl());
    }

    public function testRefreshTtlIs30Days(): void
    {
        $this->assertSame(2592000, JwtService::refreshTtl());
    }

    // ------------------------------------------------------------------ //
    // Secret 미설정
    // ------------------------------------------------------------------ //

    public function testThrowsWhenSecretNotConfigured(): void
    {
        putenv('jwt.secret=');
        $_ENV['jwt.secret'] = '';

        $this->expectException(\RuntimeException::class);
        JwtService::issueAccess($this->fakeUser);
    }
}
