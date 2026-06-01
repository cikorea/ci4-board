<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;

/**
 * 관리자 API 컨트롤러 베이스.
 * AdminJwtFilter 를 통과한 요청만 도달한다.
 */
abstract class BaseAdminApiController extends BaseApiController {}
