<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 사용자 인증 API 통합 테스트
 *
 * 실행 조건: phpunit.dist.xml 에 MySQL 테스트 DB 설정 필요
 *
 * @internal
 */
final class UserAuthApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const TEST_USER_ID  = 'testmember';
    private const TEST_PASSWORD = 'TestPass1234!';

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();
        putenv('jwt.secret=test-secret-for-auth-api-minimum32chars!!');
        $_ENV['jwt.secret'] = 'test-secret-for-auth-api-minimum32chars!!';
        service('cache')->clean();

        $this->db->table('tb_users_token')->truncate();
        $this->db->table('tb_users')->where('user_id', self::TEST_USER_ID)->delete();
        $this->db->table('tb_users')->insert([
            'user_id'                => self::TEST_USER_ID,
            'super_secured_password' => password_hash(self::TEST_PASSWORD, PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'name'                   => '테스트회원',
            'nickname'               => '테스트회원',
            'email'                  => 'testmember@example.com',
            'timezone'               => '+09',
            'status'                 => 1,
            'timestamp_insert'       => time(),
            'client_ip_insert'       => '127.0.0.1',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('jwt.secret');
        unset($_ENV['jwt.secret']);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/auth/login
    // ------------------------------------------------------------------ //

    public function testLoginWithValidCredentials(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => self::TEST_USER_ID,
                           'password' => self::TEST_PASSWORD,
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('access_token',  $body['data']);
        $this->assertArrayHasKey('refresh_token', $body['data']);
        $this->assertSame('Bearer', $body['data']['token_type']);
        $this->assertSame(3600, $body['data']['expires_in']);
        $this->assertSame(self::TEST_USER_ID, $body['data']['user']['user_id']);
    }

    public function testLoginWithWrongPassword(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => self::TEST_USER_ID,
                           'password' => 'wrongpassword',
                       ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['success' => false]);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => 'nobody',
                           'password' => 'password123',
                       ]);

        $result->assertStatus(401);
    }

    public function testLoginWithMissingFields(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', []);

        $result->assertStatus(422);
        $result->assertJSONFragment(['success' => false]);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/auth/register
    // ------------------------------------------------------------------ //

    public function testRegisterWithValidData(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/register', [
                           'user_id'   => 'newuser',
                           'nickname'  => '새회원',
                           'email'     => 'new@example.com',
                           'password'  => 'password123',
                           'password2' => 'password123',
                       ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testRegisterWithDuplicateUserId(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/register', [
                           'user_id'   => self::TEST_USER_ID,
                           'nickname'  => '다른닉네임',
                           'email'     => 'other@example.com',
                           'password'  => 'password123',
                           'password2' => 'password123',
                       ]);

        $result->assertStatus(422);
        $result->assertJSONFragment(['success' => false]);
    }

    public function testRegisterWithPasswordMismatch(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/register', [
                           'user_id'   => 'user999',
                           'nickname'  => '테스터',
                           'email'     => 'tester@example.com',
                           'password'  => 'password123',
                           'password2' => 'different456',
                       ]);

        $result->assertStatus(422);
    }

    public function testRegisterWithShortPassword(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/register', [
                           'user_id'   => 'user998',
                           'nickname'  => '테스터',
                           'email'     => 'tester2@example.com',
                           'password'  => '123',
                           'password2' => '123',
                       ]);

        $result->assertStatus(422);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/auth/me
    // ------------------------------------------------------------------ //

    public function testMeWithoutToken(): void
    {
        $result = $this->get('/api/v1/auth/me');
        $result->assertStatus(401);
    }

    public function testMeWithValidToken(): void
    {
        $token  = $this->loginAndGetToken();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/auth/me');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame(self::TEST_USER_ID, $body['data']['user_id']);
        $this->assertArrayNotHasKey('super_secured_password', $body['data']);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/auth/logout
    // ------------------------------------------------------------------ //

    public function testLogoutWithValidToken(): void
    {
        $data   = $this->loginAndGetTokenData();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$data['access_token']}"])
                       ->post('/api/v1/auth/logout', [
                           'refresh_token' => $data['refresh_token'],
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testLogoutWithoutToken(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/logout', []);
        $result->assertStatus(401);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/auth/refresh
    // ------------------------------------------------------------------ //

    public function testRefreshWithValidToken(): void
    {
        $data   = $this->loginAndGetTokenData();
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/refresh', [
                           'refresh_token' => $data['refresh_token'],
                       ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('access_token', $body['data']);
    }

    public function testRefreshWithInvalidToken(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/refresh', [
                           'refresh_token' => 'invalid.token',
                       ]);

        $result->assertStatus(401);
    }

    // ------------------------------------------------------------------ //
    // helper
    // ------------------------------------------------------------------ //

    private function loginAndGetToken(): string
    {
        return $this->loginAndGetTokenData()['access_token'];
    }

    private function loginAndGetTokenData(): array
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => self::TEST_USER_ID,
                           'password' => self::TEST_PASSWORD,
                       ]);

        return json_decode($result->getJSON(), true)['data'];
    }
}
