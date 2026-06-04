<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * 쪽지 API 통합 테스트
 *
 * @internal
 */
final class MessageApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-message-api-minimum32chars!!';

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
        $this->db->table('tb_users_message')->truncate();
        $this->db->table('tb_users_token')->truncate();
        $this->db->table('tb_users')->truncate();
    }

    private function insertTestUsers(): void
    {
        $now = time();
        foreach ([
            ['user_id' => 'sender',   'nickname' => '발신자',  'email' => 'sender@example.com'],
            ['user_id' => 'receiver', 'nickname' => '수신자',  'email' => 'receiver@example.com'],
            ['user_id' => 'stranger', 'nickname' => '제3자',   'email' => 'stranger@example.com'],
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

    private function sendMessage(string $senderToken, string $to, string $contents): int
    {
        $this->withBodyFormat('json')
             ->withHeaders(['Authorization' => "Bearer {$senderToken}"])
             ->post('/api/v1/messages', [
                 'to'       => $to,
                 'contents' => $contents,
             ]);

        // MessageController::send()는 idx를 반환하지 않으므로 DB에서 조회
        $row = $this->db->table('tb_users_message')
                        ->orderBy('idx', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRowArray();
        return (int) ($row['idx'] ?? 0);
    }

    private function login(string $userId, string $password = 'Test1234!'): string
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/auth/login', [
                           'login_id' => $userId,
                           'password' => $password,
                       ]);
        return json_decode($result->getJSON(), true)['data']['access_token'];
    }

    // ------------------------------------------------------------------ //
    // POST /api/v1/messages
    // ------------------------------------------------------------------ //

    public function testSendMessageWithoutTokenReturns401(): void
    {
        $result = $this->withBodyFormat('json')
                       ->post('/api/v1/messages', [
                           'to'       => 'receiver',
                           'contents' => '테스트 쪽지',
                       ]);
        $result->assertStatus(401);
    }

    public function testSendMessageSucceeds(): void
    {
        $token  = $this->login('sender');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/messages', [
                           'to'       => 'receiver',
                           'contents' => '안녕하세요!',
                       ]);
        $result->assertStatus(201);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testSendMessageWithMissingContentsReturns422(): void
    {
        $token  = $this->login('sender');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/messages', [
                           'to' => 'receiver',
                       ]);
        $result->assertStatus(422);
    }

    public function testSendMessageToSelfReturns422(): void
    {
        $token  = $this->login('sender');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/messages', [
                           'to'       => 'sender',
                           'contents' => '자신에게 쪽지',
                       ]);
        $result->assertStatus(422);
    }

    public function testSendMessageToNonExistentUserReturns404(): void
    {
        $token  = $this->login('sender');
        $result = $this->withBodyFormat('json')
                       ->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->post('/api/v1/messages', [
                           'to'       => 'nonexistent_user_xyz',
                           'contents' => '존재하지 않는 수신자',
                       ]);
        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/messages/inbox
    // ------------------------------------------------------------------ //

    public function testInboxWithoutTokenReturns401(): void
    {
        $result = $this->get('/api/v1/messages/inbox');
        $result->assertStatus(401);
    }

    public function testInboxReturnsOk(): void
    {
        $senderToken = $this->login('sender');
        $this->sendMessage($senderToken, 'receiver', '받은 쪽지함 테스트');

        $receiverToken = $this->login('receiver');
        $result        = $this->withHeaders(['Authorization' => "Bearer {$receiverToken}"])
                              ->get('/api/v1/messages/inbox');

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/messages/sent
    // ------------------------------------------------------------------ //

    public function testSentReturnsOk(): void
    {
        $token  = $this->login('sender');
        $result = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                       ->get('/api/v1/messages/sent');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ------------------------------------------------------------------ //
    // GET /api/v1/messages/{idx}
    // ------------------------------------------------------------------ //

    public function testShowMessageByReceiverSucceeds(): void
    {
        $senderToken = $this->login('sender');
        $messageIdx  = $this->sendMessage($senderToken, 'receiver', '쪽지 상세 테스트');
        $this->assertGreaterThan(0, $messageIdx);

        $receiverToken = $this->login('receiver');
        $result        = $this->withHeaders(['Authorization' => "Bearer {$receiverToken}"])
                              ->get('/api/v1/messages/' . $messageIdx);

        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testShowMessageByOtherUserReturns404(): void
    {
        $senderToken = $this->login('sender');
        $messageIdx  = $this->sendMessage($senderToken, 'receiver', '다른 유저 접근 테스트');
        $this->assertGreaterThan(0, $messageIdx);

        // 제3자가 접근 시도 → MessageModel::getOne()이 null 반환 → 404
        $strangerToken = $this->login('stranger');
        $result        = $this->withHeaders(['Authorization' => "Bearer {$strangerToken}"])
                           ->get('/api/v1/messages/' . $messageIdx);

        $result->assertStatus(404);
    }

    // ------------------------------------------------------------------ //
    // DELETE /api/v1/messages/{idx}
    // ------------------------------------------------------------------ //

    public function testDeleteMessageByReceiverSucceeds(): void
    {
        $senderToken = $this->login('sender');
        $messageIdx  = $this->sendMessage($senderToken, 'receiver', '삭제 테스트');
        $this->assertGreaterThan(0, $messageIdx);

        $receiverToken = $this->login('receiver');
        $result        = $this->withHeaders(['Authorization' => "Bearer {$receiverToken}"])
                              ->delete('/api/v1/messages/' . $messageIdx);

        $result->assertStatus(200);
    }

    public function testDeleteMessageWithoutTokenReturns401(): void
    {
        $senderToken = $this->login('sender');
        $messageIdx  = $this->sendMessage($senderToken, 'receiver', '삭제 테스트 (비인증)');
        $this->assertGreaterThan(0, $messageIdx);

        // withHeaders([])로 이전 Authorization 헤더 초기화
        $result = $this->withHeaders([])->delete('/api/v1/messages/' . $messageIdx);
        $result->assertStatus(401);
    }
}
