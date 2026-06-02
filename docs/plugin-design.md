# CI4 Plugin Architecture 설계

## 개요

CodeIgniter 4 기반의 플러그인 시스템으로, WordPress처럼 ZIP 파일을 업로드해 플러그인을 설치·관리할 수 있는 구조입니다.
Service Provider 패턴을 기반으로 하며, 플러그인별 라우트 격리와 어드민 메뉴 연동을 지원합니다.

> **헤드리스 구조 적용 기준**
> - 백엔드(`ci4-board`): JSON API만 반환, PHP 뷰 렌더링 없음
> - 어드민(`ci4-board-admin`): React + Vite SPA
> - 프론트엔드(`ci4-board-web`): Next.js, 모든 UI 렌더링 담당

---

## 1. 디렉터리 구조

```
app/
├── Plugins/
│   └── getWether/
│       ├── plugin.json          ← 플러그인 메타 정보
│       ├── Plugin.php           ← PluginInterface 구현 (진입점)
│       ├── Controllers/
│       │   └── WeatherController.php
│       ├── Models/
│       │   └── WeatherModel.php
│       ├── Views/               ← [수정 필요 #1] 헤드리스 구조에서 사용 불가 — 아래 참조
│       │   └── index.php
│       ├── Config/
│       │   └── Routes.php
│       └── Helpers/
│
├── Controllers/
│   └── Admin/
│       ├── PluginController.php  ← [수정 필요 #2] PHP 페이지 컨트롤러 → API 컨트롤러로 변경 필요
│       └── MenuController.php    ← [수정 필요 #3] 제거 필요 — tb_cms_menu + React 어드민으로 대체
│
├── Libraries/
│   └── PluginManager.php
│
├── Contracts/
│   └── PluginInterface.php
│
└── Config/
    └── Routes.php               ← [수정 필요 #4] /plugin/... → /api/v1/plugin/... 로 변경 필요
```

---

## 2. 플러그인 메타 정보 (plugin.json)

플러그인 디렉터리 루트에 위치하며, 플러그인의 식별 정보와 사용 방법을 정의합니다.

```json
{
    "name": "getWether",
    "display_name": "날씨 정보 플러그인",
    "version": "1.0.0",
    "description": "OpenWeather API를 이용해 현재 날씨를 조회합니다.",
    "author": "홍길동",
    "author_email": "hong@example.com",
    "usage": "메뉴 관리에서 URL을 /plugin/getWether/weather 로 설정하세요.",
    "entry_class": "App\\Plugins\\getWether\\Plugin",
    "min_ci4_version": "4.0.0"
}
```

> **[수정 필요 #5] `usage` 필드의 URL**
>
> 현재 값 `/plugin/getWether/weather`는 CI4 서버 라우트 경로입니다.
> `tb_cms_menu`의 `url` 컬럼에는 **Next.js 프론트엔드 경로**가 저장되어야 합니다.
>
> 플러그인이 프론트엔드 페이지를 제공하는 경우 두 가지 URL이 존재합니다:
> - API 경로 (CI4): `/api/v1/plugin/getWether/weather` ← 데이터 제공
> - 프론트 경로 (Next.js): `/weather` 또는 `/plugin/getWether` ← 메뉴에 등록하는 URL
>
> `usage` 필드는 프론트엔드 경로를 안내해야 합니다:
> ```json
> "usage": "CMS 메뉴 관리에서 URL을 /weather 로 설정하면 프론트엔드에서 날씨 페이지가 표시됩니다."
> ```

| 필드 | 설명 |
|---|---|
| `name` | 플러그인 디렉터리명 (영문, 고유값) |
| `display_name` | 관리 화면에 표시되는 이름 |
| `version` | 플러그인 버전 |
| `description` | 플러그인 상세 설명 |
| `author` | 작성자 이름 |
| `author_email` | 작성자 이메일 |
| `usage` | 플러그인 사용 방법 — 프론트엔드 경로 기준으로 안내 |
| `entry_class` | Plugin.php의 클래스 전체 경로 |
| `min_ci4_version` | 최소 요구 CI4 버전 |

---

## 3. PluginInterface

변경 없이 유지합니다.

```php
// app/Contracts/PluginInterface.php
namespace App\Contracts;

interface PluginInterface
{
    public function register(): void;   // 서비스 바인딩
    public function boot(): void;       // 라우트, 이벤트 등록
    public function install(): void;    // DB 마이그레이션 등 최초 설치 작업
    public function uninstall(): void;  // 삭제 시 정리 작업
}
```

| 메서드 | 호출 시점 | 역할 |
|---|---|---|
| `register()` | 매 요청 시 (활성 플러그인) | DI 컨테이너에 서비스 바인딩 |
| `boot()` | register() 이후 | 라우트·이벤트 등록 |
| `install()` | 플러그인 활성화 시 1회 | DB 테이블 생성 등 초기화 |
| `uninstall()` | 플러그인 삭제 시 1회 | DB 테이블 제거 등 정리 |

---

## 4. Plugin.php (플러그인 진입점)

변경 없이 유지합니다.

```php
// app/Plugins/getWether/Plugin.php
namespace App\Plugins\getWether;

use App\Contracts\PluginInterface;

class Plugin implements PluginInterface
{
    public function register(): void
    {
        // 필요한 서비스 바인딩
    }

    public function boot(): void
    {
        // Config/Routes.php는 PluginManager가 자동 로드
    }

    public function install(): void
    {
        // 최초 설치 시: DB 테이블 생성 등
    }

    public function uninstall(): void
    {
        // 삭제 시: DB 테이블 제거 등
    }
}
```

---

## 5. 플러그인 내부 라우트 (Config/Routes.php)

> **[수정 필요 #4] URL prefix 변경**
>
> 현재 설계의 `/plugin/{name}/...`는 세 가지 문제가 있습니다:
> 1. JWT 필터(`JwtFilter`)가 적용되지 않아 인증 우회 가능
> 2. CORS 필터가 `/api/v1/...` 그룹에만 적용되어 있어 프론트엔드에서 호출 불가
> 3. 기존 API 경로 규칙(`/api/v1/...`)과 불일치
>
> **변경 전 (현재)**
> ```php
> // app/Plugins/getWether/Config/Routes.php
> $routes->group('plugin/getWether', function ($routes) {
>     $routes->get('weather',        'App\Plugins\getWether\Controllers\WeatherController::index');
>     $routes->get('weather/(:any)', 'App\Plugins\getWether\Controllers\WeatherController::show/$1');
> });
> ```
>
> **변경 후 (헤드리스 적용)**
> ```php
> // app/Plugins/getWether/Config/Routes.php
> $routes->group('api/v1/plugin/getWether', ['filter' => 'jwt_optional'], function ($routes) {
>     $routes->get('weather',        'App\Plugins\getWether\Controllers\WeatherController::index');
>     $routes->get('weather/(:any)', 'App\Plugins\getWether\Controllers\WeatherController::show/$1');
> });
> ```
>
> 인증이 필요한 엔드포인트는 `jwt_optional` 대신 `jwt` 필터를 사용합니다.

**접근 URL 예시 (변경 후)**

```
GET /api/v1/plugin/getWether/weather          → JSON 반환
GET /api/v1/plugin/getWether/weather/seoul    → JSON 반환
```

---

## 5-1. 플러그인 컨트롤러 응답 형식

> **[수정 필요 #1] Views/ 디렉토리 및 PHP 뷰 렌더링 제거**
>
> 헤드리스 구조에서 백엔드는 HTML을 생성하지 않습니다.
> `Views/` 디렉토리는 플러그인에 포함할 필요가 없으며,
> 컨트롤러는 반드시 JSON을 반환해야 합니다.
>
> **변경 전 (현재)**
> ```php
> // WeatherController.php
> class WeatherController extends BaseController
> {
>     public function index(): string
>     {
>         $data = ['weather' => $this->getWeatherData()];
>         return view('App\Plugins\getWether\Views\index', $data); // ← 사용 불가
>     }
> }
> ```
>
> **변경 후 (헤드리스 적용)**
> ```php
> // WeatherController.php
> use App\Traits\ApiResponse;
>
> class WeatherController extends BaseController
> {
>     use ApiResponse;
>
>     public function index(): ResponseInterface
>     {
>         $data = $this->getWeatherData();
>         return $this->success($data);  // → JSON 반환
>     }
> }
> ```
>
> 디렉터리 구조에서 `Views/`는 제거하고, 프론트엔드 페이지가 필요한 경우
> `ci4-board-web`에 별도 페이지를 추가합니다. (§ 10-1 참조)

---

## 6. PluginManager

`install()`, `activate()`, `deactivate()`, `delete()`, `loadRoutes()` 로직은 변경 없이 유지합니다.

> **[수정 필요 #2] `PluginController.php` — PHP 페이지에서 API 컨트롤러로 변경**
>
> 현재 설계의 `Controllers/Admin/PluginController.php`는 PHP 뷰를 반환하는 관리 페이지 컨트롤러입니다.
> 헤드리스 구조에서는 `ci4-board-admin` (React)이 플러그인 관리 UI를 담당하므로,
> CI4 컨트롤러는 JSON API로 교체해야 합니다.
>
> **변경 전 (현재 설계)**
> ```
> app/Controllers/Admin/PluginController.php  ← PHP 뷰 반환 (관리 페이지)
> ```
>
> **변경 후 (헤드리스 적용)**
> ```
> app/Controllers/Api/V1/Admin/PluginController.php  ← JSON API
> ```
>
> ```php
> // 제공할 API 엔드포인트
> GET    /api/v1/admin/plugins           → 설치된 플러그인 목록
> POST   /api/v1/admin/plugins           → zip 업로드 및 설치
> PUT    /api/v1/admin/plugins/{name}/activate   → 활성화
> PUT    /api/v1/admin/plugins/{name}/deactivate → 비활성화
> DELETE /api/v1/admin/plugins/{name}    → 삭제
> ```

```php
// app/Libraries/PluginManager.php (변경 없음)
namespace App\Libraries;

use App\Contracts\PluginInterface;
use CodeIgniter\Router\RouteCollection;

class PluginManager
{
    private static ?self $instance = null;
    private array $plugins = [];
    private array $activePlugins = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bootActive(): void
    {
        $this->activePlugins = $this->getActiveFromDB();

        foreach ($this->activePlugins as $name) {
            $this->loadPlugin($name);
        }

        foreach ($this->plugins as $plugin) {
            $plugin->register();
        }
        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }
    }

    public function loadRoutes(RouteCollection $routes): void
    {
        foreach ($this->activePlugins as $name) {
            $routeFile = APPPATH . "Plugins/{$name}/Config/Routes.php";
            if (file_exists($routeFile)) {
                include $routeFile;
            }
        }
    }

    public function install(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'ZIP 파일을 열 수 없습니다.'];
        }

        $pluginName = $this->extractPluginName($zip);
        if (!$pluginName) {
            return ['success' => false, 'message' => '플러그인 구조가 올바르지 않습니다.'];
        }

        $targetPath = APPPATH . "Plugins/{$pluginName}";
        if (is_dir($targetPath)) {
            return ['success' => false, 'message' => '이미 설치된 플러그인입니다.'];
        }

        $zip->extractTo(APPPATH . 'Plugins/');
        $zip->close();

        if (!$this->validate($pluginName)) {
            $this->removeDirectory($targetPath);
            return ['success' => false, 'message' => 'plugin.json 또는 Plugin.php가 없습니다.'];
        }

        $this->saveToDb($pluginName, $this->readMeta($pluginName));

        return ['success' => true, 'plugin' => $pluginName];
    }

    public function activate(string $name): void
    {
        $plugin = $this->loadPlugin($name);
        $plugin->install();
        $this->setActiveInDB($name, true);
    }

    public function deactivate(string $name): void
    {
        $this->setActiveInDB($name, false);
    }

    public function delete(string $name): void
    {
        $plugin = $this->loadPlugin($name);
        $plugin->uninstall();
        $this->setActiveInDB($name, false);
        $this->removeFromDb($name);
        $this->removeDirectory(APPPATH . "Plugins/{$name}");
    }

    public function readMeta(string $name): array
    {
        $path = APPPATH . "Plugins/{$name}/plugin.json";
        return json_decode(file_get_contents($path), true);
    }

    private function loadPlugin(string $name): PluginInterface
    {
        if (!isset($this->plugins[$name])) {
            $class = "App\\Plugins\\{$name}\\Plugin";
            $this->plugins[$name] = new $class();
        }
        return $this->plugins[$name];
    }

    private function validate(string $name): bool
    {
        return file_exists(APPPATH . "Plugins/{$name}/plugin.json")
            && file_exists(APPPATH . "Plugins/{$name}/Plugin.php");
    }

    private function extractPluginName(\ZipArchive $zip): ?string
    {
        $first = $zip->getNameIndex(0);
        $parts = explode('/', $first);
        return $parts[0] ?: null;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) return;
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $full = "$path/$file";
            is_dir($full) ? $this->removeDirectory($full) : unlink($full);
        }
        rmdir($path);
    }

    private function getActiveFromDB(): array { /* ... */ }
    private function saveToDb(string $name, array $meta): void { /* ... */ }
    private function setActiveInDB(string $name, bool $active): void { /* ... */ }
    private function removeFromDb(string $name): void { /* ... */ }
}
```

---

## 7. 앱 메인 라우트 — 동적 플러그인 라우팅

변경 없이 유지합니다. (플러그인 내부 Routes.php의 경로만 변경)

```php
// app/Config/Routes.php
$pluginManager = \App\Libraries\PluginManager::getInstance();
$pluginManager->loadRoutes($routes);
```

---

## 8. 앱 시작 시 부트스트랩

변경 없이 유지합니다.

```php
// app/Config/Events.php
use CodeIgniter\Events\Events;
use App\Libraries\PluginManager;

Events::on('pre_system', function () {
    PluginManager::getInstance()->bootActive();
});
```

---

## 9. DB 스키마

### plugins 테이블 (변경 없음)

```sql
CREATE TABLE plugins (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(200),
    version      VARCHAR(20),
    description  TEXT,
    author       VARCHAR(100),
    author_email VARCHAR(200),
    usage_guide  TEXT,
    is_active    TINYINT(1) DEFAULT 0,
    installed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### admin_menus 테이블

> **[수정 필요 #3] `admin_menus` 테이블 제거 — `tb_cms_menu`로 통합**
>
> `admin_menus` 테이블은 이미 구현된 `tb_cms_menu`와 역할이 완전히 중복됩니다.
>
> | 비교 항목 | admin_menus (현재 설계) | tb_cms_menu (구현 완료) |
> |---|---|---|
> | 테이블 목적 | 사이트 네비게이션 메뉴 | 사이트 네비게이션 메뉴 |
> | 관리 UI | MenuController.php (PHP) | `ci4-board-admin` MenusPage.tsx (React) |
> | API | 미정의 | `PUT /api/v1/admin/cms/menus` 구현 완료 |
> | 프론트 사용 | 미연동 | `ci4-board-web` Navigation 컴포넌트 연동 완료 |
>
> **`admin_menus` 테이블을 새로 만들지 않고 `tb_cms_menu`를 사용합니다.**
>
> 플러그인 설치 후 메뉴를 등록하려면 기존 CMS 메뉴 API를 호출하면 됩니다:
> ```
> POST /api/v1/admin/cms/menus
> { "label": "날씨", "url": "/weather", "target": "_self", "sequence": 10 }
> ```

---

## 10. 어드민 메뉴 연동

> **[수정 필요 #3 연속] 어드민 메뉴 등록 방식 변경**
>
> 현재 설계는 PHP 기반 어드민 메뉴 관리 페이지를 가정합니다.
> 실제 구현은 `ci4-board-admin`의 `MenusPage.tsx`(React)가 담당하므로 아래와 같이 변경됩니다.

플러그인 설치 후 `ci4-board-admin` → CMS → 메뉴 관리에서 메뉴를 생성합니다.

| 필드 | 예시 | 비고 |
|---|---|---|
| 메뉴 표시명 | 날씨 정보 | |
| URL | `/weather` | Next.js 프론트엔드 경로 |
| 링크 타겟 | `_self` | |
| 정렬 순서 | 10 | |

> URL에 `/api/v1/plugin/getWether/weather` (CI4 API 경로)를 입력하면 안 됩니다.
> 메뉴 URL은 항상 Next.js가 라우팅하는 **프론트엔드 경로**여야 합니다.

---

## 10-1. 플러그인이 프론트엔드 페이지를 제공하는 경우

플러그인이 사용자에게 보이는 화면이 필요한 경우, `ci4-board-web`에 페이지를 추가해야 합니다.
현재 `ci4-board-web`은 별도 프로젝트이므로 플러그인 zip에 Next.js 페이지를 포함할 수 없습니다.

**두 가지 방식 중 선택:**

**방식 A — 범용 플러그인 데이터 페이지 (권장)**

`ci4-board-web`에 플러그인 API 응답을 범용으로 표시하는 페이지를 미리 만들어 둡니다.

```
ci4-board-web/src/app/plugin/[name]/page.tsx
  → GET /api/v1/plugin/{name}/index 호출
  → 응답의 HTML 필드를 dangerouslySetInnerHTML로 렌더링 또는 JSON 데이터 표시
```

**방식 B — 플러그인별 전용 페이지 수동 추가**

플러그인 기능이 복잡한 경우 프론트엔드 개발자가 `ci4-board-web`에 직접 페이지를 추가합니다.

```
ci4-board-web/src/app/weather/page.tsx
  → GET /api/v1/plugin/getWether/weather 호출 → 결과 렌더링
```

---

## 11. 전체 흐름 (수정 후)

```
[설치]
  관리자(ci4-board-admin) → ZIP 업로드
    └─ POST /api/v1/admin/plugins
          └─ PluginManager::install()
                ├─ ZIP 압축 해제 → app/Plugins/{name}/
                ├─ plugin.json + Plugin.php 존재 검증
                └─ plugins 테이블 등록 (is_active=0)

[활성화]
  관리자(ci4-board-admin) → 활성화 클릭
    └─ PUT /api/v1/admin/plugins/{name}/activate
          └─ PluginManager::activate()
                ├─ Plugin::install() (DB 초기화 등)
                └─ plugins 테이블 is_active=1

[메뉴 등록]
  관리자(ci4-board-admin) → CMS → 메뉴 관리
    └─ POST /api/v1/admin/cms/menus          ← tb_cms_menu 사용 (admin_menus 아님)
          { "label": "날씨", "url": "/weather" }

[사용자 접근]
  브라우저 → https://site.com/weather        ← Next.js 라우팅
    └─ ci4-board-web/src/app/weather/page.tsx
          └─ GET /api/v1/plugin/getWether/weather   ← CI4 JSON API
                └─ WeatherController::index() → JSON 반환

[비활성화 / 삭제]
  관리자 → 비활성화: PUT /api/v1/admin/plugins/{name}/deactivate → is_active=0
  관리자 → 삭제:     DELETE /api/v1/admin/plugins/{name} → Plugin::uninstall() → 파일 삭제
```

---

## 12. 설계 원칙 요약

| 원칙 | 내용 |
|---|---|
| 격리 | 비활성 플러그인은 라우트조차 로드되지 않음 |
| 명시성 | Service Provider 패턴으로 의존성을 명시적으로 선언 |
| 단계 분리 | `register()` → `boot()` 순서로 플러그인 간 의존성 문제 방지 |
| URL 충돌 방지 | `/api/v1/plugin/{플러그인명}/` prefix로 플러그인 간 라우트 충돌 없음 |
| JSON 전용 | 컨트롤러는 JSON만 반환, 뷰 렌더링 없음 |
| 메뉴 통합 | 별도 admin_menus 테이블 없이 tb_cms_menu 재사용 |

---

## 수정 사항 요약

| # | 항목 | 변경 내용 |
|---|---|---|
| #1 | `Views/` 디렉토리 | 플러그인에서 제거 — 컨트롤러는 `ApiResponse` 트레이트로 JSON 반환 |
| #2 | `Admin/PluginController.php` | PHP 페이지 컨트롤러 → `Api/V1/Admin/PluginController.php` JSON API |
| #3 | `admin_menus` 테이블 + `MenuController.php` | 제거 — `tb_cms_menu` + `ci4-board-admin` MenusPage.tsx로 대체 |
| #4 | 플러그인 URL prefix | `/plugin/{name}/...` → `/api/v1/plugin/{name}/...` + JWT/CORS 필터 적용 |
| #5 | `plugin.json`의 `usage` URL | CI4 서버 경로 → Next.js 프론트엔드 경로 기준으로 안내 |
