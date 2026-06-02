# Design Template 시스템 설계

## 개요

zip 파일 업로드로 테마를 설치하는 플러그인 방식의 디자인 템플릿 시스템.

### 프로젝트 구조와 역할 분담

```
ci4-board       → CI4 PHP API 서버  (템플릿 설치/관리, 테마 설정 API 제공)
ci4-board-admin → React + Vite      (어드민 템플릿 관리 UI)
ci4-board-web   → Next.js           (활성 템플릿 CSS 로드 및 적용)
```

백엔드는 PHP를 렌더링하지 않으므로 **PHP 레이아웃/뷰 파일은 템플릿에 포함되지 않는다.**  
템플릿은 CSS + 에셋 번들이며, 백엔드는 설치/관리만 담당한다.

---

## 템플릿 구조 (zip 내용물)

```
my-theme.zip
  template.json        ← 필수: 메타데이터
  theme.css            ← 필수: CSS 변수 및 스타일 오버라이드
  logo.png             ← 선택: 로고 이미지
  assets/              ← 선택: 폰트, 추가 이미지 등
    fonts/
    images/
```

### template.json 규격

```json
{
  "name": "my-theme",
  "label": "My Theme",
  "version": "1.0.0",
  "author": "someone",
  "description": "테마 설명",
  "logo": "logo.png",
  "preview": "preview.png"
}
```

- `name`: 디렉토리명과 일치 (영문 소문자, 하이픈 허용)
- `logo`: 템플릿 내 로고 파일 경로 (선택)
- `preview`: 어드민 목록에 표시할 미리보기 이미지 (선택)

### theme.css 작성 방식

Next.js `globals.css`의 CSS 변수를 오버라이드하는 방식으로 작성한다.

```css
/* my-theme/theme.css */
:root {
  --background: #0f0f0f;
  --foreground: #f0f0f0;
  --primary:    #6366f1;
  --primary-hover: #4f46e5;
  --nav-bg:     #1a1a2e;
  --nav-text:   #e2e8f0;
}
```

---

## 설치 디렉토리

```
app/DesignTemplates/
  default/
    template.json
    theme.css
    logo.png

  my-theme/
    template.json
    theme.css
    logo.png
    assets/
      fonts/
```

설치 후 에셋은 `Publisher`로 웹 접근 가능한 경로에 복사된다:

```
public/templates/{name}/theme.css
public/templates/{name}/logo.png
public/templates/{name}/assets/...
```

---

## 핵심 CI4 서비스

| 역할 | 사용 서비스 | 이유 |
|---|---|---|
| **에셋 배포** | `Services::publisher()` | 설치 시 `public/templates/{name}/`에 자동 복사 |
| **활성 템플릿 저장** | `tb_site_config` (기존 어드민 DB) | `config_key = 'active_template'` |
| **zip 처리** | PHP `ZipArchive` (내장) | 별도 패키지 불필요 |
| **뷰 렌더러** | 사용 안 함 | 프론트엔드가 렌더링 담당 |

---

## 백엔드 컴포넌트

```
app/
  Libraries/
    TemplateManager.php      ← zip 설치 / 삭제 / 활성화
  Controllers/Api/V1/Admin/
    TemplateController.php   ← 템플릿 관리 API
```

### API 엔드포인트

| 메서드 | 경로 | 설명 | 인증 |
|---|---|---|---|
| `GET` | `/api/v1/cms/active-template` | 활성 템플릿 정보 반환 | 불필요 |
| `GET` | `/api/v1/admin/templates` | 설치된 템플릿 목록 | 관리자 |
| `POST` | `/api/v1/admin/templates` | zip 업로드 및 설치 | 관리자 |
| `PUT` | `/api/v1/admin/templates/{name}/activate` | 템플릿 활성화 | 관리자 |
| `DELETE` | `/api/v1/admin/templates/{name}` | 템플릿 삭제 | 관리자 |

### GET /api/v1/cms/active-template 응답

```json
{
  "success": true,
  "data": {
    "name":    "my-theme",
    "label":   "My Theme",
    "css_url": "/templates/my-theme/theme.css",
    "logo_url": "/templates/my-theme/logo.png"
  }
}
```

---

## 프론트엔드 적용 (ci4-board-web)

`src/app/layout.tsx`에서 활성 템플릿 정보를 받아 CSS를 동적으로 로드한다.

```tsx
// src/app/layout.tsx
export default async function RootLayout({ children }) {
  let cssUrl: string | null = null
  let logoUrl: string | null = null

  try {
    const theme = await getActiveTemplate()
    cssUrl  = theme.data.css_url  ?? null
    logoUrl = theme.data.logo_url ?? null
  } catch {}

  return (
    <html lang="ko">
      <head>
        {cssUrl && <link rel="stylesheet" href={cssUrl} />}
      </head>
      <body>
        <Navigation logoUrl={logoUrl} />
        <main>{children}</main>
        <Footer />
      </body>
    </html>
  )
}
```

`globals.css`의 CSS 변수를 `theme.css`가 오버라이드하는 구조이므로  
Tailwind 클래스(`bg-background`, `text-foreground` 등)는 변경 없이 테마가 적용된다.

### lib/api.ts 추가

```ts
export const getActiveTemplate = () =>
  apiFetch<{ success: boolean; data: { name: string; label: string; css_url: string; logo_url: string } }>(
    '/api/v1/cms/active-template',
    { revalidate: 60 }
  )
```

---

## 어드민 적용 (ci4-board-admin)

`src/api/cms.ts`에 템플릿 관리 API를 추가하고, 템플릿 관리 페이지를 신설한다.

```
src/pages/cms/TemplatePage.tsx   ← 설치된 템플릿 목록, 활성화, 삭제
```

```ts
// src/api/cms.ts 추가
export const fetchTemplates = () => client.get('/admin/templates')
export const uploadTemplate = (formData: FormData) =>
  client.post('/admin/templates', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
export const activateTemplate = (name: string) =>
  client.put(`/admin/templates/${name}/activate`)
export const deleteTemplate = (name: string) =>
  client.delete(`/admin/templates/${name}`)
```

---

## 설치 흐름

```
관리자 zip 업로드 (ci4-board-admin)
  → POST /api/v1/admin/templates (multipart/form-data)
  → ZipArchive 압축 해제
  → template.json 유효성 검사 (name, version 필수)
  → PHP 파일 포함 여부 확인 → 포함 시 설치 거부
  → app/DesignTemplates/{name}/ 에 저장
  → Publisher로 에셋 → public/templates/{name}/ 복사
  → 설치 완료 응답

활성화
  → PUT /api/v1/admin/templates/{name}/activate
  → tb_site_config (config_key='active_template') 업데이트
  → 캐시 초기화
  → 이후 프론트엔드가 /api/v1/cms/active-template 호출 시 새 테마 반환
  → Next.js revalidate(60)으로 최대 60초 내 반영
```

---

## 구현 순서

1. **백엔드**
   - `TemplateManager` 라이브러리 (zip 설치/삭제/활성화)
   - `Api/V1/Admin/TemplateController` (CRUD API)
   - `GET /api/v1/cms/active-template` 공개 엔드포인트
   - `app/DesignTemplates/default/` 기본 템플릿 생성

2. **어드민** (`ci4-board-admin`)
   - `src/api/cms.ts` — 템플릿 API 함수 추가
   - `src/pages/cms/TemplatePage.tsx` — 목록/업로드/활성화 UI

3. **프론트엔드** (`ci4-board-web`)
   - `src/lib/api.ts` — `getActiveTemplate()` 추가
   - `src/app/layout.tsx` — `<link>` 동적 주입
   - `src/app/globals.css` — CSS 변수 기본값 정비

---

## 보안 고려사항

- zip 해제 전 경로 traversal 검사 (`../` 포함 파일명 거부)
- **PHP 파일(`.php`) 포함 시 설치 즉시 거부**
- 허용 확장자 whitelist: `.css`, `.js`, `.json`, `.png`, `.jpg`, `.svg`, `.webp`, `.woff`, `.woff2`, `.ttf`
- `app/DesignTemplates/`는 웹 루트 외부 — 직접 접근 불가
- `public/templates/`에는 에셋 파일만 복사 (PHP 제외)
- 파일 크기 제한 권장 (zip 최대 10MB)
