<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @apiDefine UserModel
 * @apiDescription tb_users 를 관리하는 모델.
 *   비밀번호는 super_secured_password 컬럼에 bcrypt 해시로 저장된다.
 */
class UserModel extends Model
{
    protected $table         = 'tb_users';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'super_secured_password', 'name', 'nickname',
        'email', 'level', 'group_idx', 'status', 'timezone',
        'timestamp_insert', 'timestamp_update', 'timestamp_delete',
        'timestamp_update_password',
        'client_ip_insert', 'client_ip_update', 'client_ip_delete',
        'client_ip_update_password',
    ];

    /**
     * @api {model} /model/UserModel/findByLoginId UserModel::findByLoginId
     * @apiName UserModel_findByLoginId
     * @apiDescription 로그인 ID로 사용자 조회 (group JOIN 포함)
     * @apiGroup UserModel
     * @apiDescription user_id 또는 email 로 검색하며 status=1(활성) 인 사용자만 반환한다.
     *   group_name 을 함께 반환하여 세션 초기화에 사용한다.
     *
     * @apiParam  {String} loginId  아이디 또는 이메일
     * @apiSuccess {Object} user 사용자 배열 (group_name 포함, 없으면 null)
     */
    public function findByLoginId(string $loginId): ?array
    {
        $row = $this->db->table('tb_users u')
            ->select('u.*, g.group_name')
            ->join('tb_users_group g', 'g.idx = u.group_idx', 'left')
            ->groupStart()
                ->where('u.user_id', $loginId)
                ->orWhere('u.email', $loginId)
            ->groupEnd()
            ->where('u.status', 1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * @api {model} /model/UserModel/findByUserId UserModel::findByUserId
     * @apiName UserModel_findByUserId
     * @apiDescription 아이디 중복 확인
     * @apiGroup UserModel
     *
     * @apiParam  {String} userId  확인할 user_id
     * @apiSuccess {Object} user 사용자 배열 (없으면 null)
     */
    public function findByUserId(string $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * @api {model} /model/UserModel/findByEmail UserModel::findByEmail
     * @apiName UserModel_findByEmail
     * @apiDescription 이메일 중복 확인
     * @apiGroup UserModel
     *
     * @apiParam  {String} email  확인할 이메일
     * @apiSuccess {Object} user 사용자 배열 (없으면 null)
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }
}
