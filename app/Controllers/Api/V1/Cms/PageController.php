<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 프론트 CMS 페이지 API
 *
 * GET /api/v1/cms/pages/:slug
 */
class PageController extends BaseApiController
{
    public function show(string $slug): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')
            ->select('idx, slug, title, contents, timestamp_insert, timestamp_update')
            ->where('slug', $slug)
            ->where('status', 1)
            ->get()->getRowArray();

        if (! $page) {
            return $this->failNotFound(lang('Api.cms_page_not_found'));
        }

        return $this->success($page);
    }
}
