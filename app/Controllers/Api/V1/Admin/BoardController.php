<?php

namespace App\Controllers\Api\V1\Admin;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 게시판 API
 *
 * GET /api/admin/v1/boards          전체 게시판 + 설정 요약
 * PUT /api/admin/v1/boards/:bbsId   게시판 설정 저장
 */
class BoardController extends BaseAdminApiController
{
    private const BBS_PERM_PARAMS = [
        'bbs_allow_group_view_list',
        'bbs_allow_group_view_article',
        'bbs_allow_group_write_article',
        'bbs_allow_group_write_comment',
    ];

    private const BBS_EDIT_PARAMS = [
        'bbs_name', 'bbs_used', 'bbs_count_list_article', 'bbs_comment_used',
        'bbs_allow_group_view_list', 'bbs_allow_group_view_article',
        'bbs_allow_group_write_article', 'bbs_allow_group_write_comment',
    ];

    public function index(): ResponseInterface
    {
        $db = \Config\Database::connect();

        $boards = $db->table('tb_bbs b')
            ->select("b.idx, b.bbs_id,
                MAX(CASE WHEN s.parameter='bbs_name'         THEN s.value END) AS bbs_name,
                MAX(CASE WHEN s.parameter='bbs_used'         THEN s.value END) AS bbs_used,
                MAX(CASE WHEN s.parameter='bbs_count_list_article' THEN s.value END) AS list_count,
                MAX(CASE WHEN s.parameter='bbs_comment_used' THEN s.value END) AS comment_used,
                MAX(CASE WHEN s.parameter='bbs_allow_group_view_list'     THEN s.value END) AS perm_view_list,
                MAX(CASE WHEN s.parameter='bbs_allow_group_write_article' THEN s.value END) AS perm_write_article")
            ->join('tb_bbs_setting s', 's.bbs_idx = b.idx')
            ->groupBy('b.idx, b.bbs_id')
            ->orderBy('b.idx', 'ASC')
            ->get()->getResultArray();

        $groups = $db->table('tb_users_group')->where('is_used', 1)->orderBy('idx')->get()->getResultArray();

        return $this->success(['boards' => $boards, 'groups' => $groups]);
    }

    public function update(string $bbsId): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $bbs = $db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
        if (! $bbs) {
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }

        $body     = (array) $this->request->getJSON(true);
        $adminIdx = $this->getUserIdx();
        $ip       = $this->request->getIPAddress();

        $updates = [
            'bbs_name'               => trim((string) ($body['bbs_name'] ?? '')),
            'bbs_used'               => isset($body['bbs_used']) && $body['bbs_used'] ? '1' : '0',
            'bbs_count_list_article' => (string) max(1, (int) ($body['bbs_count_list_article'] ?? 15)),
            'bbs_comment_used'       => isset($body['bbs_comment_used']) && $body['bbs_comment_used'] ? '1' : '0',
        ];

        foreach (self::BBS_PERM_PARAMS as $param) {
            $key            = str_replace('bbs_allow_group_', '', $param);
            $selected       = array_map('strval', (array) ($body[$key] ?? []));
            $updates[$param] = $selected ? serialize(array_values($selected)) : serialize([]);
        }

        foreach ($updates as $parameter => $value) {
            $exists = $db->table('tb_bbs_setting')
                ->where('bbs_idx', $bbs['idx'])->where('parameter', $parameter)
                ->get()->getRowArray();

            if ($exists) {
                $db->table('tb_bbs_setting')
                    ->where('bbs_idx', $bbs['idx'])->where('parameter', $parameter)
                    ->update(['value' => $value, 'exec_user_idx' => $adminIdx, 'client_ip' => $ip]);
            } else {
                $db->table('tb_bbs_setting')->insert([
                    'bbs_idx' => $bbs['idx'], 'parameter' => $parameter,
                    'value' => $value, 'exec_user_idx' => $adminIdx, 'client_ip' => $ip,
                ]);
            }
        }

        return $this->success(null, "게시판 '{$bbsId}' 설정이 저장되었습니다.");
    }
}
