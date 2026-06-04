<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 프론트 CMS 페이지 API
 *
 * GET /api/v1/cms/pages/:slug
 */
class PageController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/cms/pages/{slug}',
        summary: 'CMS 페이지 조회',
        tags: ['CMS'],
        parameters: [
            new OA\PathParameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '페이지 내용'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
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
