<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 게시판/게시글/댓글 API 통합 테스트
 *
 * @internal
 */
final class ArticleApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-article-api-minimum32chars!!';
    private const BBS    = 'free';

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
        $this->db->table('tb_bbs_comment_revision')->truncate();
        $this->db->table('tb_bbs_comment')->truncate();
        $this->db->table('tb_bbs_contents_revision')->truncate();
        $this->db->table('tb_bbs_contents')->truncate();
        $this->db->table('tb_bbs_hit')->truncate();
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

    private function login(string $userId, string $password): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => $userId,
                           'password' => $password,
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    private function createArticle(string $token, string $bbsId = self::BBS): int
    {
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post("/api/v1/boards/{$bbsId}/articles", [
                           'title'    => '테스트 게시글',
                           'contents' => '테스트 내용입니다.',
                       ]);
        return (int) (json_decode($result->getJSON(), true)['data']['idx'] ?? 0);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/boards
    // ------------------------------------------------------------------ //

    public function testBoardListReturnsOk(): void
    {
        $result = $this->get('/api/v1/boards');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testBoardShowReturnsOk(): void
    {
        $result = $this->get('/api/v1/boards/' . self::BBS);
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testBoardShowWithUnknownIdReturns404(): void
    {
        $result = $this->get('/api/v1/boards/nonexistent_board_xyz');
        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/boards/{bbsId}/articles
    // ------------------------------------------------------------------ //

    public function testArticleListReturnsOk(): void
    {
        $result = $this->get('/api/v1/boards/' . self::BBS . '/articles');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/boards/{bbsId}/articles
    // ------------------------------------------------------------------ //

    public function testArticleCreateWithoutTokenReturns401(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '제목',
                           'contents' => '내용',
                       ]);
        $result->assertStatus(401);
    }

    public function testArticleCreateWithValidToken(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '테스트 게시글',
                           'contents' => '내용입니다.',
                       ]);
        $result->assertStatus(201);
        $result->assertJSONFragment(['success' => true]);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertArrayHasKey('idx', $data);
    }

    public function testArticleCreateWithMissingTitle(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'contents' => '내용',
                       ]);
        $result->assertStatus(422);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/boards/{bbsId}/articles/{idx}
    // ------------------------------------------------------------------ //

    public function testArticleShowReturnsOk(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $this->assertGreaterThan(0, $articleIdx);

        $result = $this->get('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx);
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testArticleShowUnknownReturns404(): void
    {
        $result = $this->get('/api/v1/boards/' . self::BBS . '/articles/99999');
        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // PUT /api/v1/boards/{bbsId}/articles/{idx}
    // ------------------------------------------------------------------ //

    public function testArticleUpdateByOwner(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->put('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx, [
                           'title'    => '수정된 제목',
                           'contents' => '수정된 내용',
                       ]);
        $result->assertStatus(200);
    }

    public function testArticleUpdateByNonOwnerReturns403(): void
    {
        $ownerToken = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($ownerToken);
        $adminToken = $this->login('admin', 'admin1234');

        // 관리자 계정도 소유자가 아니면 403
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$adminToken}"])
                       ->put('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx, [
                           'title'    => '무단 수정',
                           'contents' => '내용',
                       ]);
        $result->assertStatus(403);
    }

    // ------------------------------------------------------------------ //
    // DELETE /api/v1/boards/{bbsId}/articles/{idx}
    // ------------------------------------------------------------------ //

    public function testArticleDeleteByOwner(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->delete('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx);
        $result->assertStatus(200);
    }

    public function testArticleDeleteWithoutTokenReturns401(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        // withHeaders([])로 이전 Authorization 헤더 초기화
        $result = $this->withHeaders([])->delete('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx);
        $result->assertStatus(401);
    }

    // ------------------------------------------------------------------ //
    // 댓글 API
    // ------------------------------------------------------------------ //

    public function testCommentListReturnsOk(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $result = $this->get('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx . '/comments');
        $result->assertStatus(200);
    }

    public function testCommentCreateWithValidToken(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx . '/comments', [
                           'comment' => '테스트 댓글',
                       ]);
        $result->assertStatus(201);
    }

    public function testCommentCreateWithoutTokenReturns401(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $result = $this->withHeaders([])->withBodyFormat('json')
                       ->post('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx . '/comments', [
                           'comment' => '테스트 댓글',
                       ]);
        $result->assertStatus(401);
    }

    public function testCommentDeleteByOwner(): void
    {
        $token      = $this->login('testuser', 'Test1234!');
        $articleIdx = $this->createArticle($token);

        $createResult = $this->withBodyFormat('json')
                             ->withHeaders(['Authorization' => "Bearer {$token}"])
                             ->post('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx . '/comments', [
                                 'comment' => '댓글',
                             ]);
        $commentIdx = (int) (json_decode($createResult->getJSON(), true)['data']['idx'] ?? 0);

        $deleteResult = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                             ->delete('/api/v1/boards/' . self::BBS . '/articles/' . $articleIdx . '/comments/' . $commentIdx);
        $deleteResult->assertStatus(200);
    }
}
