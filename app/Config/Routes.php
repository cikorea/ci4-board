<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// 메인
$routes->get('/', 'HomeController::index');

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
