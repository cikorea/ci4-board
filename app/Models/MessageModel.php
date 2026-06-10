<?php

namespace App\Models;

use App\Traits\ReadableModel;
use CodeIgniter\Model;

/**
 * @apiDefine MessageModel
 * @apiDescription tb_users_message 를 관리하는 모델.
 *   쪽지는 소프트 삭제 방식으로 발신자/수신자 각각의 플래그를 사용한다.
 */
class MessageModel extends Model
{
    use ReadableModel;

    protected $table         = 'tb_users_message';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sender_user_idx', 'receiver_user_idx', 'title', 'contents',
        'timestamp_send', 'timestamp_receive',
        'client_ip_send', 'client_ip_receive',
        'is_read', 'is_deleted_sender', 'is_deleted_receiver',
    ];

    /** @var int 마지막 getInbox()/getSent() 전체 레코드 수 */
    public int $_total   = 0;
    /** @var int 마지막 getInbox()/getSent() 페이지 번호 */
    public int $_page    = 1;
    /** @var int 마지막 getInbox()/getSent() 페이지당 수 */
    public int $_perPage = 20;

    public function __construct()
    {
        parent::__construct();
        $this->initReadDb();
    }

    /**
     * @api {model} /model/MessageModel/getInbox MessageModel::getInbox
     * @apiName MessageModel_getInbox
     * @apiDescription 받은 쪽지함 목록
     * @apiGroup MessageModel
     * @apiDescription 수신자 기준 목록. 호출 후 _total, _page, _perPage 프로퍼티에 메타 저장.
     *
     * @apiParam  {Number} userIdx    수신자 idx
     * @apiParam  {Number} [page=1]   페이지 번호
     * @apiParam  {Number} [perPage=20] 페이지당 수
     * @apiSuccess {Array} rows       쪽지 배열 (sender_user_id, sender_nickname 포함)
     */
    public function getInbox(int $userIdx, int $page = 1, int $perPage = 20): array
    {
        $builder = $this->readDb->table('tb_users_message m')
            ->select('m.idx, m.title, m.contents, m.timestamp_send, m.is_read,
                      s.user_id AS sender_user_id, s.nickname AS sender_nickname')
            ->join('tb_users s', 's.idx = m.sender_user_idx', 'left')
            ->where('m.receiver_user_idx', $userIdx)
            ->where('m.is_deleted_receiver', 0)
            ->orderBy('m.idx', 'DESC');

        $this->_total   = $builder->countAllResults(false);
        $this->_page    = $page;
        $this->_perPage = $perPage;

        return $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
    }

    /**
     * @api {model} /model/MessageModel/getSent MessageModel::getSent
     * @apiName MessageModel_getSent
     * @apiDescription 보낸 쪽지함 목록
     * @apiGroup MessageModel
     * @apiDescription 발신자 기준 목록. 호출 후 _total, _page, _perPage 프로퍼티에 메타 저장.
     *
     * @apiParam  {Number} userIdx    발신자 idx
     * @apiParam  {Number} [page=1]   페이지 번호
     * @apiParam  {Number} [perPage=20] 페이지당 수
     * @apiSuccess {Array} rows       쪽지 배열 (receiver_user_id, receiver_nickname 포함)
     */
    public function getSent(int $userIdx, int $page = 1, int $perPage = 20): array
    {
        $builder = $this->readDb->table('tb_users_message m')
            ->select('m.idx, m.title, m.contents, m.timestamp_send, m.is_read,
                      r.user_id AS receiver_user_id, r.nickname AS receiver_nickname')
            ->join('tb_users r', 'r.idx = m.receiver_user_idx', 'left')
            ->where('m.sender_user_idx', $userIdx)
            ->where('m.is_deleted_sender', 0)
            ->orderBy('m.idx', 'DESC');

        $this->_total   = $builder->countAllResults(false);
        $this->_page    = $page;
        $this->_perPage = $perPage;

        return $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
    }

    /**
     * @api {model} /model/MessageModel/getOne MessageModel::getOne
     * @apiName MessageModel_getOne
     * @apiDescription 쪽지 단건 조회 (접근 권한 검사 포함)
     * @apiGroup MessageModel
     * @apiDescription 발신자 또는 수신자인 userIdx 만 접근할 수 있다.
     *
     * @apiParam  {Number} idx      쪽지 idx
     * @apiParam  {Number} userIdx  접근 요청자 idx
     * @apiSuccess {Object} message 쪽지 배열 (sender/receiver 포함, 없으면 null)
     */
    public function getOne(int $idx, int $userIdx): ?array
    {
        return $this->readDb->table('tb_users_message m')
            ->select('m.*, s.user_id AS sender_user_id, s.nickname AS sender_nickname,
                      r.user_id AS receiver_user_id, r.nickname AS receiver_nickname')
            ->join('tb_users s', 's.idx = m.sender_user_idx', 'left')
            ->join('tb_users r', 'r.idx = m.receiver_user_idx', 'left')
            ->where('m.idx', $idx)
            ->groupStart()
                ->where('m.sender_user_idx', $userIdx)
                ->orWhere('m.receiver_user_idx', $userIdx)
            ->groupEnd()
            ->get()->getRowArray() ?: null;
    }

    /**
     * @api {model} /model/MessageModel/markRead MessageModel::markRead
     * @apiName MessageModel_markRead
     * @apiDescription 쪽지 읽음 처리
     * @apiGroup MessageModel
     * @apiDescription is_read=0 인 경우에만 UPDATE 하여 중복 갱신을 방지한다.
     *
     * @apiParam {Number} idx  쪽지 idx
     * @apiParam {String} ip   수신자 IP
     */
    public function markRead(int $idx, string $ip): void
    {
        $this->db->table('tb_users_message')
            ->where('idx', $idx)
            ->where('is_read', 0)
            ->update([
                'is_read'           => 1,
                'timestamp_receive' => time(),
                'client_ip_receive' => $ip,
            ]);
    }

    /**
     * @api {model} /model/MessageModel/send MessageModel::send
     * @apiName MessageModel_send
     * @apiDescription 쪽지 전송
     * @apiGroup MessageModel
     *
     * @apiParam  {Object} data  쪽지 컬럼 맵 (sender_user_idx, receiver_user_idx, contents 등)
     * @apiSuccess {Number} messageIdx 새로 생성된 쪽지 idx
     */
    public function send(array $data): int
    {
        $this->insert($data);
        return (int) $this->db->insertID();
    }

    /**
     * @api {model} /model/MessageModel/deleteForUser MessageModel::deleteForUser
     * @apiName MessageModel_deleteForUser
     * @apiDescription 쪽지 소프트 삭제 (사용자별)
     * @apiGroup MessageModel
     * @apiDescription 발신자라면 is_deleted_sender=1, 수신자라면 is_deleted_receiver=1 로 처리한다.
     *   한 사람이 삭제해도 상대방에게는 쪽지가 남는다.
     *
     * @apiParam {Number} idx      쪽지 idx
     * @apiParam {Number} userIdx  삭제 요청자 idx
     */
    public function deleteForUser(int $idx, int $userIdx): void
    {
        $msg = $this->find($idx);
        if (! $msg) return;

        if ($msg['sender_user_idx'] == $userIdx) {
            $this->update($idx, ['is_deleted_sender' => 1]);
        }
        if ($msg['receiver_user_idx'] == $userIdx) {
            $this->update($idx, ['is_deleted_receiver' => 1]);
        }
    }

    /**
     * @api {model} /model/MessageModel/getUnreadCount MessageModel::getUnreadCount
     * @apiName MessageModel_getUnreadCount
     * @apiDescription 안 읽은 쪽지 수
     * @apiGroup MessageModel
     * @apiDescription 네비게이션 뱃지 표시 등에 사용된다.
     *
     * @apiParam  {Number} userIdx  사용자 idx
     * @apiSuccess {Number} count 안 읽은 쪽지 수
     */
    public function getUnreadCount(int $userIdx): int
    {
        return (int) $this->readDb->table('tb_users_message')
            ->where('receiver_user_idx', $userIdx)
            ->where('is_read', 0)
            ->where('is_deleted_receiver', 0)
            ->countAllResults();
    }
}
