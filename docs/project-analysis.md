# ci4-board 프로젝트 분석 문서

> 최초 작성일: 2026-06-01  
> 최종 업데이트: 2026-06-01  
> 대상 브랜치: master

---

## 1. 프로젝트 개요

| 항목 | 내용 |
|------|------|
| 프로젝트명 | ci4-board |
| 프레임워크 | CodeIgniter 4.7+ |
| PHP 요구 버전 | ^8.2 |
| DB 엔진 | MySQL / InnoDB, utf8mb4 |
| 패턴 | MVC + REST API (헤드리스 겸용) |
| 목적 | 다중 게시판 커뮤니티 웹 + REST API 서버 |

CodeIgniter 4 기반의 한국형 게시판(BBS) 시스템이다. 전통적 MVC 웹 UI와 JWT 기반 REST API를 동시에 제공하는 헤드리스 겸용 구조로 운영된다.

---

## 2. 기술 스택

| 구분 | 기술 |
|------|------|
| Backend | PHP 8.2+, CodeIgniter 4.7+ |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend (웹) | Bootstrap 5.3.3, Bootstrap Icons 1.11.3 (CDN: jsDelivr) |
| 인증 | JWT (`firebase/php-jwt ^7.0`), OAuth2 (`league/oauth2-client`) |
| 정적 분석 | PHPStan 2.2+ (레벨 3) |
| 테스트 | PHPUnit 10.5+ |

---

## 3. 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Config/              # 프레임워크 설정 (Routes, Filters, Database 등)
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── SocialAuthController.php   # 소셜 로그인 콜백 (Google/네이버/카카오)
│   │   │   └── V1/                        # REST API v1
│   │   │       ├── Admin/                 # 관리자 API 컨트롤러
│   │   │       ├── AuthController.php
│   │   │       ├── ArticleController.php
│   │   │       ├── BoardController.php
│   │   │       ├── CommentController.php
│   │   │       ├── FileController.php
│   │   │       └── MessageController.php
│   │   ├── AdminController.php            # 웹 관리자 패널
│   │   ├── AuthController.php             # 웹 인증
│   │   ├── BoardController.php            # 웹 게시판
│   │   ├── FileController.php
│   │   ├── HomeController.php
│   │   ├── LanguageController.php
│   │   └── MessageController.php
│   ├── Database/
│   │   ├── Migrations/      # 마이그레이션 파일 4개
│   │   └── Seeds/           # 초기 데이터 시더
│   ├── Filters/
│   │   ├── JwtFilter.php            # JWT 필수 인증
│   │   ├── JwtOptionalFilter.php    # JWT 선택 인증
│   │   ├── AdminJwtFilter.php       # 관리자 JWT 인증
│   │   ├── AuthFilter.php           # 웹 세션 인증
│   │   ├── AdminFilter.php          # 웹 관리자 인증
│   │   ├── LocaleFilter.php         # 언어 설정
│   │   └── NavDataFilter.php        # 네비게이션 데이터 주입
│   ├── Language/            # 다국어 파일 (ko / en / ja)
│   ├── Models/
│   │   ├── Admin/                   # Admin DB 전용 모델
│   │   │   ├── AdminLogModel.php
│   │   │   ├── AdminSessionModel.php
│   │   │   ├── SiteConfigModel.php
│   │   │   └── StatsDailyModel.php
│   │   ├── ArticleModel.php
│   │   ├── BbsModel.php
│   │   ├── CommentModel.php
│   │   ├── FileModel.php
│   │   ├── MessageModel.php
│   │   ├── SocialUserModel.php
│   │   ├── UserModel.php
│   │   └── UserTokenModel.php
│   ├── Services/
│   │   ├── JwtService.php           # JWT 발급·검증·사용자 주입
│   │   ├── GoogleOAuthService.php   # Google OAuth2
│   │   └── KakaoOAuthService.php    # 카카오 OAuth2
│   ├── Traits/
│   │   └── ApiResponse.php          # 표준 JSON 응답 트레이트
│   ├── Views/               # 웹 UI 뷰 템플릿
│   └── Common.php           # 전역 헬퍼 함수
├── docs/                    # 프로젝트 문서
├── phpstan.neon             # PHPStan 레벨 3 설정
├── public/                  # 웹 루트 (index.php)
├── writable/                # 캐시 · 로그 · 세션 · 업로드
└── vendor/                  # Composer 패키지
```

---

## 4. 컨트롤러 구조

### 4.1 웹 컨트롤러 (MVC)

| 컨트롤러 | 역할 |
|----------|------|
| `HomeController` | 메인 페이지 (게시판별 최신글 위젯, 5분 캐시) |
| `AuthController` | 로그인 / 로그아웃 / 회원가입 / 프로필 수정 / 회원 탈퇴 |
| `BoardController` | 게시글 목록·상세·작성·수정·삭제 + 댓글 CRUD |
| `AdminController` | 관리자 패널 (게시판·사이트 설정, 회원·게시글 관리) |
| `MessageController` | 쪽지 받은함·보낸함·읽기·작성·삭제 |
| `FileController` | 첨부파일 다운로드·삭제 |
| `LanguageController` | 언어 전환 (세션 저장) |

### 4.2 REST API 컨트롤러 (`Controllers/Api/V1/`)

| 컨트롤러 | 역할 |
|----------|------|
| `AuthController` | JWT 로그인·회원가입·로그아웃·갱신·프로필·탈퇴 |
| `BoardController` | 게시판 목록·상세 |
| `ArticleController` | 게시글 CRUD + 페이지네이션 |
| `CommentController` | 댓글 CRUD |
| `FileController` | 파일 업로드·다운로드·삭제 |
| `MessageController` | 쪽지 발신·수신·읽기·삭제 |
| `SocialAuthController` | 소셜 로그인 콜백 처리 |

### 4.3 관리자 REST API (`Controllers/Api/V1/Admin/`)

| 컨트롤러 | 역할 |
|----------|------|
| `AuthController` | 관리자 로그인·로그아웃 (Admin JWT 발급) |
| `BoardController` | 게시판 설정 조회·수정 |
| `MemberController` | 회원 목록·수정 |
| `ArticleController` | 게시글 목록·수정·삭제 |
| `SettingController` | 사이트 설정 조회·수정 |

---

## 5. 모델 구조

| 모델 | DB | 테이블 | 주요 역할 |
|------|----|--------|-----------|
| `BbsModel` | default | `tb_bbs` + `tb_bbs_setting` | 게시판 기본정보·설정·권한 로드 |
| `ArticleModel` | default | `tb_bbs_article` + 관련 테이블 | 게시글 CRUD, 조회수, 태그, URL |
| `CommentModel` | default | `tb_bbs_comment` | 댓글 CRUD, 소프트 삭제 |
| `FileModel` | default | `tb_bbs_file` | 첨부파일 저장·조회·삭제 |
| `MessageModel` | default | `tb_users_message` | 쪽지 발신·수신·읽음 처리 |
| `UserModel` | default | `tb_users` | 회원 조회 |
| `UserTokenModel` | default | `tb_users_token` | JWT Refresh Token 저장·폐기 |
| `SocialUserModel` | default | `tb_users_social` | 소셜 계정 연결 |
| `AdminLogModel` | admin | `tb_admin_log` | 관리자 행위 감사 로그 |
| `AdminSessionModel` | admin | `tb_admin_session` | 관리자 세션/토큰 |
| `SiteConfigModel` | admin | `tb_site_config` | 사이트 전역 설정 |
| `StatsDailyModel` | admin | `tb_stats_daily` | 일별 통계 집계 |

---

## 6. 데이터베이스 스키마

### 6.1 서비스 DB (`ci4_board`) — 31개 테이블

**회원 관련 (10개)**

| 테이블 | 설명 |
|--------|------|
| `tb_users` | 회원 (bcrypt 비밀번호, 레벨, 그룹, 포인트, IP 이력) |
| `tb_users_group` | 회원 그룹 |
| `tb_users_group_revision` | 회원 그룹 변경 히스토리 |
| `tb_users_block_history` | 회원 차단 내역 |
| `tb_users_friend` | 친구 관계 |
| `tb_users_message` | 쪽지 (발신·수신, 소프트 삭제 분리) |
| `tb_users_point` | 포인트 내역 |
| `tb_users_url` | 스크랩/즐겨찾기 |
| `tb_users_token` | JWT Refresh Token |
| `tb_users_social` | 소셜 로그인 연결 (Google/네이버/카카오) |

**게시판 관련 (17개)**

`tb_bbs`, `tb_bbs_setting`, `tb_bbs_setting_revision`, `tb_bbs_category`, `tb_bbs_category_revision`, `tb_bbs_article`, `tb_bbs_article_revision`, `tb_bbs_contents`, `tb_bbs_contents_revision`, `tb_bbs_comment`, `tb_bbs_comment_revision`, `tb_bbs_file`, `tb_bbs_file_temporary`, `tb_bbs_hit`, `tb_bbs_tag`, `tb_bbs_url`, `tb_bbs_vote`

**시스템 관련 (4개)**

`tb_setting`, `tb_setting_revision`, `tb_client_ip_access`, `tb_client_ip_block`, `tb_themes`

### 6.2 Admin DB (`ci4_board_admin`) — 4개 테이블

| 테이블 | 설명 |
|--------|------|
| `tb_admin_log` | 관리자 행위 감사 로그 (JSON before/after) |
| `tb_admin_session` | 관리자 전용 세션/토큰 |
| `tb_site_config` | 사이트 전역 설정 (key-value) |
| `tb_stats_daily` | 일별 통계 집계 |

### 6.3 설계 특징

- **메타/본문 분리**: 목록 조회 시 본문 컬럼 로딩 없음
- **조회수 분리**: `tb_bbs_hit` 별도 테이블로 잠금 경합 최소화
- **히스토리 패턴**: 주요 테이블마다 `_revision` 히스토리 테이블
- **소프트 삭제**: `is_deleted`, `status` 플래그로 실제 행 보존
- **Admin DB 분리**: 감사 로그/통계를 서비스 DB와 격리

---

## 7. REST API 구조

### 7.1 엔드포인트 그룹

| 경로 접두사 | 인증 | 설명 |
|------------|------|------|
| `/api/v1/auth/*` | 없음 / JWT | 사용자 인증 |
| `/api/v1/boards/*` | JWT Optional | 게시판·게시글·댓글 |
| `/api/v1/files/*` | 없음 / JWT | 파일 |
| `/api/v1/messages/*` | JWT 필수 | 쪽지 |
| `/api/admin/v1/auth/*` | 없음 / Admin JWT | 관리자 인증 |
| `/api/admin/v1/*` | Admin JWT 필수 | 관리자 기능 |

### 7.2 인증 흐름

```
로그인 → Access Token (1시간) + Refresh Token (30일)
         ↓
API 요청: Authorization: Bearer {access_token}
         ↓
만료 시: POST /api/v1/auth/refresh → 새 Access Token 발급
```

### 7.3 표준 응답 형식

```json
{ "success": true,  "data": {...}, "message": null }
{ "success": false, "data": null, "message": "오류 메시지" }
{ "success": true,  "data": [...], "meta": { "page":1, "total":87 } }
```

---

## 8. 소셜 로그인

| 플랫폼 | 방식 | 서비스 클래스 |
|--------|------|--------------|
| Google | OAuth2 (OIDC) | `GoogleOAuthService` |
| 네이버 | OAuth2 | `SocialAuthController` (GenericProvider) |
| 카카오 | OAuth2 | `KakaoOAuthService` |

소셜 계정은 `tb_users_social`에 저장하며 자체 회원(`tb_users`)과 연결된다.  
소셜 전용 계정은 `super_secured_password = NULL` 허용.

---

## 9. 권한 시스템

### 9.1 그룹 정의

| group_idx | 그룹명 | 설명 |
|-----------|--------|------|
| 0 | (비회원) | 미로그인 사용자 |
| 1 | 최고관리자 | 관리자 패널 접근 가능 |
| 2 | 일반회원 | 기본 가입 그룹 |
| 3 | 개발자 | 개발자 전용 |

### 9.2 게시판 권한 파라미터

`bbs_allow_group_view_list`, `bbs_allow_group_view_article`, `bbs_allow_group_write_article`, `bbs_allow_group_write_comment`

권한 값은 PHP `serialize()` 형식으로 `tb_bbs_setting`에 저장.

### 9.3 권한 판정 흐름

```
parse_group_setting($raw)  →  허용 group_idx 배열
current_group_idx()        →  JWT 우선 → 세션 폴백
user_can_in_groups()       →  0(비회원) 포함이면 누구나 허용
```

---

## 10. 필터 파이프라인

| 필터 | 별칭 | 적용 범위 |
|------|------|-----------|
| `LocaleFilter` | locale | 전역 |
| `NavDataFilter` | navdata | 전역 (웹 뷰 공유 변수 주입) |
| `JwtFilter` | jwt | API 인증 필수 라우트 |
| `JwtOptionalFilter` | jwt_optional | API 선택적 인증 |
| `AdminJwtFilter` | admin_jwt | 관리자 API 전용 |
| `AuthFilter` | auth | 웹 세션 인증 |
| `AdminFilter` | admin | 웹 관리자 패널 |

---

## 11. 기능 상세

### 11.1 파일 업로드

| 항목 | 제한 |
|------|------|
| 최대 파일 수 | 게시글당 5개 |
| 파일 크기 | 개당 2MB |
| 허용 확장자 | jpg, jpeg, gif, png, txt, doc, docx, xls, xlsx, pdf, ppt, pptx, zip, 7z, alz, rar |
| 저장 경로 | `writable/uploads/{bbs_idx}/{Ymd}/{랜덤hex}.{ext}` |

### 11.2 캐싱

```
캐시 키: home_boards_g{group_idx}
TTL: 300초 (5분)
무효화: 글쓰기/수정/삭제 후 clear_home_cache() 호출
```

### 11.3 다국어

지원 언어: `ko`(한국어), `en`(영어), `ja`(일본어)  
웹: 세션 기반 전환 / API: `Accept-Language` 헤더 또는 `?lang=` 파라미터

### 11.4 소프트 삭제 정책

| 대상 | 방식 |
|------|------|
| 게시글 | `is_deleted = 1` |
| 댓글 | `is_deleted = 1` |
| 회원 | `status = 0` + `timestamp_delete` |
| 쪽지 | `is_deleted_sender` / `is_deleted_receiver` 독립 플래그 |

---

## 12. 코드 품질

### 12.1 정적 분석

```bash
vendor/bin/phpstan analyse --level=3
# 설정: phpstan.neon
```

### 12.2 테스트

```bash
composer test
```

| 파일 | 종류 | 테스트 수 |
|------|------|-----------|
| `tests/unit/Services/JwtServiceTest.php` | 단위 | 14개 |
| `tests/unit/Api/V1/Auth/UserAuthApiTest.php` | 통합 | MySQL 필요 |
| `tests/unit/Api/V1/Auth/AdminAuthApiTest.php` | 통합 | MySQL 필요 |

---

## 13. 초기 데이터 (Seeder)

`php spark db:seed InitialSeeder` 실행 시 생성:

| 항목 | 값 |
|------|----|
| 관리자 ID | admin |
| 관리자 PW | admin1234 |
| 기본 게시판 | 13개 (notice~ci) |

---

## 14. 주요 전역 헬퍼 함수 (Common.php)

| 함수 | 설명 |
|------|------|
| `esc_db(?string $text)` | DB HTML 엔티티 안전 출력 |
| `parse_group_setting(?string $value)` | serialize된 그룹 배열 파싱 |
| `current_group_idx()` | JWT 우선 → 세션 폴백으로 group_idx 반환 |
| `user_can_in_groups(array $groups)` | 허용 그룹과 현재 사용자 비교 |
| `clear_home_cache()` | 전체 그룹 홈 캐시 삭제 |
