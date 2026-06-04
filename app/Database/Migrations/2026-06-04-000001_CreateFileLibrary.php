<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFileLibrary extends Migration
{
    protected $DBGroup = 'default';

    public function getDBGroup(): ?string
    {
        return ENVIRONMENT === 'testing' ? null : 'default';
    }

    private array $tables = ['tb_file_library'];

    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_file_library` (
                `idx`              int unsigned     NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `uploader_idx`     int unsigned     NOT NULL                COMMENT '업로더 회원 idx',
                `source`           varchar(16)      NOT NULL DEFAULT 'direct' COMMENT '업로드 출처 (direct, wysiwyg)',
                `original_name`    varchar(255)     NOT NULL                COMMENT '원본 파일명',
                `stored_name`      varchar(255)     NOT NULL                COMMENT '저장 파일명',
                `file_path`        varchar(255)     NOT NULL                COMMENT '저장 상대 경로 (uploads/ 이하)',
                `mime_type`        varchar(127)     NOT NULL                COMMENT '실제 MIME 타입 (finfo 감지)',
                `file_size`        int unsigned     NOT NULL DEFAULT 0      COMMENT '파일 크기 (byte)',
                `alt_text`         varchar(255)     DEFAULT NULL            COMMENT '대체 텍스트',
                `used_count`       int unsigned     NOT NULL DEFAULT 0      COMMENT '사용처 참조 수',
                `timestamp_insert` int unsigned     NOT NULL                COMMENT '생성 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_file_library__uploader_idx` (`uploader_idx`),
                KEY `idx_file_library__source` (`source`),
                KEY `idx_file_library__mime_type` (`mime_type`(32)),
                KEY `idx_file_library__timestamp_insert` (`timestamp_insert`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='파일 라이브러리'
        ");

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
