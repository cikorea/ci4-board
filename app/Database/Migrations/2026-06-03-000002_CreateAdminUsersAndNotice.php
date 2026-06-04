<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 어드민 계정 및 공지 테이블 마이그레이션 (admin DB)
 * php spark migrate -g admin
 *
 * $DBGroup 프로퍼티 대신 getDBGroup()를 오버라이드해 사용한다.
 * 이렇게 하면 테스트 환경에서 Migration 생성자가 admin DB에 즉시 접속하지 않는다.
 */
class CreateAdminUsersAndNotice extends Migration
{
    private array $tables = ['tb_admin_notice', 'tb_admin_users'];

    public function getDBGroup(): ?string
    {
        // 테스트 환경에서는 tests 그룹(ci4_board_test)에 함께 생성
        return ENVIRONMENT === 'testing' ? null : 'admin';
    }

    public function up(): void
    {
        $db = ENVIRONMENT === 'testing' ? $this->db : \Config\Database::connect('admin');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // ── 어드민 계정 ──────────────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_admin_users` (
                `idx`                       int unsigned    NOT NULL AUTO_INCREMENT,
                `user_id`                   varchar(64)     NOT NULL                COMMENT '로그인 ID',
                `super_secured_password`    varchar(255)    NOT NULL                COMMENT '비밀번호 해시',
                `name`                      varchar(64)     NOT NULL DEFAULT ''     COMMENT '실명',
                `nickname`                  varchar(64)     NOT NULL DEFAULT ''     COMMENT '닉네임',
                `email`                     varchar(128)    NOT NULL DEFAULT ''     COMMENT '이메일',
                `role`                      varchar(32)     NOT NULL DEFAULT 'manager' COMMENT '역할 (superadmin / manager)',
                `status`                    tinyint(1)      NOT NULL DEFAULT 1      COMMENT '상태 (1=활성, 0=비활성)',
                `timestamp_insert`          int unsigned    NOT NULL DEFAULT 0      COMMENT '등록 time()',
                `timestamp_update`          int unsigned    DEFAULT NULL            COMMENT '수정 time()',
                `timestamp_login`           int unsigned    DEFAULT NULL            COMMENT '최근 로그인 time()',
                `client_ip_insert`          varchar(64)     NOT NULL DEFAULT ''     COMMENT '등록 IP',
                `client_ip_login`           varchar(64)     NOT NULL DEFAULT ''     COMMENT '최근 로그인 IP',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `uq_admin_users__user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='어드민 계정'
        ");

        // ── 어드민 내부 공지 ─────────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_admin_notice` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT,
                `title`             varchar(255)    NOT NULL                COMMENT '공지 제목',
                `contents`          text            DEFAULT NULL            COMMENT '공지 내용',
                `author_idx`        int unsigned    NOT NULL                COMMENT '작성 관리자 idx',
                `is_pinned`         tinyint(1)      NOT NULL DEFAULT 0      COMMENT '상단 고정 여부',
                `timestamp_insert`  int unsigned    NOT NULL                COMMENT '등록 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_admin_notice__author_idx` (`author_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='어드민 내부 공지'
        ");

        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $db = ENVIRONMENT === 'testing' ? $this->db : \Config\Database::connect('admin');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
