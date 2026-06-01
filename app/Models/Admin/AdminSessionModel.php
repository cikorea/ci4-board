<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

/**
 * 관리자 세션/토큰 모델 (admin DB)
 */
class AdminSessionModel extends Model
{
    protected $DBGroup       = 'admin';
    protected $table         = 'tb_admin_session';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'admin_idx', 'access_token', 'refresh_token',
        'expires_at', 'client_ip', 'created_at', 'revoked',
    ];

    public function store(int $adminIdx, string $accessToken, string $refreshToken, int $expiresAt, string $ip): void
    {
        $this->insert([
            'admin_idx'     => $adminIdx,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => $expiresAt,
            'client_ip'     => $ip,
            'created_at'    => time(),
            'revoked'       => 0,
        ]);
    }

    public function findValid(string $refreshToken): ?array
    {
        return $this->where('refresh_token', $refreshToken)
                    ->where('revoked', 0)
                    ->where('expires_at >', time())
                    ->first();
    }

    public function revoke(string $refreshToken): void
    {
        $this->where('refresh_token', $refreshToken)->set('revoked', 1)->update();
    }

    public function revokeAll(int $adminIdx): void
    {
        $this->where('admin_idx', $adminIdx)->set('revoked', 1)->update();
    }
}
