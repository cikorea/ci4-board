<?php

namespace App\Controllers\Api\V1\Admin;

use App\Models\Admin\AdminLogModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 회원 API
 *
 * GET /api/admin/v1/members        회원 목록
 * PUT /api/admin/v1/members/:idx   회원 수정
 */
class MemberController extends BaseAdminApiController
{
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
