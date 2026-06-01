# API Reference

> 작성일: 2026-06-01  
> Base URL: `http://localhost:8080`  
> Content-Type: `application/json`

---

## 공통 사항

### 인증

로그인이 필요한 엔드포인트는 `Authorization` 헤더에 Bearer 토큰을 포함해야 합니다.

```
Authorization: Bearer {access_token}
```

### 표준 응답 형식

**성공**
```json
{
    "success": true,
    "data": { ... },
    "message": null
}
```

**목록 (페이지네이션)**
```json
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "page": 1,
        "per_page": 15,
        "total": 120,
        "last_page": 8
    }
}
```

**실패**
```json
{
    "success": false,
    "data": null,
    "message": "오류 메시지",
    "errors": ["유효성 오류 배열 (선택)"]
}
```

### HTTP 상태코드

| 코드 | 의미 |
|------|------|
| 200 | 성공 |
| 201 | 생성 성공 |
| 401 | 인증 실패 (토큰 없음 / 만료) |
| 403 | 권한 없음 |
| 404 | 리소스 없음 |
| 422 | 유효성 검사 실패 |
| 500 | 서버 오류 |

---

## 1. 인증 API `/api/v1/auth`

### 1.1 로그인

```
POST /api/v1/auth/login
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| login_id | string | ✅ | 아이디 또는 이메일 |
| password | string | ✅ | 비밀번호 |

**Request 예시**
```json
{
    "login_id": "admin",
    "password": "admin1234"
}
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "access_token": "eyJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "idx": 1,
            "user_id": "admin",
            "nickname": "관리자",
            "email": "admin@example.com",
            "group_idx": 1,
            "group_name": "최고관리자"
        }
    }
}
```

---

### 1.2 회원가입

```
POST /api/v1/auth/register
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| user_id | string | ✅ | 아이디 (3~32자) |
| nickname | string | ✅ | 닉네임 (2~64자) |
| email | string | ✅ | 이메일 |
| password | string | ✅ | 비밀번호 (최소 6자) |
| password2 | string | ✅ | 비밀번호 확인 |

**Response 201**
```json
{
    "success": true,
    "data": null,
    "message": "회원가입이 완료되었습니다."
}
```

---

### 1.3 로그아웃

```
POST /api/v1/auth/logout
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| refresh_token | string | - | 폐기할 Refresh Token |

**Response 200**
```json
{
    "success": true,
    "data": null,
    "message": "로그아웃 되었습니다."
}
```

---

### 1.4 토큰 갱신

```
POST /api/v1/auth/refresh
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| refresh_token | string | ✅ | 유효한 Refresh Token |

**Response 200**
```json
{
    "success": true,
    "data": {
        "access_token": "eyJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

---

### 1.5 내 정보 조회

```
GET /api/v1/auth/me
Authorization: Bearer {access_token}
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "idx": 1,
        "user_id": "admin",
        "nickname": "관리자",
        "email": "admin@example.com",
        "group_idx": 1,
        "level": 99,
        "article_count": 5,
        "comment_count": 12
    }
}
```

---

### 1.6 프로필 수정

```
PUT /api/v1/auth/profile
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| current_password | string | ✅ | 현재 비밀번호 |
| nickname | string | ✅ | 새 닉네임 (2~64자) |
| email | string | ✅ | 새 이메일 |
| new_password | string | - | 새 비밀번호 (최소 6자) |
| new_password2 | string | - | 새 비밀번호 확인 |

---

### 1.7 회원 탈퇴

```
DELETE /api/v1/auth/withdraw
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| password | string | ✅ | 현재 비밀번호 |

---

## 2. 게시판 API `/api/v1/boards`

### 2.1 게시판 목록

```
GET /api/v1/boards
Authorization: Bearer {access_token}  (선택)
```

**Response 200**
```json
{
    "success": true,
    "data": [
        {
            "idx": 1,
            "bbs_id": "free",
            "bbs_name": "자유게시판",
            "list_count": "15"
        }
    ]
}
```

---

### 2.2 게시판 상세

```
GET /api/v1/boards/:bbsId
Authorization: Bearer {access_token}  (선택)
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "idx": 1,
        "bbs_id": "free",
        "bbs_name": "자유게시판",
        "permissions": {
            "view_list": [0, 1, 2, 3],
            "view_article": [0, 1, 2, 3],
            "write_article": [1, 2, 3],
            "write_comment": [1, 2, 3]
        }
    }
}
```

---

## 3. 게시글 API `/api/v1/boards/:bbsId/articles`

### 3.1 게시글 목록

```
GET /api/v1/boards/:bbsId/articles
Authorization: Bearer {access_token}  (선택)
```

**Query Parameters**

| 파라미터 | 타입 | 기본값 | 설명 |
|----------|------|--------|------|
| page | int | 1 | 페이지 번호 |
| per_page | int | 15 | 페이지당 수 (최대 50) |
| keyword | string | - | 제목 검색어 |

**Response 200**
```json
{
    "success": true,
    "data": [
        {
            "idx": 42,
            "title": "공지사항입니다",
            "nickname": "관리자",
            "comment_count": 3,
            "hit_count": 120,
            "is_notice": 1,
            "timestamp_insert": 1748736000
        }
    ],
    "meta": {
        "page": 1,
        "per_page": 15,
        "total": 87,
        "last_page": 6
    }
}
```

---

### 3.2 게시글 상세

```
GET /api/v1/boards/:bbsId/articles/:idx
Authorization: Bearer {access_token}  (선택)
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "idx": 42,
        "title": "공지사항입니다",
        "contents": "본문 내용...",
        "nickname": "관리자",
        "hit_count": 121,
        "is_notice": 1,
        "is_secret": 0,
        "timestamp_insert": 1748736000,
        "tags": [{"tag": "공지", "sequence": 1}],
        "urls": [],
        "files": [],
        "comments": []
    }
}
```

---

### 3.3 게시글 작성

```
POST /api/v1/boards/:bbsId/articles
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| title | string | ✅ | 제목 |
| contents | string | ✅ | 본문 |
| is_secret | int | - | 비밀글 여부 (0/1) |
| tags | string[] | - | 태그 배열 |
| urls | string[] | - | 관련 URL 배열 |

**Request 예시**
```json
{
    "title": "안녕하세요",
    "contents": "첫 글입니다.",
    "tags": ["인사", "첫글"],
    "urls": ["https://example.com"]
}
```

**Response 201**
```json
{
    "success": true,
    "data": { "idx": 43 },
    "message": "게시글이 작성되었습니다."
}
```

---

### 3.4 게시글 수정

```
PUT /api/v1/boards/:bbsId/articles/:idx
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| title | string | ✅ | 제목 |
| contents | string | ✅ | 본문 |
| tags | string[] | - | 태그 배열 (전체 교체) |
| urls | string[] | - | URL 배열 (전체 교체) |

---

### 3.5 게시글 삭제

```
DELETE /api/v1/boards/:bbsId/articles/:idx
Authorization: Bearer {access_token}
```

---

## 4. 댓글 API `/api/v1/boards/:bbsId/articles/:idx/comments`

### 4.1 댓글 목록

```
GET /api/v1/boards/:bbsId/articles/:idx/comments
Authorization: Bearer {access_token}  (선택)
```

**Response 200**
```json
{
    "success": true,
    "data": [
        {
            "idx": 5,
            "user_idx": 2,
            "nickname": "홍길동",
            "comment": "댓글 내용",
            "timestamp_insert": 1748736000
        }
    ]
}
```

---

### 4.2 댓글 작성

```
POST /api/v1/boards/:bbsId/articles/:idx/comments
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| comment | string | ✅ | 댓글 내용 |

---

### 4.3 댓글 수정

```
PUT /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| comment | string | ✅ | 수정할 댓글 내용 |

---

### 4.4 댓글 삭제

```
DELETE /api/v1/boards/:bbsId/articles/:idx/comments/:cIdx
Authorization: Bearer {access_token}
```

---

## 5. 파일 API `/api/v1/files`

### 5.1 파일 업로드

```
POST /api/v1/files
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
```

**Form Data**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| bbs_idx | int | ✅ | 게시판 idx |
| article_idx | int | ✅ | 게시글 idx |
| attachments[] | file | ✅ | 파일 (최대 5개, 2MB) |

**허용 확장자:** jpg, jpeg, gif, png, txt, doc, docx, xls, xlsx, pdf, ppt, pptx, zip, 7z, alz, rar

**Response 201**
```json
{
    "success": true,
    "data": {
        "uploaded": [
            {
                "idx": 10,
                "original_filename": "document.pdf",
                "capacity": 102400
            }
        ],
        "errors": null
    }
}
```

---

### 5.2 파일 다운로드

```
GET /api/v1/files/:idx/download
```

응답: 파일 바이너리 (`Content-Disposition: attachment`)

---

### 5.3 파일 삭제

```
DELETE /api/v1/files/:idx
Authorization: Bearer {access_token}
```

파일 소유자 또는 관리자만 삭제 가능합니다.

---

## 6. 쪽지 API `/api/v1/messages`

### 6.1 받은 쪽지함

```
GET /api/v1/messages/inbox
Authorization: Bearer {access_token}
```

**Query Parameters**

| 파라미터 | 기본값 | 설명 |
|----------|--------|------|
| page | 1 | 페이지 번호 |

---

### 6.2 보낸 쪽지함

```
GET /api/v1/messages/sent
Authorization: Bearer {access_token}
```

---

### 6.3 쪽지 읽기

```
GET /api/v1/messages/:idx
Authorization: Bearer {access_token}
```

수신자가 처음 열면 `is_read=1`로 자동 갱신됩니다.

**Response 200**
```json
{
    "success": true,
    "data": {
        "idx": 3,
        "title": "안녕하세요",
        "contents": "쪽지 내용",
        "sender_user_id": "admin",
        "sender_nickname": "관리자",
        "receiver_user_id": "hong",
        "receiver_nickname": "홍길동",
        "is_read": 1,
        "is_sender": false,
        "timestamp_send": 1748736000
    }
}
```

---

### 6.4 쪽지 전송

```
POST /api/v1/messages
Authorization: Bearer {access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| to | string | ✅ | 수신자 user_id 또는 닉네임 |
| title | string | - | 제목 |
| contents | string | ✅ | 내용 |

---

### 6.5 쪽지 삭제

```
DELETE /api/v1/messages/:idx
Authorization: Bearer {access_token}
```

발신자/수신자 각자 독립적으로 삭제됩니다.

---

## 7. 관리자 인증 API `/api/admin/v1/auth`

### 7.1 관리자 로그인

```
POST /api/admin/v1/auth/login
```

`group_idx=1` (최고관리자) 계정만 허용합니다.

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| login_id | string | ✅ | 관리자 아이디 |
| password | string | ✅ | 비밀번호 |

**Response 200** — `type: "admin"` 클레임이 포함된 JWT 발급

---

### 7.2 관리자 로그아웃

```
POST /api/admin/v1/auth/logout
Authorization: Bearer {admin_access_token}
```

---

## 8. 관리자 게시판 API `/api/admin/v1/boards`

### 8.1 게시판 목록

```
GET /api/admin/v1/boards
Authorization: Bearer {admin_access_token}
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "boards": [
            {
                "idx": 1,
                "bbs_id": "free",
                "bbs_name": "자유게시판",
                "bbs_used": "1",
                "list_count": "15",
                "comment_used": "1",
                "perm_view_list": "a:4:{...}",
                "perm_write_article": "a:3:{...}"
            }
        ],
        "groups": [
            {"idx": 1, "group_name": "최고관리자"},
            {"idx": 2, "group_name": "일반회원"}
        ]
    }
}
```

---

### 8.2 게시판 설정 저장

```
PUT /api/admin/v1/boards/:bbsId
Authorization: Bearer {admin_access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| bbs_name | string | ✅ | 게시판 이름 |
| bbs_used | bool | - | 활성화 여부 |
| bbs_count_list_article | int | - | 목록 수 (기본 15) |
| bbs_comment_used | bool | - | 댓글 기능 여부 |
| view_list | int[] | - | 목록 보기 허용 그룹 idx |
| view_article | int[] | - | 글 보기 허용 그룹 idx |
| write_article | int[] | - | 글 쓰기 허용 그룹 idx |
| write_comment | int[] | - | 댓글 쓰기 허용 그룹 idx |

---

## 9. 관리자 회원 API `/api/admin/v1/members`

### 9.1 회원 목록

```
GET /api/admin/v1/members
Authorization: Bearer {admin_access_token}
```

**Query Parameters**

| 파라미터 | 설명 |
|----------|------|
| keyword | 아이디·닉네임·이메일 검색 |
| status | 상태 필터 (0=탈퇴, 1=정상) |
| page | 페이지 번호 (기본 1) |

---

### 9.2 회원 수정

```
PUT /api/admin/v1/members/:idx
Authorization: Bearer {admin_access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| group_idx | int | ✅ | 그룹 idx |
| status | int | ✅ | 상태 (0/1) |
| nickname | string | ✅ | 닉네임 |
| email | string | ✅ | 이메일 |
| new_password | string | - | 새 비밀번호 (6자 이상) |

---

## 10. 관리자 게시글 API `/api/admin/v1/articles`

### 10.1 게시글 목록

```
GET /api/admin/v1/articles
Authorization: Bearer {admin_access_token}
```

**Query Parameters**

| 파라미터 | 설명 |
|----------|------|
| keyword | 제목·닉네임 검색 |
| bbs_id | 게시판 슬러그 필터 |
| page | 페이지 번호 |

---

### 10.2 게시글 수정

```
PUT /api/admin/v1/articles/:idx
Authorization: Bearer {admin_access_token}
```

**Request Body**

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| title | string | ✅ | 제목 |
| contents | string | ✅ | 본문 |
| is_notice | bool | - | 공지 여부 |

---

### 10.3 게시글 삭제

```
DELETE /api/admin/v1/articles/:idx
Authorization: Bearer {admin_access_token}
```

---

## 11. 관리자 사이트 설정 API `/api/admin/v1/setting`

### 11.1 설정 조회

```
GET /api/admin/v1/setting
Authorization: Bearer {admin_access_token}
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "browser_title_fix_value": "CI4 Board",
        "join_used": "1",
        "site_block_used": "0",
        "site_block_contents": "현재 사이트 점검 중입니다."
    }
}
```

---

### 11.2 설정 저장

```
PUT /api/admin/v1/setting
Authorization: Bearer {admin_access_token}
```

**Request Body**

| 필드 | 타입 | 설명 |
|------|------|------|
| browser_title_fix_value | string | 브라우저 타이틀 접미어 |
| join_used | bool | 회원가입 허용 여부 |
| site_block_used | bool | 사이트 차단 여부 |
| site_block_contents | string | 차단 시 표시 메시지 |
