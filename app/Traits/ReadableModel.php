<?php

namespace App\Traits;

use CodeIgniter\Database\BaseConnection;

/**
 * 읽기/쓰기 DB 분리 트레이트.
 *
 * - $this->readDb  → read 그룹 (Slave, SELECT 전용)
 * - $this->db      → default 그룹 (Master, INSERT/UPDATE/DELETE)
 *
 * 적용 방법:
 *   use ReadableModel;
 *   public function __construct() { parent::__construct(); $this->initReadDb(); }
 *
 * Slave 추가 시 .env 의 database.read.hostname 만 변경하면 즉시 분산 적용.
 */
trait ReadableModel
{
    protected BaseConnection $readDb;

    public function initReadDb(): void
    {
        $this->readDb = db_connect('read');
    }
}
