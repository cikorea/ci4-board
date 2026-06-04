<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 파일 업로드 API 통합 테스트
 *
 * 실제 멀티파트 업로드는 CI4 FeatureTestTrait에서 직접 지원하지 않으므로
 * 인증/권한 검증 중심으로 테스트한다.
 *
 * @internal
 */
final class FileUploadApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-file-upload-api-min32chars!!';

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
        $this->db->table('tb_bbs_file')->truncate();
        $this->db->table('tb_bbs_contents_revision')->truncate();
        $this->db->table('tb_bbs_contents')->truncate();
        $this->db->table('tb_bbs_hit')->truncate();
        $this->db->table('tb_bbs_article_revision')->truncate();
        $this->db->table('tb_bbs_article')->truncate();
        $this->db->table('tb_users_token')->truncate();
        $this->db->table('tb_users')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function insertTestUser(): void
    {
        $this->db->table('tb_users')->insert([
            'user_id'                => 'fileuser',
            'super_secured_password' => password_hash('Test1234!', PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'name'                   => '파일테스터',
            'nickname'               => '파일유저',
            'email'                  => 'fileuser@example.com',
            'timezone'               => '+09',
            'status'                 => 1,
            'timestamp_insert'       => time(),
            'client_ip_insert'       => '127.0.0.1',
        ]);
    }

    private function login(string $userId = 'fileuser', string $password = 'Test1234!'): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => $userId,
                           'password' => $password,
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    private function createArticle(string $token): int
    {
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/free/articles', [
                           'title'    => '파일 테스트 게시글',
                           'contents' => '파일 업로드 테스트용 게시글',
                       ]);
        return (int) (json_decode($result->getJSON(), true)['data']['idx'] ?? 0);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/files (인증 검증)
    // ------------------------------------------------------------------ //

    public function testFileUploadWithoutTokenReturns401(): void
    {
        $result = $this->post('/api/v1/files', []);
        $result->assertStatus(401);
    }

    public function testFileUploadWithTokenButNoFileReturns201WithEmptyList(): void
    {
        $token      = $this->login();
        $articleIdx = $this->createArticle($token);

        // 파일 없이 요청 → 201 빈 uploaded 배열 반환 (FileController 스펙)
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/files', [
                           'bbs_idx'     => 1,
                           'article_idx' => $articleIdx,
                       ]);
        $result->assertStatus(201);
        $body = json_decode($result->getJSON(), true);
        $this->assertEmpty($body['data']['uploaded']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/files/{idx}/download (인증 불필요)
    // ------------------------------------------------------------------ //

    public function testDownloadNonExistentFileReturns404(): void
    {
        $result = $this->get('/api/v1/files/99999/download');
        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // DELETE /api/v1/files/{idx} (인증 필요)
    // ------------------------------------------------------------------ //

    public function testDeleteFileWithoutTokenReturns401(): void
    {
        $result = $this->delete('/api/v1/files/1');
        $result->assertStatus(401);
    }

    public function testDeleteNonExistentFileWithTokenReturns404(): void
    {
        $token  = $this->login();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/v1/files/99999');
        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/files/wysiwyg (WYSIWYG 이미지 업로드)
    // ------------------------------------------------------------------ //

    public function testWysiwygUploadWithoutTokenReturns401(): void
    {
        $result = $this->post('/api/v1/files/wysiwyg', []);
        $result->assertStatus(401);
    }

    public function testWysiwygUploadWithTokenButNoImageReturns422(): void
    {
        $token  = $this->login();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/files/wysiwyg', []);
        $result->assertStatus(422);
    }
}
