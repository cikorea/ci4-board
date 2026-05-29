# CI4 Board

CodeIgniter 4 기반의 PHP 게시판 웹 애플리케이션입니다.

## 주요 기능

- **회원 관리** — 회원가입, 로그인/로그아웃, 프로필 수정, 회원 탈퇴
- **게시판** — 다중 게시판 지원, 글 작성/수정/삭제, 댓글 작성/수정/삭제, 파일 첨부
- **쪽지** — 회원 간 쪽지 송수신, 받은 쪽지함/보낸 쪽지함
- **관리자** — 게시판 관리, 회원 관리, 게시글 관리, 사이트 설정

## 기술 스택

- **Backend** — PHP, CodeIgniter 4
- **Database** — MySQL
- **Package Manager** — Composer

## 설치 방법

### 요구 사항

- PHP 8.1 이상
- MySQL 5.7 이상
- Composer

### 설치

```bash
# 저장소 클론
git clone https://github.com/pushwing/ci4-board.git
cd ci4-board

# 의존성 설치
composer install

# 환경 설정 파일 복사
cp env .env
```

### 환경 설정

`.env` 파일을 열어 아래 항목을 설정합니다.

```ini
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = ci4_board
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
```

### 데이터베이스 마이그레이션

```bash
php spark migrate
```

### 개발 서버 실행

```bash
php spark serve
```

브라우저에서 `http://localhost:8080` 으로 접속합니다.

## 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Controllers/     # 컨트롤러
│   ├── Models/          # 모델
│   ├── Views/           # 뷰 템플릿
│   ├── Config/          # 설정 (라우팅 등)
│   ├── Filters/         # 인증 필터
│   └── Database/        # 마이그레이션
├── public/              # 웹 루트 (index.php)
├── writable/            # 캐시, 로그, 업로드
└── vendor/              # Composer 패키지
```

## 웹 서버 설정

웹 서버의 도큐먼트 루트를 `public/` 디렉토리로 지정해야 합니다.

**Apache** — `mod_rewrite` 활성화 필요  
**Nginx** — `public/index.php`로 요청을 전달하도록 설정

## 라이선스

MIT License
