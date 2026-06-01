# 헤드리스 게시판 전환 설계 문서

> 작성일: 2026-06-01  
> 기반 문서: [project-analysis.md](./project-analysis.md)

---

## 1. 개요

### 현재 구조 (전통적 MVC)

```
클라이언트(브라우저)
    ↕ HTTP (HTML Form / 페이지 이동)
CodeIgniter 4 (MVC)
  → Controller → Model → View(PHP 템플릿)
  → 세션 기반 인증
  → HTML 응답 / redirect()
```

### 목표 구조 (헤드리스 API)

```
클라이언트 (React / Vue / 모바일앱 / 외부 서비스)
    ↕ HTTP (JSON / REST)
CodeIgniter 4 (API 서버)
  → API Controller → Model → JSON 응답
  → JWT 기반 인증
  → CORS 허용
```

헤드리스 전환의 핵심은 **서버가 뷰를 렌더링하지 않고 데이터만 반환**하는 것이다.  
프론트엔드는 완전히 분리된 독립 프로젝트로 관리하며, 서버는 순수 JSON API만 제공한다.

---

## 2. 변경 범위 요약

| 구분 | 현재 | 변경 후 |
|------|------|---------|
| 인증 | PHP 세션 | JWT (Bearer Token) |
| 응답 형식 | HTML (View 렌더링) | JSON |
| 에러 처리 | redirect() + 플래시 메시지 | HTTP 상태코드 + JSON |
| 라우팅 | `/board/:bbsId` (폼 기반) | `/api/v1/boards/:bbsId` (RESTful) |
| 언어 처리 | 세션 + LanguageController | `Accept-Language` 헤더 |
| CORS | 미설정 | 허용 Origin 명시 |
| 파일 다운로드 | 직접 스트리밍 | URL 반환 또는 Presigned URL |
| NavDataFilter | 전역 뷰 변수 주입 | 제거 (클라이언트가 별도 호출) |
| AdminFilter | 세션 group_name 비교 | JWT payload group_idx 비교 |

---

## 3. 인증 전환: 세션 → JWT

### 3.1 현재 세션 구조

```php
// 로그인 성공 시 세션에 저장
session()->set([
    'user_idx'   => $user['idx'],
    'user_id'    => $user['user_id'],
    'nickname'   => $user['nickname'],
    'email'      => $user['email'],
    'level'      => $user['level'],
    'group_idx'  => (int) $user['group_idx'],
    'group_name' => $user['group_name'],
    'logged_in'  => true,
]);
```

### 3.2 JWT 구조

```json
// Header
{ "alg": "HS256", "typ": "JWT" }

// Payload
{
  "sub":        1,
  "user_id":    "admin",
  "nickname":   "관리자",
  "email":      "admin@example.com",
  "group_idx":  1,
  "group_name": "최고관리자",
  "iat":        1748736000,
  "exp":        1748739600
}
```

### 3.3 토큰 전략

| 토큰 | 유효시간 | 용도 |
|------|----------|------|
| Access Token | 1시간 | API 요청 인증 헤더 |
| Refresh Token | 30일 | Access Token 재발급 |

Refresh Token은 `tb_users_token` 테이블에 저장하여 강제 만료(로그아웃, 탈퇴)를 지원한다.

### 3.4 추가 마이그레이션: tb_users_token

```sql
CREATE TABLE `tb_users_token` (
    `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
    `user_idx`      int unsigned    NOT NULL,
    `refresh_token` varchar(512)    NOT NULL,
    `expires_at`    int unsigned    NOT NULL,
    `client_ip`     varchar(64)     NOT NULL DEFAULT '',
    `created_at`    int unsigned    NOT NULL,
    `revoked`       tinyint(1)      NOT NULL DEFAULT 0,
    PRIMARY KEY (`idx`),
    KEY `idx_users_token__user_idx` (`user_idx`),
    KEY `idx_users_token__refresh_token` (`refresh_token`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='JWT Refresh Token';
```

### 3.5 필요 패키지

```bash
composer require firebase/php-jwt
```

---

## 4. 표준 JSON 응답 구조

모든 API 응답은 아래 형식을 따른다.

### 성공 응답

```json
{
    "success": true,
    "data": { ... },
    "message": null,
    "meta": {
        "page": 1,
        "per_page": 15,
        "total": 120,
        "last_page": 8
    }
}
```

### 실패 응답

```json
{
    "success": false,
    "data": null,
    "message": "로그인이 필요합니다.",
    "errors": ["필드별 유효성 오류 배열 (선택)"]
}
```

### HTTP 상태코드 규칙

| 상황 | 코드 |
|------|------|
| 성공 (조회) | 200 |
| 성공 (생성) | 201 |
| 유효성 오류 | 422 |
| 인증 실패 | 401 |
| 권한 없음 | 403 |
| 리소스 없음 | 404 |
| 서버 오류 | 500 |

### ApiResponse 트레이트 (신규 생성)

```php
// app/Traits/ApiResponse.php
trait ApiResponse
{
    protected function success(mixed $data = null, string $message = null, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ]);
    }

    protected function successList(array $data, array $meta): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
        ]);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors ?: null,
        ]);
    }
}
```

---

## 5. RESTful API 엔드포인트 설계

### 5.1 인증 (`/api/v1/auth`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `POST` | `/api/v1/auth/login` | 로그인 → Access/Refresh Token 반환 | 불필요 |
| `POST` | `/api/v1/auth/register` | 회원가입 | 불필요 |
| `POST` | `/api/v1/auth/logout` | 로그아웃 (Refresh Token 폐기) | 필요 |
| `POST` | `/api/v1/auth/refresh` | Access Token 재발급 | Refresh Token |
| `GET`  | `/api/v1/auth/me` | 내 정보 조회 | 필요 |
| `PUT`  | `/api/v1/auth/profile` | 프로필 수정 | 필요 |
| `DELETE` | `/api/v1/auth/withdraw` | 회원 탈퇴 | 필요 |

### 5.2 게시판 (`/api/v1/boards`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `GET` | `/api/v1/boards` | 접근 가능한 게시판 목록 | 선택 |
| `GET` | `/api/v1/boards/:bbsId` | 게시판 정보 + 권한 맵 | 선택 |

### 5.3 게시글 (`/api/v1/boards/:bbsId/articles`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `GET` | `/api/v1/boards/:bbsId/articles` | 글 목록 (페이지네이션, 검색) | 선택 |
| `POST` | `/api/v1/boards/:bbsId/articles` | 글 작성 | 필요 |
| `GET` | `/api/v1/boards/:bbsId/articles/:idx` | 글 상세 (조회수 증가) | 선택 |
| `PUT` | `/api/v1/boards/:bbsId/articles/:idx` | 글 수정 (본인) | 필요 |
| `DELETE` | `/api/v1/boards/:bbsId/articles/:idx` | 글 삭제 (본인) | 필요 |

### 5.4 댓글 (`/api/v1/boards/:bbsId/articles/:idx/comments`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `GET` | `.../comments` | 댓글 목록 | 선택 |
| `POST` | `.../comments` | 댓글 작성 | 필요 |
| `PUT` | `.../comments/:cIdx` | 댓글 수정 (본인) | 필요 |
| `DELETE` | `.../comments/:cIdx` | 댓글 삭제 (본인) | 필요 |

### 5.5 파일 (`/api/v1/files`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `POST` | `/api/v1/files` | 파일 업로드 (multipart/form-data) | 필요 |
| `GET` | `/api/v1/files/:idx/download` | 파일 다운로드 | 선택 |
| `DELETE` | `/api/v1/files/:idx` | 파일 삭제 (본인 또는 관리자) | 필요 |

### 5.6 쪽지 (`/api/v1/messages`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `GET` | `/api/v1/messages/inbox` | 받은 쪽지함 | 필요 |
| `GET` | `/api/v1/messages/sent` | 보낸 쪽지함 | 필요 |
| `GET` | `/api/v1/messages/:idx` | 쪽지 읽기 (읽음 처리) | 필요 |
| `POST` | `/api/v1/messages` | 쪽지 전송 | 필요 |
| `DELETE` | `/api/v1/messages/:idx` | 쪽지 삭제 | 필요 |

### 5.7 관리자 (`/api/v1/admin`)

| 메서드 | 경로 | 설명 | 인증 |
|--------|------|------|------|
| `GET` | `/api/v1/admin/boards` | 게시판 목록 | 관리자 |
| `PUT` | `/api/v1/admin/boards/:bbsId` | 게시판 설정 저장 | 관리자 |
| `GET` | `/api/v1/admin/setting` | 사이트 설정 조회 | 관리자 |
| `PUT` | `/api/v1/admin/setting` | 사이트 설정 저장 | 관리자 |
| `GET` | `/api/v1/admin/members` | 회원 목록 | 관리자 |
| `PUT` | `/api/v1/admin/members/:idx` | 회원 수정 | 관리자 |
| `GET` | `/api/v1/admin/articles` | 게시글 목록 | 관리자 |
| `PUT` | `/api/v1/admin/articles/:idx` | 게시글 수정 | 관리자 |
| `DELETE` | `/api/v1/admin/articles/:idx` | 게시글 삭제 | 관리자 |

---

## 6. 필터 변경

### 6.1 JwtFilter (신규 — AuthFilter 대체)

```php
// app/Filters/JwtFilter.php
class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['success' => false, 'message' => '인증이 필요합니다.']);
        }

        try {
            $payload = JwtService::decode($token);
            // 요청 객체에 사용자 정보 주입
            $request->setUserPayload($payload);
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['success' => false, 'message' => '유효하지 않은 토큰입니다.']);
        }
    }

    private function extractToken(RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
```

### 6.2 AdminJwtFilter (신규 — AdminFilter 대체)

```php
// app/Filters/AdminJwtFilter.php
// JwtFilter 통과 후 group_idx === 1 확인
if ($payload->group_idx !== 1) {
    return service('response')
        ->setStatusCode(403)
        ->setJSON(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
}
```

### 6.3 제거 대상 필터

| 필터 | 이유 |
|------|------|
| `NavDataFilter` | 뷰 공유 변수 주입 — 헤드리스에서 불필요 |
| `LocaleFilter` | 클라이언트가 `Accept-Language` 헤더로 처리 |
| `AuthFilter` | `JwtFilter`로 대체 |
| `AdminFilter` | `AdminJwtFilter`로 대체 |

### 6.4 CORS 필터 활성화

```php
// app/Config/Filters.php
public array $globals = [
    'before' => ['cors'],  // NavData, Locale 제거
    'after'  => ['cors'],
];
```

```php
// app/Config/Cors.php
public string $allowedOriginsPatterns = ['http://localhost:[0-9]+'];
public array  $allowedOrigins  = ['https://your-frontend.com'];
public array  $allowedHeaders  = ['Content-Type', 'Authorization', 'X-Requested-With'];
public array  $allowedMethods  = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
public bool   $supportsCredentials = true;
```

---

## 7. 컨트롤러별 변경 상세

### 7.1 AuthController

| 현재 메서드 | 변경 후 | 주요 변경점 |
|-------------|---------|------------|
| `login()` | 제거 | 뷰 불필요 |
| `loginProcess()` | `login()` | 세션 → JWT 발급, JSON 반환 |
| `register()` | 제거 | 뷰 불필요 |
| `registerProcess()` | `register()` | JSON 반환 |
| `logout()` | `logout()` | Refresh Token DB 폐기 |
| `profile()` | 제거 | 뷰 불필요 |
| `profileProcess()` | `updateProfile()` | PUT 메서드, JSON 반환 |
| `withdrawProcess()` | `withdraw()` | DELETE 메서드 |
| (신규) | `refresh()` | Refresh Token → 새 Access Token |
| (신규) | `me()` | 현재 사용자 정보 반환 |

**로그인 응답 예시:**

```json
{
    "success": true,
    "data": {
        "access_token":  "eyJ...",
        "refresh_token": "eyJ...",
        "token_type":    "Bearer",
        "expires_in":    3600,
        "user": {
            "idx":        1,
            "user_id":    "admin",
            "nickname":   "관리자",
            "email":      "admin@example.com",
            "group_idx":  1,
            "group_name": "최고관리자"
        }
    }
}
```

### 7.2 BoardController

| 현재 메서드 | 변경 후 | 주요 변경점 |
|-------------|---------|------------|
| `index()` | `list()` | JSON 페이지네이션 반환 |
| `view()` | `show()` | JSON 반환 |
| `write()` | 제거 | 뷰 불필요 |
| `writeProcess()` | `create()` | POST, 201 반환 |
| `edit()` | 제거 | 뷰 불필요 |
| `editProcess()` | `update()` | PUT 메서드 |
| `delete()` | `delete()` | DELETE 메서드 |
| `commentWrite()` | `createComment()` | POST, 201 반환 |
| `commentEdit()` | `updateComment()` | PUT 메서드 |
| `commentDelete()` | `deleteComment()` | DELETE 메서드 |

**글 목록 응답 예시:**

```json
{
    "success": true,
    "data": [
        {
            "idx":           42,
            "title":         "공지사항입니다",
            "nickname":      "관리자",
            "comment_count": 3,
            "hit_count":     120,
            "is_notice":     1,
            "timestamp_insert": 1748736000
        }
    ],
    "meta": {
        "page":      1,
        "per_page":  15,
        "total":     87,
        "last_page": 6
    }
}
```

### 7.3 FileController

| 현재 | 변경 후 |
|------|---------|
| `download()` — 파일 바이너리 스트리밍 | 유지 (바이너리 스트리밍은 그대로) |
| `delete()` — redirect() 반환 | JSON 반환으로 변경 |
| (없음) | `upload()` — POST `/api/v1/files`, 파일 업로드 후 메타 반환 |

### 7.4 제거 대상 컨트롤러

| 컨트롤러 | 이유 |
|----------|------|
| `HomeController` | 메인 페이지 뷰 — 클라이언트가 `/api/v1/boards` 호출로 대체 |
| `LanguageController` | 언어 전환 세션 저장 — `Accept-Language` 헤더로 대체 |
| `Home.php` | CI4 기본 컨트롤러 잔재 |

---

## 8. 권한 판정 변경

### 현재 (세션 기반)

```php
// Common.php
function current_group_idx(): int {
    return (int) (session()->get('group_idx') ?? 0);
}

function user_can_in_groups(array $allowedGroups): bool { ... }
```

### 변경 후 (JWT 기반)

```php
// Common.php 또는 JwtService
function current_group_idx(): int {
    // JwtFilter가 주입한 payload에서 읽음
    return (int) (service('request')->getUserPayload()?->group_idx ?? 0);
}
// user_can_in_groups() 로직 자체는 변경 없음
```

`Common.php`의 `user_can_in_groups()`, `parse_group_setting()`, `clear_home_cache()` 로직은 그대로 재사용 가능하다.

---

## 9. 신규 생성 파일 목록

```
app/
├── Controllers/Api/V1/
│   ├── AuthController.php       # 로그인·회원가입·토큰 재발급
│   ├── BoardController.php      # 게시판 조회
│   ├── ArticleController.php    # 게시글 CRUD
│   ├── CommentController.php    # 댓글 CRUD
│   ├── FileController.php       # 파일 업로드·다운로드·삭제
│   ├── MessageController.php    # 쪽지
│   └── Admin/
│       ├── BoardController.php  # 관리자 게시판 설정
│       ├── MemberController.php # 관리자 회원 관리
│       ├── ArticleController.php# 관리자 게시글 관리
│       └── SettingController.php# 관리자 사이트 설정
├── Filters/
│   ├── JwtFilter.php            # JWT 인증 필터
│   ├── JwtOptionalFilter.php    # 선택적 JWT (비로그인도 허용)
│   └── AdminJwtFilter.php       # 관리자 권한 필터
├── Services/
│   └── JwtService.php           # JWT 발급·검증·갱신
├── Traits/
│   └── ApiResponse.php          # 표준 JSON 응답 트레이트
└── Database/Migrations/
    └── 2026-xx-xx-000002_CreateUsersToken.php
```

---

## 10. 라우팅 설계 (Routes.php)

```php
// 기존 웹 라우트 유지 (선택) 또는 제거 후 API만 운영

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], function ($routes) {

    // 인증 (공개)
    $routes->post('auth/login',    'AuthController::login');
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/refresh',  'AuthController::refresh');

    // 인증 필요
    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        $routes->post  ('auth/logout',   'AuthController::logout');
        $routes->get   ('auth/me',       'AuthController::me');
        $routes->put   ('auth/profile',  'AuthController::updateProfile');
        $routes->delete('auth/withdraw', 'AuthController::withdraw');
    });

    // 게시판·게시글 (선택적 인증)
    $routes->group('', ['filter' => 'jwt_optional'], function ($routes) {
        $routes->get('boards',                                    'BoardController::index');
        $routes->get('boards/(:segment)',                         'BoardController::show/$1');
        $routes->get('boards/(:segment)/articles',               'ArticleController::index/$1');
        $routes->get('boards/(:segment)/articles/(:num)',        'ArticleController::show/$1/$2');
        $routes->get('boards/(:segment)/articles/(:num)/comments','CommentController::index/$1/$2');
    });

    // 게시판·게시글 (인증 필요)
    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        $routes->post  ('boards/(:segment)/articles',                      'ArticleController::create/$1');
        $routes->put   ('boards/(:segment)/articles/(:num)',               'ArticleController::update/$1/$2');
        $routes->delete('boards/(:segment)/articles/(:num)',               'ArticleController::delete/$1/$2');
        $routes->post  ('boards/(:segment)/articles/(:num)/comments',      'CommentController::create/$1/$2');
        $routes->put   ('boards/(:segment)/articles/(:num)/comments/(:num)','CommentController::update/$1/$2/$3');
        $routes->delete('boards/(:segment)/articles/(:num)/comments/(:num)','CommentController::delete/$1/$2/$3');
    });

    // 파일
    $routes->get ('files/(:num)/download', 'FileController::download/$1');
    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        $routes->post  ('files',         'FileController::upload');
        $routes->delete('files/(:num)',  'FileController::delete/$1');
    });

    // 쪽지 (인증 필요)
    $routes->group('messages', ['filter' => 'jwt'], function ($routes) {
        $routes->get   ('inbox',       'MessageController::inbox');
        $routes->get   ('sent',        'MessageController::sent');
        $routes->get   ('(:num)',      'MessageController::show/$1');
        $routes->post  ('',            'MessageController::send');
        $routes->delete('(:num)',      'MessageController::delete/$1');
    });

    // 관리자 (관리자 인증 필요)
    $routes->group('admin', ['filter' => 'admin_jwt', 'namespace' => 'App\Controllers\Api\V1\Admin'], function ($routes) {
        $routes->get   ('boards',              'BoardController::index');
        $routes->put   ('boards/(:segment)',   'BoardController::update/$1');
        $routes->get   ('setting',             'SettingController::index');
        $routes->put   ('setting',             'SettingController::update');
        $routes->get   ('members',             'MemberController::index');
        $routes->put   ('members/(:num)',      'MemberController::update/$1');
        $routes->get   ('articles',            'ArticleController::index');
        $routes->put   ('articles/(:num)',     'ArticleController::update/$1');
        $routes->delete('articles/(:num)',     'ArticleController::delete/$1');
    });
});
```

---

## 11. 다국어 처리 변경

### 현재

- `LocaleFilter`가 세션에서 locale을 읽어 `app()->setLocale()` 호출
- `LanguageController`가 `/lang/:locale`로 세션 저장

### 변경 후

```php
// app/Filters/LocaleFilter.php (수정)
public function before(RequestInterface $request, $arguments = null): void
{
    // 1순위: 쿼리 파라미터 ?lang=ko
    // 2순위: Accept-Language 헤더
    // 3순위: 기본값 ko
    $locale = $request->getGet('lang')
        ?? $this->parseAcceptLanguage($request->getHeaderLine('Accept-Language'))
        ?? 'ko';

    app()->setLocale(in_array($locale, ['ko', 'en', 'ja']) ? $locale : 'ko');
}
```

`LanguageController`는 제거한다.

---

## 12. 페이지네이션 표준화

### 현재 (ArticleModel 부산물)

```php
$articles = $this->article->getList($board['idx'], $keyword, 15);
// 별도로 _pagerTotal, _pagerPage, _pagerPerPage 참조
```

### 변경 후 (응답 meta로 통일)

```php
// 모든 목록 API 응답
return $this->successList($articles, [
    'page'      => $page,
    'per_page'  => $perPage,
    'total'     => $total,
    'last_page' => (int) ceil($total / $perPage),
]);
```

---

## 13. 보안 고려사항

### 13.1 JWT 보안

- Access Token 유효시간 짧게 유지 (1시간 권장)
- Refresh Token은 DB 저장하여 강제 폐기 가능하게 유지
- JWT Secret Key는 `.env`에 저장 (`jwt.secret`)
- HTTPS 강제 (`ForceHTTPS` 필터 활성화)

### 13.2 CSRF

API는 JWT로 인증하므로 CSRF 필터 불필요. 단, 웹뷰 혼용 시 별도 검토 필요.

### 13.3 Rate Limiting

로그인 엔드포인트에 Rate Limit 적용 권장:

```php
// app/Filters/RateLimitFilter.php (신규)
// ex) 분당 10회 초과 시 429 반환
```

### 13.4 파일 업로드

- 현재 확장자 체크는 유지
- Content-Type도 추가 검증 권장
- 업로드 경로는 `public/` 외부 유지 (현재 `writable/uploads/` → 유지)

---

## 14. 단계별 구현 계획

### Phase 1 — 기반 인프라 (1~2일)

1. `firebase/php-jwt` 패키지 설치
2. `JwtService` 클래스 작성
3. `JwtFilter`, `JwtOptionalFilter`, `AdminJwtFilter` 작성
4. `ApiResponse` 트레이트 작성
5. `tb_users_token` 마이그레이션 추가
6. `Config/Cors.php` CORS 설정
7. `Config/Filters.php` 필터 별칭 등록 + CORS 전역 적용

### Phase 2 — 인증 API (1일)

1. `Api/V1/AuthController` 작성
   - `login()`, `register()`, `logout()`, `refresh()`, `me()`, `updateProfile()`, `withdraw()`
2. `Common.php`의 `current_group_idx()` JWT payload 기반으로 수정

### Phase 3 — 게시판 API (2일)

1. `Api/V1/BoardController` (목록, 상세)
2. `Api/V1/ArticleController` (CRUD)
3. `Api/V1/CommentController` (CRUD)
4. `Api/V1/FileController` (업로드, 다운로드, 삭제)

### Phase 4 — 쪽지 + 관리자 API (1~2일)

1. `Api/V1/MessageController`
2. `Api/V1/Admin/*` 컨트롤러들

### Phase 5 — 라우팅 + 정리 (1일)

1. `Routes.php` API 라우트 추가
2. `LocaleFilter` Accept-Language 헤더 방식으로 수정
3. 불필요 컨트롤러/필터 제거 또는 레거시 보존 여부 결정
4. 기존 웹 라우트 제거 또는 병행 운영 결정

---

## 15. 기존 코드 재사용 범위

| 구성요소 | 재사용 여부 | 비고 |
|----------|------------|------|
| `BbsModel` | 그대로 재사용 | 변경 불필요 |
| `ArticleModel` | 그대로 재사용 | 변경 불필요 |
| `CommentModel` | 그대로 재사용 | 변경 불필요 |
| `FileModel` | 그대로 재사용 | 변경 불필요 |
| `MessageModel` | 그대로 재사용 | 변경 불필요 |
| `UserModel` | 그대로 재사용 | 변경 불필요 |
| `Common.php` | 부분 수정 | `current_group_idx()` 수정 |
| DB 스키마 | 그대로 유지 | `tb_users_token` 1개 추가 |
| `parse_group_setting()` | 그대로 재사용 | 변경 불필요 |
| `user_can_in_groups()` | 그대로 재사용 | 변경 불필요 |
| Views 전체 | 제거 또는 보존 | 헤드리스에서 불필요 |

**모델 레이어 전체를 그대로 재사용할 수 있다**는 것이 이 프로젝트의 전환 비용을 낮추는 핵심이다.
