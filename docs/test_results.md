# 통합 테스트 결과

## 요약

| 이슈 | 제목 | PR | 테스트 수 | 결과 |
|------|------|----|-----------|------|
| [#28](https://github.com/pushwing/ci4-board/issues/28) | [Phase 7] 통합 테스트 작성 | [PR #55](https://github.com/pushwing/ci4-board/pull/55) | 21개 | ✅ 전체 통과 |
| [#57](https://github.com/pushwing/ci4-board/issues/57) | 소셜 로그인 콜백 통합 테스트 | [PR #67](https://github.com/pushwing/ci4-board/pull/67) | 32개 | ✅ 전체 통과 |
| [#58](https://github.com/pushwing/ci4-board/issues/58) | CMS API 통합 테스트 | [PR #62](https://github.com/pushwing/ci4-board/pull/62) | 18개 | ✅ 전체 통과 |
| [#59](https://github.com/pushwing/ci4-board/issues/59) | 어드민 게시판/회원 관리 API 통합 테스트 | [PR #61](https://github.com/pushwing/ci4-board/pull/61) | 15개 | ✅ 전체 통과 |
| [#60](https://github.com/pushwing/ci4-board/issues/60) | 파일 업로드 바이너리 테스트 | [PR #63](https://github.com/pushwing/ci4-board/pull/63) | 12개 | ✅ 전체 통과 |

**총 98개 통합 테스트 전체 통과**

---

## 상세 결과

### #28 — 인증 API (21개) · PR #55

**테스트 환경**
- MySQL 테스트 DB (`ci4_board_test`), `phpunit.dist.xml` 활성화
- GitHub Actions CI 파이프라인 추가

**테스트 파일**
- `tests/unit/Api/V1/Auth/UserAuthApiTest.php` — 14개
- `tests/unit/Api/V1/Auth/AdminAuthApiTest.php` — 7개

| # | 테스트 | 결과 |
|---|--------|------|
| 1 | 사용자 로그인 (정상) | ✅ |
| 2 | 사용자 로그인 (잘못된 비밀번호) | ✅ |
| 3 | 사용자 로그인 (존재하지 않는 계정) | ✅ |
| 4 | 사용자 로그인 (필드 누락) | ✅ |
| 5 | 회원가입 (정상) | ✅ |
| 6 | 회원가입 (중복 아이디) | ✅ |
| 7 | 회원가입 (비밀번호 불일치) | ✅ |
| 8 | 회원가입 (짧은 비밀번호) | ✅ |
| 9 | 내 정보 조회 (토큰 없음) | ✅ |
| 10 | 내 정보 조회 (유효한 토큰) | ✅ |
| 11 | 로그아웃 (유효한 토큰) | ✅ |
| 12 | 로그아웃 (토큰 없음) | ✅ |
| 13 | 토큰 갱신 (유효한 리프레시 토큰) | ✅ |
| 14 | 토큰 갱신 (유효하지 않은 토큰) | ✅ |
| 15 | 관리자 로그인 (정상) | ✅ |
| 16 | 관리자 로그인 (잘못된 비밀번호) | ✅ |
| 17 | 관리자 로그인 (일반 계정) | ✅ |
| 18 | 관리자 API (admin 토큰) | ✅ |
| 19 | 관리자 API (user 토큰 → 403) | ✅ |
| 20 | 관리자 API (토큰 없음 → 401) | ✅ |
| 21 | 관리자 로그아웃 | ✅ |

**버그 수정 (PR #55)**
- `AuthController::login()`: `getJSON(true)` null 안전 처리
- `AuthController::refresh()`: `status` 타입 비교 `!==` → `(int) !== 1`

---

### #57 — 소셜 로그인 콜백 (32개) · PR #67

**특이사항**: DB 불필요 — 환경변수·state·code 검증만 수행

**테스트 파일**
- `tests/unit/Api/SocialAuth/GoogleCallbackTest.php` — 11개 (신규)
- `tests/unit/Api/SocialAuth/KakaoCallbackTest.php` — 11개 (신규)
- `tests/unit/Api/SocialAuth/NaverCallbackTest.php` — 10개 (기존)

| # | 테스트 패턴 (Google / Naver / Kakao 공통) | 결과 |
|---|-------------------------------------------|------|
| 1 | 환경변수 누락 → 500 | ✅ |
| 2 | client_id만 설정 시 → 500 | ✅ |
| 3 | 리다이렉트 URL 정상 생성 | ✅ |
| 4 | 리다이렉트 URL에 state 파라미터 포함 | ✅ |
| 5 | 콜백: code 누락 → 400 | ✅ |
| 6 | 콜백: state 누락 → 400 | ✅ |
| 7 | 콜백: state 불일치 → 400 | ✅ |
| 8 | 콜백: 잘못된 code → 502 | ✅ |
| 9 | 리다이렉트 JSON 구조 검증 | ✅ |
| 10 | code 누락 JSON 구조 검증 | ✅ |
| 11 | (Google/Kakao) state 빈 문자열 → 400 | ✅ |

---

### #58 — CMS API (18개) · PR #62

**테스트 파일**
- `tests/unit/Api/V1/Cms/CmsApiTest.php` — 18개 (신규)

| 영역 | 개수 | 테스트 내용 | 결과 |
|------|------|-------------|------|
| Page | 5개 | CRUD, slug 중복 방지, 공개 조회 | ✅ |
| Banner | 3개 | CRUD, 필수값 검증, 공개 목록 | ✅ |
| Popup | 3개 | CRUD, 필수값 검증, 공개 목록 | ✅ |
| Menu | 5개 | CRUD, 트리 구조, 부모 검증, 공개 목록 | ✅ |
| Auth | 1개 | admin_jwt 미인증 → 401 | ✅ |
| 공개 Menu | 1개 | 트리 구조 검증 | ✅ |

---

### #59 — 어드민 API (15개) · PR #61

**테스트 파일**
- `tests/unit/Api/V1/Admin/AdminApiTest.php` — 15개 (신규)

| 영역 | 내용 | 결과 |
|------|------|------|
| 게시판 설정 | 목록 조회, 설정 수정 | ✅ |
| 사이트 설정 | 조회, 수정 | ✅ |
| 회원 관리 | 목록 조회, 상태 변경 | ✅ |
| 게시글 관리 | CRUD | ✅ |
| 권한 검증 | 미인증 → 401, 일반유저 → 403 | ✅ |

**버그 수정 (PR #61)**
- `BoardController::update()`: 권한 파라미터 미전달 시 빈 배열로 덮어쓰는 문제 수정

---

### #60 — 파일 업로드 바이너리 (12개) · PR #63

**테스트 파일**
- `tests/unit/Api/V1/File/FileUploadBinaryTest.php` — 12개 (신규)

| # | 테스트 내용 | 결과 |
|---|-------------|------|
| 1 | 파라미터 누락 → 422 | ✅ |
| 2 | CLI 파일 주입 동작 검증 | ✅ |
| 3 | 다운로드 (DB 레코드 + 실제 파일 생성) | ✅ |
| 4 | 삭제 — 소유자 권한 | ✅ |
| 5 | 삭제 — 비소유자 → 403 | ✅ |
| 6 | 삭제 — 미인증 → 401 | ✅ |
| 7~12 | WYSIWYG 인증/MIME 검증 | ✅ |

**CLI 환경 제약 사항**

`UploadedFile::isValid()`는 `is_uploaded_file()`을 사용하여 실제 HTTP 업로드만 유효하게 처리한다.
PHPUnit CLI 환경에서는 이를 우회할 수 없으므로, 실제 파일 이동이 필요한 업로드 성공 케이스는 HTTP 서버 환경에서 별도 테스트가 필요하다.

---

## 알려진 이슈

### 클래스명 충돌 (`JwtServiceTest`)

`tests/unit/JwtServiceTest.php` (untracked)와 `tests/unit/Services/JwtServiceTest.php` (committed)가 동일한 클래스명을 사용하여 PHPUnit 실행 시 Fatal Error가 발생한다.

```
Fatal error: Cannot redeclare class JwtServiceTest
```

둘 중 하나를 삭제하거나 네임스페이스를 분리해야 한다.

### DB 테스트 미설정 시 에러

`phpunit.dist.xml`의 테스트 DB 설정이 주석 처리된 상태에서 `UserAuthApiTest` / `AdminAuthApiTest`를 실행하면 아래 에러가 발생한다.

```
DatabaseException: Unable to prepare statement: no such table: db_tb_users_group
```

테스트 실행 전 MySQL 테스트 DB(`ci4_board_test`) 설정 및 마이그레이션이 필요하다.
