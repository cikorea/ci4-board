<?php

namespace App\Commands;

use App\Models\Admin\StatsDailyModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 일별 통계 집계 커맨드
 *
 * php spark stats:collect [--date=YYYY-MM-DD]
 */
class CollectDailyStats extends BaseCommand
{
    protected $group       = 'Stats';
    protected $name        = 'stats:collect';
    protected $description = '일별 통계(신규 가입자·게시글·댓글·조회수)를 집계합니다.';

    protected $options = [
        '--date' => '집계 대상 날짜 (YYYY-MM-DD, 기본값: 어제)',
    ];

    public function run(array $params): void
    {
        $date = CLI::getOption('date') ?? date('Y-m-d', strtotime('yesterday'));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            CLI::error("잘못된 날짜 형식: {$date} (YYYY-MM-DD 필요)");
            return;
        }

        $start = strtotime($date . ' 00:00:00');
        $end   = strtotime($date . ' 23:59:59');

        $db = \Config\Database::connect();

        $newUsers = $db->table('tb_users')
            ->where('timestamp_insert >=', $start)
            ->where('timestamp_insert <=', $end)
            ->countAllResults();

        $newArticles = $db->table('tb_bbs_article')
            ->where('timestamp_insert >=', $start)
            ->where('timestamp_insert <=', $end)
            ->where('is_deleted', 0)
            ->countAllResults();

        $newComments = $db->table('tb_bbs_comment')
            ->where('timestamp_insert >=', $start)
            ->where('timestamp_insert <=', $end)
            ->where('is_deleted', 0)
            ->countAllResults();

        $totalHitsRow = $db->query(
            "SELECT COALESCE(SUM(hit), 0) AS total FROM tb_bbs_hit WHERE timestamp >= ? AND timestamp <= ?",
            [$start, $end]
        )->getRowArray();
        $totalHits = (int) ($totalHitsRow['total'] ?? 0);

        $model = new StatsDailyModel();
        $model->upsert($date, [
            'new_users'    => $newUsers,
            'new_articles' => $newArticles,
            'new_comments' => $newComments,
            'total_hits'   => $totalHits,
            'active_users' => 0,
        ]);

        CLI::write("통계 집계 완료 [{$date}]: 가입 {$newUsers}, 게시글 {$newArticles}, 댓글 {$newComments}, 조회 {$totalHits}", 'green');
    }
}
