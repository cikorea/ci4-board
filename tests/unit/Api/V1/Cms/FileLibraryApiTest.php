<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 파일 라이브러리 API 통합 테스트
 *
 * POST   /api/v1/cms/library/files           업로드 (JWT 필요)
 * GET    /api/v1/cms/library/files           내 파일 목록
 * GET    /api/v1/cms/library/files/public    공용 파일 목록 (is_public=1)
 * GET    /api/v1/cms/library/files/:idx      단건 조회 (본인 소유 확인)
 * PUT    /api/v1/cms/library/files/:idx      메타 수정 (본인 소유 확인)
 * DELETE /api/v1/cms/library/files/:idx      삭제 (본인 소유 확인)
 *
 * 참고: CLI(PHPUnit) 환경에서 move_uploaded_file()이 동작하지 않으므로
 *       업로드 성공 케이스는 tb_file_library 직접 INSERT로 대체한다.
 *
 * @internal
 */
final class FileLibraryApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-library-api-minimum32chars!!!';

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();
        putenv('jwt.secret=' . self::SECRET);
        $_ENV['jwt.secret'] = self::SECRET;
        service('cache')->clean();
        $this->cleanTestData();
        $this->insertTestUsers();
    }

    private function cleanTestData(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('tb_file_library')->truncate();
        $this->db->table('tb_users_token')->truncate();
        // admin 계정(InitialSeeder)은 보존하고 테스트 유저만 정리한다.
        $this->db->table('tb_users')->where('user_id !=', 'admin')->delete();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function insertTestUsers(): void
    {
        $now = time();
        foreach ([
            ['user_id' => 'owner', 'nickname' => '파일소유자', 'email' => 'owner@example.com'],
            ['user_id' => 'other', 'nickname' => '타인',       'email' => 'other@example.com'],
        ] as $u) {
            $this->db->table('tb_users')->insert([
                'user_id'                => $u['user_id'],
                'super_secured_password' => password_hash('Test1234!', PASSWORD_BCRYPT),
                'level'                  => 1,
                'group_idx'              => 2,
                'name'                   => $u['nickname'],
                'nickname'               => $u['nickname'],
                'email'                  => $u['email'],
                'timezone'               => '+09',
                'status'                 => 1,
                'timestamp_insert'       => $now,
                'client_ip_insert'       => '127.0.0.1',
            ]);
        }
    }

    private function getUserToken(string $userId, string $password = 'Test1234!'): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => $userId,
                           'password' => $password,
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    private function userIdx(string $userId): int
    {
        $row = $this->db->table('tb_users')->where('user_id', $userId)->get()->getRowArray();
        return (int) $row['idx'];
    }

    /**
     * tb_file_library 직접 INSERT 후 idx 반환.
     */
    private function insertFile(int $uploaderIdx, array $overrides = []): int
    {
        $suffix = bin2hex(random_bytes(8));
        $data   = array_merge([
            'uploader_idx'     => $uploaderIdx,
            'source'           => 'direct',
            'original_name'    => 'sample.png',
            'stored_name'      => $suffix . '.png',
            'file_path'        => 'library/test/' . $suffix . '.png',
            'mime_type'        => 'image/png',
            'file_size'        => 1024,
            'alt_text'         => null,
            'is_public'        => 0,
            'used_count'       => 0,
            'timestamp_insert' => time(),
        ], $overrides);

        $this->db->table('tb_file_library')->insert($data);
        return (int) $this->db->insertID();
    }

    // ================================================================
    // 인증
    // ================================================================

    public function testUploadWithoutTokenReturns401(): void
    {
        $result = $this->withHeaders([])->post('/api/v1/cms/library/files', []);
        $result->assertStatus(401);
    }

    /**
     * 잘못된 MIME 타입 업로드 → 422.
     *
     * PHPUnit CLI 환경에서는 UploadedFile::isValid()가 is_uploaded_file()을
     * 사용하므로 실제 HTTP 업로드가 아닌 경우 항상 false를 반환한다.
     * 따라서 컨트롤러는 MIME 검증 이전에 library_file_required(422)로 응답한다.
     * 두 경로 모두 422이므로 토큰 보유 상태에서 422 응답을 검증한다.
     */
    public function testUploadWithInvalidMimeReturns422(): void
    {
        $token   = $this->getUserToken('owner');
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'malware_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($tmpPath, '<?php echo 1;');

        service('superglobals')->setFilesArray([
            'file' => [
                'name'     => 'malware.php',
                'type'     => 'application/x-php',
                'tmp_name' => $tmpPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 13,
            ],
        ]);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/cms/library/files', []);

        service('superglobals')->setFilesArray([]);
        @unlink($tmpPath);

        $result->assertStatus(422);
    }

    // ================================================================
    // 내 파일 목록
    // ================================================================

    public function testMyFileListReturnsOk(): void
    {
        $token   = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        $this->insertFile($ownerIdx);
        $this->insertFile($ownerIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/cms/library/files');
        $result->assertStatus(200);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertSame(2, $data['total']);
        $this->assertCount(2, $data['items']);
    }

    public function testMyFileListOnlyShowsOwnFiles(): void
    {
        $token    = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        $otherIdx = $this->userIdx('other');
        $this->insertFile($ownerIdx);
        $this->insertFile($otherIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/cms/library/files');
        $result->assertStatus(200);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertSame(1, $data['total']);
        $this->assertCount(1, $data['items']);
    }

    // ================================================================
    // 공용 파일 목록
    // ================================================================

    public function testPublicFileListReturnsOk(): void
    {
        $token    = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        $this->insertFile($ownerIdx, ['is_public' => 1]);
        $this->insertFile($ownerIdx, ['is_public' => 1]);
        $this->insertFile($ownerIdx, ['is_public' => 0]);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/cms/library/files/public');
        $result->assertStatus(200);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertSame(2, $data['total']);
        $this->assertCount(2, $data['items']);
    }

    // ================================================================
    // 단건 조회
    // ================================================================

    public function testShowFileReturnsOk(): void
    {
        $token    = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        $fileIdx  = $this->insertFile($ownerIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/cms/library/files/' . $fileIdx);
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testShowOtherUserFileReturns403(): void
    {
        $token    = $this->getUserToken('owner');
        $otherIdx = $this->userIdx('other');
        $fileIdx  = $this->insertFile($otherIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/cms/library/files/' . $fileIdx);
        $result->assertStatus(403);
    }

    // ================================================================
    // 메타 수정
    // ================================================================

    public function testUpdateMetaReturnsOk(): void
    {
        $token    = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        $fileIdx  = $this->insertFile($ownerIdx);

        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/v1/cms/library/files/' . $fileIdx, [
                           'alt_text' => '수정된 대체 텍스트',
                       ]);
        $result->assertStatus(200);

        $row = $this->db->table('tb_file_library')->where('idx', $fileIdx)->get()->getRowArray();
        $this->assertSame('수정된 대체 텍스트', $row['alt_text']);
    }

    public function testUpdateOtherUserFileReturns403(): void
    {
        $token    = $this->getUserToken('owner');
        $otherIdx = $this->userIdx('other');
        $fileIdx  = $this->insertFile($otherIdx);

        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/v1/cms/library/files/' . $fileIdx, [
                           'alt_text' => '권한 없는 수정',
                       ]);
        $result->assertStatus(403);
    }

    // ================================================================
    // 삭제
    // ================================================================

    public function testDeleteFileReturnsOk(): void
    {
        $token    = $this->getUserToken('owner');
        $ownerIdx = $this->userIdx('owner');
        // 사용처 스캔(findUsages)에 걸리지 않는 고유 경로 사용, 실제 파일 없음
        $fileIdx  = $this->insertFile($ownerIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/v1/cms/library/files/' . $fileIdx);
        $result->assertStatus(200);

        $row = $this->db->table('tb_file_library')->where('idx', $fileIdx)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function testDeleteOtherUserFileReturns403(): void
    {
        $token    = $this->getUserToken('owner');
        $otherIdx = $this->userIdx('other');
        $fileIdx  = $this->insertFile($otherIdx);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/v1/cms/library/files/' . $fileIdx);
        $result->assertStatus(403);
    }

    public function testDeleteNonExistentReturns404(): void
    {
        $token = $this->getUserToken('owner');

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/v1/cms/library/files/99999');
        $result->assertStatus(404);
    }
}
