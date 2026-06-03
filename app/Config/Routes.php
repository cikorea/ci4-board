<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// 메인
$routes->get('/', 'HomeController::index');

// API 문서 (Swagger UI)
$routes->get('swagger', static function () {
    return redirect()->to('/docs/swagger.html');
});

// 언어 전환
$routes->get('lang/(:segment)', 'LanguageController::switchLocale/$1');

// 인증
$routes->get('auth/login',     'AuthController::login');
$routes->post('auth/login',    'AuthController::loginProcess');
$routes->get('auth/register',  'AuthController::register');
$routes->post('auth/register', 'AuthController::registerProcess');
$routes->get('auth/logout',    'AuthController::logout');

// 관리자
$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('',                    'AdminController::index');
    $routes->get('boards',              'AdminController::boards');
    $routes->get('boards/(:segment)',        'AdminController::boardEdit/$1');
    $routes->post('boards/(:segment)',       'AdminController::boardEditProcess/$1');
    $routes->get('setting',                  'AdminController::setting');
    $routes->post('setting',                 'AdminController::settingProcess');
    $routes->get('members',                  'AdminController::members');
    $routes->get('members/(:num)',           'AdminController::memberEdit/$1');
    $routes->post('members/(:num)',          'AdminController::memberEditProcess/$1');
    $routes->get('posts',                    'AdminController::posts');
    $routes->get('posts/(:num)/edit',        'AdminController::postEdit/$1');
    $routes->post('posts/(:num)/edit',       'AdminController::postEditProcess/$1');
    $routes->get('posts/(:num)/delete',      'AdminController::postDelete/$1');
});

// 파일
$routes->get('file/(:num)',        'FileController::download/$1');
$routes->get('file/(:num)/delete', 'FileController::delete/$1', ['filter' => 'auth']);

// 쪽지
$routes->group('message', ['filter' => 'auth'], static function ($routes) {
    $routes->get('',              'MessageController::inbox');
    $routes->get('sent',          'MessageController::sent');
    $routes->get('write',         'MessageController::write');
    $routes->post('write',        'MessageController::send');
    $routes->get('(:num)',        'MessageController::view/$1');
    $routes->get('(:num)/delete', 'MessageController::delete/$1');
});

// 회원정보 수정 - 로그인 필요
$routes->get('auth/profile',    'AuthController::profile',    ['filter' => 'auth']);
$routes->post('auth/profile',   'AuthController::profileProcess', ['filter' => 'auth']);
$routes->post('auth/withdraw',  'AuthController::withdrawProcess', ['filter' => 'auth']);

// API v1 — 소셜 로그인
$routes->group('api/v1/auth/social', static function ($routes) {
    $routes->get('google',          'Api\SocialAuthController::googleRedirect');
    $routes->get('google/callback', 'Api\SocialAuthController::googleCallback');
    $routes->get('kakao',           'Api\SocialAuthController::kakaoRedirect');
    $routes->get('kakao/callback',  'Api\SocialAuthController::kakaoCallback');
    $routes->get('naver',           'Api\SocialAuthController::naverRedirect');
    $routes->get('naver/callback',  'Api\SocialAuthController::naverCallback');
});

// 게시판 - 로그인 불필요
$routes->get('board/(:segment)',              'BoardController::index/$1');
$routes->get('board/(:segment)/view/(:num)', 'BoardController::view/$1/$2');

// 게시판 - 로그인 필요
$routes->group('board', ['filter' => 'auth'], static function ($routes) {
    $routes->get('(:segment)/write',                         'BoardController::write/$1');
    $routes->post('(:segment)/write',                        'BoardController::writeProcess/$1');
    $routes->get('(:segment)/edit/(:num)',                   'BoardController::edit/$1/$2');
    $routes->post('(:segment)/edit/(:num)',                  'BoardController::editProcess/$1/$2');
    $routes->get('(:segment)/delete/(:num)',                 'BoardController::delete/$1/$2');
    $routes->post('(:segment)/view/(:num)/comment',          'BoardController::commentWrite/$1/$2');
    $routes->get('(:segment)/view/(:num)/comment/(:num)/delete', 'BoardController::commentDelete/$1/$2/$3');
    $routes->post('(:segment)/view/(:num)/comment/(:num)/edit',   'BoardController::commentEdit/$1/$2/$3');
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
        $routes->post  ('files/wysiwyg',   'WysiwygController::upload');   // 에디터 이미지 업로드
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
