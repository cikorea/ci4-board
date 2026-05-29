<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
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

    /** 받은 쪽지함 */
    public function getInbox(int $userIdx, int $page = 1, int $perPage = 20): array
    {
        $builder = $this->db->table('tb_users_message m')
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

    /** 보낸 쪽지함 */
    public function getSent(int $userIdx, int $page = 1, int $perPage = 20): array
    {
        $builder = $this->db->table('tb_users_message m')
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

    /** 단일 쪽지 조회 (발신자 or 수신자만 접근 가능) */
    public function getOne(int $idx, int $userIdx): ?array
    {
        return $this->db->table('tb_users_message m')
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

    /** 읽음 처리 */
    public function markRead(int $idx, string $ip): void
    {
        $this->db->table('tb_users_message')
            ->where('idx', $idx)
            ->where('is_read', 0)
            ->update([
                'is_read'          => 1,
                'timestamp_receive' => time(),
                'client_ip_receive' => $ip,
            ]);
    }

    /** 쪽지 전송 */
    public function send(array $data): int
    {
        $this->insert($data);
        return (int) $this->db->insertID();
    }

    /** 소프트 삭제 (발신자/수신자 각각) */
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

    /** 안 읽은 쪽지 수 */
    public function getUnreadCount(int $userIdx): int
    {
        return (int) $this->db->table('tb_users_message')
            ->where('receiver_user_idx', $userIdx)
            ->where('is_read', 0)
            ->where('is_deleted_receiver', 0)
            ->countAllResults();
    }

    public int $_total   = 0;
    public int $_page    = 1;
    public int $_perPage = 20;
}
