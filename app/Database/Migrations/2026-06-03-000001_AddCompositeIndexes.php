<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 복합 인덱스 추가 — 단일 컬럼 인덱스를 hot-path 쿼리에 맞춘 복합 인덱스로 교체
 *
 * tb_bbs_article  : 게시글 목록 filter(bbs_idx, is_deleted) + sort(is_notice DESC, idx DESC)
 * tb_bbs_comment  : 댓글 목록 filter(article_idx, is_deleted) + sort(idx ASC)
 * tb_users_message: 받은쪽지함, 보낸쪽지함, 안읽은쪽지 카운트
 * tb_bbs_file     : 첨부파일 목록 filter(article_idx, is_wysiwyg)
 */
class AddCompositeIndexes extends Migration
{
    protected $DBGroup = 'default';

    public function getDBGroup(): ?string
    {
        return ENVIRONMENT === 'testing' ? null : 'default';
    }

    public function up(): void
    {
        // ── tb_bbs_article ────────────────────────────────────────────────
        // 기존 단일 인덱스 3개 제거 → 복합 인덱스 1개로 대체
        $this->db->query('ALTER TABLE `tb_bbs_article`
            DROP KEY `fk_bbs__idx__vs__bbs_article__bbs_idx`,
            DROP KEY `idx_bbs_article__is_deleted`,
            DROP KEY `idx_bbs_article__is_notice`,
            ADD KEY `idx_bbs_article__list` (`bbs_idx`, `is_deleted`, `is_notice`, `idx`)
        ');

        // ── tb_bbs_comment ───────────────────────────────────────────────
        // 기존 단일 인덱스 2개 제거 → 복합 인덱스 1개로 대체
        $this->db->query('ALTER TABLE `tb_bbs_comment`
            DROP KEY `fk_bbs_article__idx__vs__bbs_comment__article_idx`,
            DROP KEY `idx_bbs_comment__is_deleted`,
            ADD KEY `idx_bbs_comment__list` (`article_idx`, `is_deleted`, `idx`)
        ');

        // ── tb_users_message ─────────────────────────────────────────────
        // 기존 단일 인덱스 4개 제거 → 복합 인덱스 3개로 대체
        // inbox list      : receiver_user_idx + is_deleted_receiver + idx(정렬)
        // unread count    : receiver_user_idx + is_deleted_receiver + is_read(필터)
        // sent list       : sender_user_idx   + is_deleted_sender   + idx(정렬)
        $this->db->query('ALTER TABLE `tb_users_message`
            DROP KEY `fk_users__idx__vs__users_message__sender_user_idx`,
            DROP KEY `fk_users__idx__vs__users_message__receiver_user_idx`,
            DROP KEY `idx_users_message__is_deleted_sender`,
            DROP KEY `idx_users_message__is_deleted_receiver`,
            ADD KEY `idx_users_message__inbox`  (`receiver_user_idx`, `is_deleted_receiver`, `idx`),
            ADD KEY `idx_users_message__unread` (`receiver_user_idx`, `is_deleted_receiver`, `is_read`),
            ADD KEY `idx_users_message__sent`   (`sender_user_idx`, `is_deleted_sender`, `idx`)
        ');

        // ── tb_bbs_file ───────────────────────────────────────────────────
        // 기존 단일 article_idx 인덱스 제거 → (article_idx, is_wysiwyg) 복합으로 대체
        $this->db->query('ALTER TABLE `tb_bbs_file`
            DROP KEY `fk_bbs_article__idx__vs__bbs_file__article_idx`,
            ADD KEY `idx_bbs_file__list` (`article_idx`, `is_wysiwyg`)
        ');
    }

    public function down(): void
    {
        // ── tb_bbs_article ────────────────────────────────────────────────
        $this->db->query('ALTER TABLE `tb_bbs_article`
            DROP KEY `idx_bbs_article__list`,
            ADD KEY `fk_bbs__idx__vs__bbs_article__bbs_idx` (`bbs_idx`),
            ADD KEY `idx_bbs_article__is_deleted` (`is_deleted`),
            ADD KEY `idx_bbs_article__is_notice` (`is_notice`)
        ');

        // ── tb_bbs_comment ───────────────────────────────────────────────
        $this->db->query('ALTER TABLE `tb_bbs_comment`
            DROP KEY `idx_bbs_comment__list`,
            ADD KEY `fk_bbs_article__idx__vs__bbs_comment__article_idx` (`article_idx`),
            ADD KEY `idx_bbs_comment__is_deleted` (`is_deleted`)
        ');

        // ── tb_users_message ─────────────────────────────────────────────
        $this->db->query('ALTER TABLE `tb_users_message`
            DROP KEY `idx_users_message__inbox`,
            DROP KEY `idx_users_message__unread`,
            DROP KEY `idx_users_message__sent`,
            ADD KEY `fk_users__idx__vs__users_message__sender_user_idx` (`sender_user_idx`),
            ADD KEY `fk_users__idx__vs__users_message__receiver_user_idx` (`receiver_user_idx`),
            ADD KEY `idx_users_message__is_deleted_sender` (`is_deleted_sender`),
            ADD KEY `idx_users_message__is_deleted_receiver` (`is_deleted_receiver`)
        ');

        // ── tb_bbs_file ───────────────────────────────────────────────────
        $this->db->query('ALTER TABLE `tb_bbs_file`
            DROP KEY `idx_bbs_file__list`,
            ADD KEY `fk_bbs_article__idx__vs__bbs_file__article_idx` (`article_idx`)
        ');
    }
}
