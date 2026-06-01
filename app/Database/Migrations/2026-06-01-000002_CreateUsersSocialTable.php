<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersSocialTable extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_social` (
                `idx`              int unsigned  NOT NULL AUTO_INCREMENT,
                `user_idx`         int unsigned  NOT NULL                 COMMENT 'tb_users.idx',
                `provider`         varchar(20)   NOT NULL                 COMMENT 'google | naver | kakao',
                `provider_id`      varchar(128)  NOT NULL                 COMMENT '소셜 플랫폼 고유 ID',
                `email`            varchar(128)  DEFAULT NULL             COMMENT '소셜 계정 이메일',
                `nickname`         varchar(64)   DEFAULT NULL             COMMENT '소셜 계정 닉네임',
                `timestamp_insert` int unsigned  NOT NULL                 COMMENT '등록 시각 (UNIX)',
                `timestamp_update` int unsigned  DEFAULT NULL             COMMENT '수정 시각 (UNIX)',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `uq_provider_id` (`provider`, `provider_id`),
                KEY `fk_users__idx` (`user_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='소셜 로그인 연결 계정'
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `tb_users_social`');
    }
}
