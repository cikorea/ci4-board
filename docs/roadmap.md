# CI4 Board 개발 로드맵

> 작성일: 2026-06-01  
> 기반 문서: [project-analysis.md](./project-analysis.md), [headless-board-design.md](./headless-board-design.md)

---

## 아키텍처 방향

```
┌─────────────────────────────────────────────────────────┐
│                     클라이언트 레이어                      │
│  ┌──────────────┐          ┌──────────────────────────┐  │
│  │  Admin Front │          │      User Front          │  │
│  │  (CSR SPA)   │          │  (SSR/SSG - Next.js)     │  │
│  └──────┬───────┘          └───────────┬──────────────┘  │
└─────────┼────────────────────────────────┼───────────────┘
          │ JWT (Admin Token)              │ JWT (User Token)
┌─────────┼────────────────────────────────┼───────────────┐
│         ▼        CI4 API 서버            ▼               │
│  ┌─────────────┐          ┌──────────────────────────┐   │
│  │ /api/admin  │          │       /api/v1            │   │
│  │ AdminDB 연결│          │     MainDB 연결          │   │
│  └──────┬──────┘          └───────────┬──────────────┘   │
└─────────┼────────────────────────────────┼───────────────┘
          │                               │
┌─────────▼────────────────────────────────▼───────────────┐
│              Database 레이어                               │
│  ┌──────────────────┐    ┌──────────────────────────┐    │
│  │   Admin DB       │    │       Main DB            │    │
│  │ (관리 전용)       │    │  (서비스 데이터)          │    │
│  └──────────────────┘    └──────────────────────────┘    │
└───────────────────────────────────────────────────────────┘
```

---

## 1. 어드민 DB 분리

### 배경
관리자 기능(회원 관리, 통계, 설정)과 서비스 데이터를 DB 레벨에서 분리하여 보안성과 유지보수성을 높인다.

### 전략: DB 연결 분리 (스키마 분리)

동일 MySQL 서버에서 데이터베이스만 분리하는 방식이 현실적이다.

```
Main DB:   ci4_board       (게시글, 댓글, 회원, 쪽지 등 서비스 데이터)
Admin DB:  ci4_board_admin (관리 로그, 감사 이력, 시스템 설정 등)
```

### CI4 다중 DB 설정

```php
// app/Config/Database.php
public array $default = [
    'hostname' => 'localhost',
    'database' => 'ci4_board',
    // ...
];

public array $admin = [
    'hostname' => 'localhost',
    'database' => 'ci4_board_admin',
    // ...
];
```

### Model 분리 전략

```php
// 서비스 모델 — Main DB 사용
class ArticleModel extends Model
{
    protected $DBGroup = 'default';
}

// 어드민 전용 모델 — Admin DB 사용
class AdminLogModel extends Model
{
    protected $DBGroup = 'admin';
}
```

### Admin DB 전용 테이블 (신규)

| 테이블 | 설명 |
|--------|------|
| `tb_admin_log` | 관리자 행위 감사 로그 |
| `tb_admin_session` | 관리자 세션/토큰 |
| `tb_admin_notice` | 내부 공지 |
| `tb_stats_daily` | 일별 통계 집계 |
| `tb_site_config` | 사이트 전역 설정 (현재 tb_setting 이관) |

### 결론

> **동일 서버 내 DB 분리**를 권장한다. 물리적 서버 분리는 규모가 커진 뒤 검토한다.

---

## 2. API 분리 설계

### API 그룹 4개

```
/api/admin/v1/*       어드민 API       (관리자 기능, Admin DB + Main DB)
/api/admin/v1/auth/*  어드민 회원 API  (관리자 로그인, 토큰 발급)
/api/v1/*             프론트 API       (게시판, 쪽지 등 서비스)
/api/v1/auth/*        프론트 회원 API  (로그인, 회원가입, 소셜 로그인)
```

### 엔드포인트 구조

```
/api
├── admin
│   └── v1
│       ├── auth
│       │   ├── POST   login          관리자 로그인
│       │   └── POST   logout         관리자 로그아웃
│       ├── boards
│       │   ├── GET    /              게시판 목록
│       │   └── PUT    /:bbsId        게시판 설정 저장
│       ├── members
│       │   ├── GET    /              회원 목록
│       │   └── PUT    /:idx          회원 수정
│       ├── articles
│       │   ├── GET    /              게시글 목록
│       │   ├── PUT    /:idx          게시글 수정
│       │   └── DELETE /:idx          게시글 삭제
│       ├── setting
│       │   ├── GET    /              사이트 설정 조회
│       │   └── PUT    /              사이트 설정 저장
│       └── stats
│           └── GET    /              통계 조회
└── v1
    ├── auth
    │   ├── POST   login              로그인 (자체 + 소셜)
    │   ├── POST   register           회원가입
    │   ├── POST   logout             로그아웃
    │   ├── POST   refresh            토큰 갱신
    │   ├── GET    me                 내 정보
    │   ├── PUT    profile            프로필 수정
    │   ├── DELETE withdraw           회원 탈퇴
    │   └── social
    │       ├── GET  google           Google OAuth2 시작
    │       ├── GET  google/callback  Google 콜백
    │       ├── GET  naver            네이버 로그인 시작
    │       ├── GET  naver/callback   네이버 콜백
    │       ├── GET  kakao            카카오 로그인 시작
    │       └── GET  kakao/callback   카카오 콜백
    ├── boards
    ├── boards/:bbsId/articles
    ├── boards/:bbsId/articles/:idx/comments
    ├── files
    └── messages
```

---

## 3. 토큰 시스템 설계

### JWT vs API Token 비교

| 항목 | JWT | API Token (DB 저장) |
|------|-----|---------------------|
| 상태 | Stateless | Stateful |
| 즉시 폐기 | 불가 (만료 전까지 유효) | 가능 (DB 삭제) |
| 확장성 | 높음 (서버 간 공유 용이) | 낮음 (DB 의존) |
| 구현 복잡도 | 중간 | 낮음 |
| 소셜 로그인 연동 | 적합 | 적합 |

### 결론: JWT 채택 (Refresh Token은 DB 저장)

- Access Token: JWT, 1시간, Stateless
- Refresh Token: JWT + DB 저장(`tb_users_token`), 30일, 강제 폐기 가능

### API별 토큰 분리 전략

| API 그룹 | 토큰 타입 | 검증 방법 |
|----------|-----------|-----------|
| 프론트 회원 API | User JWT | `JwtFilter` — group_idx 1~3 |
| 어드민 회원 API | Admin JWT | `AdminJwtFilter` — group_idx = 1 전용 |
| 프론트 API (공개) | 없음 / User JWT (선택) | `JwtOptionalFilter` |
| 어드민 API | Admin JWT 필수 | `AdminJwtFilter` |

### 토큰 Payload 구조

```json
// User Token
{
  "iss": "ci4-board",
  "sub": 42,
  "type": "user",
  "user_id": "hong",
  "nickname": "홍길동",
  "group_idx": 2,
  "iat": 1748736000,
  "exp": 1748739600
}

// Admin Token
{
  "iss": "ci4-board",
  "sub": 1,
  "type": "admin",
  "user_id": "admin",
  "group_idx": 1,
  "iat": 1748736000,
  "exp": 1748739600
}
```

> Admin Token과 User Token을 `type` 클레임으로 구분하여 어드민 API에서 `type === "admin"` 검증을 추가한다.

---

## 4. 소셜 로그인 연동

### 지원 플랫폼

| 플랫폼 | 방식 | 필요 항목 |
|--------|------|-----------|
| Google | OAuth2 (OIDC) | Client ID, Client Secret |
| 네이버 | OAuth2 | Client ID, Client Secret |
| 카카오 | OAuth2 | REST API Key |

### 패키지

```bash
composer require league/oauth2-client
composer require league/oauth2-google
```

네이버·카카오는 공식 `league/oauth2-*` 패키지가 없으므로 `GenericProvider` 직접 구현.

### DB 설계: tb_users_social (신규)

```sql
CREATE TABLE `tb_users_social` (
    `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
    `user_idx`      int unsigned    NOT NULL        COMMENT '연결된 자체 회원 idx',
    `provider`      varchar(20)     NOT NULL        COMMENT 'google | naver | kakao',
    `provider_id`   varchar(128)    NOT NULL        COMMENT '플랫폼 고유 ID',
    `email`         varchar(128)    DEFAULT NULL    COMMENT '플랫폼 이메일',
    `nickname`      varchar(64)     DEFAULT NULL    COMMENT '플랫폼 닉네임',
    `access_token`  text            DEFAULT NULL    COMMENT '플랫폼 Access Token (선택 저장)',
    `timestamp_insert` int unsigned NOT NULL,
    `timestamp_update` int unsigned DEFAULT NULL,
    PRIMARY KEY (`idx`),
    UNIQUE KEY `uq_provider_id` (`provider`, `provider_id`),
    KEY `fk_users__idx` (`user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='소셜 로그인 연결';
```

### 로그인 흐름

```
[신규 소셜 로그인]
소셜 인증 완료
    → provider_id로 tb_users_social 조회
    → 없으면: tb_users 자동 생성 + tb_users_social 연결
    → JWT 발급 → 클라이언트 반환

[기존 자체 회원 + 소셜 연결]
자체 로그인 상태에서 소셜 연결 요청
    → 동일 email이면 자동 연결
    → email 불일치면 사용자 확인 후 연결
```

### 소셜 전용 비밀번호 처리

소셜 로그인 전용 계정은 `super_secured_password = NULL` 허용.  
비밀번호 없는 계정은 자체 로그인 불가 처리.

---

## 5. 프론트엔드 기술 스택

### 요구사항 분석

| 구분 | 특성 |
|------|------|
| 어드민 프론트 | SEO 불필요, 인증 필수, 데이터 중심 UI |
| 사용자 프론트 | SEO 필요 (게시글 검색 노출), 공개 페이지 다수 |

### 렌더링 방식 선택

| 방식 | 설명 | 적합 대상 |
|------|------|-----------|
| CSR | 클라이언트 렌더링 (SPA) | 어드민 |
| SSR | 서버 렌더링 (요청마다) | 사용자 게시글 상세 |
| SSG | 빌드 시 정적 생성 | 공지사항, 고정 페이지 |
| ISR | 주기적 정적 재생성 | 인기 게시글 목록 |

### 권장 스택

#### 사용자 프론트: **Next.js 14+ (App Router)**

| 이유 | 내용 |
|------|------|
| SEO | SSR/SSG/ISR 모두 지원 |
| 생태계 | React 기반, 가장 넓은 생태계 |
| API 연동 | CI4 REST API와 fetch/axios 연동 간단 |
| 인증 | next-auth로 JWT + 소셜 로그인 통합 관리 |

```
기술: Next.js 14, TypeScript, Tailwind CSS, shadcn/ui, TanStack Query
```

#### 어드민 프론트: **React + Vite (CSR)**

SEO 불필요, 데이터 중심 UI에는 무거운 SSR 불필요.

```
기술: React 18, TypeScript, Vite, Ant Design / shadcn/ui, TanStack Query
```

### 프로젝트 분리 구조

```
ci4-board/          ← 현재 (CI4 API 서버)
ci4-board-web/      ← 사용자 프론트 (Next.js)
ci4-board-admin/    ← 어드민 프론트 (React + Vite)
```

### CI4 API ↔ 프론트 통신 구조

```
Next.js (서버)
  → CI4 API (서버 → 서버, CORS 불필요)
  → 렌더링된 HTML 반환

Next.js (클라이언트)
  → CI4 API (브라우저 → 서버, CORS 필요)
  → Authorization: Bearer {access_token}
```

---

## 6. DB 엔진 검토

### 현재: MySQL / MariaDB — 유지 결정

SQLite는 지원하지 않는다.

### PostgreSQL 검토

| 항목 | MySQL | PostgreSQL |
|------|-------|------------|
| 동시성 | 높음 | 더 높음 (MVCC) |
| JSON 지원 | JSON 컬럼 | JSONB (인덱싱 가능) |
| 마이그레이션 비용 | 없음 | 중간 (쿼리 일부 수정) |
| CI4 지원 | 완전 지원 | 완전 지원 |
| 운영 복잡도 | 낮음 | 중간 |

### 최종 결론

> **MySQL 유지**를 권장한다.  
> 현재 규모(50명 이하)에서 DB 엔진 교체로 얻는 이점보다 마이그레이션 비용이 크다.  
> 향후 트래픽이 급증하거나 JSON 쿼리가 복잡해지면 PostgreSQL 이관을 재검토한다.

---

## 7. CMS 추가 개발

### 콘텐츠 유형 정의

| 유형 | 설명 | 관리 위치 |
|------|------|-----------|
| 페이지 | 정적/동적 페이지 (소개, 이용약관 등) | 어드민 |
| 게시판 | 현재 구조 확장 | 어드민 |
| 배너 | 위치별 배너 이미지/링크 | 어드민 |
| 팝업 | 공지 팝업 (기간/위치 설정) | 어드민 |
| 메뉴 | 네비게이션 메뉴 구성 | 어드민 |
| 파일 라이브러리 | 미디어 파일 통합 관리 | 어드민 |

### 에디터 선정

| 에디터 | 장점 | 단점 | 적합도 |
|--------|------|------|--------|
| **CKEditor 5** | 모던 아키텍처, React/Vue 플러그인, 풍부한 기능 | 무거움, 상용 플러그인 유료 | ⭐⭐⭐⭐ |
| **TinyMCE** | 가장 성숙, 플러그인 생태계 최대 | 고급 기능 유료 | ⭐⭐⭐⭐ |
| **Quill** | 가볍고 커스터마이징 쉬움 | 기능 제한적 | ⭐⭐⭐ |
| **Tiptap** | ProseMirror 기반, Headless, React 최적 | 러닝커브 | ⭐⭐⭐⭐ |

> **권장: Tiptap** (Next.js 환경에서 Headless 에디터로 UI 자유도가 가장 높음)  
> 어드민 전용 간단 에디터는 **TinyMCE 무료 플랜**으로 충분.

### CMS DB 설계 (신규 테이블)

```sql
-- 페이지
tb_cms_page          (idx, slug, title, contents, status, timestamp_*)

-- 배너
tb_cms_banner        (idx, position, image_path, link_url, start_at, end_at, is_used)

-- 팝업
tb_cms_popup         (idx, title, contents, start_at, end_at, position, is_used)

-- 메뉴
tb_cms_menu          (idx, parent_idx, label, url, target, sequence, is_used)
```

---

## 로드맵 우선순위 및 일정

### Phase 1 — API 기반 인프라 ✅ 완료

- [x] 헤드리스 전환 설계 문서 완성
- [x] JWT 인프라 구축 (`JwtService`, `JwtFilter`, `ApiResponse` 트레이트)
- [x] 어드민/프론트 API 라우팅 분리 (`/api/v1/*`, `/api/admin/v1/*`)
- [x] Admin DB 분리 설정 (`ci4_board_admin`, Admin 전용 모델 4개)

### Phase 2 — 인증 API ✅ 완료

- [x] 자체 로그인 API (User / Admin JWT 분리 발급)
- [x] Refresh Token 발급·갱신·폐기 (`/api/v1/auth/refresh`)
- [x] `tb_users_token` 마이그레이션
- [x] 회원가입·프로필 수정·회원 탈퇴 API

### Phase 3 — 서비스 API ✅ 완료

- [x] 게시판/게시글/댓글 API (CRUD + 페이지네이션 + 권한 검사)
- [x] 파일 업로드 API (multipart, 5개/2MB/확장자 검증)
- [x] 쪽지 API (inbox/sent/읽음 처리)
- [x] 어드민 API (게시판 설정, 회원 관리, 사이트 설정, 게시글 관리)

### Phase 4 — 소셜 로그인 ✅ 완료

- [x] `tb_users_social` 마이그레이션
- [x] Google OAuth2 연동
- [x] 네이버 로그인 연동
- [x] 카카오 로그인 연동

### Phase 5 — 프론트엔드 (별도 프로젝트, FE 개발자 필요)

- [ ] Next.js 프로젝트 세팅 (`ci4-board-web`)
- [ ] React + Vite 어드민 프로젝트 세팅 (`ci4-board-admin`)
- [ ] next-auth 소셜 로그인 연동
- [ ] CI4 API 연동

### Phase 6 — CMS 🔄 진행 중

- [x] CMS 테이블 마이그레이션 (`tb_cms_page`, `tb_cms_banner`, `tb_cms_popup`, `tb_cms_menu`)
- [x] CMS API 설계 및 구현 (어드민 CRUD + 프론트 공개 API)
- [x] WYSIWYG 이미지 업로드 API (`POST /api/v1/files/wysiwyg`)
- [ ] 에디터 연동 (Tiptap / TinyMCE) — FE 작업
- [ ] 어드민 CMS 관리 화면 — FE 작업

### Phase 7 — 안정화

- [ ] Rate Limiting 적용
- [ ] CSRF 필터 활성화
- [ ] API 문서 자동화 (Swagger / apiDoc)
- [x] PHPStan 레벨 3 정적 분석 적용
- [ ] 통합 테스트 작성
