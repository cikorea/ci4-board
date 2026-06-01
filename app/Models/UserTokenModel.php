<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table         = 'tb_users_token';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_idx', 'refresh_token', 'expires_at', 'client_ip', 'created_at', 'revoked',
    ];

    public function store(int $userIdx, string $token, int $expiresAt, string $ip): void
    {
        $this->insert([
            'user_idx'      => $userIdx,
            'refresh_token' => $token,
            'expires_at'    => $expiresAt,
            'client_ip'     => $ip,
            'created_at'    => time(),
            'revoked'       => 0,
        ]);
    }

    public function findValid(string $token): ?array
    {
        return $this->where('refresh_token', $token)
                    ->where('revoked', 0)
                    ->where('expires_at >', time())
                    ->first();
    }

    public function revoke(string $token): void
    {
        $this->where('refresh_token', $token)->set('revoked', 1)->update();
    }

    public function revokeAll(int $userIdx): void
    {
        $this->where('user_idx', $userIdx)->set('revoked', 1)->update();
    }

    public function cleanExpired(): void
    {
        $this->where('expires_at <', time())->delete();
    }
}
