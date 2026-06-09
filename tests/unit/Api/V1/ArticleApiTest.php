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
        $this->db->table('tb_users')->truncate();
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
        $this->db->table('tb_users')->insert([
            'user_id'                => 'anotheruser',
            'super_secured_password' => password_hash('Another1234!', PASSWORD_BCRYPT),
            'level'                  => 1,
            'group_idx'              => 2,
            'name'                   => '다른유저',
            'nickname'               => '다른유저',
            'email'                  => 'another@example.com',
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
                           'contents' => '테스트 게시글 내용입니다.',
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
        $ownerToken   = $this->login('testuser', 'Test1234!');
        $articleIdx   = $this->createArticle($ownerToken);
        $anotherToken = $this->login('anotheruser', 'Another1234!');

        // 다른 회원은 소유자가 아니면 403
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$anotherToken}"])
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

    // ------------------------------------------------------------------ //
    // 스팸 필터
    // ------------------------------------------------------------------ //

    public function testSpamCooldownBlocksQuickRepost(): void
    {
        $token = $this->login('testuser', 'Test1234!');

        $first = $this->withBodyFormat('json')
                      ->withHeaders(['Authorization' => "Bearer {$token}"])
                      ->post('/api/v1/boards/' . self::BBS . '/articles', [
                          'title'    => '첫 번째 글',
                          'contents' => '첫 번째 내용입니다.',
                      ]);
        $first->assertStatus(201);

        $second = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '두 번째 글',
                           'contents' => '두 번째 내용입니다.',
                       ]);
        $second->assertStatus(422);
        $this->assertStringContainsString('너무 빠르게', $second->getJSON());
    }

    public function testSpamDuplicateBlocksSameContent(): void
    {
        $token = $this->login('testuser', 'Test1234!');

        $first = $this->withBodyFormat('json')
                      ->withHeaders(['Authorization' => "Bearer {$token}"])
                      ->post('/api/v1/boards/' . self::BBS . '/articles', [
                          'title'    => '중복 테스트 글',
                          'contents' => '중복 테스트 내용입니다.',
                      ]);
        $first->assertStatus(201);

        // 쿨다운 캐시만 제거해 중복 검사까지 도달
        $userRow = $this->db->table('tb_users')->where('user_id', 'testuser')->get()->getRowArray();
        service('cache')->delete('spam_cd_' . $userRow['idx']);

        $second = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '중복 테스트 글',
                           'contents' => '중복 테스트 내용입니다.',
                       ]);
        $second->assertStatus(422);
        $this->assertStringContainsString('중복', $second->getJSON());
    }

    public function testSpamRepeatedCharsBlocked(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '반복문자 테스트',
                           'contents' => 'aaaaaaaaaa 반복 문자가 10개 이상입니다.',
                       ]);
        $result->assertStatus(422);
        $this->assertStringContainsString('반복', $result->getJSON());
    }

    public function testSpamTooManyUrlsBlocked(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => 'URL 스팸 테스트',
                           'contents' => 'https://a.com https://b.com https://c.com https://d.com https://e.com 링크 다섯 개',
                       ]);
        $result->assertStatus(422);
        $this->assertStringContainsString('URL', $result->getJSON());
    }

    public function testSpamContentTooShortBlocked(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '짧은 내용',
                           'contents' => '짧아요',
                       ]);
        $result->assertStatus(422);
        $this->assertStringContainsString('내용', $result->getJSON());
    }

    public function testSpamTitleTooShortBlocked(): void
    {
        $token  = $this->login('testuser', 'Test1234!');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/boards/' . self::BBS . '/articles', [
                           'title'    => '가',
                           'contents' => '충분히 긴 내용입니다. 스팸 필터 통과를 위한 내용.',
                       ]);
        $result->assertStatus(422);
        $this->assertStringContainsString('제목', $result->getJSON());
    }
}
