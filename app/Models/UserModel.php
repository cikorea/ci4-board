<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'tb_users';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'super_secured_password', 'name', 'nickname',
        'email', 'level', 'group_idx', 'status', 'timezone',
        'timestamp_insert', 'timestamp_update', 'timestamp_delete',
        'timestamp_update_password',
        'client_ip_insert', 'client_ip_update', 'client_ip_delete',
        'client_ip_update_password',
    ];

    /**
     * 로그인 처리용 - group_name 까지 JOIN해서 반환
     */
    public function findByLoginId(string $loginId): ?array
    {
        $row = $this->db->table('tb_users u')
            ->select('u.*, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->groupStart()
                ->where('u.user_id', $loginId)
                ->orWhere('u.email', $loginId)
            ->groupEnd()
            ->where('u.status', 1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function findByUserId(string $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}
