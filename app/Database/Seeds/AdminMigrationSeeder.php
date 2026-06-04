<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 어드민 계정 이관 시더
 *
 * ci4_board.tb_users (group_idx=1) → codeigniter_admin.tb_admin_users 복사 후
 * ci4_board.tb_users의 해당 계정은 group_idx=2(일반회원)로 변경.
 *
 * 어드민은 두 DB에 동시에 존재한다.
 *   - tb_admin_users : 관리자 패널 로그인 (role 기반)
 *   - tb_users       : 프론트(커뮤니티) 참여 (group_idx=2)
 *
 * Run: php spark db:seed AdminMigrationSeeder
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
            // ── admin DB에 복사 ───────────────────────────────────────────
            $exists = $adminDb->table('tb_admin_users')
                ->where('user_id', $user['user_id'])->countAllResults();

            if ($exists) {
                echo "  ⏭  admin DB 건너뜀: {$user['user_id']} (이미 존재)\n";
                $skipped++;
            } else {
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
                echo "  ✔ admin DB 이관: {$user['user_id']}\n";
                $migrated++;
            }

            // ── tb_users의 group_idx를 2(일반회원)로 변경 ─────────────────
            // 삭제하지 않고 group_idx를 낮춰 커뮤니티 참여를 유지한다.
            $mainDb->table('tb_users')
                ->where('idx', $user['idx'])
                ->update(['group_idx' => 2]);
        }

        echo "\n  완료: admin DB 이관 {$migrated}명 / 건너뜀 {$skipped}명\n";
        echo "  tb_users 해당 계정 group_idx → 2 변경 완료\n";
    }
}
