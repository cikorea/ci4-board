<?php

namespace App\Models;

use CodeIgniter\Model;

class SocialUserModel extends Model
{
    protected $table         = 'tb_users_social';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_idx', 'provider', 'provider_id',
        'email', 'nickname',
        'timestamp_insert', 'timestamp_update',
    ];

    public function findByProvider(string $provider, string $providerId): ?array
    {
        return $this->where('provider', $provider)
                    ->where('provider_id', $providerId)
                    ->first();
    }

    public function upsert(int $userIdx, string $provider, string $providerId, array $data = [], ?array $existing = null): void
    {
        $existing = $existing ?? $this->findByProvider($provider, $providerId);
        $now      = time();

        if ($existing) {
            $this->update($existing['idx'], array_merge($data, [
                'timestamp_update' => $now,
            ]));
        } else {
            $this->insert(array_merge([
                'user_idx'         => $userIdx,
                'provider'         => $provider,
                'provider_id'      => $providerId,
                'timestamp_insert' => $now,
            ], $data));
        }
    }
}
