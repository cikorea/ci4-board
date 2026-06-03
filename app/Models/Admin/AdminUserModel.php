<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

/**
 * 어드민 계정 모델 (admin DB)
 */
class AdminUserModel extends Model
{
    protected $DBGroup       = 'admin';
    protected $table         = 'tb_admin_users';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'super_secured_password', 'name', 'nickname', 'email',
        'role', 'status', 'timestamp_insert', 'timestamp_update',
        'timestamp_login', 'client_ip_insert', 'client_ip_login',
    ];

    public function findByLoginId(string $loginId): ?array
    {
        return $this->where('user_id', $loginId)->where('status', 1)->first();
    }

    public function updateLoginTimestamp(int $idx, string $ip): void
    {
        $this->where('idx', $idx)->set([
            'timestamp_login' => time(),
            'client_ip_login' => $ip,
        ])->update();
    }
}
