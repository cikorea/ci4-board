<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 어드민 전용 DB 스키마 마이그레이션.
 * 'admin' DB 그룹에서 실행된다: php spark migrate -g admin
 *
 * $DBGroup 프로퍼티 대신 getDBGroup()를 오버라이드해 사용한다.
 * 이렇게 하면 테스트 환경에서 Migration 생성자가 admin DB에 즉시 접속하지 않는다.
 */
class CreateAdminSchema extends Migration
{
    private array $tables = [
        'tb_stats_daily',
        'tb_site_config',
        'tb_admin_session',
        'tb_admin_log',
    ];

    public function getDBGroup(): ?string
    {
        // 테스트 환경에서는 tests 그룹(ci4_board_test)에 함께 생성
        return ENVIRONMENT === 'testing' ? null : 'admin';
    }

    public function up(): void
    {
        $db = \Config\Database::connect('admin');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // ── 관리자 행위 감사 로그 ────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_admin_log` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `admin_idx`     int unsigned    NOT NULL                COMMENT '관리자 회원 idx (main DB 참조)',
                `action`        varchar(64)     NOT NULL                COMMENT '행위 유형 (board.update, member.delete 등)',
                `target_table`  varchar(64)     DEFAULT NULL            COMMENT '대상 테이블명',
                `target_idx`    int unsigned    DEFAULT NULL            COMMENT '대상 레코드 idx',
                `before_data`   json            DEFAULT NULL            COMMENT '변경 전 데이터',
                `after_data`    json            DEFAULT NULL            COMMENT '변경 후 데이터',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '요청 IP',
                `user_agent`    varchar(255)    DEFAULT NULL            COMMENT 'User-Agent',
                `timestamp`     int unsigned    NOT NULL                COMMENT '실행 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_admin_log__admin_idx` (`admin_idx`),
                KEY `idx_admin_log__action` (`action`),
                KEY `idx_admin_log__timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='관리자 행위 감사 로그'
        ");

        // ── 관리자 세션/토큰 ─────────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_admin_session` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `admin_idx`     int unsigned    NOT NULL                COMMENT '관리자 회원 idx',
                `access_token`  varchar(512)    NOT NULL                COMMENT 'Admin Access Token (JWT)',
                `refresh_token` varchar(512)    NOT NULL                COMMENT 'Admin Refresh Token (JWT)',
                `expires_at`    int unsigned    NOT NULL                COMMENT 'Refresh Token 만료 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '로그인 IP',
                `created_at`    int unsigned    NOT NULL                COMMENT '발급 time()',
                `revoked`       tinyint(1)      NOT NULL DEFAULT 0      COMMENT '폐기 여부',
                PRIMARY KEY (`idx`),
                KEY `idx_admin_session__admin_idx` (`admin_idx`),
                KEY `idx_admin_session__refresh_token` (`refresh_token`(64))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='관리자 세션/토큰'
        ");

        // ── 사이트 전역 설정 ─────────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_site_config` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `config_key`    varchar(128)    NOT NULL                COMMENT '설정 키',
                `config_value`  text            DEFAULT NULL            COMMENT '설정 값',
                `description`   varchar(255)    DEFAULT NULL            COMMENT '설명',
                `updated_by`    int unsigned    DEFAULT NULL            COMMENT '마지막 수정 관리자 idx',
                `updated_at`    int unsigned    DEFAULT NULL            COMMENT '마지막 수정 time()',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `uq_site_config__config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='사이트 전역 설정'
        ");

        // ── 일별 통계 집계 ───────────────────────────────────────────────
        $db->query("
            CREATE TABLE IF NOT EXISTS `tb_stats_daily` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT,
                `stat_date`         date            NOT NULL                COMMENT '통계 날짜',
                `new_users`         int unsigned    NOT NULL DEFAULT 0      COMMENT '신규 가입자 수',
                `new_articles`      int unsigned    NOT NULL DEFAULT 0      COMMENT '신규 게시글 수',
                `new_comments`      int unsigned    NOT NULL DEFAULT 0      COMMENT '신규 댓글 수',
                `total_hits`        int unsigned    NOT NULL DEFAULT 0      COMMENT '총 조회수',
                `active_users`      int unsigned    NOT NULL DEFAULT 0      COMMENT '활성 사용자 수',
                `created_at`        int unsigned    NOT NULL                COMMENT '집계 time()',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `uq_stats_daily__stat_date` (`stat_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='일별 통계 집계'
        ");

        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $db = \Config\Database::connect('admin');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
