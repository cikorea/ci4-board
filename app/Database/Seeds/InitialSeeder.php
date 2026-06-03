<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Initial data seeder.
 *
 * Inserts the minimum data required for the application to run:
 *   - Member groups (idx 1=최고관리자, 2=일반회원, 3=개발자)
 *   - Admin user  (ID: admin / PW: admin1234)
 *   - Site settings
 *   - Default boards and their settings
 *
 * Run: php spark db:seed InitialSeeder
 */
class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $now = time();
        $ip  = '127.0.0.1';

        $this->seedGroups($now, $ip);
        $this->seedAdminUser($now, $ip);
        $this->seedSiteSettings($now, $ip);
        $this->seedBoards($now, $ip);
        $this->seedSiteConfig($now, $ip);
    }

    // ------------------------------------------------------------------ //
    // 회원 그룹
    // ------------------------------------------------------------------ //

    private function seedGroups(int $now, string $ip): void
    {
        $groups = [
            ['idx' => 1, 'group_name' => '최고관리자'],
            ['idx' => 2, 'group_name' => '일반회원'],
            ['idx' => 3, 'group_name' => '개발자'],
        ];

        foreach ($groups as $g) {
            $exists = $this->db->table('tb_users_group')
                ->where('idx', $g['idx'])->countAllResults();
            if ($exists) continue;

            $this->db->table('tb_users_group')->insert([
                'idx'           => $g['idx'],
                'group_name'    => $g['group_name'],
                'exec_user_idx' => 0,
                'client_ip'     => $ip,
                'is_used'       => 1,
            ]);
        }

        echo "  ✔ 회원 그룹 완료\n";
    }

    // ------------------------------------------------------------------ //
    // 관리자 계정
    // ------------------------------------------------------------------ //

    private function seedAdminUser(int $now, string $ip): void
    {
        try {
            $adminDb = \Config\Database::connect('admin');
            $adminDb->table('tb_admin_users')->countAllResults(); // 접속 검증
        } catch (\Throwable $e) {
            echo "  ⚠ admin DB 미설정, 관리자 계정 시딩 건너뜀\n";
            return;
        }

        $exists = $adminDb->table('tb_admin_users')
            ->where('user_id', 'admin')->countAllResults();
        if ($exists) {
            echo "  ✔ 관리자 계정 이미 존재 (건너뜀)\n";
            return;
        }

        $adminDb->table('tb_admin_users')->insert([
            'user_id'                => 'admin',
            'super_secured_password' => password_hash('admin1234', PASSWORD_BCRYPT),
            'name'                   => '관리자',
            'nickname'               => '관리자',
            'email'                  => 'admin@example.com',
            'role'                   => 'superadmin',
            'status'                 => 1,
            'timestamp_insert'       => $now,
            'client_ip_insert'       => $ip,
        ]);

        echo "  ✔ 관리자 계정 생성 (admin DB: admin / admin1234, role=superadmin)\n";
    }

    // ------------------------------------------------------------------ //
    // 사이트 환경설정
    // ------------------------------------------------------------------ //

    private function seedSiteSettings(int $now, string $ip): void
    {
        $settings = [
            ['parameter' => 'browser_title_fix_value', 'value' => 'CI4 Board'],
            ['parameter' => 'join_used',               'value' => '1'],
            ['parameter' => 'site_block_used',         'value' => '0'],
            ['parameter' => 'site_block_contents',     'value' => '현재 사이트 점검 중입니다.'],
        ];

        foreach ($settings as $s) {
            $exists = $this->db->table('tb_setting')
                ->where('parameter', $s['parameter'])->countAllResults();
            if ($exists) continue;

            $this->db->table('tb_setting')->insert([
                'parameter'     => $s['parameter'],
                'value'         => $s['value'],
                'default_bbs'   => 0,
                'exec_user_idx' => 0,
                'client_ip'     => $ip,
            ]);
        }

        echo "  ✔ 사이트 설정 완료\n";
    }

    // ------------------------------------------------------------------ //
    // 사이트 설정 (admin DB)
    // ------------------------------------------------------------------ //

    private function seedSiteConfig(int $now, string $ip): void
    {
        try {
            $adminDb = \Config\Database::connect('admin');
            $adminDb->table('tb_site_config')->countAllResults(); // 접속 검증
        } catch (\Throwable $e) {
            echo "  ⚠ admin DB 미설정, 사이트 설정 시딩 건너뜀\n";
            return;
        }

        $configs = [
            ['config_key' => 'browser_title_fix_value', 'config_value' => 'CI4 Board',              'description' => '브라우저 탭 제목'],
            ['config_key' => 'join_used',                'config_value' => '1',                      'description' => '회원가입 허용 여부'],
            ['config_key' => 'site_block_used',          'config_value' => '0',                      'description' => '사이트 차단 여부'],
            ['config_key' => 'site_block_contents',      'config_value' => '현재 사이트 점검 중입니다.', 'description' => '사이트 차단 안내 문구'],
        ];

        foreach ($configs as $c) {
            $exists = $adminDb->table('tb_site_config')
                ->where('config_key', $c['config_key'])->countAllResults();
            if ($exists) continue;

            $adminDb->table('tb_site_config')->insert([
                'config_key'   => $c['config_key'],
                'config_value' => $c['config_value'],
                'description'  => $c['description'],
                'updated_by'   => 0,
                'updated_at'   => $now,
            ]);
        }

        echo "  ✔ 사이트 설정 (admin DB) 완료\n";
    }

    // ------------------------------------------------------------------ //
    // 게시판 & 게시판 설정
    // ------------------------------------------------------------------ //

    private function seedBoards(int $now, string $ip): void
    {
        // [bbs_id => bbs_name]
        $boards = [
            'notice'   => '공지사항',
            'news'     => '새소식',
            'free'     => '자유게시판',
            'qna'      => 'CodeIgniter Q&A',
            'source'   => '소스코드 공유',
            'tip'      => '팁 & 강좌',
            'etc_qna'  => '기타 Q&A',
            'file'     => '자료실',
            'ad'       => '홍보게시판',
            'job'      => '구인구직',
            'cibook'   => 'CI 도서',
        ];

        // 권한: 그룹 idx serialize
        // 0=비회원, 1=최고관리자, 2=일반회원, 3=개발자
        $permAll      = serialize(['0', '1', '2', '3']); // 모두
        $permMember   = serialize(['1', '2', '3']);       // 회원만
        $permAdmin    = serialize(['1']);                  // 최고관리자만

        foreach ($boards as $bbsId => $bbsName) {
            // tb_bbs
            $bbs = $this->db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
            if (! $bbs) {
                $this->db->table('tb_bbs')->insert([
                    'bbs_id'        => $bbsId,
                    'exec_user_idx' => 1,
                    'timestamp'     => $now,
                    'client_ip'     => $ip,
                ]);
                $bbsIdx = $this->db->insertID();
            } else {
                $bbsIdx = $bbs['idx'];
            }

            // tb_bbs_setting
            $bbsSettings = [
                'bbs_name'                       => $bbsName,
                'bbs_used'                       => '1',
                'bbs_count_list_article'         => '15',
                'bbs_comment_used'               => '1',
                'bbs_allow_group_view_list'      => $permAll,
                'bbs_allow_group_view_article'   => $permAll,
                'bbs_allow_group_write_article'  => $permMember,
                'bbs_allow_group_write_comment'  => $permMember,
            ];

            foreach ($bbsSettings as $param => $value) {
                $exists = $this->db->table('tb_bbs_setting')
                    ->where('bbs_idx', $bbsIdx)
                    ->where('parameter', $param)
                    ->countAllResults();
                if ($exists) continue;

                $this->db->table('tb_bbs_setting')->insert([
                    'bbs_idx'       => $bbsIdx,
                    'parameter'     => $param,
                    'value'         => $value,
                    'exec_user_idx' => 1,
                    'client_ip'     => $ip,
                ]);
            }
        }

        // 운영자 전용 게시판 (관리자/개발자만)
        $staffBoards = [
            'su' => ['name' => '운영자 게시판', 'write' => $permAdmin],
            'ci' => ['name' => '포럼 개발자',  'write' => serialize(['1', '3'])],
        ];

        foreach ($staffBoards as $bbsId => $info) {
            $bbs = $this->db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
            if (! $bbs) {
                $this->db->table('tb_bbs')->insert([
                    'bbs_id'        => $bbsId,
                    'exec_user_idx' => 1,
                    'timestamp'     => $now,
                    'client_ip'     => $ip,
                ]);
                $bbsIdx = $this->db->insertID();
            } else {
                $bbsIdx = $bbs['idx'];
            }

            $bbsSettings = [
                'bbs_name'                       => $info['name'],
                'bbs_used'                       => '1',
                'bbs_count_list_article'         => '15',
                'bbs_comment_used'               => '1',
                'bbs_allow_group_view_list'      => $permAdmin,
                'bbs_allow_group_view_article'   => $permAdmin,
                'bbs_allow_group_write_article'  => $info['write'],
                'bbs_allow_group_write_comment'  => $info['write'],
            ];

            foreach ($bbsSettings as $param => $value) {
                $exists = $this->db->table('tb_bbs_setting')
                    ->where('bbs_idx', $bbsIdx)
                    ->where('parameter', $param)
                    ->countAllResults();
                if ($exists) continue;

                $this->db->table('tb_bbs_setting')->insert([
                    'bbs_idx'       => $bbsIdx,
                    'parameter'     => $param,
                    'value'         => $value,
                    'exec_user_idx' => 1,
                    'client_ip'     => $ip,
                ]);
            }
        }

        echo "  ✔ 게시판 " . (count($boards) + count($staffBoards)) . "개 완료\n";
    }
}
