# 통합 테스트 가이드

## 목차

1. [환경 설정](#1-환경-설정)
2. [테스트 DB 초기화](#2-테스트-db-초기화)
3. [테스트 실행](#3-테스트-실행)
4. [테스트 스위트 구성](#4-테스트-스위트-구성)
5. [GitHub Actions CI](#5-github-actions-ci)
6. [알려진 제약 사항](#6-알려진-제약-사항)

---

## 1. 환경 설정

### 테스트 전용 DB 생성

```bash
# MySQL root 계정으로 테스트 DB 생성 및 권한 부여
sudo mysql -e "
  CREATE DATABASE IF NOT EXISTS ci4_board_test
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL PRIVILEGES ON ci4_board_test.*
    TO 'ci4user'@'localhost';
  FLUSH PRIVILEGES;
"
```

### phpunit.dist.xml 확인

테스트 DB 설정이 이미 활성화되어 있습니다. (`app/Config/Database.php`의 `$tests` 그룹 참고)

```xml
<!-- phpunit.dist.xml 내 DB 설정 (이미 활성화됨) -->
<env name="database.tests.hostname" value="localhost"/>
<env name="database.tests.database" value="ci4_board_test"/>
<env name="database.tests.username" value="ci4user"/>
<env name="database.tests.password" value="ci4pass"/>
```

---

## 2. 테스트 DB 초기화

테스트는 `migrateOnce=true` + `refresh=false` 설정을 사용하므로, **최초 1회** 마이그레이션이 필요합니다.

```bash
php -r "
  define('FCPATH', __DIR__ . '/public/');
  \$_SERVER['CI_ENVIRONMENT'] = 'testing';
  require 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
  \$runner = \Config\Services::migrations();
  \$runner->setNamespace(null);
  \$runner->latest('tests');
  echo 'Migrations done' . PHP_EOL;
"
```

> PHPUnit 실행 중 `Table 'ci4_board_test.tb_users_group' doesn't exist` 에러 발생 시 이 명령을 실행하세요.

---

## 3. 테스트 실행

### 전체 테스트 실행

```bash
php vendor/bin/phpunit
# 또는
composer test
```

### 테스트 파일별 실행

```bash
# 단위 테스트
php vendor/bin/phpunit tests/unit/Services/JwtServiceTest.php

# 사용자 인증 API
php vendor/bin/phpunit tests/unit/Api/V1/Auth/UserAuthApiTest.php

# 관리자 인증 API
php vendor/bin/phpunit tests/unit/Api/V1/Auth/AdminAuthApiTest.php

# 게시판/게시글/댓글 API
php vendor/bin/phpunit tests/unit/Api/V1/ArticleApiTest.php

# 파일 업로드 API (인증/권한)
php vendor/bin/phpunit tests/unit/Api/V1/FileUploadApiTest.php

# 파일 업로드 바이너리
php vendor/bin/phpunit tests/unit/Api/V1/FileBinaryUploadTest.php

# 쪽지 API
php vendor/bin/phpunit tests/unit/Api/V1/MessageApiTest.php

# 관리자 API
php vendor/bin/phpunit tests/unit/Api/V1/Admin/AdminApiTest.php

# CMS API
php vendor/bin/phpunit tests/unit/Api/V1/Cms/CmsApiTest.php
```

### 특정 테스트 메서드 실행

```bash
php vendor/bin/phpunit --filter testLoginWithValidCredentials tests/unit/Api/V1/Auth/UserAuthApiTest.php
```

---

## 4. 테스트 스위트 구성

**총 116개 테스트** (전체 통과)

| 파일 | 종류 | 테스트 수 | 커버리지 |
|------|------|----------|---------|
| `tests/unit/Services/JwtServiceTest.php` | 단위 | 14개 | JWT 발급·검증·TTL |
| `tests/unit/Api/V1/Auth/UserAuthApiTest.php` | 통합 | 14개 | 로그인·회원가입·로그아웃·토큰 갱신 |
| `tests/unit/Api/V1/Auth/AdminAuthApiTest.php` | 통합 | 7개 | 관리자 로그인·토큰 검증 |
| `tests/unit/Api/V1/ArticleApiTest.php` | 통합 | 17개 | 게시판·게시글·댓글 CRUD + 권한 |
| `tests/unit/Api/V1/FileUploadApiTest.php` | 통합 | 7개 | 파일 업로드 인증·권한 |
| `tests/unit/Api/V1/FileBinaryUploadTest.php` | 통합 | 12개 | 다운로드·삭제·WYSIWYG |
| `tests/unit/Api/V1/MessageApiTest.php` | 통합 | 12개 | 쪽지 CRUD + 권한 |
| `tests/unit/Api/V1/Admin/AdminApiTest.php` | 통합 | 15개 | 관리자 게시판·설정·회원·게시글 |
| `tests/unit/Api/V1/Cms/CmsApiTest.php` | 통합 | 18개 | CMS Page·Banner·Popup·Menu CRUD |

### 테스트 설계 패턴

모든 통합 테스트는 공통 패턴을 따릅니다.

```php
final class ExampleApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;   // 마이그레이션 1회만 실행
    protected $refresh     = false;  // 테스트마다 regress 없음
    protected $seed        = 'App\Database\Seeds\InitialSeeder';

    protected function setUp(): void
    {
        ob_start();
        parent::setUp();
        ob_end_clean();  // 시더 echo 출력 억제
        putenv('jwt.secret=test-secret-minimum-32-chars!!');
        $_ENV['jwt.secret'] = 'test-secret-minimum-32-chars!!';
        service('cache')->clean();  // Rate Limit 초기화
        $this->cleanTestData();
    }
}
```

**주요 설계 결정:**

| 항목 | 선택값 | 이유 |
|------|--------|------|
| `migrateOnce=true` | 마이그레이션 1회 | regress/latest 반복 시 CI4 버그 회피 |
| `refresh=false` | regress 비활성 | 테스트 DB 안정성 |
| `getJSON()` | 바디 파싱 | `getBody()`가 HTML 래핑 반환하는 CI4 동작 우회 |
| `withHeaders([])` | 헤더 초기화 | FeatureTestTrait가 헤더를 다음 요청에도 유지 |
| `tb_bbs_hit` truncate | 데이터 정리 | UNIQUE(bbs_idx, article_idx) 제약 충돌 방지 |

---

## 5. GitHub Actions CI

`.github/workflows/ci.yml`이 설정되어 있습니다.

**트리거:** `develop`·`master` push, `master` PR

**단계:**
1. MySQL 8.0 서비스 컨테이너 시작 (`ci4_board_test` DB)
2. PHP 8.2 + 필수 확장 설치
3. `composer install`
4. `.env` 파일 생성
5. 테스트 DB 마이그레이션
6. PHPStan level 3 분석
7. PHPUnit 전체 실행

```yaml
# .github/workflows/ci.yml 요약
on:
  push:
    branches: [develop, master]
  pull_request:
    branches: [master]
```

---

## 6. 알려진 제약 사항

### 파일 업로드 실제 이동 불가 (CLI 환경)

`UploadedFile::isValid()`는 PHP 내장 함수 `is_uploaded_file()`을 사용합니다.
이 함수는 **실제 HTTP POST 요청으로 업로드된 파일만** `true`를 반환하므로,
PHPUnit CLI 환경에서는 항상 `false`를 반환합니다.

**영향:** 파일 업로드 성공 케이스 (실제 파일 저장, 확장자/크기 422 에러) 테스트 불가

**현재 커버리지:** 인증·권한 검증, 다운로드, 삭제 (파일 이동 없는 경로)

**해결 방법:** HTTP 서버에서 실제 요청으로 테스트 (Postman, curl 등)

```bash
# 로컬 개발 서버에서 파일 업로드 테스트
curl -X POST http://localhost:8083/api/v1/files \
  -H "Authorization: Bearer {token}" \
  -F "bbs_idx=3" \
  -F "article_idx=1" \
  -F "attachments=@/path/to/file.jpg"
```

### 소셜 로그인 콜백 테스트

OAuth2 공급자(Google/Naver/Kakao) 응답을 mock으로 처리해야 합니다.
별도 이슈 [#57](https://github.com/pushwing/ci4-board/issues/57) 참고.
