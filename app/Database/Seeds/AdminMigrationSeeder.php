<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 어드민 계정 이관 시더
 *
 * ci4_board.tb_users (group_idx=1) → codeigniter_admin.tb_admin_users
 *
 * Run: php spark db:seed AdminMigrationSeeder
 *
 * 주의: 실행 전 codeigniter_admin.tb_admin_users 가 비어 있거나
 *       중복 user_id 처리를 확인하세요.
 */
class AdminMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $mainDb  = \Config\Database::connect('default');
        $adminDb = \Config\Database::connect('admin');

        $adminUsers = $mainDb->table('tb_users')
            ->where('group_idx', 1)
            ->get()->getResultArray();

        $migrated = 0;
        $skipped  = 0;

        foreach ($adminUsers as $user) {
            $exists = $adminDb->table('tb_admin_users')
                ->where('user_id', $user['user_id'])->countAllResults();

            if ($exists) {
                echo "  ⏭  건너뜀: {$user['user_id']} (이미 존재)\n";
                $skipped++;
                continue;
            }

            $adminDb->table('tb_admin_users')->insert([
                'user_id'                => $user['user_id'],
                'super_secured_password' => $user['super_secured_password'],
                'name'                   => $user['name']     ?? '',
                'nickname'               => $user['nickname'] ?? '',
                'email'                  => $user['email']    ?? '',
                'role'                   => 'manager',
                'status'                 => (int) ($user['status'] ?? 1),
                'timestamp_insert'       => (int) ($user['timestamp_insert'] ?? time()),
                'timestamp_login'        => $user['timestamp_login'] ?? null,
                'client_ip_insert'       => $user['client_ip_insert'] ?? '',
                'client_ip_login'        => $user['client_ip_login']  ?? '',
            ]);

            echo "  ✔ 이관: {$user['user_id']}\n";
            $migrated++;
        }

        echo "\n  완료: 이관 {$migrated}명 / 건너뜀 {$skipped}명\n";
        echo "\n  [다음 단계] 검증 후 아래 SQL로 main DB에서 어드민 계정 삭제:\n";
        echo "  DELETE FROM tb_users WHERE group_idx = 1;\n";
    }
}
