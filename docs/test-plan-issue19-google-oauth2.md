# 테스트 플랜: Google OAuth2 소셜 로그인 (issue #19 / PR #36)

## 사전 준비

- `.env`에 `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` 설정
- `php spark migrate` 실행 (`tb_users_social` 테이블 생성 확인)
- 테스트용 Google 계정 2개 준비:
  - **계정 A** — DB에 이메일 없는 완전 신규 사용자
  - **계정 B** — DB `tb_users`에 이미 이메일이 존재하는 기존 회원

---

## 1. Redirect 엔드포인트

### TC-01 정상 — redirect_url 반환

```
GET /api/v1/auth/social/google
```

**예상 결과**

```json
{
  "success": true,
  "data": { "redirect_url": "https://accounts.google.com/o/oauth2/v2/auth?..." },
  "message": "Google 인증 URL을 발급했습니다."
}
```

- `redirect_url`에 `state` 쿼리 파라미터 포함 여부 확인
- 세션에 `oauth2_state` 저장됐는지 확인 (세션 디버그 or 다음 콜백 검증으로 간접 확인)

### TC-02 환경변수 누락 — 500 반환

`.env`에서 `GOOGLE_CLIENT_ID` 제거 후 요청.

**예상 결과:** HTTP 500, `success: false`

---

## 2. Callback 엔드포인트 — 정상 케이스

### TC-03 신규 사용자 — 계정 자동 생성

1. TC-01로 `redirect_url` 획득 후 브라우저로 Google 인증 완료
2. Callback URL 자동 호출됨

**예상 결과**

```json
{
  "success": true,
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": { "idx": N, "user_id": "google_XXXX", "email": "...", ... }
  },
  "message": "소셜 로그인 성공"
}
```

**DB 확인**

- `tb_users`: `user_id = 'google_...'`, `super_secured_password IS NULL`, `status = 1` 레코드 생성
- `tb_users_social`: `provider = 'google'`, `provider_id`, `email`, `nickname` 레코드 생성
- `access_token` 파싱 후 `user_id` 클레임이 위 `tb_users.idx`와 일치

### TC-04 기존 이메일 회원 — tb_users 유지, tb_users_social 신규 생성

전제: 계정 B의 이메일이 `tb_users`에 이미 존재

1. 계정 B로 Google 인증 완료

**예상 결과**

- `tb_users`에 새 레코드 생성되지 않음 (기존 레코드 그대로)
- `tb_users_social`에 신규 레코드 생성 (기존 이메일 회원과 연결)
- 응답 `user.user_id`가 기존 `tb_users.user_id`와 동일

### TC-05 재로그인 — tb_users_social 갱신

TC-03 또는 TC-04 완료 후 동일 Google 계정으로 재로그인.

**예상 결과**

- `tb_users`, `tb_users_social` 레코드 수 변화 없음
- `tb_users_social.timestamp_update` 갱신됨
- JWT 재발급 성공

---

## 3. Callback 엔드포인트 — 에러 케이스

### TC-06 state 불일치 — 400

```
GET /api/v1/auth/social/google/callback?code=valid_code&state=wrong_state
```

**예상 결과:** HTTP 400, `"유효하지 않은 state 값입니다."`

### TC-07 state 파라미터 누락 — 400  ⚠️ 현재 버그

```
GET /api/v1/auth/social/google/callback?code=any_code
```

`state` 파라미터 없이 요청. 세션에 `oauth2_state`도 없는 상태.

**예상 결과:** HTTP 400  
**현재 동작:** null !== null → false이므로 검증 통과 (버그)

> `if (empty($state) || $state !== session()->get('oauth2_state'))` 로 수정 필요

### TC-08 code 누락 — 400

```
GET /api/v1/auth/social/google/callback?state=valid_state
```

**예상 결과:** HTTP 400, `"인증 코드가 없습니다."`

### TC-09 code 만료/위조 — 502

유효한 state지만 만료된(또는 임의 문자열) code 전달.

```
GET /api/v1/auth/social/google/callback?code=invalid_code&state={valid_state}
```

**예상 결과:** HTTP 502  
**확인:** 응답 body에 Google API 내부 메시지가 노출되지 않는지 확인 ⚠️ 현재 노출 버그

---

## 4. JWT 유효성

### TC-10 Access Token 검증

TC-03/04 응답의 `access_token`을 `GET /api/v1/auth/me` (또는 인증이 필요한 엔드포인트)에 Bearer 토큰으로 사용.

**예상 결과:** 정상 인증

### TC-11 Refresh Token으로 재발급

`refresh_token`으로 토큰 재발급 엔드포인트 호출.

**예상 결과:** 새 access_token 발급

---

## 5. DB 스키마 및 마이그레이션

### TC-12 마이그레이션

```bash
php spark migrate:rollback --batch 1  # 가장 최근 배치 롤백
php spark migrate                      # 재실행
```

**예상 결과:** 에러 없이 완료, `tb_users_social` 테이블 존재

```sql
DESCRIBE tb_users_social;
SHOW INDEX FROM tb_users_social;
```

- `PRIMARY KEY (idx)` 확인
- `UNIQUE KEY uq_provider_id (provider, provider_id)` 확인

### TC-13 UNIQUE 제약 — 중복 소셜 연결 방지

동일 `provider` + `provider_id` 조합으로 두 번 insert 시도 (upsert가 아닌 raw insert 테스트).

**예상 결과:** DB 레벨에서 duplicate key 오류 발생

---

## 6. 닉네임 충돌 처리

### TC-14 닉네임 중복 자동 해소

`tb_users`에 `nickname = '홍길동'`인 레코드가 이미 존재하는 상태에서  
닉네임이 `홍길동`인 Google 계정으로 신규 가입.

**예상 결과:** `tb_users.nickname = '홍길동_1'` 로 저장됨

---

## 7. 보안 점검

| 항목 | 확인 방법 | 기대 결과 |
|------|-----------|-----------|
| state 파라미터 누락 시 400 | TC-07 | 현재 버그 — 수정 후 통과 |
| 에러 응답에 Google 내부 메시지 미노출 | TC-09 | 현재 버그 — 수정 후 통과 |
| 액세스 토큰 DB 미저장 | `DESCRIBE tb_users_social` | access_token 컬럼 없음 확인 |
| Callback URL에 HTTPS 강제 | 운영 환경 설정 | Redirect URI가 HTTPS인지 확인 |

---

## 체크리스트 요약

- [ ] TC-01: redirect_url 반환 + state 포함
- [ ] TC-02: 환경변수 누락 500
- [ ] TC-03: 신규 사용자 자동 생성 + tb_users/social 레코드 확인
- [ ] TC-04: 기존 이메일 회원 연결 + tb_users 변경 없음
- [ ] TC-05: 재로그인 시 access_token/timestamp_update 갱신
- [ ] TC-06: state 불일치 → 400
- [ ] **TC-07: state 누락 → 400 (현재 버그, 수정 필요)**
- [ ] TC-08: code 누락 → 400
- [ ] **TC-09: 내부 에러 메시지 미노출 (현재 버그, 수정 필요)**
- [ ] TC-10: 발급 JWT로 인증 성공
- [ ] TC-11: Refresh Token 재발급
- [ ] TC-12: 마이그레이션 up/down 정상
- [ ] TC-13: UNIQUE 제약 동작
- [ ] TC-14: 닉네임 충돌 자동 해소
