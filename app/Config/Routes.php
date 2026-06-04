<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// GET / → Swagger UI 리다이렉트 (브라우저) 또는 API 정보 JSON 반환
$routes->get('/', 'HomeController::index');

// API 문서 (Swagger UI)
$routes->get('swagger', static function () {
    return redirect()->to('/docs/swagger.html');
});

// ================================================================
// API v1 — 소셜 로그인
// ================================================================
$routes->group('api/v1/auth/social', static function ($routes) {
    $routes->get('google',          'Api\SocialAuthController::googleRedirect');
    $routes->get('google/callback', 'Api\SocialAuthController::googleCallback');
    $routes->get('kakao',           'Api\SocialAuthController::kakaoRedirect');
    $routes->get('kakao/callback',  'Api\SocialAuthController::kakaoCallback');
    $routes->get('naver',           'Api\SocialAuthController::naverRedirect');
    $routes->get('naver/callback',  'Api\SocialAuthController::naverCallback');
});

// ================================================================
// API v1 — 프론트엔드용
// ================================================================
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'rate_limit:60'], static function ($routes) {

    // 인증 (공개)
    $routes->post('auth/login',    'AuthController::login',    ['filter' => 'rate_limit:10']);
    $routes->post('auth/register', 'AuthController::register', ['filter' => 'rate_limit:10']);
    $routes->post('auth/refresh',  'AuthController::refresh',  ['filter' => 'rate_limit:20']);

    // 인증 (로그인 필요)
    $routes->group('', ['filter' => 'jwt'], static function ($routes) {
        $routes->post  ('auth/logout',   'AuthController::logout');
        $routes->get   ('auth/me',       'AuthController::me');
        $routes->put   ('auth/profile',  'AuthController::updateProfile');
        $routes->delete('auth/withdraw', 'AuthController::withdraw');
    });

    // 게시판·게시글·댓글 (선택적 인증 — 권한은 컨트롤러에서 판단)
    $routes->group('', ['filter' => 'jwt_optional'], static function ($routes) {
        $routes->get('boards',                                               'BoardController::index');
        $routes->get('boards/(:segment)',                                    'BoardController::show/$1');
        $routes->get('boards/(:segment)/articles',                          'ArticleController::index/$1');
        $routes->get('boards/(:segment)/articles/(:num)',                   'ArticleController::show/$1/$2');
        $routes->get('boards/(:segment)/articles/(:num)/comments',          'CommentController::index/$1/$2');
    });

    // 게시글·댓글 쓰기 (로그인 필요)
    $routes->group('', ['filter' => 'jwt'], static function ($routes) {
        $routes->post  ('boards/(:segment)/articles',                              'ArticleController::create/$1');
        $routes->put   ('boards/(:segment)/articles/(:num)',                       'ArticleController::update/$1/$2');
        $routes->delete('boards/(:segment)/articles/(:num)',                       'ArticleController::delete/$1/$2');
        $routes->post  ('boards/(:segment)/articles/(:num)/comments',              'CommentController::create/$1/$2');
        $routes->put   ('boards/(:segment)/articles/(:num)/comments/(:num)',       'CommentController::update/$1/$2/$3');
        $routes->delete('boards/(:segment)/articles/(:num)/comments/(:num)',       'CommentController::delete/$1/$2/$3');
    });

    // 파일
    $routes->get('files/(:num)/download', 'FileController::download/$1');
    $routes->group('', ['filter' => 'jwt'], static function ($routes) {
        $routes->post  ('files',           'FileController::upload');
        $routes->delete('files/(:num)',    'FileController::delete/$1');
        $routes->post  ('files/wysiwyg',   'WysiwygController::upload');
    });

    // 쪽지 (로그인 필요)
    $routes->group('messages', ['filter' => 'jwt'], static function ($routes) {
        $routes->get   ('inbox',      'MessageController::inbox');
        $routes->get   ('sent',       'MessageController::sent');
        $routes->get   ('(:num)',     'MessageController::show/$1');
        $routes->post  ('',           'MessageController::send');
        $routes->delete('(:num)',     'MessageController::delete/$1');
    });

    // 사이트 공개 설정
    $routes->get('config', 'ConfigController::index');

    // CMS (공개)
    $routes->get('cms/pages/(:segment)', 'Cms\PageController::show/$1');
    $routes->get('cms/banners',          'Cms\BannerController::index');
    $routes->get('cms/popups',           'Cms\PopupController::index');
    $routes->get('cms/menus',            'Cms\MenuController::index');
});

// ================================================================
// API v1 — 관리자용
// ================================================================
$routes->group('api/admin/v1', ['namespace' => 'App\Controllers\Api\V1\Admin'], static function ($routes) {

    // 관리자 로그인 (공개)
    $routes->post('auth/login', 'AuthController::login', ['filter' => 'rate_limit:10']);

    // 관리자 전용 (Admin JWT 필요)
    $routes->group('', ['filter' => 'admin_jwt'], static function ($routes) {
        $routes->post  ('auth/logout',         'AuthController::logout');
        $routes->get   ('boards',              'BoardController::index');
        $routes->put   ('boards/(:segment)',   'BoardController::update/$1');
        $routes->get   ('setting',             'SettingController::index');
        $routes->put   ('setting',             'SettingController::update');
        $routes->get   ('members',             'MemberController::index');
        $routes->put   ('members/(:num)',      'MemberController::update/$1');
        $routes->get   ('articles',            'ArticleController::index');
        $routes->get   ('articles/(:num)',     'ArticleController::show/$1');
        $routes->put   ('articles/(:num)',     'ArticleController::update/$1');
        $routes->delete('articles/(:num)',     'ArticleController::delete/$1');

        // 감사 로그
        $routes->get('logs', 'LogController::index');

        // 어드민 내부 공지
        $routes->get   ('notices',        'NoticeController::index');
        $routes->post  ('notices',        'NoticeController::create');
        $routes->delete('notices/(:num)', 'NoticeController::delete/$1');

        // 일별 통계
        $routes->get('stats', 'StatsController::index');

        // CMS 관리
        $routes->get   ('cms/pages',              'Cms\PageController::index');
        $routes->post  ('cms/pages',              'Cms\PageController::create');
        $routes->put   ('cms/pages/(:num)',        'Cms\PageController::update/$1');
        $routes->delete('cms/pages/(:num)',        'Cms\PageController::delete/$1');

        $routes->get   ('cms/banners',             'Cms\BannerController::index');
        $routes->post  ('cms/banners',             'Cms\BannerController::create');
        $routes->put   ('cms/banners/(:num)',       'Cms\BannerController::update/$1');
        $routes->delete('cms/banners/(:num)',       'Cms\BannerController::delete/$1');

        $routes->get   ('cms/popups',              'Cms\PopupController::index');
        $routes->get   ('cms/popups/(:num)',        'Cms\PopupController::show/$1');
        $routes->post  ('cms/popups',              'Cms\PopupController::create');
        $routes->put   ('cms/popups/(:num)',        'Cms\PopupController::update/$1');
        $routes->delete('cms/popups/(:num)',        'Cms\PopupController::delete/$1');

        $routes->get   ('cms/menus',               'Cms\MenuController::index');
        $routes->post  ('cms/menus',               'Cms\MenuController::create');
        $routes->put   ('cms/menus/reorder',        'Cms\MenuController::reorder');
        $routes->put   ('cms/menus/(:num)',          'Cms\MenuController::update/$1');
        $routes->delete('cms/menus/(:num)',          'Cms\MenuController::delete/$1');
    });
});
