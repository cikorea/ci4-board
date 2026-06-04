<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/admin/v1/boards',
        summary: '게시판 목록 + 설정',
        tags: ['AdminBoard'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '게시판 목록 및 권한 그룹 정보'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/admin/v1/boards/{bbsId}',
        summary: '게시판 설정 저장',
        tags: ['AdminBoard'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'free'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'bbs_name', type: 'string'),
                    new OA\Property(property: 'bbs_used', type: 'boolean'),
                    new OA\Property(property: 'bbs_count_list_article', type: 'integer', default: 15),
                    new OA\Property(property: 'bbs_comment_used', type: 'boolean'),
                    new OA\Property(property: 'view_list', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'view_article', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'write_article', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'write_comment', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '설정 저장 완료'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function update(string $bbsId): ResponseInterface
    {
        $db  = \Config\Database::connect();
        $bbs = $db->table('tb_bbs')->where('bbs_id', $bbsId)->get()->getRowArray();
        if (! $bbs) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
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

        $placeholders = implode(', ', array_fill(0, count($updates), '(?,?,?,?,?)'));
        $binds        = [];
        foreach ($updates as $parameter => $value) {
            $binds[] = $bbs['idx'];
            $binds[] = $parameter;
            $binds[] = $value;
            $binds[] = $adminIdx;
            $binds[] = $ip;
        }
        $db->query(
            "INSERT INTO `tb_bbs_setting` (bbs_idx, parameter, value, exec_user_idx, client_ip)
             VALUES {$placeholders} AS new_val
             ON DUPLICATE KEY UPDATE
                 value         = new_val.value,
                 exec_user_idx = new_val.exec_user_idx,
                 client_ip     = new_val.client_ip",
            $binds
        );

        $logModel = new AdminLogModel();
        $logModel->record(
            $this->getUserIdx(),
            'board.update',
            'tb_bbs_setting',
            (int) $bbs['idx'],
            null,
            $updates,
            $ip,
            $this->request->getUserAgent()->getAgentString()
        );

        // "게시판 '{0}' 설정이 저장되었습니다."
        return $this->success(null, lang('Api.admin_board_setting_saved', [$bbsId]));
    }
}
