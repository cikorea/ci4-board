<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 관리자 회원 API
 *
 * GET /api/admin/v1/members        회원 목록
 * PUT /api/admin/v1/members/:idx   회원 수정
 */
class MemberController extends BaseAdminApiController
{
    #[OA\Get(
        path: '/api/admin/v1/members',
        summary: '회원 목록',
        tags: ['AdminMember'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\QueryParameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: '회원 목록 (pagination 포함)'),
        ]
    )]
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $status  = $this->request->getGet('status');
        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_users u')
            ->select('u.idx, u.user_id, u.nickname, u.email, u.status,
                      u.timestamp_insert, u.article_count, u.comment_count, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->orderBy('u.idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('u.user_id', $keyword)
                ->orLike('u.nickname', $keyword)
                ->orLike('u.email', $keyword)
                ->groupEnd();
        }
        if ($status !== null && $status !== '') {
            $builder->where('u.status', (int) $status);
        }

        $total  = $builder->countAllResults(false);
        $users  = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        $groups = $db->table('tb_users_group')->orderBy('idx')->get()->getResultArray();

        return $this->successList($users, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage),
            'groups'    => $groups,
        ]);
    }

    #[OA\Put(
        path: '/api/admin/v1/members/{idx}',
        summary: '회원 정보 수정',
        tags: ['AdminMember'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'idx', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'group_idx', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                    new OA\Property(property: 'nickname', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'new_password', type: 'string', minLength: 6),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ]
    )]
    public function update(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $user = $db->table('tb_users')->where('idx', $idx)->get()->getRowArray();
        if (! $user) {
            return $this->failNotFound(lang('Api.member_not_found'));
        }

        $body     = (array) $this->request->getJSON(true);
        $nickname = trim((string) ($body['nickname'] ?? ''));
        $email    = trim((string) ($body['email']    ?? ''));
        $ip       = $this->request->getIPAddress();

        if ($db->table('tb_users')->where('nickname', $nickname)->where('idx !=', $idx)->countAllResults()) {
            return $this->failValidation([], lang('Api.admin_member_nickname_dup'));
        }
        if ($db->table('tb_users')->where('email', $email)->where('idx !=', $idx)->countAllResults()) {
            return $this->failValidation([], lang('Api.admin_member_email_dup'));
        }

        $data = [
            'group_idx'        => (int) ($body['group_idx'] ?? $user['group_idx']),
            'status'           => (int) ($body['status']    ?? $user['status']),
            'nickname'         => $nickname,
            'name'             => $nickname,
            'email'            => $email,
            'timestamp_update' => time(),
            'client_ip_update' => $ip,
        ];

        $newPw = trim((string) ($body['new_password'] ?? ''));
        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                return $this->failValidation([], lang('Api.admin_member_pw_min_length'));
            }
            $data['super_secured_password']    = password_hash($newPw, PASSWORD_BCRYPT);
            $data['timestamp_update_password'] = time();
            $data['client_ip_update_password'] = $ip;
        }

        $before = array_intersect_key($user, array_flip(['group_idx', 'status', 'nickname', 'email']));
        $db->table('tb_users')->where('idx', $idx)->update($data);

        $logModel = new AdminLogModel();
        $logModel->record(
            $this->getUserIdx(),
            'member.update',
            'tb_users',
            $idx,
            $before,
            array_intersect_key($data, array_flip(['group_idx', 'status', 'nickname', 'email'])),
            $ip,
            $this->request->getUserAgent()->getAgentString()
        );

        return $this->success(null, lang('Api.admin_member_updated'));
    }
}
