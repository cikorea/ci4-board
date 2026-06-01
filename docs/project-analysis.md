# ci4-board 프로젝트 분석 문서

> 작성일: 2026-06-01  
> 대상 브랜치: master

---

## 1. 프로젝트 개요

| 항목 | 내용 |
|------|------|
| 프로젝트명 | ci4-board |
| 프레임워크 | CodeIgniter 4 (^4.7) |
| PHP 요구 버전 | ^8.2 |
| DB 엔진 | MySQL / InnoDB, utf8mb4 |
| 패턴 | MVC (Model-View-Controller) |
| 목적 | 다중 게시판 커뮤니티 웹 애플리케이션 |

CodeIgniter 4 기반의 한국형 게시판(BBS) 시스템이다. 다중 게시판, 회원 관리, 쪽지, 파일 첨부, 그룹 기반 권한, 다국어(한/영/일), 관리자 페이지를 제공한다.

---

## 2. 디렉토리 구조

```
ci4-board/
├── app/
│   ├── Config/          # 프레임워크 설정 (Routes, Filters, Database 등)
│   ├── Controllers/     # 컨트롤러 7개
│   ├── Database/
│   │   ├── Migrations/  # 초기 스키마 마이그레이션 1개
│   │   └── Seeds/       # 초기 데이터 시더 1개
│   ├── Filters/         # 커스텀 필터 4개
│   ├── Language/        # 다국어 파일 (ko / en / ja)
│   ├── Models/          # 모델 6개
│   ├── Views/           # 뷰 템플릿
│   │   ├── admin/       # 관리자 뷰
│   │   ├── auth/        # 인증 뷰
│   │   ├── board/       # 게시판 뷰
│   │   ├── home/        # 메인 뷰
│   │   ├── layouts/     # 공통 레이아웃
│   │   └── message/     # 쪽지 뷰
│   └── Common.php       # 전역 헬퍼 함수
├── public/
│   ├── index.php        # 진입점
│   └── docs/            # apiDoc 빌드 결과물
├── writable/
│   └── uploads/         # 업로드 파일 저장소
├── tests/               # PHPUnit 테스트
├── docs/                # 프로젝트 문서
├── apidoc.json          # apiDoc 빌드 설정
└── composer.json
```

---

## 3. 컨트롤러 구조

### 3.1 컨트롤러 목록

| 컨트롤러 | 역할 |
|----------|------|
| `HomeController` | 메인 페이지 (게시판별 최신글 위젯) |
| `AuthController` | 로그인 / 로그아웃 / 회원가입 / 프로필 수정 / 회원 탈퇴 |
| `BoardController` | 게시글 목록·상세·작성·수정·삭제 + 댓글 CRUD |
| `AdminController` | 관리자 패널 (게시판·사이트 설정, 회원·게시글 관리) |
| `MessageController` | 쪽지 받은함·보낸함·읽기·작성·삭제 |
| `FileController` | 첨부파일 다운로드·삭제 |
| `LanguageController` | 언어 전환 |

### 3.2 라우팅 요약

```
GET  /                              → HomeController::index
GET  /lang/:locale                  → LanguageController::switchLocale

# 인증
GET/POST  /auth/login               → AuthController::login(Process)
GET/POST  /auth/register            → AuthController::register(Process)
GET       /auth/logout              → AuthController::logout
GET/POST  /auth/profile             [auth]
POST      /auth/withdraw            [auth]

# 게시판 (비로그인 허용)
GET  /board/:bbsId                  → BoardController::index
GET  /board/:bbsId/view/:idx        → BoardController::view

# 게시판 (로그인 필요) [auth]
GET/POST  /board/:bbsId/write
GET/POST  /board/:bbsId/edit/:idx
GET       /board/:bbsId/delete/:idx
POST      /board/:bbsId/view/:idx/comment
POST/GET  /board/:bbsId/view/:idx/comment/:cIdx/edit|delete

# 파일
GET  /file/:idx                     → FileController::download
GET  /file/:idx/delete              [auth]

# 쪽지 [auth]
GET       /message
GET       /message/sent
GET/POST  /message/write → /message/send
GET       /message/:idx
GET       /message/:idx/delete

# 관리자 [admin]
GET/POST  /admin/boards
GET/POST  /admin/boards/:bbsId
GET/POST  /admin/setting
GET/POST  /admin/members
GET/POST  /admin/members/:idx
GET/POST  /admin/posts
GET/POST  /admin/posts/:idx/edit
GET       /admin/posts/:idx/delete
```

---

## 4. 모델 구조

### 4.1 모델 목록

| 모델 | 테이블 | 주요 역할 |
|------|--------|-----------|
| `BbsModel` | `tb_bbs` + `tb_bbs_setting` | 게시판 기본정보·설정·권한 로드 |
| `ArticleModel` | `tb_bbs_article` + `tb_bbs_contents` + `tb_bbs_hit` + `tb_bbs_tag` + `tb_bbs_url` | 게시글 CRUD, 페이지네이션, 조회수, 태그, URL |
| `CommentModel` | `tb_bbs_comment` | 댓글 CRUD, 소프트 삭제 |
| `FileModel` | `tb_bbs_file` | 첨부파일 저장·조회·삭제 |
| `MessageModel` | `tb_users_message` | 쪽지 발신·수신·읽음 처리 |
| `UserModel` | `tb_users` | 회원 조회 (로그인ID, 이메일, 중복 확인) |

### 4.2 주요 메서드

#### BbsModel
- `getActiveBoards()` — 활성 게시판 + 권한 필터 (네비게이션/홈 위젯용)
- `getByBbsId(string $bbsId)` — 슬러그로 게시판 조회 + 권한 맵 포함
- `loadPermissions(int $bbsIdx)` — 4가지 권한 파라미터를 파싱하여 반환

#### ArticleModel
- `getList(bbsIdx, keyword, perPage)` — 페이지네이션 목록 (`_pagerTotal`, `_pagerPage` 부산물)
- `getArticleWithContents(articleIdx)` — 본문·조회수·닉네임 JOIN
- `writeArticle(data, contents)` — article + contents + hit 트랜잭션 삽입
- `updateArticle(idx, data, contents)` — article + contents 트랜잭션 수정
- `softDelete(articleIdx)` — `is_deleted=1` 플래그
- `incrementHit(bbsIdx, articleIdx)` — hit upsert
- `saveTagsForArticle()` / `saveUrlsForArticle()` — 전체 교체 방식

---

## 5. 데이터베이스 스키마

총 29개 테이블 (InnoDB, utf8mb4)

### 5.1 회원 관련 (8개)

| 테이블 | 설명 |
|--------|------|
| `tb_users` | 회원 (bcrypt 비밀번호, 레벨, 그룹, 포인트, IP 이력) |
| `tb_users_group` | 회원 그룹 (최고관리자/일반회원/개발자) |
| `tb_users_group_revision` | 회원 그룹 변경 히스토리 |
| `tb_users_block_history` | 회원 차단 내역 |
| `tb_users_friend` | 친구 관계 |
| `tb_users_message` | 쪽지 (발신·수신, 소프트 삭제 분리) |
| `tb_users_point` | 포인트 내역 |
| `tb_users_url` | 스크랩/즐겨찾기 |

### 5.2 게시판 관련 (17개)

| 테이블 | 설명 |
|--------|------|
| `tb_bbs` | 게시판 기본정보 (bbs_id 슬러그) |
| `tb_bbs_setting` | 게시판 설정 key-value |
| `tb_bbs_setting_revision` | 설정 변경 히스토리 |
| `tb_bbs_category` | 게시판 카테고리 |
| `tb_bbs_category_revision` | 카테고리 히스토리 |
| `tb_bbs_article` | 게시글 메타 (제목, 플래그들, IP 이력) |
| `tb_bbs_article_revision` | 게시글 메타 히스토리 |
| `tb_bbs_contents` | 게시글 본문 (article과 1:1 분리) |
| `tb_bbs_contents_revision` | 본문 히스토리 |
| `tb_bbs_comment` | 댓글 |
| `tb_bbs_comment_revision` | 댓글 히스토리 |
| `tb_bbs_file` | 첨부파일 메타 |
| `tb_bbs_file_temporary` | 파일 임시 저장 |
| `tb_bbs_hit` | 조회수 (article과 분리) |
| `tb_bbs_tag` | 태그 |
| `tb_bbs_url` | 참조 URL |
| `tb_bbs_vote` | 추천 (게시글/댓글 공용) |

### 5.3 시스템 관련 (4개)

| 테이블 | 설명 |
|--------|------|
| `tb_setting` | 사이트 환경설정 key-value |
| `tb_setting_revision` | 환경설정 히스토리 |
| `tb_client_ip_access` | 접근 IP 로그 |
| `tb_client_ip_block` | 차단 IP |
| `tb_themes` | 테마 관리 (PC/모바일) |

### 5.4 설계 특징

- **메타/본문 분리**: `tb_bbs_article`(메타) + `tb_bbs_contents`(본문)으로 목록 조회 시 본문 로딩 없음
- **조회수 분리**: `tb_bbs_hit` 별도 테이블로 잦은 UPDATE를 article 테이블과 격리
- **히스토리 패턴**: 주요 테이블마다 `_revision` 히스토리 테이블이 존재 (변경 이력 보존)
- **소프트 삭제**: `is_deleted`, `status` 플래그로 실제 행 보존

---

## 6. 권한 시스템

### 6.1 그룹 정의

| group_idx | 그룹명 | 설명 |
|-----------|--------|------|
| 0 | (비회원) | 미로그인 사용자 |
| 1 | 최고관리자 | 관리자 패널 접근 가능 |
| 2 | 일반회원 | 기본 가입 그룹 |
| 3 | 개발자 | 개발자 전용 |

### 6.2 게시판 권한 파라미터

| 파라미터 | 의미 |
|---------|------|
| `bbs_allow_group_view_list` | 목록 보기 허용 그룹 |
| `bbs_allow_group_view_article` | 글 보기 허용 그룹 |
| `bbs_allow_group_write_article` | 글 쓰기 허용 그룹 |
| `bbs_allow_group_write_comment` | 댓글 쓰기 허용 그룹 |

권한 값은 PHP `serialize()` 형식으로 `tb_bbs_setting`에 저장된다.  
예: `a:3:{i:0;s:1:"0";i:1;s:1:"2";i:2;s:1:"3";}` → [0, 2, 3]

### 6.3 권한 판정 흐름

```
parse_group_setting($raw)  →  허용 group_idx 배열
        ↓
user_can_in_groups($groups)
  - groups에 0 포함 → 누구나 허용
  - 비로그인(group_idx=0) + 0 미포함 → 거부
  - 로그인 사용자의 group_idx가 groups에 포함 → 허용
```

---

## 7. 필터 파이프라인

| 필터 | 별칭 | 적용 범위 |
|------|------|-----------|
| `LocaleFilter` | locale | 전역 (모든 요청 before) |
| `NavDataFilter` | navdata | 전역 (모든 요청 before) |
| `AuthFilter` | auth | 로그인 필요 라우트 |
| `AdminFilter` | admin | `/admin/*` 그룹 |

**NavDataFilter** 는 매 요청 전에 실행되어 뷰 공유 변수를 주입한다:
- `navBoards` — 현재 사용자가 접근 가능한 게시판 목록
- `unreadCount` — 로그인 사용자의 안 읽은 쪽지 수

---

## 8. 기능 상세

### 8.1 파일 업로드

| 항목 | 제한 |
|------|------|
| 최대 파일 수 | 게시글당 5개 |
| 파일 크기 | 개당 2MB |
| 허용 확장자 | jpg, jpeg, gif, png, txt, doc, docx, xls, xlsx, pdf, ppt, pptx, zip, 7z, alz, rar |
| 저장 경로 | `writable/uploads/{bbs_idx}/{Ymd}/{16바이트 랜덤hex}.{ext}` |

### 8.2 캐싱

메인 페이지는 그룹 단위로 5분 캐시된다.

```
캐시 키: home_boards_g{group_idx}
TTL: 300초
무효화: 글쓰기/수정/삭제 후 clear_home_cache() 호출
         → g0, g1, g2, g3 전체 삭제
```

### 8.3 다국어

지원 언어: `ko`(한국어), `en`(영어), `ja`(일본어)  
언어 파일 위치: `app/Language/{locale}/App.php`  
전환 경로: `GET /lang/{locale}`

### 8.4 쪽지

- 발신자/수신자 독립 소프트 삭제 (`is_deleted_sender`, `is_deleted_receiver`)
- 수신자가 처음 열 때 `is_read=1`, `timestamp_receive`, `client_ip_receive` 갱신
- user_id 또는 닉네임으로 수신자 검색
- 자기 자신에게 전송 불가

### 8.5 소프트 삭제 정책

| 대상 | 삭제 방식 |
|------|-----------|
| 게시글 | `is_deleted = 1` |
| 댓글 | `is_deleted = 1` |
| 회원 | `status = 0` + `timestamp_delete` |
| 쪽지 | `is_deleted_sender` / `is_deleted_receiver` 독립 플래그 |

---

## 9. 초기 데이터 (Seeder)

`php spark db:seed InitialSeeder` 실행 시 생성된다.

### 기본 계정

| 항목 | 값 |
|------|-----|
| ID | admin |
| PW | admin1234 |
| 그룹 | 최고관리자 (group_idx=1) |
| 레벨 | 99 |

### 기본 게시판 (13개)

| bbs_id | 게시판명 | 쓰기 권한 |
|--------|----------|-----------|
| notice | 공지사항 | 회원 |
| news | 새소식 | 회원 |
| free | 자유게시판 | 회원 |
| qna | CodeIgniter Q&A | 회원 |
| source | 소스코드 공유 | 회원 |
| tip | 팁 & 강좌 | 회원 |
| etc_qna | 기타 Q&A | 회원 |
| file | 자료실 | 회원 |
| ad | 홍보게시판 | 회원 |
| job | 구인구직 | 회원 |
| cibook | CI 도서 | 회원 |
| su | 운영자 게시판 | 최고관리자 |
| ci | 포럼 개발자 | 최고관리자 + 개발자 |

---

## 10. 전역 헬퍼 함수 (Common.php)

| 함수 | 설명 |
|------|------|
| `esc_db(?string $text)` | DB에 HTML 엔티티로 저장된 값을 안전하게 출력 |
| `parse_group_setting(?string $value)` | serialize된 그룹 배열을 파싱하여 int 배열 반환 |
| `current_group_idx()` | 현재 세션의 group_idx 반환 (비로그인=0) |
| `user_can_in_groups(array $groups)` | 허용 그룹 목록과 현재 사용자 그룹 비교 |
| `clear_home_cache()` | 전체 그룹(0~3)의 홈 캐시 삭제 |

---

## 11. API 문서화

모든 컨트롤러와 모델에 `apiDoc` 주석이 작성되어 있다.

- 설정 파일: `apidoc.json`
- 빌드 결과: `public/docs/index.html` (Bootstrap 기반 HTML 문서)
- 빌드 명령: `npx apidoc -i app/ -o public/docs/`

문서화된 그룹: `Auth`, `Board`, `Admin`, `Message`, `Home`, `Filter`, `BbsModel`, `ArticleModel`, `UserModel`

---

## 12. 테스트

| 항목 | 내용 |
|------|------|
| 프레임워크 | PHPUnit ^10.5 |
| 설정 파일 | `phpunit.dist.xml` |
| 테스트 위치 | `tests/unit/`, `tests/session/` |
| 실행 명령 | `composer test` 또는 `php spark test` |

현재 `HealthTest` (단위), `ExampleSessionTest` (세션) 2개의 기본 테스트가 존재한다.

---

## 13. 개발 환경 설정

```bash
# 의존성 설치
composer install

# 환경 파일 설정
cp env .env
# .env 에서 database.default.* 설정

# DB 마이그레이션
php spark migrate

# 초기 데이터 삽입
php spark db:seed InitialSeeder

# 개발 서버 실행
php spark serve
```

---

## 14. 주요 설계 결정 및 특이사항

1. **히스토리 테이블 분리**: 게시글/댓글/설정의 모든 변경 이력을 `_revision` 테이블에 보존 — 감사 추적 목적
2. **게시판 설정 key-value 구조**: `tb_bbs_setting`의 key-value 방식으로 게시판별 유연한 설정 지원. 권한값은 `serialize()` 직렬화
3. **본문/메타 분리**: 목록 조회 시 본문 컬럼을 로드하지 않아 성능 절약
4. **조회수 별도 테이블**: 잦은 UPDATE를 article 행과 격리하여 잠금 경합 최소화
5. **그룹 기반 캐시 분리**: 그룹마다 접근 가능한 게시판이 달라 캐시 키를 `g{idx}` 단위로 분리
6. **NavDataFilter 전역 주입**: 모든 뷰에서 모델 직접 호출 없이 네비게이션 데이터 사용 가능
7. **CSRF 비활성화**: `Filters.php`의 globals에서 `csrf`가 주석 처리 — 운영 전 활성화 필요
