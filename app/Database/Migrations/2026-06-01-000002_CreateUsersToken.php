<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersToken extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_token` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `user_idx`      int unsigned    NOT NULL                COMMENT '회원 idx',
                `refresh_token` varchar(512)    NOT NULL                COMMENT 'Refresh Token (JWT)',
                `expires_at`    int unsigned    NOT NULL                COMMENT '만료 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '발급 IP',
                `created_at`    int unsigned    NOT NULL                COMMENT '발급 time()',
                `revoked`       tinyint(1)      NOT NULL DEFAULT 0      COMMENT '폐기 여부 (0:유효, 1:폐기)',
                PRIMARY KEY (`idx`),
                KEY `idx_users_token__user_idx` (`user_idx`),
                KEY `idx_users_token__refresh_token` (`refresh_token`(64)),
                KEY `idx_users_token__expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='JWT Refresh Token'
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('tb_users_token', true);
    }
}
