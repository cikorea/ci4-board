<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @apiDefine BbsModel
 * @apiDescription tb_bbs (게시판 기본) + tb_bbs_setting (설정 key-value) 을 통합 관리하는 모델.
 *   권한 설정값은 PHP serialize() 형식으로 저장되며, 읽을 때 parse_group_setting() 으로 파싱된다.
 */
class BbsModel extends Model
{
    protected $table         = 'tb_bbs';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['bbs_id', 'exec_user_idx', 'timestamp', 'client_ip'];

    /** 권한 체크에 사용할 설정 파라미터 키 */
    private const PERM_PARAMS = [
        'bbs_allow_group_view_list',
        'bbs_allow_group_view_article',
        'bbs_allow_group_write_article',
        'bbs_allow_group_write_comment',
    ];

    /**
     * @api {model} /model/BbsModel/getActiveBoards BbsModel::getActiveBoards
     * @apiName BbsModel_getActiveBoards
     * @apiDescription 활성 게시판 목록 (권한 필터 포함)
     * @apiGroup BbsModel
     * @apiDescription bbs_used=1 인 게시판 중 현재 사용자가 view_list 권한을 가진 게시판만 반환한다.
     *   네비게이션 및 홈 위젯에 사용된다.
     *
     * @apiSuccess {Array} rows  게시판 배열 (idx, bbs_id, bbs_name, list_count)
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

        $filtered = array_filter($rows, function (array $board): bool {
            $groups = parse_group_setting($board['view_list_raw'] ?? '');
            return user_can_in_groups($groups);
        });

        return array_map([$this, 'decodeName'], array_values($filtered));
    }

    /**
     * @api {model} /model/BbsModel/getByBbsId BbsModel::getByBbsId
     * @apiName BbsModel_getByBbsId
     * @apiDescription 슬러그로 게시판 조회 (권한 맵 포함)
     * @apiGroup BbsModel
     * @apiDescription bbs_id(slug)로 게시판 기본 정보를 가져오고, permissions 키에 권한 배열을 추가한다.
     *
     * @apiParam  {String} bbsId  게시판 슬러그
     * @apiSuccess {Object} board 게시판 배열 (permissions.view_list, view_article, write_article, write_comment 포함)
     *   또는 존재하지 않으면 null
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
     * @api {model} /model/BbsModel/loadPermissions BbsModel::loadPermissions
     * @apiName BbsModel_loadPermissions
     * @apiDescription 게시판 권한 설정 로드
     * @apiGroup BbsModel
     * @apiDescription tb_bbs_setting 에서 4가지 권한 파라미터를 읽어 파싱된 그룹 idx 배열로 반환한다.
     *   반환 키: view_list, view_article, write_article, write_comment
     *
     * @apiParam  {Number} bbsIdx  게시판 idx
     * @apiSuccess {Object} perms  {'view_list': [0,1,2], 'view_article': [...], ...}
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
