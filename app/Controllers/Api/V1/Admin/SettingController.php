<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use App\Models\Admin\SiteConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 사이트 설정 API
 *
 * GET /api/admin/v1/setting   설정 조회
 * PUT /api/admin/v1/setting   설정 저장
 */
class SettingController extends BaseAdminApiController
{
    private const SITE_KEYS = [
        'browser_title_fix_value',
        'join_used',
        'site_block_used',
        'site_block_contents',
    ];

    public function index(): ResponseInterface
    {
        $model    = new SiteConfigModel();
        $settings = [];

        foreach (self::SITE_KEYS as $key) {
            $settings[$key] = $model->get($key);
        }

        return $this->success($settings);
    }

    public function update(): ResponseInterface
    {
        $model    = new SiteConfigModel();
        $logModel = new AdminLogModel();
        $body     = (array) $this->request->getJSON(true);
        $adminIdx = $this->getUserIdx();
        $ip       = $this->request->getIPAddress();

        $updates = [
            'browser_title_fix_value' => trim((string) ($body['browser_title_fix_value'] ?? '')),
            'join_used'               => isset($body['join_used']) && $body['join_used'] ? '1' : '0',
            'site_block_used'         => isset($body['site_block_used']) && $body['site_block_used'] ? '1' : '0',
            'site_block_contents'     => trim((string) ($body['site_block_contents'] ?? '')),
        ];

        $before = $model->getAll();

        foreach ($updates as $key => $value) {
            $model->setConfig($key, $value, $adminIdx);
        }

        $logModel->record($adminIdx, 'setting.update', 'tb_site_config', null, $before, $updates, $ip,
            $this->request->getUserAgent()->getAgentString());

        return $this->success(null, lang('Api.admin_setting_saved'));
    }
}
