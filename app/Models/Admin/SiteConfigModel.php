<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

/**
 * 사이트 전역 설정 모델 (admin DB)
 */
class SiteConfigModel extends Model
{
    protected $DBGroup       = 'admin';
    protected $table         = 'tb_site_config';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'config_key', 'config_value', 'description', 'updated_by', 'updated_at',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->where('config_key', $key)->first();
        return $row ? $row['config_value'] : $default;
    }

    public function setConfig(string $key, mixed $value, int $adminIdx = 0): void
    {
        $existing = $this->where('config_key', $key)->first();

        $data = [
            'config_value' => $value,
            'updated_by'   => $adminIdx,
            'updated_at'   => time(),
        ];

        if ($existing) {
            $this->where('config_key', $key)->update(null, $data);
        } else {
            $this->insert(array_merge(['config_key' => $key], $data));
        }
    }

    public function getAll(): array
    {
        $rows   = $this->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row['config_value'];
        }
        return $result;
    }
}
