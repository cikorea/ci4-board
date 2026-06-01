<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

/**
 * 관리자 행위 감사 로그 모델 (admin DB)
 */
class AdminLogModel extends Model
{
    protected $DBGroup       = 'admin';
    protected $table         = 'tb_admin_log';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'admin_idx', 'action', 'target_table', 'target_idx',
        'before_data', 'after_data', 'client_ip', 'user_agent', 'timestamp',
    ];

    public function record(
        int     $adminIdx,
        string  $action,
        ?string $targetTable = null,
        ?int    $targetIdx   = null,
        mixed   $before      = null,
        mixed   $after       = null,
        string  $ip          = '',
        ?string $ua          = null
    ): void {
        $this->insert([
            'admin_idx'    => $adminIdx,
            'action'       => $action,
            'target_table' => $targetTable,
            'target_idx'   => $targetIdx,
            'before_data'  => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after_data'   => $after  !== null ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
            'client_ip'    => $ip,
            'user_agent'   => $ua,
            'timestamp'    => time(),
        ]);
    }
}
