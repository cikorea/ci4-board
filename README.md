# CI4 Board API Server

> **이 저장소는 REST API 서버입니다.** 웹 UI는 별도 프론트엔드 프로젝트(`ci4-board-web`, `ci4-board-admin`)에서 제공합니다.

CodeIgniter 4 기반의 PHP REST API 서버입니다.  
배강민, 전상민이 CodeIgniter 2 시절 만들었던 **tab bbs**의 데이터베이스 스키마를 기반으로, Claude Code를 이용해 CodeIgniter 4로 재작성했습니다.

> 작성자: 웅파 (blumine@gmail.com), 불의회상 (hoksi3k@gmail.com)

---

## API 문서

| | |
|---|---|
| **Swagger UI** | `http://localhost:8080/swagger` |
| **OpenAPI 스펙** | `http://localhost:8080/docs/openapi.yaml` |
| **API 레퍼런스** | [`docs/api-reference.md`](docs/api-reference.md) |

서버를 실행한 뒤 브라우저에서 `http://localhost:8080` 에 접속하면 자동으로 Swagger UI로 이동합니다.

---

## 프로젝트 구성

CI4 Board는 3개의 분리된 저장소로 구성됩니다.

```
ci4-board/          ← REST API 서버 (현재 저장소, PHP, :8080)
ci4-board-admin/    ← 관리자 SPA (React + Vite, :5173)
ci4-board-web/      ← 사용자 프론트 (Next.js, :3000)
```

```bash
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

## 주요 API 엔드포인트

### 사용자 API (`/api/v1/*`)

| 메서드 | 경로 | 인증 | 설명 |
|--------|------|------|------|
| POST | `/api/v1/auth/login` | — | 로그인 → JWT 발급 |
| POST | `/api/v1/auth/register` | — | 회원가입 |
| POST | `/api/v1/auth/refresh` | — | 토큰 갱신 |
| GET  | `/api/v1/boards` | — | 게시판 목록 |
| GET  | `/api/v1/boards/:id/articles` | — | 게시글 목록 |
| POST | `/api/v1/boards/:id/articles` | JWT | 게시글 작성 |
| GET  | `/api/v1/cms/menus` | — | 메뉴 트리 |

### 관리자 API (`/api/admin/v1/*`)

| 메서드 | 경로 | 설명 |
|--------|------|------|
| POST | `/api/admin/v1/auth/login` | 관리자 로그인 → Admin JWT |
| GET  | `/api/admin/v1/boards` | 게시판 목록 |
| GET  | `/api/admin/v1/members` | 회원 목록 |
| GET  | `/api/admin/v1/stats` | 일별 통계 |
| GET  | `/api/admin/v1/logs` | 감사 로그 |

전체 명세는 **[Swagger UI](http://localhost:8080/swagger)** 또는 [`docs/api-reference.md`](docs/api-reference.md) 참조.

---

## 기술 스택

- **Backend** — PHP 8.2+, CodeIgniter 4.7+
- **Database** — MySQL 5.7+ / MariaDB 10.4+ (서비스 DB + Admin DB 분리)
- **인증** — JWT (`firebase/php-jwt ^7.0`), OAuth2 (`league/oauth2-client`)
- **Package Manager** — Composer
- **Test** — PHPUnit 10.5+ (162개)
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

# Admin DB (관리자 전용 테이블 분리)
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
# KAKAO_REDIRECT_URI   = http://localhost:8080/api/v1/auth/social/kakao/callback
```

### 4. 데이터베이스 생성

```sql
-- 서비스 DB
CREATE DATABASE ci4_board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Admin DB
CREATE DATABASE ci4_board_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. 마이그레이션 실행

```bash
# 서비스 DB 마이그레이션
php spark migrate

# Admin DB 마이그레이션
php spark migrate -g admin

# 초기 데이터 (회원 그룹, 관리자 계정, 게시판, 사이트 설정)
php spark db:seed InitialSeeder
```

초기 관리자 계정:

| 항목 | 값 |
|------|----|
| 아이디 | `admin` |
| 비밀번호 | `admin1234` |
| 로그인 경로 | `POST /api/admin/v1/auth/login` |

> **⚠️ 보안 주의:** 운영 환경에서는 로그인 후 즉시 비밀번호를 변경하고, `jwt.secret`을 반드시 변경하세요.

### 6. 개발 서버 실행

```bash
php spark serve          # http://localhost:8080
```

브라우저에서 `http://localhost:8080` 접속 시 자동으로 Swagger UI로 이동합니다.

---

## REST API 인증

JWT Bearer Token 방식을 사용합니다.

```bash
# 1. 로그인으로 토큰 발급
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login_id":"user","password":"password"}'

# 2. 발급된 access_token으로 요청
curl http://localhost:8080/api/v1/boards \
  -H "Authorization: Bearer {access_token}"
```

| 토큰 | 유효시간 | 설명 |
|------|----------|------|
| Access Token | 1시간 | API 요청 인증 |
| Refresh Token | 30일 | Access Token 재발급 |

---

## 데이터베이스 구조

### 서비스 DB (`ci4_board`)

게시판, 회원, 댓글, 파일, 쪽지, CMS 등 서비스 테이블 전체를 담습니다.

### Admin DB (`ci4_board_admin`)

관리 기능 전용 테이블을 분리하여 서비스 DB와 격리합니다.

| 테이블 | 설명 |
|--------|------|
| `tb_admin_users` | 관리자 계정 (`role`: superadmin / manager) |
| `tb_admin_log` | 관리 행위 감사 로그 |
| `tb_admin_session` | 관리자 세션/토큰 |
| `tb_admin_notice` | 관리자 내부 공지 |
| `tb_site_config` | 사이트 전역 설정 |
| `tb_stats_daily` | 일별 통계 집계 |

---

## 소셜 로그인

Google, 네이버, 카카오 OAuth2 로그인을 지원합니다.

| 플랫폼 | 인증 시작 | 콜백 |
|--------|-----------|------|
| Google | `GET /api/v1/auth/social/google` | `/api/v1/auth/social/google/callback` |
| 네이버 | `GET /api/v1/auth/social/naver` | `/api/v1/auth/social/naver/callback` |
| 카카오 | `GET /api/v1/auth/social/kakao` | `/api/v1/auth/social/kakao/callback` |

---

## 마이그레이션

| 파일 | 대상 DB | 설명 |
|------|---------|------|
| `2026-05-29-000001_CreateInitialSchema` | default | 서비스 테이블 29개 |
| `2026-06-01-000001_CreateUsersSocialTable` | default | 소셜 로그인 연결 테이블 |
| `2026-06-01-000002_CreateUsersToken` | default | JWT Refresh Token |
| `2026-06-01-000003_CreateAdminSchema` | admin | Admin DB 테이블 4개 |
| `2026-06-01-000004_CreateCmsSchema` | default | CMS 테이블 4개 |
| `2026-06-03-000001_AddCompositeIndexes` | default | hot-path 복합 인덱스 |
| `2026-06-03-000002_CreateAdminUsersAndNotice` | admin | 관리자 계정·공지 테이블 |

```bash
php spark migrate              # 서비스 DB
php spark migrate -g admin     # Admin DB
php spark migrate:status       # 상태 확인
```

---

## 통계 집계

```bash
# 어제 통계 집계
php spark stats:collect

# 특정 날짜 집계
php spark stats:collect --date=2026-06-01
```

---

## 코드 품질

```bash
# 정적 분석
vendor/bin/phpstan analyse --level=3

# 테스트 (162개)
vendor/bin/phpunit --testdox
```

테스트 설정: [`phpunit.dist.xml`](phpunit.dist.xml) — `ci4_board_test` DB 필요

---

## 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Commands/              spark 커맨드 (통계 집계 등)
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
│   │   └── HomeController.php             API 랜딩 (→ Swagger)
│   ├── Filters/
│   │   ├── JwtFilter.php                  JWT 필수 인증
│   │   ├── JwtOptionalFilter.php          JWT 선택 인증
│   │   └── AdminJwtFilter.php             관리자 JWT 인증
│   ├── Models/
│   │   ├── Admin/                         Admin DB 전용 모델
│   │   └── ...
│   ├── Services/
│   │   ├── JwtService.php
│   │   └── *OAuthService.php
│   ├── Traits/
│   │   └── ApiResponse.php                표준 JSON 응답
│   ├── Views/
│   │   └── errors/                        에러 페이지 (프레임워크)
│   ├── Language/                          ko / en
│   └── Database/
│       ├── Migrations/
│       └── Seeds/
├── docs/
│   ├── api-reference.md                   API 엔드포인트 전체 명세
│   └── roadmap.md                         개발 로드맵
├── public/
│   └── docs/openapi.yaml                  OpenAPI 3.0 스펙
└── tests/unit/                            PHPUnit 테스트
```

---

## 웹 서버 설정

DocumentRoot를 `public/` 디렉터리로 지정합니다.

**Nginx**

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

## 보안 주의사항

- `CI_ENVIRONMENT`를 `production`으로 변경
- `jwt.secret`을 충분히 긴 랜덤 문자열로 변경
- 초기 관리자 비밀번호(`admin1234`) 즉시 변경
- `.env` 파일이 웹에 노출되지 않도록 확인
- `writable/` 디렉토리 권한 최소화

---

## 문서

| 파일 | 설명 |
|------|------|
| [`docs/api-reference.md`](docs/api-reference.md) | REST API 엔드포인트 전체 명세 |
| [`public/docs/openapi.yaml`](public/docs/openapi.yaml) | OpenAPI 3.0 스펙 |
| [`docs/roadmap.md`](docs/roadmap.md) | 개발 로드맵 |

---

## 라이선스

MIT License
