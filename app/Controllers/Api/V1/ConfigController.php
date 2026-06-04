<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 공개 사이트 설정 API (인증 불필요)
 *
 * GET /api/v1/config
 */
class ConfigController extends BaseApiController
{
    private const PUBLIC_PARAMS = [
        'browser_title_fix_value',
        'join_used',
        'site_block_used',
        'site_block_contents',
    ];

    #[OA\Get(
        path: '/api/v1/config',
        summary: '사이트 공개 설정 조회',
        tags: ['Config'],
        responses: [
            new OA\Response(response: 200, description: '사이트 설정'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tb_setting')
            ->whereIn('parameter', self::PUBLIC_PARAMS)
            ->get()->getResultArray();

        $config = [];
        foreach ($rows as $r) {
            $config[$r['parameter']] = $r['value'];
        }

        return $this->success($config);
    }
}
