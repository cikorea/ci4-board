<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Initial schema migration.
 * Creates all tables for the CI4 Board application.
 */
class CreateInitialSchema extends Migration
{
    protected $DBGroup = 'default';

    /** Tables to drop in down() — reverse dependency order */
    private array $tables = [
        'tb_users_url',
        'tb_users_point',
        'tb_users_message',
        'tb_users_group_revision',
        'tb_users_friend',
        'tb_users_block_history',
        'tb_themes',
        'tb_setting_revision',
        'tb_setting',
        'tb_client_ip_block',
        'tb_client_ip_access',
        'tb_bbs_vote',
        'tb_bbs_url',
        'tb_bbs_tag',
        'tb_bbs_setting_revision',
        'tb_bbs_setting',
        'tb_bbs_hit',
        'tb_bbs_file_temporary',
        'tb_bbs_file',
        'tb_bbs_contents_revision',
        'tb_bbs_contents',
        'tb_bbs_comment_revision',
        'tb_bbs_comment',
        'tb_bbs_category_revision',
        'tb_bbs_category',
        'tb_bbs_article_revision',
        'tb_bbs_article',
        'tb_bbs',
        'tb_users_group',
        'tb_users',
    ];

    // ------------------------------------------------------------------ //

    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // ── 회원 그룹 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_group` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `group_name`    varchar(64)     NOT NULL                COMMENT '그룹명',
                `icon_path`     varchar(255)    DEFAULT NULL            COMMENT '아이콘 경로',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '마지막 수정 접근 IP',
                `is_used`       tinyint(1)      NOT NULL DEFAULT 1      COMMENT '1:사용, 0:미사용',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `group_name_UNIQUE` (`group_name`),
                KEY `idx_users_group__is_used` (`is_used`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원그룹'
        ");

        // ── 회원 ────────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users` (
                `idx`                       int unsigned    NOT NULL AUTO_INCREMENT,
                `user_id`                   varchar(32)     NOT NULL                COMMENT '회원 ID',
                `password`                  varchar(64)     DEFAULT NULL            COMMENT '비밀번호 (legacy)',
                `super_secured_password`    varchar(255)    DEFAULT NULL            COMMENT '비밀번호 (bcrypt)',
                `new_password`              varchar(255)    DEFAULT NULL            COMMENT '임시 새 비밀번호',
                `new_password_timestamp`    int unsigned    DEFAULT NULL            COMMENT '임시 새 비밀번호 요청 time()',
                `level`                     tinyint unsigned NOT NULL DEFAULT 1     COMMENT '레벨 (큰 수가 상위)',
                `group_idx`                 int unsigned    NOT NULL DEFAULT 2      COMMENT '회원그룹',
                `name`                      varchar(64)     NOT NULL DEFAULT ''     COMMENT '실명',
                `nickname`                  varchar(64)     NOT NULL                COMMENT '닉네임',
                `message_receive_type`      tinyint unsigned NOT NULL DEFAULT 1    COMMENT '쪽지수신방법 (0:전체거부, 1:전체수신, 2:친구만수신)',
                `email`                     varchar(128)    NOT NULL                COMMENT '이메일',
                `timezone`                  varchar(4)      NOT NULL DEFAULT '+09'  COMMENT '타임존',
                `article_count`             int unsigned    NOT NULL DEFAULT 0      COMMENT '글 작성 수',
                `comment_count`             int unsigned    NOT NULL DEFAULT 0      COMMENT '코멘트 작성 수',
                `vote_send_count`           int unsigned    NOT NULL DEFAULT 0      COMMENT '추천한 수',
                `vote_receive_count`        int unsigned    NOT NULL DEFAULT 0      COMMENT '추천받은 수',
                `point`                     int             NOT NULL DEFAULT 0      COMMENT '포인트',
                `avatar_used`               tinyint unsigned NOT NULL DEFAULT 0    COMMENT '아바타 사용 (0:미사용, 1:사용)',
                `memo`                      text            DEFAULT NULL            COMMENT '개인메모장',
                `status`                    tinyint unsigned NOT NULL DEFAULT 1    COMMENT '계정상태 (1:정상, 0:비활성)',
                `timestamp_insert`          int unsigned    NOT NULL                COMMENT '가입 time()',
                `timestamp_update`          int unsigned    DEFAULT NULL            COMMENT '마지막수정 time()',
                `timestamp_delete`          int unsigned    DEFAULT NULL            COMMENT '탈퇴 time()',
                `timestamp_login`           int unsigned    DEFAULT NULL            COMMENT '마지막로그인 time()',
                `timestamp_post`            int unsigned    DEFAULT NULL            COMMENT '마지막 글/코멘트 time()',
                `timestamp_update_password` int unsigned    DEFAULT NULL            COMMENT '비번변경 time()',
                `client_ip_insert`          varchar(64)     NOT NULL DEFAULT ''     COMMENT '가입 IP',
                `client_ip_update`          varchar(64)     DEFAULT NULL            COMMENT '마지막수정 IP',
                `client_ip_delete`          varchar(64)     DEFAULT NULL            COMMENT '탈퇴 IP',
                `client_ip_login`           varchar(64)     DEFAULT NULL            COMMENT '마지막로그인 IP',
                `client_ip_post`            varchar(64)     DEFAULT NULL            COMMENT '마지막 글/코멘트 IP',
                `client_ip_update_password` varchar(64)     DEFAULT NULL            COMMENT '비번변경 IP',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `user_id_UNIQUE` (`user_id`),
                UNIQUE KEY `email_UNIQUE` (`email`),
                KEY `idx_users__nickname` (`nickname`),
                KEY `idx_users__status` (`status`),
                KEY `fk_users_group__idx__vs__users__group_idx` (`group_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원'
        ");

        // ── 게시판 기본정보 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_id`        varchar(64)     NOT NULL                COMMENT '게시판 고유 ID',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '생성 회원 idx',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '생성 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '생성 IP',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `bbs_id_UNIQUE` (`bbs_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시판 기본정보'
        ");

        // ── 게시판 설정 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_setting` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `parameter`     varchar(128)    NOT NULL                COMMENT '파라미터',
                `value`         varchar(1000)   NOT NULL DEFAULT ''     COMMENT '값',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                PRIMARY KEY (`idx`, `bbs_idx`, `parameter`),
                UNIQUE KEY `uq_bbs_setting__bbs_idx__parameter` (`bbs_idx`, `parameter`),
                KEY `fk_bbs__idx__vs__bbs_setting__bbs_idx` (`bbs_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시판 설정'
        ");

        // ── 게시판 설정 히스토리 ─────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_setting_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `setting_idx`   int unsigned    NOT NULL                COMMENT '부모 idx',
                `parameter`     varchar(128)    NOT NULL                COMMENT '파라미터',
                `value`         varchar(1000)   NOT NULL DEFAULT ''     COMMENT '값',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs__idx__vs__bbs_setting_revision__bbs_idx` (`bbs_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시판 설정 히스토리'
        ");

        // ── 게시판 카테고리 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_category` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `category_name` varchar(64)     NOT NULL                COMMENT '카테고리명',
                `sequence`      int unsigned    DEFAULT NULL            COMMENT '순서',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                `is_used`       tinyint unsigned NOT NULL DEFAULT 1     COMMENT '1:사용, 0:미사용',
                PRIMARY KEY (`idx`),
                KEY `idx_bbs_category__is_used` (`is_used`),
                KEY `fk_bbs__idx__vs__bbs_category__bbs_idx` (`bbs_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시판 카테고리'
        ");

        // ── 게시판 카테고리 히스토리 ─────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_category_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `category_idx`  int unsigned    NOT NULL                COMMENT '부모 idx',
                `category_name` varchar(64)     NOT NULL                COMMENT '카테고리명',
                `sequence`      int unsigned    DEFAULT NULL            COMMENT '순서',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                `is_used`       tinyint unsigned NOT NULL DEFAULT 1     COMMENT '1:사용, 0:미사용',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시판 카테고리 히스토리'
        ");

        // ── 게시물 기본정보 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_article` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`           int unsigned    NOT NULL                COMMENT '게시판 idx',
                `category_idx`      int unsigned    DEFAULT NULL            COMMENT '카테고리 idx',
                `user_idx`          int unsigned    NOT NULL                COMMENT '회원 idx',
                `exec_user_idx`     int unsigned    NOT NULL                COMMENT '마지막 수정 회원 idx',
                `title`             varchar(255)    NOT NULL                COMMENT '제목',
                `comment_count`     int unsigned    NOT NULL DEFAULT 0      COMMENT '댓글 수',
                `vote_count`        int unsigned    NOT NULL DEFAULT 0      COMMENT '추천 수',
                `scrap_count`       int unsigned    NOT NULL DEFAULT 0      COMMENT '스크랩 수',
                `timestamp_insert`  int unsigned    NOT NULL                COMMENT '작성 time()',
                `timestamp_update`  int unsigned    DEFAULT NULL            COMMENT '수정 time()',
                `client_ip_insert`  varchar(64)     NOT NULL DEFAULT ''     COMMENT '작성 IP',
                `client_ip_update`  varchar(64)     DEFAULT NULL            COMMENT '수정 IP',
                `html_used`         tinyint unsigned NOT NULL DEFAULT 0    COMMENT 'HTML 사용 (0:미사용, 1:사용)',
                `is_notice`         tinyint unsigned NOT NULL DEFAULT 0    COMMENT '공지 여부 (1:공지, 0:일반)',
                `is_secret`         tinyint unsigned NOT NULL DEFAULT 0    COMMENT '비밀글 여부',
                `is_deleted`        tinyint unsigned NOT NULL DEFAULT 0    COMMENT '삭제 여부 (1:삭제)',
                `agent_insert`      char(1)         NOT NULL DEFAULT 'P'   COMMENT '등록 agent (M:mobile, P:pc)',
                `agent_last_update` char(1)         DEFAULT NULL           COMMENT '수정 agent',
                PRIMARY KEY (`idx`),
                KEY `idx_bbs_article__bbs_idx` (`bbs_idx`),
                KEY `idx_bbs_article__user_idx` (`user_idx`),
                KEY `idx_bbs_article__timestamp_insert` (`timestamp_insert`),
                KEY `idx_bbs_article__is_deleted` (`is_deleted`),
                KEY `idx_bbs_article__is_notice` (`is_notice`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 기본정보'
        ");

        // ── 게시물 기본정보 히스토리 ─────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_article_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '부모 idx',
                `category_idx`  int unsigned    DEFAULT NULL            COMMENT '카테고리 idx',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `title`         varchar(255)    NOT NULL                COMMENT '제목',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                `is_notice`     tinyint unsigned NOT NULL DEFAULT 0     COMMENT '공지 여부',
                `is_secret`     tinyint unsigned NOT NULL DEFAULT 0     COMMENT '비밀글 여부',
                `is_deleted`    tinyint unsigned NOT NULL DEFAULT 0     COMMENT '삭제 여부',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs_article__idx__vs__bbs_article_revision__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 기본정보 히스토리'
        ");

        // ── 게시물 상세내용 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_contents` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `contents`      text            NOT NULL                COMMENT '내용',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs_article__idx__vs__bbs_contents__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 상세내용'
        ");

        // ── 게시물 상세내용 히스토리 ─────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_contents_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `contents_idx`  int unsigned    NOT NULL                COMMENT '부모 idx',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `contents`      text            NOT NULL                COMMENT '내용',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 상세내용 히스토리'
        ");

        // ── 게시물 댓글 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_comment` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`           int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`       int unsigned    NOT NULL                COMMENT '게시물 idx',
                `user_idx`          int unsigned    NOT NULL                COMMENT '회원 idx',
                `exec_user_idx`     int unsigned    NOT NULL                COMMENT '마지막 수정 회원 idx',
                `comment`           text            NOT NULL                COMMENT '댓글 내용',
                `vote_count`        int unsigned    NOT NULL DEFAULT 0      COMMENT '추천 수',
                `timestamp_insert`  int unsigned    NOT NULL                COMMENT '작성 time()',
                `timestamp_update`  int unsigned    DEFAULT NULL            COMMENT '수정 time()',
                `client_ip_insert`  varchar(64)     NOT NULL DEFAULT ''     COMMENT '작성 IP',
                `client_ip_update`  varchar(64)     DEFAULT NULL            COMMENT '수정 IP',
                `is_deleted`        tinyint(1)      NOT NULL DEFAULT 0      COMMENT '삭제 여부',
                `agent_insert`      char(1)         NOT NULL DEFAULT 'P'    COMMENT '등록 agent',
                `agent_last_update` char(1)         DEFAULT NULL            COMMENT '수정 agent',
                PRIMARY KEY (`idx`),
                KEY `idx_bbs_comment__article_idx` (`article_idx`),
                KEY `idx_bbs_comment__timestamp_insert` (`timestamp_insert`),
                KEY `idx_bbs_comment__is_deleted` (`is_deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 댓글'
        ");

        // ── 게시물 댓글 히스토리 ─────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_comment_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `comment_idx`   int unsigned    NOT NULL                COMMENT '부모 idx',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `comment`       text            NOT NULL                COMMENT '댓글 내용',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                `is_deleted`    tinyint unsigned NOT NULL DEFAULT 0     COMMENT '삭제 여부',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 댓글 히스토리'
        ");

        // ── 게시물 첨부파일 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_file` (
                `idx`                   int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`               int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`           int unsigned    NOT NULL                COMMENT '게시물 idx',
                `user_idx`              int unsigned    NOT NULL                COMMENT '회원 idx',
                `is_wysiwyg`            tinyint(1)      NOT NULL DEFAULT 0      COMMENT '위지윅 첨부 여부',
                `original_filename`     varchar(255)    NOT NULL                COMMENT '원본 파일명',
                `conversion_filename`   varchar(255)    NOT NULL                COMMENT '저장 파일명',
                `mime`                  varchar(255)    NOT NULL                COMMENT '파일 MIME',
                `capacity`              int unsigned    NOT NULL DEFAULT 0      COMMENT '파일 크기(byte)',
                `sequence`              int unsigned    DEFAULT NULL            COMMENT '순서',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs_article__idx__vs__bbs_file__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 첨부파일'
        ");

        // ── 파일 임시 저장 ───────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_file_temporary` (
                `idx`                   int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `user_idx`              int unsigned    NOT NULL                COMMENT '회원 idx',
                `conversion_filename`   varchar(255)    NOT NULL                COMMENT '저장 파일명',
                `timestamp`             int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_bbs_file_temporary__timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='파일 임시 저장'
        ");

        // ── 게시물 조회수 ────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_hit` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `hit`           int unsigned    NOT NULL DEFAULT 0      COMMENT '조회수',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `uq_bbs_hit__bbs_idx__article_idx` (`bbs_idx`, `article_idx`),
                KEY `fk_bbs_article__idx__vs__bbs_hit__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 조회수'
        ");

        // ── 게시물 태그 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_tag` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `tag`           varchar(64)     NOT NULL                COMMENT '태그',
                `sequence`      int unsigned    DEFAULT NULL            COMMENT '순서',
                PRIMARY KEY (`idx`),
                KEY `idx_bbs_tag__tag` (`tag`),
                KEY `fk_bbs_article__idx__vs__bbs_tag__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 태그'
        ");

        // ── 게시물 참조링크 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_url` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`       int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`   int unsigned    NOT NULL                COMMENT '게시물 idx',
                `url`           varchar(255)    NOT NULL                COMMENT 'URL',
                `sequence`      int unsigned    DEFAULT NULL            COMMENT '순서',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs_article__idx__vs__bbs_url__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 참조링크'
        ");

        // ── 게시물 추천 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_bbs_vote` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `bbs_idx`           int unsigned    NOT NULL                COMMENT '게시판 idx',
                `article_idx`       int unsigned    DEFAULT NULL            COMMENT '연관 게시물 idx',
                `comment_idx`       int unsigned    DEFAULT NULL            COMMENT '연관 댓글 idx',
                `user_idx_sender`   int unsigned    NOT NULL                COMMENT '추천 실행 회원 idx',
                PRIMARY KEY (`idx`),
                KEY `fk_bbs_article__idx__vs__bbs_vote__article_idx` (`article_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='게시물 추천'
        ");

        // ── 사이트 환경설정 ──────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_setting` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `parameter`     varchar(128)    NOT NULL                COMMENT '파라미터',
                `value`         varchar(1000)   NOT NULL DEFAULT ''     COMMENT '값',
                `default_bbs`   tinyint unsigned NOT NULL DEFAULT 0     COMMENT '게시판 기본설정 여부',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '마지막 수정 회원 idx',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '마지막 수정 IP',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `parameter_UNIQUE` (`parameter`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='환경설정'
        ");

        // ── 사이트 환경설정 히스토리 ─────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_setting_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `setting_idx`   int unsigned    NOT NULL                COMMENT '부모 idx',
                `parameter`     varchar(128)    NOT NULL                COMMENT '파라미터',
                `value`         varchar(1000)   NOT NULL DEFAULT ''     COMMENT '값',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='환경설정 히스토리'
        ");

        // ── 접근 IP 로그 ─────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_client_ip_access` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `client_ip`     varchar(64)     NOT NULL                COMMENT '접근 IP',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '접근 time()',
                PRIMARY KEY (`idx`),
                KEY `idx_client_ip_access__client_ip` (`client_ip`),
                KEY `idx_client_ip_access__timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='접근 IP 로그'
        ");

        // ── 차단 IP ──────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_client_ip_block` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT,
                `client_ip`     varchar(64)     NOT NULL                COMMENT '차단 IP',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '등록 time()',
                PRIMARY KEY (`idx`),
                UNIQUE KEY `client_ip_UNIQUE` (`client_ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='차단 IP'
        ");

        // ── 테마 ─────────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_themes` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `type`              char(1)         NOT NULL DEFAULT 'P'   COMMENT 'M:mobile, P:PC',
                `parent_idx`        int unsigned    DEFAULT NULL            COMMENT '복사해온 테마 idx',
                `title`             varchar(100)    NOT NULL                COMMENT '테마명',
                `folder_name`       varchar(100)    NOT NULL                COMMENT '폴더명',
                `exec_user_idx`     int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `timestamp_insert`  int unsigned    NOT NULL DEFAULT 0      COMMENT '생성 time()',
                `timestamp_update`  int unsigned    DEFAULT NULL            COMMENT '수정 time()',
                `client_ip_insert`  varchar(64)     NOT NULL DEFAULT ''     COMMENT '생성 IP',
                `client_ip_update`  varchar(64)     DEFAULT NULL            COMMENT '수정 IP',
                `is_used`           tinyint unsigned NOT NULL DEFAULT 0     COMMENT '1:사용, 0:미사용',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='테마 관리'
        ");

        // ── 회원 그룹 히스토리 ───────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_group_revision` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `group_idx`     int unsigned    NOT NULL                COMMENT '부모 idx',
                `group_name`    varchar(64)     NOT NULL                COMMENT '그룹명',
                `icon_path`     varchar(255)    DEFAULT NULL            COMMENT '아이콘 경로',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '실행 회원 idx',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                `is_used`       tinyint unsigned NOT NULL DEFAULT 1     COMMENT '1:사용, 0:미사용',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원그룹 히스토리'
        ");

        // ── 회원 차단 내역 ───────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_block_history` (
                `idx`           int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `user_idx`      int unsigned    NOT NULL                COMMENT '차단 당한 회원 idx',
                `exec_user_idx` int unsigned    NOT NULL DEFAULT 0      COMMENT '차단 실행 회원 idx',
                `comment`       text            DEFAULT NULL            COMMENT '사유',
                `timestamp`     int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `client_ip`     varchar(64)     NOT NULL DEFAULT ''     COMMENT '접근 IP',
                `is_used`       tinyint unsigned NOT NULL DEFAULT 1     COMMENT '1:사용, 0:해제',
                PRIMARY KEY (`idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 차단 내역'
        ");

        // ── 친구 관리 ────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_friend` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `user_idx`          int unsigned    NOT NULL                COMMENT '회원 idx (친구목록 주인)',
                `friend_user_idx`   int unsigned    NOT NULL                COMMENT '회원 idx (친구)',
                `timestamp`         int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                PRIMARY KEY (`idx`),
                KEY `fk_users__idx__vs__users_friend__user_idx` (`user_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='친구 관리'
        ");

        // ── 쪽지 ─────────────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_message` (
                `idx`                   int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `sender_user_idx`       int unsigned    NOT NULL                COMMENT '발신자 회원 idx',
                `receiver_user_idx`     int unsigned    NOT NULL                COMMENT '수신자 회원 idx',
                `title`                 varchar(255)    DEFAULT NULL            COMMENT '제목',
                `contents`              text            NOT NULL                COMMENT '내용',
                `timestamp_send`        int unsigned    NOT NULL DEFAULT 0      COMMENT '발신 time()',
                `timestamp_receive`     int unsigned    DEFAULT NULL            COMMENT '읽은 time()',
                `client_ip_send`        varchar(64)     NOT NULL DEFAULT ''     COMMENT '발신 IP',
                `client_ip_receive`     varchar(64)     DEFAULT NULL            COMMENT '수신 IP',
                `is_read`               tinyint(1)      NOT NULL DEFAULT 0      COMMENT '읽음 여부 (0:미읽음, 1:읽음)',
                `is_deleted_sender`     tinyint(1)      NOT NULL DEFAULT 0      COMMENT '발신자 삭제 여부',
                `is_deleted_receiver`   tinyint(1)      NOT NULL DEFAULT 0      COMMENT '수신자 삭제 여부',
                PRIMARY KEY (`idx`),
                KEY `idx_users_message__sender_user_idx` (`sender_user_idx`),
                KEY `idx_users_message__receiver_user_idx` (`receiver_user_idx`),
                KEY `idx_users_message__is_deleted_sender` (`is_deleted_sender`),
                KEY `idx_users_message__is_deleted_receiver` (`is_deleted_receiver`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='쪽지'
        ");

        // ── 회원 포인트 ──────────────────────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_point` (
                `idx`                   int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `user_idx`              int unsigned    NOT NULL                COMMENT '회원 idx',
                `point`                 int             NOT NULL DEFAULT 0      COMMENT '포인트',
                `article_idx`           int unsigned    DEFAULT NULL            COMMENT '관련 게시물 idx',
                `comment_idx`           int unsigned    DEFAULT NULL            COMMENT '관련 댓글 idx',
                `vote_idx`              int unsigned    DEFAULT NULL            COMMENT '관련 추천 idx',
                `comment`               varchar(255)    DEFAULT NULL            COMMENT '내용',
                `exec_user_idx`         int unsigned    DEFAULT NULL            COMMENT '실행 회원 idx',
                `exec_timestamp`        int unsigned    DEFAULT NULL            COMMENT '실행 time()',
                `exec_client_ip`        varchar(64)     DEFAULT NULL            COMMENT '실행 IP',
                `is_deleted`            tinyint(1)      NOT NULL DEFAULT 0      COMMENT '삭제 여부',
                `exec_user_idx_delete`  int unsigned    DEFAULT NULL            COMMENT '삭제 실행 회원 idx',
                `exec_timestamp_delete` int unsigned    DEFAULT NULL            COMMENT '삭제 time()',
                `exec_client_ip_delete` varchar(64)     DEFAULT NULL            COMMENT '삭제 IP',
                PRIMARY KEY (`idx`),
                KEY `idx_users_point__user_idx` (`user_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 포인트'
        ");

        // ── 회원 URL (스크랩/즐겨찾기) ───────────────────────────────────
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tb_users_url` (
                `idx`               int unsigned    NOT NULL AUTO_INCREMENT COMMENT '인덱스',
                `user_idx`          int unsigned    NOT NULL                COMMENT '회원 idx',
                `article_idx`       int unsigned    DEFAULT NULL            COMMENT '게시물 idx (스크랩)',
                `title`             varchar(255)    NOT NULL                COMMENT '제목',
                `url`               varchar(255)    DEFAULT NULL            COMMENT 'URL',
                `type`              tinyint unsigned NOT NULL DEFAULT 0    COMMENT '타입 (0:스크랩, 1:즐겨찾기)',
                `timestamp_insert`  int unsigned    NOT NULL DEFAULT 0      COMMENT '삽입 time()',
                `timestamp_update`  int             DEFAULT NULL            COMMENT '수정 time()',
                PRIMARY KEY (`idx`),
                KEY `fk_users__idx__vs__users_url__user_idx` (`user_idx`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 URL (스크랩/즐겨찾기)'
        ");

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    // ------------------------------------------------------------------ //

    public function down(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tables as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
