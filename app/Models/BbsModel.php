<?php

namespace App\Models;

use CodeIgniter\Model;

class BbsModel extends Model
{
    protected $table         = 'tb_bbs';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['bbs_id', 'exec_user_idx', 'timestamp', 'client_ip'];

    /** 권한 체크에 사용할 설정 파라미터 */
    private const PERM_PARAMS = [
        'bbs_allow_group_view_list',
        'bbs_allow_group_view_article',
        'bbs_allow_group_write_article',
        'bbs_allow_group_write_comment',
    ];

    /**
     * 현재 사용자가 view_list 권한을 가진 활성 게시판 목록 반환
     */
    public function getActiveBoards(): array
    {
        $rows = $this->db->table('tb_bbs b')
            ->select('b.idx, b.bbs_id, sn.value AS bbs_name, sl.value AS list_count, sp.value AS view_list_raw')
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->join('tb_bbs_setting sl', "sl.bbs_idx = b.idx AND sl.parameter = 'bbs_count_list_article'", 'left')
            ->join('tb_bbs_setting su', "su.bbs_idx = b.idx AND su.parameter = 'bbs_used'", 'inner')
            ->join('tb_bbs_setting sp', "sp.bbs_idx = b.idx AND sp.parameter = 'bbs_allow_group_view_list'", 'left')
            ->where('su.value', '1')
            ->orderBy('b.idx', 'ASC')
            ->get()
            ->getResultArray();

        // view_list 권한이 있는 게시판만 필터링
        $filtered = array_filter($rows, function (array $board): bool {
            $groups = parse_group_setting($board['view_list_raw'] ?? '');
            return user_can_in_groups($groups);
        });

        return array_map([$this, 'decodeName'], array_values($filtered));
    }

    /**
     * bbs_id(slug)로 게시판 정보 + 권한 설정 조회
     */
    public function getByBbsId(string $bbsId): ?array
    {
        $row = $this->db->table('tb_bbs b')
            ->select('b.idx, b.bbs_id, sn.value AS bbs_name')
            ->join('tb_bbs_setting sn', "sn.bbs_idx = b.idx AND sn.parameter = 'bbs_name'", 'left')
            ->where('b.bbs_id', $bbsId)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $row['permissions'] = $this->loadPermissions($row['idx']);

        return $this->decodeName($row);
    }

    /**
     * 게시판의 권한 설정을 파싱해서 반환
     * 반환 형태: ['view_list' => [0,1,2,3], 'view_article' => [...], ...]
     */
    public function loadPermissions(int $bbsIdx): array
    {
        $settings = $this->db->table('tb_bbs_setting')
            ->whereIn('parameter', self::PERM_PARAMS)
            ->where('bbs_idx', $bbsIdx)
            ->get()
            ->getResultArray();

        $perms = [];
        foreach ($settings as $s) {
            // 'bbs_allow_group_view_list' → 'view_list'
            $key = str_replace('bbs_allow_group_', '', $s['parameter']);
            $perms[$key] = parse_group_setting($s['value']);
        }

        return $perms;
    }

    private function decodeName(array $row): array
    {
        if (isset($row['bbs_name'])) {
            $row['bbs_name'] = html_entity_decode($row['bbs_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $row;
    }
}
