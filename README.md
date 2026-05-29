# CI4 Board

CodeIgniter 4 기반의 PHP 게시판 웹 애플리케이션입니다.  
배강민, 전상민이 CodeIgniter 2 시절 만들었던 **tab bbs**의 데이터베이스 스키마를 기반으로, Claude Code를 이용해 CodeIgniter 4로 재작성한 게시판입니다.

> 작성자: 웅파 (blumine@gmail.com)

---

## 주요 기능

| 기능 | 설명 |
|------|------|
| **회원** | 회원가입 · 로그인/로그아웃 · 프로필 수정 · 회원 탈퇴 |
| **게시판** | 다중 게시판 · 글 작성/수정/삭제 · 댓글 · 파일 첨부 · 태그 · 관련 링크 |
| **쪽지** | 회원 간 쪽지 송수신 · 받은 쪽지함 / 보낸 쪽지함 |
| **관리자** | 게시판 관리 · 회원 관리 · 게시물 관리 · 사이트 설정 |
| **다국어** | 한국어 / 영어 / 일본어 (CI4 Localization, 세션 기반 전환) |

---

## 기술 스택

- **Backend** — PHP 8.2+, CodeIgniter 4
- **Database** — MySQL 5.7+ / MariaDB 10.4+
- **Frontend** — Bootstrap 5.3, Bootstrap Icons
- **Package Manager** — Composer

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

database.default.hostname = localhost
database.default.database = ci4_board
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBCollat  = utf8mb4_general_ci
```

### 4. 데이터베이스 생성

MySQL에서 데이터베이스를 먼저 생성합니다.

```sql
CREATE DATABASE ci4_board CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 5. 마이그레이션 실행

```bash
# 테이블 생성
php spark migrate

# 초기 데이터 입력 (회원 그룹, 관리자 계정, 게시판, 사이트 설정)
php spark db:seed InitialSeeder
```

초기 관리자 계정:

| 항목 | 값 |
|------|----|
| 아이디 | `admin` |
| 비밀번호 | `admin1234` |

> **⚠️ 보안 주의:** 운영 환경에서는 로그인 후 즉시 비밀번호를 변경하세요.

### 6. 개발 서버 실행

```bash
php spark serve
```

브라우저에서 `http://localhost:8080` 으로 접속합니다.

---

## 마이그레이션 & 시더

| 파일 | 설명 |
|------|------|
| `app/Database/Migrations/2026-05-29-000001_CreateInitialSchema.php` | 전체 테이블 생성 (30개) |
| `app/Database/Seeds/InitialSeeder.php` | 회원 그룹 · 관리자 계정 · 사이트 설정 · 게시판 13개 |

유용한 spark 명령어:

```bash
php spark migrate           # 마이그레이션 실행
php spark migrate:rollback  # 마이그레이션 롤백
php spark migrate:status    # 마이그레이션 상태 확인
php spark db:seed InitialSeeder  # 초기 데이터 입력
```

---

## 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Controllers/        컨트롤러 (Auth, Board, Message, Admin, …)
│   ├── Models/             모델 (User, Article, Comment, …)
│   ├── Views/              뷰 템플릿
│   │   ├── layouts/        공통 레이아웃
│   │   ├── auth/           로그인 · 회원가입 · 프로필
│   │   ├── board/          게시판 목록 · 글보기 · 작성 · 수정
│   │   ├── message/        쪽지
│   │   └── admin/          관리자 패널
│   ├── Config/             설정 파일 (Routes, Filters, App, …)
│   ├── Filters/            필터 (Auth, Admin, Locale)
│   ├── Language/           다국어 파일 (ko / en / ja)
│   └── Database/
│       ├── Migrations/     마이그레이션 파일
│       └── Seeds/          시더 파일
├── public/                 웹 루트 (index.php)
├── writable/               캐시 · 로그 · 세션 · 업로드
└── vendor/                 Composer 패키지
```

---

## 웹 서버 설정

웹 서버의 DocumentRoot를 `public/` 디렉터리로 지정해야 합니다.

**Apache** — `mod_rewrite` 활성화 필요 (`public/.htaccess` 포함)

**Nginx** — 아래 설정 참고:

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

| 언어 | 언어 파일 |
|------|-----------|
| 한국어 | `app/Language/ko/App.php` |
| 영어 | `app/Language/en/App.php` |
| 일본어 | `app/Language/ja/App.php` |

---

## 라이선스

MIT License
