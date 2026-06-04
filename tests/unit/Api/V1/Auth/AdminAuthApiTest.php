<?php

use App\Services\JwtService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 관리자 인증 API 통합 테스트
 *
 * 실행 조건: phpunit.dist.xml 에 MySQL 테스트 DB 설정 필요
 * admin DB(codeigniter_admin_test)와 main DB(ci4_board_test) 모두 필요
 *
 * @internal
 */
final class AdminAuthApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();
        putenv('jwt.secret=test-secret-for-admin-auth-minimum32chars!!');
        $_ENV['jwt.secret'] = 'test-secret-for-admin-auth-minimum32chars!!';
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('jwt.secret=');
        unset($_ENV['jwt.secret']);
    }

    // ------------------------------------------------------------------ //
    // POST /api/admin/v1/auth/login
    // ------------------------------------------------------------------ //

    public function testAdminLoginWithAdminAccount(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/admin/v1/auth/login', [
                           'login_id' => 'admin',
                           'password' => 'admin1234',
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('access_token', $body['data']);
        $this->assertSame('superadmin', $body['data']['user']['role']);
    }

    public function testAdminLoginWithWrongPassword(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/admin/v1/auth/login', [
                           'login_id' => 'admin',
                           'password' => 'wrongpassword',
                       ]);

        $result->assertStatus(401);
    }

    public function testAdminLoginWithUnknownAccount(): void
    {
        // admin DB에 없는 계정으로 로그인 시도 → 401
        $result = $this->withBodyFormat('json')
                       ->post('/api/admin/v1/auth/login', [
                           'login_id' => 'normaluser_not_admin',
                           'password' => 'password123',
                       ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['success' => false]);
    }

    // ------------------------------------------------------------------ //
    // 관리자 API 접근 권한
    // ------------------------------------------------------------------ //

    public function testAdminApiWithAdminToken(): void
    {
        $token  = $this->adminLoginAndGetToken();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/boards');

        $result->assertStatus(200);
    }

    public function testAdminApiWithUserToken(): void
    {
        // 일반 사용자 토큰으로 관리자 API 접근 → 403
        $userToken = $this->makeFakeUserToken();
        $result    = $this->withHeaders(['Authorization' => "Bearer {$userToken}"])
                          ->get('/api/admin/v1/boards');

        $result->assertStatus(403);
    }

    public function testAdminApiWithoutToken(): void
    {
        $result = $this->get('/api/admin/v1/boards');
        $result->assertStatus(401);
    }

    // ------------------------------------------------------------------ //
    // POST /api/admin/v1/auth/logout
    // ------------------------------------------------------------------ //

    public function testAdminLogout(): void
    {
        $data   = $this->adminLoginAndGetTokenData();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$data['access_token']}"])
                       ->post('/api/admin/v1/auth/logout', [
                           'refresh_token' => $data['refresh_token'],
                       ]);

        $result->assertStatus(200);
    }

    // ------------------------------------------------------------------ //
    // helper
    // ------------------------------------------------------------------ //

    private function adminLoginAndGetToken(): string
    {
        return $this->adminLoginAndGetTokenData()['access_token'];
    }

    private function adminLoginAndGetTokenData(): array
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/admin/v1/auth/login', [
                           'login_id' => 'admin',
                           'password' => 'admin1234',
                       ]);

        return json_decode($result->getJSON(), true)['data'];
    }

    private function makeFakeUserToken(): string
    {
        $fakeUser = [
            'idx'        => 999,
            'user_id'    => 'fakeuser',
            'nickname'   => '페이크',
            'email'      => 'fake@example.com',
            'group_idx'  => 2,
            'group_name' => '일반회원',
        ];

        return JwtService::issueAccess($fakeUser);
    }
}
