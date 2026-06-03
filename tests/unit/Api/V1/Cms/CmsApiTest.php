<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * CMS API 통합 테스트 (#58)
 *
 * Public  GET  /api/v1/cms/pages/:slug
 * Public  GET  /api/v1/cms/banners
 * Public  GET  /api/v1/cms/popups
 * Public  GET  /api/v1/cms/menus
 *
 * Admin   CRUD /api/admin/v1/cms/pages
 * Admin   CRUD /api/admin/v1/cms/banners
 * Admin   CRUD /api/admin/v1/cms/popups
 * Admin   CRUD /api/admin/v1/cms/menus
 *
 * @internal
 */
final class CmsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    private const SECRET = 'test-secret-for-cms-api-tests-minimum32chars!!';

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();
        putenv('jwt.secret=' . self::SECRET);
        $_ENV['jwt.secret'] = self::SECRET;
        service('cache')->clean();
        $this->cleanCmsData();
    }

    private function cleanCmsData(): void
    {
        $this->db->table('tb_cms_menu')->truncate();
        $this->db->table('tb_cms_popup')->truncate();
        $this->db->table('tb_cms_banner')->truncate();
        $this->db->table('tb_cms_page')->truncate();
        $this->db->table('tb_users_token')->truncate();
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

    private function adminHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function getInsertedIdx(mixed $result): int
    {
        return (int) (json_decode($result->getJSON(), true)['data']['idx'] ?? 0);
    }

    // ================================================================
    // Admin CMS 인증 검증
    // ================================================================

    public function testCmsAdminWithoutTokenReturns401(): void
    {
        $result = $this->withHeaders([])->get('/api/admin/v1/cms/pages');
        $result->assertStatus(401);
    }

    // ================================================================
    // Admin Page API
    // ================================================================

    public function testAdminPageCreateValidation(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/pages', [
                           'title' => '제목만',
                       ]);
        $result->assertStatus(422);
    }

    public function testAdminPageCreateInvalidSlug(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/pages', [
                           'slug'     => 'Invalid Slug!',
                           'title'    => '제목',
                           'contents' => '내용',
                       ]);
        $result->assertStatus(422);
    }

    public function testAdminPageCrud(): void
    {
        $token = $this->adminLogin();

        // Create
        $createResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->post('/api/admin/v1/cms/pages', [
                                 'slug'     => 'about-us',
                                 'title'    => '회사 소개',
                                 'contents' => '<p>회사 소개 내용입니다.</p>',
                                 'status'   => true,
                             ]);
        $createResult->assertStatus(201);
        $idx = $this->getInsertedIdx($createResult);
        $this->assertGreaterThan(0, $idx);

        // List
        $listResult = $this->withHeaders($this->adminHeader($token))
                           ->get('/api/admin/v1/cms/pages');
        $listResult->assertStatus(200);
        $listResult->assertJSONFragment(['success' => true]);

        // Update
        $updateResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->put('/api/admin/v1/cms/pages/' . $idx, [
                                 'slug'     => 'about-us',
                                 'title'    => '회사 소개 (수정)',
                                 'contents' => '<p>수정된 내용</p>',
                                 'status'   => true,
                             ]);
        $updateResult->assertStatus(200);

        // Delete
        $deleteResult = $this->withHeaders($this->adminHeader($token))
                             ->delete('/api/admin/v1/cms/pages/' . $idx);
        $deleteResult->assertStatus(200);
    }

    public function testAdminPageDuplicateSlugReturns422(): void
    {
        $token = $this->adminLogin();

        $this->withBodyFormat('json')
             ->withHeaders($this->adminHeader($token))
             ->post('/api/admin/v1/cms/pages', [
                 'slug'     => 'duplicate-slug',
                 'title'    => '첫 번째',
                 'contents' => '내용',
             ]);

        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/pages', [
                           'slug'     => 'duplicate-slug',
                           'title'    => '두 번째',
                           'contents' => '내용',
                       ]);
        $result->assertStatus(422);
    }

    // ================================================================
    // Public Page API
    // ================================================================

    public function testPublicPageShowReturnsOk(): void
    {
        $token = $this->adminLogin();
        $this->withBodyFormat('json')
             ->withHeaders($this->adminHeader($token))
             ->post('/api/admin/v1/cms/pages', [
                 'slug'     => 'public-test',
                 'title'    => '공개 테스트',
                 'contents' => '공개 페이지',
                 'status'   => true,
             ]);

        $result = $this->withHeaders([])->get('/api/v1/cms/pages/public-test');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testPublicPageShowNonExistentReturns404(): void
    {
        $result = $this->withHeaders([])->get('/api/v1/cms/pages/nonexistent-page-xyz');
        $result->assertStatus(404);
    }

    // ================================================================
    // Admin Banner API
    // ================================================================

    public function testAdminBannerCreateValidation(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/banners', [
                           'position' => 'main',
                       ]);
        $result->assertStatus(422);
    }

    public function testAdminBannerCrud(): void
    {
        $token = $this->adminLogin();

        // Create
        $createResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->post('/api/admin/v1/cms/banners', [
                                 'position'   => 'main',
                                 'image_path' => '/uploads/banner/test.jpg',
                                 'link_url'   => 'https://example.com',
                                 'is_used'    => true,
                             ]);
        $createResult->assertStatus(201);
        $idx = $this->getInsertedIdx($createResult);
        $this->assertGreaterThan(0, $idx);

        // List
        $listResult = $this->withHeaders($this->adminHeader($token))
                           ->get('/api/admin/v1/cms/banners');
        $listResult->assertStatus(200);

        // Update
        $updateResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->put('/api/admin/v1/cms/banners/' . $idx, [
                                 'position'   => 'main',
                                 'image_path' => '/uploads/banner/updated.jpg',
                                 'is_used'    => false,
                             ]);
        $updateResult->assertStatus(200);

        // Delete
        $deleteResult = $this->withHeaders($this->adminHeader($token))
                             ->delete('/api/admin/v1/cms/banners/' . $idx);
        $deleteResult->assertStatus(200);
    }

    // ================================================================
    // Public Banner API
    // ================================================================

    public function testPublicBannerListReturnsOk(): void
    {
        $result = $this->withHeaders([])->get('/api/v1/cms/banners');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ================================================================
    // Admin Popup API
    // ================================================================

    public function testAdminPopupCreateValidation(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/popups', [
                           'title' => '제목만',
                       ]);
        $result->assertStatus(422);
    }

    public function testAdminPopupCrud(): void
    {
        $token = $this->adminLogin();

        // Create
        $createResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->post('/api/admin/v1/cms/popups', [
                                 'title'    => '이벤트 팝업',
                                 'contents' => '<p>이벤트 내용</p>',
                                 'is_used'  => true,
                             ]);
        $createResult->assertStatus(201);
        $idx = $this->getInsertedIdx($createResult);
        $this->assertGreaterThan(0, $idx);

        // List
        $listResult = $this->withHeaders($this->adminHeader($token))
                           ->get('/api/admin/v1/cms/popups');
        $listResult->assertStatus(200);

        // Show
        $showResult = $this->withHeaders($this->adminHeader($token))
                           ->get('/api/admin/v1/cms/popups/' . $idx);
        $showResult->assertStatus(200);

        // Update
        $updateResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->put('/api/admin/v1/cms/popups/' . $idx, [
                                 'title'    => '수정된 팝업',
                                 'contents' => '<p>수정된 내용</p>',
                                 'is_used'  => false,
                             ]);
        $updateResult->assertStatus(200);

        // Delete
        $deleteResult = $this->withHeaders($this->adminHeader($token))
                             ->delete('/api/admin/v1/cms/popups/' . $idx);
        $deleteResult->assertStatus(200);
    }

    // ================================================================
    // Public Popup API
    // ================================================================

    public function testPublicPopupListReturnsOk(): void
    {
        $result = $this->withHeaders([])->get('/api/v1/cms/popups');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    // ================================================================
    // Admin Menu API
    // ================================================================

    public function testAdminMenuCreateValidation(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/menus', [
                           'url' => 'https://example.com',
                       ]);
        $result->assertStatus(422);
    }

    public function testAdminMenuCrudWithTree(): void
    {
        $token = $this->adminLogin();

        // 루트 메뉴 생성
        $rootResult = $this->withBodyFormat('json')
                           ->withHeaders($this->adminHeader($token))
                           ->post('/api/admin/v1/cms/menus', [
                               'label'    => '서비스',
                               'url'      => '#',
                               'is_used'  => true,
                               'sequence' => 0,
                           ]);
        $rootResult->assertStatus(201);
        $rootIdx = $this->getInsertedIdx($rootResult);
        $this->assertGreaterThan(0, $rootIdx);

        // 하위 메뉴 생성
        $childResult = $this->withBodyFormat('json')
                            ->withHeaders($this->adminHeader($token))
                            ->post('/api/admin/v1/cms/menus', [
                                'label'      => '소개',
                                'url'        => '/about',
                                'parent_idx' => $rootIdx,
                                'is_used'    => true,
                            ]);
        $childResult->assertStatus(201);
        $childIdx = $this->getInsertedIdx($childResult);
        $this->assertGreaterThan(0, $childIdx);

        // 목록 (트리 구조)
        $listResult = $this->withHeaders($this->adminHeader($token))
                           ->get('/api/admin/v1/cms/menus');
        $listResult->assertStatus(200);

        // 수정
        $updateResult = $this->withBodyFormat('json')
                             ->withHeaders($this->adminHeader($token))
                             ->put('/api/admin/v1/cms/menus/' . $rootIdx, [
                                 'label'   => '서비스 (수정)',
                                 'url'     => '#',
                                 'is_used' => true,
                             ]);
        $updateResult->assertStatus(200);

        // 하위 메뉴 삭제
        $this->withHeaders($this->adminHeader($token))
             ->delete('/api/admin/v1/cms/menus/' . $childIdx);

        // 루트 메뉴 삭제
        $deleteResult = $this->withHeaders($this->adminHeader($token))
                             ->delete('/api/admin/v1/cms/menus/' . $rootIdx);
        $deleteResult->assertStatus(200);
    }

    public function testAdminMenuNonExistentParentReturns422(): void
    {
        $token  = $this->adminLogin();
        $result = $this->withBodyFormat('json')
                       ->withHeaders($this->adminHeader($token))
                       ->post('/api/admin/v1/cms/menus', [
                           'label'      => '하위메뉴',
                           'url'        => '/sub',
                           'parent_idx' => 99999,
                       ]);
        $result->assertStatus(422);
    }

    // ================================================================
    // Public Menu API
    // ================================================================

    public function testPublicMenuListReturnsOk(): void
    {
        $result = $this->withHeaders([])->get('/api/v1/cms/menus');
        $result->assertStatus(200);
        $result->assertJSONFragment(['success' => true]);
    }

    public function testPublicMenuListReturnsTreeStructure(): void
    {
        $token = $this->adminLogin();

        // 루트 메뉴 생성
        $rootResult = $this->withBodyFormat('json')
                           ->withHeaders($this->adminHeader($token))
                           ->post('/api/admin/v1/cms/menus', [
                               'label'   => '메인메뉴',
                               'url'     => '#',
                               'is_used' => true,
                           ]);
        $rootIdx = $this->getInsertedIdx($rootResult);

        // 하위 메뉴
        $this->withBodyFormat('json')
             ->withHeaders($this->adminHeader($token))
             ->post('/api/admin/v1/cms/menus', [
                 'label'      => '하위메뉴',
                 'url'        => '/sub',
                 'parent_idx' => $rootIdx,
                 'is_used'    => true,
             ]);

        $result = $this->withHeaders([])->get('/api/v1/cms/menus');
        $result->assertStatus(200);

        $data = json_decode($result->getJSON(), true)['data'];
        $this->assertIsArray($data);

        // 트리 구조 검증: 루트 메뉴에 children 키 존재 (MySQLi가 idx를 문자열로 반환)
        $root = array_filter($data, fn($m) => (int) $m['idx'] === $rootIdx);
        $root = array_values($root)[0] ?? null;
        $this->assertNotNull($root);
        $this->assertArrayHasKey('children', $root);
    }
}
