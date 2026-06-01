<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

/**
 * 일별 통계 집계 모델 (admin DB)
 */
class StatsDailyModel extends Model
{
    protected $DBGroup       = 'admin';
    protected $table         = 'tb_stats_daily';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'stat_date', 'new_users', 'new_articles', 'new_comments',
        'total_hits', 'active_users', 'created_at',
    ];

    public function getByDate(string $date): ?array
    {
        return $this->where('stat_date', $date)->first();
    }

    public function getRange(string $from, string $to): array
    {
        return $this->where('stat_date >=', $from)
                    ->where('stat_date <=', $to)
                    ->orderBy('stat_date', 'ASC')
                    ->findAll();
    }

    public function upsert(string $date, array $data): void
    {
        $existing = $this->getByDate($date);

        if ($existing) {
            $this->where('stat_date', $date)->update(null, $data);
        } else {
            $this->insert(array_merge(['stat_date' => $date, 'created_at' => time()], $data));
        }
    }
}
