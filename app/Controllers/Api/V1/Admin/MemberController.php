<?php

namespace App\Controllers\Api\V1\Admin;

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
            return $this->failNotFound('회원을 찾을 수 없습니다.');
        }

        $body     = (array) $this->request->getJSON(true);
        $nickname = trim((string) ($body['nickname'] ?? ''));
        $email    = trim((string) ($body['email']    ?? ''));
        $ip       = $this->request->getIPAddress();

        if ($db->table('tb_users')->where('nickname', $nickname)->where('idx !=', $idx)->countAllResults()) {
            return $this->failValidation([], '이미 사용 중인 닉네임입니다.');
        }
        if ($db->table('tb_users')->where('email', $email)->where('idx !=', $idx)->countAllResults()) {
            return $this->failValidation([], '이미 사용 중인 이메일입니다.');
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
                return $this->failValidation([], '비밀번호는 6자 이상이어야 합니다.');
            }
            $data['super_secured_password']    = password_hash($newPw, PASSWORD_BCRYPT);
            $data['timestamp_update_password'] = time();
            $data['client_ip_update_password'] = $ip;
        }

        $db->table('tb_users')->where('idx', $idx)->update($data);

        return $this->success(null, '회원 정보가 수정되었습니다.');
    }
}
