<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileLibraryIsPublic extends Migration
{
    protected $DBGroup = 'default';

    public function getDBGroup(): ?string
    {
        return ENVIRONMENT === 'testing' ? null : 'default';
    }

    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `tb_file_library`
                ADD COLUMN `is_public` tinyint(1) NOT NULL DEFAULT 0
                    COMMENT '0:비공개, 1:공용 (다른 사용자가 목록에서 검색·재사용 가능)'
                AFTER `alt_text`,
                ADD KEY `idx_file_library__is_public` (`is_public`)
        ");
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `tb_file_library` DROP INDEX `idx_file_library__is_public`');
        $this->db->query('ALTER TABLE `tb_file_library` DROP COLUMN `is_public`');
    }
}
