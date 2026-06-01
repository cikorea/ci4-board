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
| **권한** | 그룹 기반 게시판별 읽기/쓰기/댓글 권한 제어 |
| **캐싱** | 메인 페이지 그룹별 5분 캐시 |

---

## 기술 스택

- **Backend** — PHP 8.2+, CodeIgniter 4.7+
- **Database** — MySQL 5.7+ / MariaDB 10.4+
- **Frontend** — Bootstrap 5.3.3, Bootstrap Icons 1.11.3 (CDN: jsDelivr)
- **Package Manager** — Composer
- **Test** — PHPUnit 10.5+

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

### 6. writable/ 디렉토리 권한 설정

실제 서버에 배포할 때는 `writable/` 디렉토리에 웹 서버 쓰기 권한이 필요합니다.

```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/   # Apache/Nginx
```

### 7. 개발 서버 실행

```bash
php spark serve
```

브라우저에서 `http://localhost:8080` 으로 접속합니다.

---

## 주요 접속 URL

| URL | 설명 |
|-----|------|
| `http://localhost:8080/` | 메인 페이지 |
| `http://localhost:8080/auth/login` | 로그인 |
| `http://localhost:8080/auth/register` | 회원가입 |
| `http://localhost:8080/board/free` | 자유게시판 |
| `http://localhost:8080/admin` | 관리자 패널 (최고관리자 전용) |

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
관리자 패널(`/admin/boards`)에서 설정합니다.

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

| 파일 | 설명 |
|------|------|
| `app/Database/Migrations/2026-05-29-000001_CreateInitialSchema.php` | 전체 테이블 생성 (29개) |
| `app/Database/Seeds/InitialSeeder.php` | 회원 그룹 · 관리자 계정 · 사이트 설정 · 게시판 13개 |

유용한 spark 명령어:

```bash
php spark migrate                    # 마이그레이션 실행
php spark migrate:rollback           # 마이그레이션 롤백
php spark migrate:status             # 마이그레이션 상태 확인
php spark db:seed InitialSeeder      # 초기 데이터 입력
```

---

## 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Controllers/        컨트롤러 (Auth, Board, Message, Admin, Home, File, Language)
│   ├── Models/             모델 (User, Bbs, Article, Comment, File, Message)
│   ├── Views/              뷰 템플릿
│   │   ├── layouts/        공통 레이아웃
│   │   ├── auth/           로그인 · 회원가입 · 프로필
│   │   ├── board/          게시판 목록 · 글보기 · 작성 · 수정
│   │   ├── message/        쪽지
│   │   └── admin/          관리자 패널
│   ├── Config/             설정 파일 (Routes, Filters, App, …)
│   ├── Filters/            필터 (Auth, Admin, Locale, NavData)
│   ├── Language/           다국어 파일 (ko / en / ja)
│   ├── Common.php          전역 헬퍼 함수 (권한 판정, 캐시 삭제 등)
│   └── Database/
│       ├── Migrations/     마이그레이션 파일
│       └── Seeds/          시더 파일
├── public/                 웹 루트 (index.php, API 문서)
├── writable/               캐시 · 로그 · 세션 · 업로드
├── docs/                   프로젝트 분석 문서
└── vendor/                 Composer 패키지
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

| 언어 | 언어 파일 |
|------|-----------|
| 한국어 | `app/Language/ko/App.php` |
| 영어 | `app/Language/en/App.php` |
| 일본어 | `app/Language/ja/App.php` |

---

## API 문서

컨트롤러와 모델에 [apiDoc](https://apidocjs.com/) 주석이 작성되어 있습니다.

```bash
# 빌드 (npx 사용)
npx apidoc -i app/ -o public/docs/
```

빌드 후 `http://localhost:8080/docs/` 에서 확인할 수 있습니다.

---

## 보안 주의사항

> **운영 환경 배포 전 반드시 확인하세요.**

- `CI_ENVIRONMENT`를 `production`으로 변경
- `.env` 파일이 웹에 노출되지 않도록 확인
- 초기 관리자 비밀번호(`admin1234`) 즉시 변경
- `app/Config/Filters.php`의 `csrf` 필터 활성화 (현재 주석 처리 상태)
- `writable/` 디렉토리 권한 최소화

---

## 라이선스

MIT License
