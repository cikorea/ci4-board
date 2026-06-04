<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsSchema extends Migration
{
    protected $DBGroup = 'default';

    public function getDBGroup(): ?string
    {
        return ENVIRONMENT === 'testing' ? null : 'default';
    }

    private array $tables = [
        'tb_cms_menu',
        'tb_cms_popup',
        'tb_cms_banner',
        'tb_cms_page',
    ];

    // ------------------------------------------------------------------ //

    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // ── CMS 페이지 ───────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_cms_page` (
                `idx`              int unsigned     NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `slug`             varchar(128)     NOT NULL                COMMENT '페이지 슬러그 (URL용 고유 식별자)',
                `title`            varchar(255)     NOT NULL                COMMENT '페이지 제목',
                `contents`         longtext         NOT NULL                COMMENT '페이지 본문 (HTML)',
                `status`           tinyint unsigned NOT NULL DEFAULT 0      COMMENT '상태 (0:임시저장, 1:발행)',
                `exec_user_idx`    int unsigned     NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`        varchar(64)      NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                `timestamp_insert` int unsigned     NOT NULL                COMMENT '생성 time()',
                `timestamp_update` int unsigned     DEFAULT NULL            COMMENT '수정 time()',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `slug_UNIQUE` (`slug`),
                KEY `idx_cms_page__status` (`status`),
                KEY `idx_cms_page__timestamp_insert` (`timestamp_insert`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 페이지'
        ");

        // ── CMS 배너 ─────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_cms_banner` (
                `idx`              int unsigned     NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `position`         varchar(64)      NOT NULL                COMMENT '노출 위치 코드',
                `image_path`       varchar(255)     NOT NULL                COMMENT '이미지 경로',
                `link_url`         varchar(255)     DEFAULT NULL            COMMENT '클릭 링크 URL',
                `start_at`         int unsigned     DEFAULT NULL            COMMENT '노출 시작 time()',
                `end_at`           int unsigned     DEFAULT NULL            COMMENT '노출 종료 time()',
                `sequence`         int unsigned     NOT NULL DEFAULT 0      COMMENT '노출 순서',
                `is_used`          tinyint unsigned NOT NULL DEFAULT 1      COMMENT '1:사용, 0:미사용',
                `exec_user_idx`    int unsigned     NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`        varchar(64)      NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                `timestamp_insert` int unsigned     NOT NULL                COMMENT '생성 time()',
                `timestamp_update` int unsigned     DEFAULT NULL            COMMENT '수정 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_cms_banner__position` (`position`),
                KEY `idx_cms_banner__is_used` (`is_used`),
                KEY `idx_cms_banner__start_at__end_at` (`start_at`, `end_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 배너'
        ");

        // ── CMS 팝업 ─────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_cms_popup` (
                `idx`              int unsigned     NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `title`            varchar(255)     NOT NULL                COMMENT '팝업 제목',
                `contents`         text             NOT NULL                COMMENT '팝업 내용 (HTML)',
                `position`         varchar(64)      NOT NULL DEFAULT ''     COMMENT '노출 위치 코드',
                `start_at`         int unsigned     DEFAULT NULL            COMMENT '노출 시작 time()',
                `end_at`           int unsigned     DEFAULT NULL            COMMENT '노출 종료 time()',
                `is_used`          tinyint unsigned NOT NULL DEFAULT 1      COMMENT '1:사용, 0:미사용',
                `exec_user_idx`    int unsigned     NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`        varchar(64)      NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                `timestamp_insert` int unsigned     NOT NULL                COMMENT '생성 time()',
                `timestamp_update` int unsigned     DEFAULT NULL            COMMENT '수정 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_cms_popup__is_used` (`is_used`),
                KEY `idx_cms_popup__start_at__end_at` (`start_at`, `end_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 팝업'
        ");

        // ── CMS 메뉴 ─────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_cms_menu` (
                `idx`              int unsigned     NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `parent_idx`       int unsigned     DEFAULT NULL            COMMENT '부모 메뉴 idx (NULL이면 최상위)',
                `label`            varchar(128)     NOT NULL                COMMENT '메뉴 표시명',
                `url`              varchar(255)     NOT NULL DEFAULT ''     COMMENT '링크 URL',
                `target`           varchar(16)      NOT NULL DEFAULT '_self' COMMENT '링크 타겟 (_self, _blank)',
                `sequence`         int unsigned     NOT NULL DEFAULT 0      COMMENT '동일 depth 내 순서',
                `is_used`          tinyint unsigned NOT NULL DEFAULT 1      COMMENT '1:사용, 0:미사용',
                `exec_user_idx`    int unsigned     NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`        varchar(64)      NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                `timestamp_insert` int unsigned     NOT NULL                COMMENT '생성 time()',
                `timestamp_update` int unsigned     DEFAULT NULL            COMMENT '수정 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_cms_menu__parent_idx` (`parent_idx`),
                KEY `idx_cms_menu__is_used` (`is_used`),
                KEY `idx_cms_menu__sequence` (`sequence`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CMS 메뉴'
        ");

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    // ------------------------------------------------------------------ //

    public function down(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
