<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 관리자 API 통합 테스트 (#59)
 *
 * GET/PUT /api/admin/v1/boards
 * GET/PUT /api/admin/v1/setting
 * GET/PUT /api/admin/v1/members
 * GET/GET/PUT/DELETE /api/admin/v1/articles
 *
 * @internal
 */
final class AdminApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-admin-api-minimum32chars!!!';

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
        $this->db->table('tb_bbs_hit')->truncate();
        $this->db->table('tb_bbs_contents_revision')->truncate();
        $this->db->table('tb_bbs_contents')->truncate();
        $this->db->table('tb_bbs_article_revision')->truncate();
        $this->db->table('tb_bbs_article')->truncate();
        $this->db->table('tb_users_token')->truncate();
        $this->db->table('tb_users')->where('user_id !=', 'admin')->delete();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function insertTestUser(): void
    {
        $this->db->table('tb_users')->insert([
            'user_id'                => 'testuser',
            'super_secured_password' => password_hash('Test1234!', PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'name'                   => '테스트유저',
            'nickname'               => '테스터',
            'email'                  => 'test@example.com',
            'timezone'               => '+09',
            'status'                 => 1,
            'timestamp_insert'       => time(),
            'client_ip_insert'       => '127.0.0.1',
        ]);
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

    private function userLogin(): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => 'testuser',
                           'password' => 'Test1234!',
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    // ------------------------------------------------------------------ //
    // admin_jwt 필터 검증
    // ------------------------------------------------------------------ //

    public function testAdminEndpointWithoutTokenReturns401(): void
    {
        $result = $this->withHeaders([])->get('/api/admin/v1/boards');
        $result->assertStatus(401);
    }

    public function testAdminEndpointWithUserTokenReturns403(): void
    {
        $userToken = $this->userLogin();
        $result    = $this->withHeaders(['Authorization' => "Bearer {$userToken}"])
                          ->get('/api/admin/v1/boards');
        $result->assertStatus(403);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/boards
    // ------------------------------------------------------------------ //

    public function testAdminBoardListReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/boards');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertArrayHasKey('boards', $data);
        $this->assertArrayHasKey('groups', $data);
    }

    // ------------------------------------------------------------------ //
    // PUT /api/admin/v1/boards/{bbsId}
    // ------------------------------------------------------------------ //

    public function testAdminBoardUpdateReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/boards/free', [
                           'bbs_name'              => '자유게시판',
                           'bbs_used'              => '1',
                           'bbs_comment_used'      => '1',
                           'bbs_count_list_article' => '15',
                           'view_list'             => ['0', '1', '2', '3'],
                           'view_article'          => ['0', '1', '2', '3'],
                           'write_article'         => ['1', '2', '3'],
                           'write_comment'         => ['1', '2', '3'],
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testAdminBoardUpdateWithUnknownBoardReturns404(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/boards/nonexistent_xyz', [
                           'bbs_name' => '없는게시판',
                       ]);

        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/setting
    // ------------------------------------------------------------------ //

    public function testAdminSettingGetReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/setting');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // PUT /api/admin/v1/setting
    // ------------------------------------------------------------------ //

    public function testAdminSettingUpdateReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/setting', [
                           'browser_title_fix_value' => 'CI4 Board Test',
                           'join_used'               => true,
                           'site_block_used'         => false,
                           'site_block_contents'     => '',
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/members
    // ------------------------------------------------------------------ //

    public function testAdminMemberListReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/members');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testAdminMemberListWithKeywordFilter(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/members?keyword=testuser');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // PUT /api/admin/v1/members/{idx}
    // ------------------------------------------------------------------ //

    public function testAdminMemberUpdateReturnsOk(): void
    {
        $token   = $this->adminLogin();
        $testRow = $this->db->table('tb_users')->where('user_id', 'testuser')->get()->getRowArray();

        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/members/' . $testRow['idx'], [
                           'nickname'  => '수정된닉네임',
                           'email'     => 'modified@example.com',
                           'group_idx' => 2,
                           'status'    => 1,
                       ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testAdminMemberUpdateWithNonExistentUserReturns404(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/admin/v1/members/99999', [
                           'nickname' => '없는유저',
                           'email'    => 'none@example.com',
                       ]);

        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/articles
    // ------------------------------------------------------------------ //

    public function testAdminArticleListReturnsOk(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/articles');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testAdminArticleListWithBbsFilter(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/articles?bbs_id=free');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // GET/PUT/DELETE /api/admin/v1/articles/{idx}
    // ------------------------------------------------------------------ //

    public function testAdminArticleShowNonExistentReturns404(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/articles/99999');

        $result->assertStatus(404);
    }

    public function testAdminArticleCrudFlow(): void
    {
        // 일반 유저로 게시글 작성
        $userToken = $this->userLogin();
        $createResult = $this->withBodyFormat('json')
                             ->withHeaders(['Authorization' => "Bearer {$userToken}"])
                             ->post('/api/v1/boards/free/articles', [
                                 'title'    => '관리자 테스트 게시글',
                                 'contents' => '관리자 API 테스트용 내용',
                             ]);
        $createResult->assertStatus(201);
        $articleIdx = (int) (json_decode($createResult->getJSON(), true)['data']['idx'] ?? 0);
        $this->assertGreaterThan(0, $articleIdx);

        $adminToken = $this->adminLogin();

        // 관리자 게시글 상세 조회
        $showResult = $this->withHeaders(['Authorization' => "Bearer {$adminToken}"])
                           ->get('/api/admin/v1/articles/' . $articleIdx);
        $showResult->assertStatus(200);
        $showResult->assertJSONFragment(['success' => true]);

        // 관리자 게시글 수정
        $updateResult = $this->withBodyFormat('json')
                             ->withHeaders(['Authorization' => "Bearer {$adminToken}"])
                             ->put('/api/admin/v1/articles/' . $articleIdx, [
                                 'title'    => '관리자가 수정한 제목',
                                 'contents' => '관리자가 수정한 내용',
                             ]);
        $updateResult->assertStatus(200);

        // 관리자 게시글 삭제
        $deleteResult = $this->withHeaders(['Authorization' => "Bearer {$adminToken}"])
                             ->delete('/api/admin/v1/articles/' . $articleIdx);
        $deleteResult->assertStatus(200);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/stats
    // ------------------------------------------------------------------ //

    public function testAdminStatsReturnsOk(): void
    {
        $this->cleanAdminData();

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/stats');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertArrayHasKey('stats', $data);
        $this->assertSame([], $data['stats']);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/logs
    // ------------------------------------------------------------------ //

    public function testAdminLogsReturnsOk(): void
    {
        $this->cleanAdminData();

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/logs');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertSame([], $data);
    }

    // ------------------------------------------------------------------ //
    // GET /api/admin/v1/notices
    // ------------------------------------------------------------------ //

    public function testAdminNoticeListReturnsOk(): void
    {
        $this->cleanAdminData();

        $token  = $this->adminLogin();
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/admin/v1/notices');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
        $body = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function testAdminNoticeCreateValidation(): void
    {
        $this->cleanAdminData();

        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/admin/v1/notices', [
                           'contents' => '제목 없는 공지',
                       ]);

        $result->assertStatus(422);
        $result->assertJSONFragment(['success' => false]);
    }

    public function testAdminNoticeCrudFlow(): void
    {
        $this->cleanAdminData();

        $token = $this->adminLogin();

        // 공지 등록
        $createResult = $this->withBodyFormat('json')
                             ->withHeaders(['Authorization' => "Bearer {$token}"])
                             ->post('/api/admin/v1/notices', [
                                 'title'     => '테스트 공지',
                                 'contents'  => '테스트 공지 내용',
                                 'is_pinned' => true,
                             ]);
        $createResult->assertStatus(200);
        $createResult->assertJSONFragment(['success' => true]);
        $noticeIdx = (int) (json_decode($createResult->getJSON(), true)['data']['idx'] ?? 0);
        $this->assertGreaterThan(0, $noticeIdx);

        // 공지 목록 확인
        $listResult = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                           ->get('/api/admin/v1/notices');
        $listResult->assertStatus(200);
        $listTitles = array_column(json_decode($listResult->getJSON(), true)['data'], 'title');
        $this->assertContains('테스트 공지', $listTitles);

        // 공지 삭제
        $deleteResult = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                             ->delete('/api/admin/v1/notices/' . $noticeIdx);
        $deleteResult->assertStatus(200);
        $deleteResult->assertJSONFragment(['success' => true]);

        // 없는 공지 삭제 시 404
        $notFoundResult = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                               ->delete('/api/admin/v1/notices/' . $noticeIdx);
        $notFoundResult->assertStatus(404);
    }

    /**
     * Stats/Logs/Notices 는 admin DB(ci4_board_admin) 를 사용하므로
     * 별도 연결로 관련 테이블을 정리한다. (기존 cleanTestData 와 분리)
     */
    private function cleanAdminData(): void
    {
        $adminDb = \Config\Database::connect('admin');
        $adminDb->table('tb_admin_notice')->truncate();
        $adminDb->table('tb_admin_log')->truncate();
        $adminDb->table('tb_stats_daily')->truncate();
    }
}
