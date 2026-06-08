<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 관리자 파일 라이브러리 API 통합 테스트
 *
 * GET    /api/admin/v1/cms/library/files
 * GET    /api/admin/v1/cms/library/files/{idx}
 * PUT    /api/admin/v1/cms/library/files/{idx}
 * DELETE /api/admin/v1/cms/library/files/{idx}
 *
 * @internal
 */
final class AdminFileLibraryApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-admin-library-minimum32chars!!!';

    private int $uploaderIdx = 0;

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();
        putenv('jwt.secret=' . self::SECRET);
        $_ENV['jwt.secret'] = self::SECRET;
        service('cache')->clean();
        $this->cleanTestData();
        $this->insertTestUser();
    }

    private function cleanTestData(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('tb_cms_page')->truncate();
        $this->db->table('tb_file_library')->truncate();
        $this->db->table('tb_users_token')->truncate();
        $this->db->table('tb_users')->where('user_id !=', 'admin')->delete();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function insertTestUser(): void
    {
        $this->db->table('tb_users')->insert([
            'user_id'                => 'uploader',
            'super_secured_password' => password_hash('Test1234!', PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'name'                   => '업로더',
            'nickname'               => '업로더',
            'email'                  => 'uploader@example.com',
            'timezone'               => '+09',
            'status'                 => 1,
            'timestamp_insert'       => time(),
            'client_ip_insert'       => '127.0.0.1',
        ]);
        $this->uploaderIdx = (int) $this->db->insertID();
    }

    private function adminLogin(): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/admin/v1/auth/login', [
                           'login_id' => 'admin',
                           'password' => 'admin1234',
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    /**
     * tb_file_library 에 레코드를 직접 삽입하고 idx 를 반환한다.
     */
    private function insertFile(int $uploaderIdx, array $overrides = []): int
    {
        $suffix = bin2hex(random_bytes(8));
        $data   = array_merge([
            'uploader_idx'     => $uploaderIdx,
            'source'           => 'direct',
            'original_name'    => "sample-{$suffix}.jpg",
            'stored_name'      => "{$suffix}.jpg",
            'file_path'        => "library/2026/06/{$suffix}.jpg",
            'mime_type'        => 'image/jpeg',
            'file_size'        => 1024,
            'alt_text'         => null,
            'is_public'        => 0,
            'used_count'       => 0,
            'timestamp_insert' => time(),
        ], $overrides);

        $this->db->table('tb_file_library')->insert($data);

        return (int) $this->db->insertID();
    }

    // ------------------------------------------------------------------ //
    // 인증
    // ------------------------------------------------------------------ //

    public function testAdminFileLibraryWithoutTokenReturns401(): void
    {
        $result = $this->withHeaders([])->get('/api/admin/v1/cms/library/files');
        $result->assertStatus(401);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/cms/library/files
    // ------------------------------------------------------------------ //

    public function testAdminFileLibraryListReturnsOk(): void
    {
        $this->insertFile($this->uploaderIdx);
        $this->insertFile($this->uploaderIdx);
        $this->insertFile($this->uploaderIdx);

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/cms/library/files');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(3, $data['items']);
    }

    public function testAdminFileLibraryListWithMimeFilter(): void
    {
        $this->insertFile($this->uploaderIdx, ['mime_type' => 'image/jpeg']);
        $this->insertFile($this->uploaderIdx, ['mime_type' => 'image/jpeg']);
        $this->insertFile($this->uploaderIdx, ['mime_type' => 'application/pdf']);

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/cms/library/files?mime=image');

        $result->assertStatus(200);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertCount(2, $data['items']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/cms/library/files/{idx}
    // ------------------------------------------------------------------ //

    public function testAdminFileLibraryShowReturnsOk(): void
    {
        $idx = $this->insertFile($this->uploaderIdx);

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/cms/library/files/' . $idx);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertSame($idx, (int) $data['idx']);
    }

    public function testAdminFileLibraryShowNonExistentReturns404(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/cms/library/files/99999');

        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // PUT /api/admin/v1/cms/library/files/{idx}
    // ------------------------------------------------------------------ //

    public function testAdminFileLibraryUpdateMetaReturnsOk(): void
    {
        $idx = $this->insertFile($this->uploaderIdx);

        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/cms/library/files/' . $idx, [
                           'alt_text' => '수정된 대체 텍스트',
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $row = $this->db->table('tb_file_library')->where('idx', $idx)->get()->getRowArray();
        $this->assertSame('수정된 대체 텍스트', $row['alt_text']);
    }

    // ------------------------------------------------------------------ //
    // DELETE /api/admin/v1/cms/library/files/{idx}
    // ------------------------------------------------------------------ //

    public function testAdminFileLibraryDeleteReturnsOk(): void
    {
        $idx = $this->insertFile($this->uploaderIdx);

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/admin/v1/cms/library/files/' . $idx);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);

        $row = $this->db->table('tb_file_library')->where('idx', $idx)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function testAdminFileLibraryDeleteInUseReturns409(): void
    {
        $idx  = $this->insertFile($this->uploaderIdx);
        $row  = $this->db->table('tb_file_library')->where('idx', $idx)->get()->getRowArray();
        $path = $row['file_path'];

        // 파일이 CMS 페이지 본문에서 참조되도록 만든다 → findUsages() 가 사용처를 반환.
        $this->db->table('tb_cms_page')->insert([
            'slug'             => 'usage-page',
            'title'            => '사용처 페이지',
            'contents'         => '<img src="/uploads/' . $path . '">',
            'status'           => 1,
            'timestamp_insert' => time(),
        ]);

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/admin/v1/cms/library/files/' . $idx);

        $result->assertStatus(409);

        // 파일은 삭제되지 않아야 한다.
        $still = $this->db->table('tb_file_library')->where('idx', $idx)->get()->getRowArray();
        $this->assertNotNull($still);
    }
}
