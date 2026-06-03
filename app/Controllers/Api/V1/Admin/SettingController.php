<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 사이트 설정 API
 *
 * GET /api/admin/v1/setting   설정 조회
 * PUT /api/admin/v1/setting   설정 저장
 */
class SettingController extends BaseAdminApiController
{
    private const SITE_PARAMS = [
        'browser_title_fix_value',
        'join_used',
        'site_block_used',
        'site_block_contents',
    ];

    public function index(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('tb_setting')
            ->whereIn('parameter', self::SITE_PARAMS)
            ->get()->getResultArray();

        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['parameter']] = $r['value'];
        }

        return $this->success($settings);
    }

    public function update(): ResponseInterface
    {
        $db       = \Config\Database::connect();
        $body     = (array) $this->request->getJSON(true);
        $adminIdx = $this->getUserIdx();
        $ip       = $this->request->getIPAddress();

        $updates = [
            'browser_title_fix_value' => trim((string) ($body['browser_title_fix_value'] ?? '')),
            'join_used'               => isset($body['join_used']) && $body['join_used'] ? '1' : '0',
            'site_block_used'         => isset($body['site_block_used']) && $body['site_block_used'] ? '1' : '0',
            'site_block_contents'     => trim((string) ($body['site_block_contents'] ?? '')),
        ];

        foreach ($updates as $parameter => $value) {
            $db->table('tb_setting')
                ->where('parameter', $parameter)
                ->update(['value' => $value, 'exec_user_idx' => $adminIdx, 'client_ip' => $ip]);
        }

        return $this->success(null, lang('Api.admin_setting_saved'));
    }
}
