# CI4 Board

CodeIgniter 4 기반의 PHP 게시판 웹 애플리케이션 + REST API 서버입니다.  
배강민, 전상민이 CodeIgniter 2 시절 만들었던 **tab bbs**의 데이터베이스 스키마를 기반으로, Claude Code를 이용해 CodeIgniter 4로 재작성한 게시판입니다.

> 작성자: 웅파 (blumine@gmail.com), 불의회상 (hoksi3k@gmail.com)

---

## 프로젝트 구성

CI4 Board는 3개의 분리된 프로젝트로 구성됩니다.

```
ci4-board/          ← CI4 API 서버 (현재 저장소, PHP, :8080)
ci4-board-admin/    ← 관리자 SPA (React + Vite, :5173)
ci4-board-web/      ← 사용자 프론트 (Next.js, :3000)
```

```
# 개발 시 3개 서버 동시 실행
cd ci4-board       && php spark serve    # API 서버 (:8080)
cd ci4-board-admin && npm run dev        # 관리자 (:5173)
cd ci4-board-web   && npm run dev        # 사용자 프론트 (:3000)
```

| 저장소 | 역할 | API 연결 |
|--------|------|---------|
| `ci4-board` | REST API 서버 | — |
| `ci4-board-admin` | 관리자 대시보드 (CSR SPA) | `/api/admin/v1/*` (Admin JWT) |
| `ci4-board-web` | 사용자 프론트 (SSR/ISR) | `/api/v1/*` (공개 + User JWT) |

---

## 주요 기능

| 기능 | 설명 |
|------|------|
| **회원** | 회원가입 · 로그인/로그아웃 · 프로필 수정 · 회원 탈퇴 |
| **게시판** | 다중 게시판 · 글 작성/수정/삭제 · 댓글 · 파일 첨부 · 태그 · 관련 링크 |
| **쪽지** | 회원 간 쪽지 송수신 · 받은 쪽지함 / 보낸 쪽지함 |
| **관리자** | 게시판 관리 · 회원 관리 · 게시물 관리 · 사이트 설정 |
| **다국어** | 한국어 / 영어 / 일본어 (CI4 Localization, 세션 기반 전환) |
| **권한** | 그룹 기반 게시판별 읽기/쓰기/댓글 권한 제어 |
| **캐싱** | 메인 페이지 그룹별 5분 캐시 |
| **REST API** | JWT 인증 기반 헤드리스 API (`/api/v1/*`, `/api/admin/v1/*`) |
| **소셜 로그인** | Google · 네이버 · 카카오 OAuth2 |
| **CMS** | 페이지 · 배너(위치별·기간 설정) · 팝업 · 메뉴 관리 |

---

## 기술 스택

- **Backend** — PHP 8.2+, CodeIgniter 4.7+
- **Database** — MySQL 5.7+ / MariaDB 10.4+
- **Frontend** — Bootstrap 5.3.3, Bootstrap Icons 1.11.3 (CDN: jsDelivr)
- **인증** — JWT (`firebase/php-jwt ^7.0`), OAuth2 (`league/oauth2-client`)
- **Package Manager** — Composer
- **Test** — PHPUnit 10.5+
- **정적 분석** — PHPStan 2.2+ (레벨 3)

---

## 설치

### 1. 요구 사항

- PHP 8.2 이상 (mbstring, pdo, pdo_mysql, intl 확장 필요)
- MySQL 5.7 이상 (또는 MariaDB 10.4 이상)
- Composer

### 2. 저장소 클론 및 의존성 설치

```bash
git clone https://github.com/pushwing/ci4-board.git
cd ci4-board
composer install
```

### 3. 환경 설정

```bash
cp env .env
```

`.env` 파일을 열어 아래 항목을 수정합니다.

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

# 서비스 DB
database.default.hostname = localhost
database.default.database = ci4_board
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBCollat  = utf8mb4_general_ci

# Admin DB (별도 DB 사용 시)
database.admin.hostname = localhost
database.admin.database = ci4_board_admin
database.admin.username = root
database.admin.password =
database.admin.DBDriver = MySQLi

# JWT (반드시 변경)
jwt.secret = your-secret-key-change-this-in-production

# 소셜 로그인 (사용 시 설정)
# GOOGLE_CLIENT_ID     = your-google-client-id
# GOOGLE_CLIENT_SECRET = your-google-client-secret
# GOOGLE_REDIRECT_URI  = http://localhost:8080/api/v1/auth/social/google/callback
# NAVER_CLIENT_ID      = your-naver-client-id
# NAVER_CLIENT_SECRET  = your-naver-client-secret
# NAVER_REDIRECT_URI   = http://localhost:8080/api/v1/auth/social/naver/callback
# KAKAO_CLIENT_ID      = your-kakao-rest-api-key
# KAKAO_CLIENT_SECRET  =
# KAKAO_REDIRECT_URI   = http://localhost:8080/api/v1/auth/social/kakao/callback
```

### 4. 데이터베이스 생성

```sql
-- 서비스 DB
CREATE DATABASE ci4_board CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Admin DB (선택)
CREATE DATABASE ci4_board_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 5. 마이그레이션 실행

```bash
# 서비스 DB + Admin DB 테이블 생성 (한 번에 실행)
php spark migrate

# 초기 데이터 입력 (회원 그룹, 관리자 계정, 게시판, 사이트 설정)
php spark db:seed InitialSeeder
```

> `CreateAdminSchema` 마이그레이션은 `$DBGroup = 'admin'`으로 설정되어 있어 `spark migrate` 한 번으로 Admin DB에도 자동 적용됩니다. `.env`에 `database.admin.*` 값을 반드시 설정하세요.

초기 관리자 계정:

| 항목 | 값 |
|------|----|
| 아이디 | `admin` |
| 비밀번호 | `admin1234` |

> **⚠️ 보안 주의:** 운영 환경에서는 로그인 후 즉시 비밀번호를 변경하고, `jwt.secret`을 반드시 변경하세요.

### 6. writable/ 디렉토리 권한 설정

실제 서버에 배포할 때는 `writable/` 디렉토리에 웹 서버 쓰기 권한이 필요합니다.

```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/   # Apache/Nginx
```

### 7. 개발 서버 실행

CodeIgniter 내장 개발 서버(`php spark serve`)를 사용합니다.

```bash
# 기본 실행 (host: localhost, port: 8080)
php spark serve

# 호스트·포트 지정
php spark serve --host 0.0.0.0 --port 8000

# PHP 버전 지정
php spark serve --php /usr/bin/php8.3
```

| 옵션 | 기본값 | 설명 |
|------|--------|------|
| `--host` | `localhost` | 바인딩 호스트 |
| `--port` | `8080` | 바인딩 포트 |
| `--php` | 시스템 기본 PHP | 사용할 PHP 실행 파일 경로 |

브라우저에서 `http://localhost:8080` 으로 접속합니다.

> **참고:** `php spark serve`는 개발 전용입니다. 운영 환경에서는 Apache 또는 Nginx를 사용하세요.

---

## 주요 접속 URL

### 웹 UI

| URL | 설명 |
|-----|------|
| `http://localhost:8080/` | 메인 페이지 |
| `http://localhost:8080/auth/login` | 로그인 |
| `http://localhost:8080/auth/register` | 회원가입 |
| `http://localhost:8080/board/free` | 자유게시판 |
| `http://localhost:8080/admin` | 관리자 패널 (최고관리자 전용) |

### REST API

| URL | 설명 |
|-----|------|
| `POST http://localhost:8080/api/v1/auth/login` | 로그인 → JWT 발급 |
| `GET  http://localhost:8080/api/v1/boards` | 게시판 목록 |
| `GET  http://localhost:8080/api/v1/boards/free/articles` | 게시글 목록 |
| `POST http://localhost:8080/api/admin/v1/auth/login` | 관리자 로그인 |

전체 API 명세: [`docs/api-reference.md`](docs/api-reference.md) 또는 **[Swagger UI](#api-문서-swagger-ui)** 참조

---

## REST API 인증

JWT Bearer Token 방식을 사용합니다.

```bash
# 1. 로그인으로 토큰 발급
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login_id":"admin","password":"admin1234"}'

# 2. 발급된 access_token으로 요청
curl -X GET http://localhost:8080/api/v1/boards \
  -H "Authorization: Bearer {access_token}"
```

| 토큰 | 유효시간 | 설명 |
|------|----------|------|
| Access Token | 1시간 | API 요청 인증 |
| Refresh Token | 30일 | Access Token 재발급 |

---

## 소셜 로그인

Google, 네이버, 카카오 OAuth2 로그인을 지원합니다.

| 플랫폼 | 인증 시작 URL | 콜백 URL |
|--------|--------------|---------|
| Google | `GET /api/v1/auth/social/google` | `/api/v1/auth/social/google/callback` |
| 네이버 | `GET /api/v1/auth/social/naver` | `/api/v1/auth/social/naver/callback` |
| 카카오 | `GET /api/v1/auth/social/kakao` | `/api/v1/auth/social/kakao/callback` |

각 플랫폼 개발자 콘솔에서 앱을 등록하고 `.env`에 `GOOGLE_CLIENT_ID` 등 Client ID/Secret을 설정하세요.

---

## 권한 시스템

그룹 기반으로 게시판별 읽기/쓰기 권한을 제어합니다.

| group_idx | 그룹명 | 설명 |
|-----------|--------|------|
| 0 | 비회원 | 미로그인 사용자 |
| 1 | 최고관리자 | 관리자 패널 접근, 모든 게시판 관리 |
| 2 | 일반회원 | 기본 가입 그룹 |
| 3 | 개발자 | 개발자 전용 그룹 |

게시판별로 **목록 보기 / 글 보기 / 글 쓰기 / 댓글 쓰기** 권한을 그룹 단위로 설정할 수 있습니다.  
웹 관리자 패널(`/admin/boards`) 또는 Admin API(`PUT /api/admin/v1/boards/:bbsId`)에서 설정합니다.

---

## 기본 게시판

초기 시더(`InitialSeeder`) 실행 시 아래 13개 게시판이 생성됩니다.

| bbs_id | 게시판명 | 열람 | 쓰기 |
|--------|----------|------|------|
| notice | 공지사항 | 전체 | 회원 |
| news | 새소식 | 전체 | 회원 |
| free | 자유게시판 | 전체 | 회원 |
| qna | CodeIgniter Q&A | 전체 | 회원 |
| source | 소스코드 공유 | 전체 | 회원 |
| tip | 팁 & 강좌 | 전체 | 회원 |
| etc_qna | 기타 Q&A | 전체 | 회원 |
| file | 자료실 | 전체 | 회원 |
| ad | 홍보게시판 | 전체 | 회원 |
| job | 구인구직 | 전체 | 회원 |
| cibook | CI 도서 | 전체 | 회원 |
| su | 운영자 게시판 | 관리자 | 관리자 |
| ci | 포럼 개발자 | 관리자 | 관리자+개발자 |

---

## 파일 업로드

| 항목 | 제한 |
|------|------|
| 게시글당 최대 파일 수 | 5개 |
| 파일당 최대 크기 | 2MB |
| 허용 확장자 | jpg, jpeg, gif, png, txt, doc, docx, xls, xlsx, pdf, ppt, pptx, zip, 7z, alz, rar |
| 저장 경로 | `writable/uploads/{게시판idx}/{날짜}/{랜덤파일명}.{ext}` |

---

## 마이그레이션 & 시더

| 파일 | 대상 DB | 설명 |
|------|---------|------|
| `2026-05-29-000001_CreateInitialSchema.php` | default | 서비스 테이블 29개 생성 |
| `2026-06-01-000001_CreateUsersSocialTable.php` | default | 소셜 로그인 연결 테이블 |
| `2026-06-01-000002_CreateUsersToken.php` | default | JWT Refresh Token 테이블 |
| `2026-06-01-000003_CreateAdminSchema.php` | admin | 어드민 전용 테이블 4개 |
| `2026-06-01-000004_CreateCmsSchema.php` | default | CMS 테이블 4개 (Phase 6) |
| `app/Database/Seeds/InitialSeeder.php` | default | 회원 그룹·관리자·게시판·설정 |

유용한 spark 명령어:

```bash
php spark migrate                        # 전체 마이그레이션 (default + admin 자동 적용)
php spark migrate:rollback               # 마이그레이션 롤백
php spark migrate:status                 # 마이그레이션 상태 확인
php spark db:seed InitialSeeder          # 초기 데이터 입력
```

---

## 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── SocialAuthController.php   소셜 로그인 콜백
│   │   │   └── V1/                        REST API 컨트롤러
│   │   │       ├── Admin/                 관리자 API
│   │   │       ├── AuthController.php
│   │   │       ├── ArticleController.php
│   │   │       ├── BoardController.php
│   │   │       ├── CommentController.php
│   │   │       ├── FileController.php
│   │   │       └── MessageController.php
│   │   ├── AdminController.php            웹 관리자 패널
│   │   ├── AuthController.php             웹 인증
│   │   ├── BoardController.php            웹 게시판
│   │   └── ...
│   ├── Filters/
│   │   ├── JwtFilter.php                  JWT 필수 인증
│   │   ├── JwtOptionalFilter.php          JWT 선택 인증
│   │   ├── AdminJwtFilter.php             관리자 JWT 인증
│   │   ├── AuthFilter.php                 웹 세션 인증
│   │   ├── AdminFilter.php                웹 관리자 인증
│   │   ├── LocaleFilter.php               언어 설정
│   │   └── NavDataFilter.php              네비게이션 데이터 주입
│   ├── Models/
│   │   ├── Admin/                         Admin DB 전용 모델
│   │   ├── ArticleModel.php
│   │   ├── BbsModel.php
│   │   ├── CommentModel.php
│   │   ├── FileModel.php
│   │   ├── MessageModel.php
│   │   ├── SocialUserModel.php
│   │   ├── UserModel.php
│   │   └── UserTokenModel.php
│   ├── Services/
│   │   ├── JwtService.php                 JWT 발급·검증
│   │   ├── GoogleOAuthService.php         Google OAuth2
│   │   ├── KakaoOAuthService.php          카카오 OAuth2
│   │   └── NaverOAuthService.php          네이버 OAuth2
│   ├── Traits/
│   │   └── ApiResponse.php                표준 JSON 응답
│   ├── Views/                             웹 UI 뷰 템플릿
│   ├── Config/                            설정 파일
│   ├── Language/                          다국어 파일 (ko / en / ja)
│   ├── Common.php                         전역 헬퍼 함수
│   └── Database/
│       ├── Migrations/
│       └── Seeds/
├── docs/                                  프로젝트 문서
│   ├── api-reference.md                   API 엔드포인트 명세
│   ├── headless-board-design.md           헤드리스 전환 설계
│   ├── project-analysis.md                프로젝트 구조 분석
│   └── roadmap.md                         개발 로드맵
├── public/                                웹 루트 (index.php)
├── writable/                              캐시 · 로그 · 세션 · 업로드
└── vendor/                                Composer 패키지
```

---

## 웹 서버 설정

웹 서버의 DocumentRoot를 `public/` 디렉터리로 지정해야 합니다.

**Apache** — `mod_rewrite` 활성화 필요 (`public/.htaccess` 포함)

**Nginx** — 아래 설정 참고 (`php-fpm` 소켓 경로는 환경에 맞게 수정):

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/ci4-board/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 다국어 지원

네비게이션 바 우측의 **KO / EN / JA** 드롭다운에서 언어를 전환합니다.  
선택한 언어는 세션에 저장되어 페이지 이동 후에도 유지됩니다.

### UI 메시지

| 언어 | 언어 파일 |
|------|-----------|
| 한국어 | `app/Language/ko/App.php` |
| 영어 | `app/Language/en/App.php` |
| 일본어 | `app/Language/ja/App.php` |

### API 응답 메시지

API 컨트롤러의 모든 응답 메시지는 `lang('Api.key')` 형식으로 관리됩니다.

| 언어 | 언어 파일 | 키 수 |
|------|-----------|-------|
| 한국어 | `app/Language/ko/Api.php` | 131개 |
| 영어 | `app/Language/en/Api.php` | 131개 |

```php
// 단순 메시지
return $this->failNotFound(lang('Api.article_not_found'));

// 동적 메시지 (원문 주석으로 의미 명시)
// "게시판 '{0}'을 찾을 수 없습니다."
return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
```

> 일본어(`ja`) API 메시지 파일은 추후 추가 예정입니다.

---

## CMS API (Phase 6)

어드민과 프론트에서 사용하는 CMS 관련 엔드포인트입니다.

### 어드민 API (Admin JWT 필요)

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/api/admin/v1/cms/pages` | 페이지 목록 |
| POST | `/api/admin/v1/cms/pages` | 페이지 생성 |
| PUT | `/api/admin/v1/cms/pages/:idx` | 페이지 수정 |
| DELETE | `/api/admin/v1/cms/pages/:idx` | 페이지 삭제 |
| GET | `/api/admin/v1/cms/banners` | 배너 목록 |
| POST | `/api/admin/v1/cms/banners` | 배너 생성 |
| PUT | `/api/admin/v1/cms/banners/:idx` | 배너 수정 |
| DELETE | `/api/admin/v1/cms/banners/:idx` | 배너 삭제 |
| GET | `/api/admin/v1/cms/popups` | 팝업 목록 |
| GET | `/api/admin/v1/cms/popups/:idx` | 팝업 단건 조회 (수정 폼 로드용) |
| POST | `/api/admin/v1/cms/popups` | 팝업 생성 |
| PUT | `/api/admin/v1/cms/popups/:idx` | 팝업 수정 |
| DELETE | `/api/admin/v1/cms/popups/:idx` | 팝업 삭제 |
| GET | `/api/admin/v1/cms/menus` | 메뉴 트리 조회 |
| POST | `/api/admin/v1/cms/menus` | 메뉴 생성 |
| PUT | `/api/admin/v1/cms/menus/reorder` | 메뉴 순서 일괄 변경 |
| PUT | `/api/admin/v1/cms/menus/:idx` | 메뉴 수정 |
| DELETE | `/api/admin/v1/cms/menus/:idx` | 메뉴 삭제 |

### 프론트 API (공개)

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/api/v1/cms/pages/:slug` | 발행된 페이지 조회 |
| GET | `/api/v1/cms/banners?position=코드` | 활성 배너 목록 (기간 필터 적용) |
| GET | `/api/v1/cms/popups` | 활성 팝업 목록 (기간 필터 적용) |
| GET | `/api/v1/cms/menus` | 사용 중인 메뉴 트리 |

### CMS 테이블

| 테이블 | 설명 |
|--------|------|
| `tb_cms_page` | 정적 페이지 (slug, title, contents, status) |
| `tb_cms_banner` | 배너 (position, image_path, link_url, start_at, end_at) |
| `tb_cms_popup` | 팝업 (title, contents, start_at, end_at, position) |
| `tb_cms_menu` | 메뉴 (parent_idx 자기참조, label, url, target, sequence) |

---

## 코드 품질

### 정적 분석 (PHPStan 레벨 3)

```bash
vendor/bin/phpstan analyse --level=3
```

설정 파일: `phpstan.neon` (분석 대상: `app/`, Views 제외)

### 테스트 (PHPUnit)

**총 149개 테스트** (단위 14개 + 통합 135개)

```bash
# 전체 실행
php vendor/bin/phpunit

# 특정 파일
php vendor/bin/phpunit tests/unit/Api/V1/Auth/UserAuthApiTest.php
```

테스트 DB 설정 및 상세 가이드: **[docs/testing.md](docs/testing.md)**

> **최초 실행 전** 테스트 DB(`ci4_board_test`) 생성 및 마이그레이션이 필요합니다.  
> 자세한 방법은 [테스트 가이드](docs/testing.md#2-테스트-db-초기화)를 참고하세요.

### API 문서 (Swagger UI)

개발 서버 실행 후 브라우저에서 접근:

```
http://localhost:8080/swagger
```

| 경로 | 설명 |
|------|------|
| `/swagger` | Swagger UI (인터랙티브, 30개 엔드포인트) |
| `/docs/openapi.yaml` | OpenAPI 3.0 원본 스펙 |

```bash
# 스펙 유효성 검사
php spark swagger:generate --validate

# PHP 애노테이션에서 스펙 재생성 (애노테이션 작성 후)
php spark swagger:generate --yaml
```

---

## 문서

| 파일 | 설명 |
|------|------|
| [`docs/api-reference.md`](docs/api-reference.md) | REST API 엔드포인트 전체 명세 |
| [`public/docs/openapi.yaml`](public/docs/openapi.yaml) | OpenAPI 3.0 스펙 (Swagger UI 원본) |
| [`docs/testing.md`](docs/testing.md) | 통합 테스트 가이드 (환경 설정·실행·CI) |
| [`docs/headless-board-design.md`](docs/headless-board-design.md) | 헤드리스 전환 설계 문서 |
| [`docs/project-analysis.md`](docs/project-analysis.md) | 프로젝트 구조 분석 |
| [`docs/roadmap.md`](docs/roadmap.md) | 개발 로드맵 |

---

## 보안 주의사항

> **운영 환경 배포 전 반드시 확인하세요.**

- `CI_ENVIRONMENT`를 `production`으로 변경
- `.env` 파일이 웹에 노출되지 않도록 확인
- 초기 관리자 비밀번호(`admin1234`) 즉시 변경
- `jwt.secret`을 충분히 긴 랜덤 문자열로 변경
- `app/Config/Filters.php`의 `csrf` 필터 활성화 (현재 주석 처리 상태)
- `writable/` 디렉토리 권한 최소화
- 소셜 로그인 Client Secret은 `.env`에만 저장, 코드에 하드코딩 금지

---

## 라이선스

MIT License
