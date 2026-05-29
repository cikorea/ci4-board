# ************************************************************
# Sequel Ace SQL dump
# Version 20100
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: localhost (MySQL 9.7.0)
# Database: codeigniter
# Generation Time: 2026-05-29 07:59:13 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table tb_bbs
# ------------------------------------------------------------

CREATE TABLE `tb_bbs` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_id` varchar(64) NOT NULL COMMENT '게시판 고유 ID',
  `exec_user_idx` int unsigned NOT NULL COMMENT '삽입 회원 idx',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  PRIMARY KEY (`idx`),
  UNIQUE KEY `bbs_id_UNIQUE` (`bbs_id`),
  KEY `fk_users__idx__vs__bbs__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시판 기본정보 (삽입만 가능)';



# Dump of table tb_bbs_article
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_article` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `category_idx` int unsigned DEFAULT NULL COMMENT '카테고리 idx',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `title` varchar(255) NOT NULL COMMENT '제목',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '코멘트 갯수',
  `vote_count` int unsigned NOT NULL DEFAULT '0' COMMENT '추천 받은 갯수',
  `scrap_count` int unsigned NOT NULL DEFAULT '0' COMMENT '스크랩 해간 갯수',
  `timestamp_insert` int unsigned NOT NULL COMMENT '삽입 time()',
  `timestamp_update` int unsigned DEFAULT NULL COMMENT '마지막 수정 time()',
  `client_ip_insert` varchar(64) NOT NULL COMMENT '삽입 접근 IP',
  `client_ip_update` varchar(64) DEFAULT NULL COMMENT '마지막 수정 접근 IP',
  `html_used` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'HTML 사용여부 (0:미사용,1:사용)',
  `is_notice` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '공지사항 여부 (1:공지사항,0:일반)',
  `is_secret` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '비밀글 여부 (1:비밀글,0:일반)',
  `is_deleted` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '삭제 여부 (1:삭제,0:일반)',
  `agent_insert` char(1) NOT NULL DEFAULT 'M' COMMENT '등록 agent (M:mobile,P:pc)',
  `agent_last_update` char(1) DEFAULT NULL COMMENT '마지막수정 agent (M:mobile,P:pc)',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_article__title` (`title`),
  KEY `idx_bbs_article__timestamp_insert` (`timestamp_insert`),
  KEY `idx_bbs_article__is_deleted` (`is_deleted`),
  KEY `idx_bbs_article__is_notice` (`is_notice`),
  KEY `idx_bbs_article__is_secret` (`is_secret`),
  KEY `idx_bbs_article__agent_insert` (`agent_insert`),
  KEY `idx_bbs_article__agent_last_update` (`agent_last_update`),
  KEY `fk_bbs_category__idx__vs__bbs_article__category_idx` (`category_idx`),
  KEY `fk_users__idx__vs__bbs_article__user_idx` (`user_idx`),
  KEY `fk_bbs__idx__vs__bbs_article__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_article__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 기본정보';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_bbs_article_revision` AFTER INSERT ON `tb_bbs_article` FOR EACH ROW BEGIN
    INSERT INTO tb_bbs_article_revision (bbs_idx, article_idx, category_idx, exec_user_idx, title, timestamp, client_ip, is_notice, is_secret, is_deleted)
    VALUES (NEW.bbs_idx, NEW.idx, NEW.category_idx, NEW.exec_user_idx, NEW.title, UNIX_TIMESTAMP(NOW()), NEW.client_ip_insert, NEW.is_notice, NEW.is_secret, NEW.is_deleted);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_bbs_article_revision` AFTER UPDATE ON `tb_bbs_article` FOR EACH ROW BEGIN
    IF OLD.bbs_idx != NEW.bbs_idx OR OLD.category_idx != NEW.category_idx OR OLD.title != NEW.title OR OLD.is_notice != NEW.is_notice OR OLD.is_secret != NEW.is_secret OR OLD.is_deleted != NEW.is_deleted THEN
        INSERT INTO tb_bbs_article_revision (bbs_idx, article_idx, category_idx, exec_user_idx, title, timestamp, client_ip, is_notice, is_secret, is_deleted)
        VALUES (NEW.bbs_idx, OLD.idx, NEW.category_idx, NEW.exec_user_idx, NEW.title, UNIX_TIMESTAMP(NOW()), NEW.client_ip_update, NEW.is_notice, NEW.is_secret, NEW.is_deleted);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_bbs_article_revision
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_article_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `category_idx` int unsigned DEFAULT NULL COMMENT '카테고리 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `title` varchar(255) NOT NULL COMMENT '제목',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  `is_notice` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '공지사항 여부 (1:공지사항,0:일반)',
  `is_secret` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '비밀글 여부 (1:비밀글,0:일반)',
  `is_deleted` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '삭제 여부 (1:삭제,0:일반)',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_article_revision__is_deleted` (`is_deleted`),
  KEY `fk_bbs_category__idx__vs__bbs_article_revision__category_idx` (`category_idx`),
  KEY `fk_users__idx__vs__bbs_article_revision__exec_user_idx` (`exec_user_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_article_revision__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_article_revision__bbs_idx` (`bbs_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 기본정보 히스토리';



# Dump of table tb_bbs_category
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_category` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `category_name` varchar(64) NOT NULL COMMENT '카테고리명',
  `sequence` int unsigned DEFAULT NULL COMMENT '순서',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `client_ip` varchar(64) NOT NULL COMMENT '마지막 수정 접근 IP',
  `is_used` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_category__is_used` (`is_used`),
  KEY `idx_bbs_category__sequence` (`sequence`),
  KEY `fk_users__idx__vs__bbs_category__exec_user_idx` (`exec_user_idx`),
  KEY `fk_bbs__idx__vs__bbs_category__bbs_idx` (`bbs_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시판 카테고리';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_bbs_category_revision` AFTER INSERT ON `tb_bbs_category` FOR EACH ROW BEGIN
    INSERT INTO tb_bbs_category_revision (bbs_idx, category_idx, category_name, sequence, exec_user_idx, timestamp, client_ip, is_used)
    VALUES (NEW.bbs_idx, NEW.idx, NEW.category_name, NEW.sequence, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip, NEW.is_used);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_bbs_category_revision` AFTER UPDATE ON `tb_bbs_category` FOR EACH ROW BEGIN
    IF OLD.category_name != NEW.category_name OR OLD.is_used != NEW.is_used OR OLD.sequence != NEW.sequence THEN
        INSERT INTO tb_bbs_category_revision (bbs_idx, category_idx, category_name, sequence, exec_user_idx, timestamp, client_ip, is_used)
        VALUES (OLD.bbs_idx, OLD.idx, NEW.category_name, NEW.sequence, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip, NEW.is_used);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_bbs_category_revision
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_category_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `category_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `category_name` varchar(64) NOT NULL COMMENT '카테고리명',
  `sequence` int unsigned DEFAULT NULL COMMENT '순서',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  `is_used` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_category_revision__is_used` (`is_used`),
  KEY `fk_bbs__idx__vs__bbs_category_revision__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_category__idx__vs__bbs_category_revision__category_idx` (`category_idx`),
  KEY `fk_users__idx__vs__bbs_category_revision__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시판 카테고리 히스토리';



# Dump of table tb_bbs_comment
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_comment` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `comment` text NOT NULL COMMENT '코멘트',
  `vote_count` int unsigned NOT NULL DEFAULT '0' COMMENT '추천 받은 갯수',
  `timestamp_insert` int unsigned NOT NULL COMMENT '삽입 time()',
  `timestamp_update` int unsigned DEFAULT NULL COMMENT '마지막 수정 time()',
  `client_ip_insert` varchar(64) NOT NULL COMMENT '삽입 접근 IP',
  `client_ip_update` varchar(64) DEFAULT NULL COMMENT '마지막 수정 접근 IP',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '삭제 여부 (1:삭제,0:일반)',
  `agent_insert` char(1) NOT NULL DEFAULT 'M' COMMENT '등록 agent (M:mobile,P:pc)',
  `agent_last_update` char(1) DEFAULT NULL COMMENT '마지막수정 agent (M:mobile,P:pc)',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_comment__timestamp_insert` (`timestamp_insert`),
  KEY `idx_bbs_comment__is_deleted` (`is_deleted`),
  KEY `fk_bbs_article__idx__vs__bbs_comment__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_comment__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_comment__user_idx` (`user_idx`),
  KEY `fk_users__idx__vs__bbs_comment__exec_user_idx` (`exec_user_idx`),
  KEY `idx_bbs_comment__agent_insert` (`agent_insert`),
  KEY `idx_bbs_comment__agent_last_update` (`agent_last_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 코멘트';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_bbs_comment_revision` AFTER INSERT ON `tb_bbs_comment` FOR EACH ROW BEGIN
    INSERT INTO tb_bbs_comment_revision (bbs_idx, article_idx, comment_idx, exec_user_idx, comment, timestamp, client_ip, is_deleted)
    VALUES (NEW.bbs_idx, NEW.article_idx, NEW.idx, NEW.exec_user_idx, NEW.comment, UNIX_TIMESTAMP(NOW()), NEW.client_ip_insert, NEW.is_deleted);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_bbs_comment_revision` AFTER UPDATE ON `tb_bbs_comment` FOR EACH ROW BEGIN
    IF OLD.bbs_idx != NEW.bbs_idx OR OLD.comment != NEW.comment OR OLD.is_deleted != NEW.is_deleted THEN
        INSERT INTO tb_bbs_comment_revision (bbs_idx, article_idx, comment_idx, exec_user_idx, comment, timestamp, client_ip, is_deleted)
        VALUES (NEW.bbs_idx, OLD.article_idx, OLD.idx, NEW.exec_user_idx, NEW.comment, UNIX_TIMESTAMP(NOW()), NEW.client_ip_update, NEW.is_deleted);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_bbs_comment_revision
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_comment_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `comment_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `comment` text NOT NULL COMMENT '코멘트',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  `is_deleted` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '삭제 여부 (1:삭제,0:일반)',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_comment_revision__is_deleted` (`is_deleted`),
  KEY `fk_bbs_comment__idx__vs__bbs_comment_revision__comment_idx` (`comment_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_comment_revision__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_comment_revision__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_comment_revision__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 코멘트 히스토리';



# Dump of table tb_bbs_contents
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_contents` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `contents` text NOT NULL COMMENT '내용',
  `client_ip` varchar(64) NOT NULL COMMENT '마지막 수정 접근 IP',
  PRIMARY KEY (`idx`),
  KEY `fk_bbs_article__idx__vs__bbs_contents__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_contents__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_contents__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 상세내용';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_bbs_contents_revision` AFTER INSERT ON `tb_bbs_contents` FOR EACH ROW BEGIN
    INSERT INTO tb_bbs_contents_revision (bbs_idx, article_idx, contents_idx, exec_user_idx, contents, timestamp, client_ip)
    VALUES (NEW.bbs_idx, NEW.article_idx, NEW.idx, NEW.exec_user_idx, NEW.contents, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_bbs_contents_revision` AFTER UPDATE ON `tb_bbs_contents` FOR EACH ROW BEGIN
    IF OLD.bbs_idx != NEW.bbs_idx OR OLD.contents != NEW.contents THEN
        INSERT INTO tb_bbs_contents_revision (bbs_idx, article_idx, contents_idx, exec_user_idx, contents, timestamp, client_ip)
        VALUES (NEW.bbs_idx, OLD.article_idx, OLD.idx, NEW.exec_user_idx, NEW.contents, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_bbs_contents_revision
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_contents_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `contents_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `contents` text NOT NULL COMMENT '내용',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  PRIMARY KEY (`idx`),
  KEY `fk_bbs_contents__idx__vs__bbs_contents_revision__contents_idx` (`contents_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_contents_revision__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_contents_revision__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_contents_revision__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 상세내용 히스토리';



# Dump of table tb_bbs_file
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_file` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx (개인첨부용량 체크용)',
  `is_wysiwyg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '위지윅을 통한 첨부(0 : 첨부, 1 : 위지윅 첨부)',
  `original_filename` varchar(255) NOT NULL COMMENT '원본 파일명',
  `conversion_filename` varchar(255) NOT NULL COMMENT '변경 파일명 (중복,한글)',
  `mime` varchar(255) NOT NULL COMMENT '파일유형',
  `capacity` int unsigned NOT NULL COMMENT '파일크기(byte)',
  `sequence` int unsigned DEFAULT NULL COMMENT '순서',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_file__sequence` (`sequence`),
  KEY `fk_bbs__idx__vs__bbs_file__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_file__article_idx` (`article_idx`),
  KEY `fk_users__idx__vs__bbs_file__user_idx` (`user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 첨부파일 (삭제가능)';



# Dump of table tb_bbs_file_temporary
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_file_temporary` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx',
  `conversion_filename` varchar(255) NOT NULL COMMENT '변경 파일명 (중복,한글)',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  PRIMARY KEY (`idx`),
  KEY `fk_users__idx__vs__bbs_file_temporary__user_idx` (`user_idx`),
  KEY `idx_bbs_file_temporary__timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='파일업로드 임시 저장';



# Dump of table tb_bbs_hit
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_hit` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `hit` int unsigned NOT NULL COMMENT '조회수',
  PRIMARY KEY (`idx`),
  KEY `fk_bbs__idx__vs__bbs_hit__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_hit__article_idx` (`article_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 조회수';



# Dump of table tb_bbs_setting
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_setting` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `parameter` varchar(128) NOT NULL COMMENT '파라메터',
  `value` varchar(1000) NOT NULL COMMENT '값',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `client_ip` varchar(64) NOT NULL COMMENT '마지막 수정 접근 IP',
  PRIMARY KEY (`idx`,`bbs_idx`,`parameter`),
  KEY `fk_bbs__idx__vs__bbs_setting__bbs_idx` (`bbs_idx`),
  KEY `fk_users__idx__vs__bbs_setting__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시판 설정';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_bbs_setting_revision` AFTER INSERT ON `tb_bbs_setting` FOR EACH ROW BEGIN
    INSERT INTO tb_bbs_setting_revision (bbs_idx, setting_idx, parameter, value, exec_user_idx, timestamp, client_ip)
    VALUES (NEW.bbs_idx, NEW.idx, NEW.parameter, NEW.value, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_bbs_setting_revision` AFTER UPDATE ON `tb_bbs_setting` FOR EACH ROW BEGIN
    IF OLD.value != NEW.value THEN
        INSERT INTO tb_bbs_setting_revision (bbs_idx, setting_idx, parameter, value, exec_user_idx, timestamp, client_ip)
        VALUES (OLD.bbs_idx, OLD.idx, OLD.parameter, NEW.value, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_bbs_setting_revision
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_setting_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `setting_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `parameter` varchar(128) NOT NULL COMMENT '파라메터',
  `value` varchar(1000) NOT NULL COMMENT '값',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  PRIMARY KEY (`idx`),
  KEY `fk_users__idx__vs__bbs_setting_revision__exec_user_idx` (`exec_user_idx`),
  KEY `fk_bbs__idx__vs__bbs_setting_revision__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_setting__idx__vs__bbs_setting_revision__setting_idx` (`setting_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시판 설정 히스토리';



# Dump of table tb_bbs_tag
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_tag` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `tag` varchar(64) NOT NULL COMMENT '태그',
  `sequence` int unsigned DEFAULT NULL COMMENT '순서',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_tag__tag` (`tag`),
  KEY `idx_bbs_tag__sequence` (`sequence`),
  KEY `fk_bbs_article__idx__vs__bbs_tag__article_idx` (`article_idx`),
  KEY `fk_bbs__idx__vs__bbs_tag__bbs_idx` (`bbs_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 태그 (삭제가능)';



# Dump of table tb_bbs_url
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_url` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned NOT NULL COMMENT '게시물 idx',
  `url` varchar(255) NOT NULL COMMENT 'URL',
  `sequence` int unsigned DEFAULT NULL COMMENT '순서',
  PRIMARY KEY (`idx`),
  KEY `idx_bbs_url__sequence` (`sequence`),
  KEY `fk_bbs__idx__vs__bbs_url__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_url__article_idx` (`article_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 참조링크 (삭제가능)';



# Dump of table tb_bbs_vote
# ------------------------------------------------------------

CREATE TABLE `tb_bbs_vote` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `bbs_idx` int unsigned NOT NULL COMMENT '게시판 idx',
  `article_idx` int unsigned DEFAULT NULL COMMENT '연관 게시물 idx',
  `comment_idx` int unsigned DEFAULT NULL COMMENT '연관 코멘트 idx',
  `user_idx_sender` int unsigned NOT NULL COMMENT '추천 실행 회원 idx',
  PRIMARY KEY (`idx`),
  KEY `fk_bbs__idx__vs__bbs_vote__bbs_idx` (`bbs_idx`),
  KEY `fk_bbs_article__idx__vs__bbs_vote__article_idx` (`article_idx`),
  KEY `fk_bbs_comment__idx__vs__bbs_vote__comment_idx` (`comment_idx`),
  KEY `fk_users__idx__vs__bbs_vote__user_idx_sender` (`user_idx_sender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='게시물 추천';



# Dump of table tb_client_ip_access
# ------------------------------------------------------------

CREATE TABLE `tb_client_ip_access` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT,
  `client_ip` varchar(64) NOT NULL,
  `timestamp` int unsigned NOT NULL,
  PRIMARY KEY (`idx`),
  KEY `idx_client_ip_access__client_ip` (`client_ip`),
  KEY `idx_client_ip_access__timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='접근 IP (삭제 가능)';



# Dump of table tb_client_ip_block
# ------------------------------------------------------------

CREATE TABLE `tb_client_ip_block` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT,
  `client_ip` varchar(64) NOT NULL,
  `timestamp` int unsigned NOT NULL,
  PRIMARY KEY (`idx`),
  UNIQUE KEY `client_ip_UNIQUE` (`client_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='차단 IP (삭제 가능)';



# Dump of table tb_setting
# ------------------------------------------------------------

CREATE TABLE `tb_setting` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `parameter` varchar(128) NOT NULL COMMENT '파라메터',
  `value` varchar(1000) NOT NULL COMMENT '값',
  `default_bbs` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '게시판기본설정여부 (0:일반,1:게시판기본설정)',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `client_ip` varchar(64) NOT NULL COMMENT '마지막 수정 접근 IP',
  PRIMARY KEY (`idx`),
  UNIQUE KEY `parameter_UNIQUE` (`parameter`),
  KEY `idx_setting__default_bbs` (`default_bbs`),
  KEY `fk_users__idx__vs__setting__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='환경설정';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_setting_revision` AFTER INSERT ON `tb_setting` FOR EACH ROW BEGIN
    INSERT INTO tb_setting_revision (setting_idx, parameter, value, exec_user_idx, timestamp, client_ip)
    VALUES (NEW.idx, NEW.parameter, NEW.value, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_setting_revision` AFTER UPDATE ON `tb_setting` FOR EACH ROW BEGIN
    IF OLD.value != NEW.value THEN
        INSERT INTO tb_setting_revision (setting_idx, parameter, value, exec_user_idx, timestamp, client_ip)
        VALUES (OLD.idx, OLD.parameter, NEW.value, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_setting_revision
# ------------------------------------------------------------

CREATE TABLE `tb_setting_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `setting_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `parameter` varchar(128) NOT NULL COMMENT '파라메터',
  `value` varchar(1000) NOT NULL COMMENT '값',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  PRIMARY KEY (`idx`),
  KEY `fk_users__idx__vs__setting_revision__exec_user_idx` (`exec_user_idx`),
  KEY `fk_setting__idx__vs__setting_revision__setting_idx` (`setting_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='환경설정 히스토리';



# Dump of table tb_themes
# ------------------------------------------------------------

CREATE TABLE `tb_themes` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `type` char(1) NOT NULL COMMENT 'M:mobile,P:PC',
  `parent_idx` int unsigned DEFAULT NULL COMMENT '복사해온 테마idx',
  `title` varchar(100) NOT NULL COMMENT '테마명',
  `folder_name` varchar(100) NOT NULL COMMENT '폴더명',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `timestamp_insert` int unsigned NOT NULL COMMENT '삽입 time()',
  `timestamp_update` int unsigned DEFAULT NULL COMMENT '수정 time()',
  `client_ip_insert` varchar(64) NOT NULL COMMENT '삽입 IP',
  `client_ip_update` varchar(64) DEFAULT NULL COMMENT '수정 IP',
  `is_used` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  KEY `idx_themes__is_used` (`is_used`),
  KEY `fk_users__idx__vs__themes__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='테마관리';



# Dump of table tb_users
# ------------------------------------------------------------

CREATE TABLE `tb_users` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL COMMENT '회원 ID',
  `password` varchar(64) DEFAULT NULL COMMENT '비밀번호',
  `super_secured_password` varchar(255) DEFAULT NULL COMMENT '비밀번호 (보안강화)',
  `new_password` varchar(255) DEFAULT NULL COMMENT '임시 새 비밀번호',
  `new_password_timestamp` int unsigned DEFAULT NULL COMMENT '임시 새 비밀버호 요청 time()',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '레벨 (큰 수가 상위 ~99)',
  `group_idx` int unsigned NOT NULL COMMENT '회원그룹',
  `name` varchar(64) NOT NULL COMMENT '실명',
  `nickname` varchar(64) NOT NULL COMMENT '닉네임',
  `message_receive_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '쪽지수신방법 (0:전체거부,1:전체수신,2:친구만수신)',
  `email` varchar(128) NOT NULL COMMENT '이메일',
  `timezone` varchar(4) NOT NULL COMMENT '타임존',
  `article_count` int unsigned NOT NULL DEFAULT '0' COMMENT '글 작성 수',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '코멘트 작성 수',
  `vote_send_count` int unsigned NOT NULL DEFAULT '0' COMMENT '추천한 수',
  `vote_receive_count` int unsigned NOT NULL DEFAULT '0' COMMENT '추천받은 수',
  `point` int NOT NULL DEFAULT '0' COMMENT '포인트 (마이너스 가능)',
  `avatar_used` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '아바타 사용 (0:미사용, 1:사용)',
  `memo` text COMMENT '개인메모장',
  `timestamp_insert` int unsigned NOT NULL COMMENT '삽입 time()',
  `timestamp_update` int unsigned DEFAULT NULL COMMENT '마지막수정 time()',
  `timestamp_delete` int unsigned DEFAULT NULL COMMENT '탈퇴 time()',
  `timestamp_login` int unsigned DEFAULT NULL COMMENT '마지막로그인 time()',
  `timestamp_post` int unsigned DEFAULT NULL COMMENT '마지막 글/코멘트 time()',
  `timestamp_update_password` int unsigned DEFAULT NULL COMMENT '비번변경 time()',
  `client_ip_insert` varchar(64) NOT NULL COMMENT '삽입 접근 IP',
  `client_ip_update` varchar(64) DEFAULT NULL COMMENT '마지막수정 접근 IP',
  `client_ip_delete` varchar(64) DEFAULT NULL COMMENT '탈퇴 접근 IP',
  `client_ip_login` varchar(64) DEFAULT NULL COMMENT '마지막로그인 접근 IP',
  `client_ip_post` varchar(64) DEFAULT NULL COMMENT '마지막 글/코멘트 접근 IP',
  `client_ip_update_password` varchar(64) DEFAULT NULL COMMENT '비번변경 접근 IP',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '상태 (0:탈퇴, 1:정상, 2:차단)',
  PRIMARY KEY (`idx`),
  UNIQUE KEY `user_id_UNIQUE` (`user_id`),
  UNIQUE KEY `nickname_UNIQUE` (`nickname`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `idx_users__level` (`level`),
  KEY `idx_users__name` (`name`),
  KEY `idx_users__status` (`status`),
  KEY `idx_users__timestamp_login` (`timestamp_login`),
  KEY `idx_users__article_count` (`article_count`),
  KEY `idx_users__comment_count` (`comment_count`),
  KEY `idx_users__vote_send_count` (`vote_send_count`),
  KEY `idx_users__vote_receive_count` (`vote_receive_count`),
  KEY `idx_users__point` (`point`),
  KEY `idx_users__timestamp_post` (`timestamp_post`),
  KEY `fk_users_group__idx__vs__users__group_idx` (`group_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원테이블';



# Dump of table tb_users_block_history
# ------------------------------------------------------------

CREATE TABLE `tb_users_block_history` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `user_idx` int unsigned NOT NULL COMMENT '차단당한 회원 idx',
  `exec_user_idx` int unsigned NOT NULL COMMENT '차단을 실행한 회원 idx',
  `comment` text COMMENT '코멘트',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  `is_used` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  KEY `idx_users_block_history__is_used` (`is_used`),
  KEY `fk_users__idx__vs__users_block_history__user_idx` (`user_idx`),
  KEY `fk_users__idx__vs__users_block_history__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원 차단 내역';



# Dump of table tb_users_friend
# ------------------------------------------------------------

CREATE TABLE `tb_users_friend` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx (친구목록 주인)',
  `friend_user_idx` int unsigned NOT NULL COMMENT '회원 idx (친구들)',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  PRIMARY KEY (`idx`),
  KEY `fk_users__idx__vs__users_friend__user_idx` (`user_idx`),
  KEY `fk_users__idx__vs__users_friend__friend_user_idx` (`friend_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='친구관리 (삭제가능)';



# Dump of table tb_users_group
# ------------------------------------------------------------

CREATE TABLE `tb_users_group` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `group_name` varchar(64) NOT NULL COMMENT '그룹명',
  `icon_path` varchar(255) DEFAULT NULL COMMENT '아이콘 경로',
  `exec_user_idx` int unsigned NOT NULL COMMENT '마지막 수정 회원 idx',
  `client_ip` varchar(64) NOT NULL COMMENT '마지막 수정 접근 IP',
  `is_used` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  UNIQUE KEY `group_name_UNIQUE` (`group_name`),
  KEY `idx_users_group__is_used` (`is_used`),
  KEY `fk_users__idx__vs__users_group__exec_user_idx` (`exec_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원그룹';


DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_insert_users_group_revision` AFTER INSERT ON `tb_users_group` FOR EACH ROW BEGIN
    INSERT INTO tb_users_group_revision (group_idx, group_name, icon_path, exec_user_idx, timestamp, client_ip, is_used)
    VALUES (NEW.idx, NEW.group_name, NEW.icon_path, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip, NEW.is_used);
END */;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`cikorea`@`localhost` */ /*!50003 TRIGGER `by_update_users_group_revision` AFTER UPDATE ON `tb_users_group` FOR EACH ROW BEGIN
    IF OLD.group_name != NEW.group_name OR OLD.icon_path != NEW.icon_path OR OLD.is_used != NEW.is_used THEN
        INSERT INTO tb_users_group_revision (group_idx, group_name, icon_path, exec_user_idx, timestamp, client_ip, is_used)
        VALUES (OLD.idx, NEW.group_name, NEW.icon_path, NEW.exec_user_idx, UNIX_TIMESTAMP(NOW()), NEW.client_ip, NEW.is_used);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


# Dump of table tb_users_group_revision
# ------------------------------------------------------------

CREATE TABLE `tb_users_group_revision` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `group_idx` int unsigned NOT NULL COMMENT '부모 idx',
  `group_name` varchar(64) NOT NULL COMMENT '그룹명',
  `icon_path` varchar(255) DEFAULT NULL COMMENT '아이콘 경로',
  `exec_user_idx` int unsigned NOT NULL COMMENT '실행 회원 idx',
  `timestamp` int unsigned NOT NULL COMMENT '삽입 time()',
  `client_ip` varchar(64) NOT NULL COMMENT '접근 IP',
  `is_used` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1:사용, 0:미사용',
  PRIMARY KEY (`idx`),
  KEY `idx_users_group_revision__is_used` (`is_used`),
  KEY `fk_users__idx__vs__users_group_revision__exec_user_idx` (`exec_user_idx`),
  KEY `fk_users_group__idx__vs__users_group_revision__group_idx` (`group_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원그룹 히스토리';



# Dump of table tb_users_message
# ------------------------------------------------------------

CREATE TABLE `tb_users_message` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `sender_user_idx` int unsigned NOT NULL COMMENT '회원 idx (발신자)',
  `receiver_user_idx` int unsigned NOT NULL COMMENT '회원 idx (수신자)',
  `title` varchar(255) DEFAULT NULL COMMENT '제목',
  `contents` text NOT NULL COMMENT '내용',
  `timestamp_send` int unsigned NOT NULL COMMENT '삽입 time() (발신시각)',
  `timestamp_receive` int unsigned DEFAULT NULL COMMENT '읽은 time()',
  `client_ip_send` varchar(64) NOT NULL COMMENT '접근 IP (보낸이)',
  `client_ip_receive` varchar(64) DEFAULT NULL COMMENT '접근 IP (받은이)',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '수신여부 (0:읽지않음, 1:읽음)',
  `is_deleted_sender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '발신자 삭제여부 (0:정상, 1:삭제)',
  `is_deleted_receiver` tinyint(1) NOT NULL DEFAULT '0' COMMENT '수신자 삭제여부 (0:정상, 1:삭제)',
  PRIMARY KEY (`idx`),
  KEY `idx_users_message__is_deleted_sender` (`is_deleted_sender`),
  KEY `idx_users_message__is_deleted_receiver` (`is_deleted_receiver`),
  KEY `fk_users__idx__vs__users_message__sender_user_idx` (`sender_user_idx`),
  KEY `fk_users__idx__vs__users_message__receiver_user_idx` (`receiver_user_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='메시지 (쪽지)';



# Dump of table tb_users_point
# ------------------------------------------------------------

CREATE TABLE `tb_users_point` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx',
  `point` int NOT NULL COMMENT '포인트 (마이너스 가능)',
  `article_idx` int unsigned DEFAULT NULL COMMENT '관련글 idx',
  `comment_idx` int unsigned DEFAULT NULL COMMENT '관련코멘트 idx',
  `vote_idx` int unsigned DEFAULT NULL COMMENT '추천정보  idx',
  `comment` varchar(255) DEFAULT NULL COMMENT '내용',
  `exec_user_idx` int unsigned DEFAULT NULL COMMENT '실행 회원 idx - 운영자 혹은 선물',
  `exec_timestamp` int unsigned DEFAULT NULL COMMENT '실행 time() - 운영자 혹은 선물',
  `exec_client_ip` varchar(64) DEFAULT NULL COMMENT '실행 접근 IP - 운영자 혹은 선물',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '삭제여부(1:삭제, 0:정상)',
  `exec_user_idx_delete` int unsigned DEFAULT NULL COMMENT '삭제여부 변경 마지막 회원 idx',
  `exec_timestamp_delete` int unsigned DEFAULT NULL COMMENT '삭제여부 변경 마지막 time()',
  `exec_client_ip_delete` varchar(64) DEFAULT NULL COMMENT '삭제여부 변경 마지막 접근 IP',
  PRIMARY KEY (`idx`),
  KEY `idx_users_point__is_deleted` (`is_deleted`),
  KEY `fk_users__idx__vs__users_point__user_idx` (`user_idx`),
  KEY `fk_users__idx__vs__users_point__exec_user_idx` (`exec_user_idx`),
  KEY `fk_bbs_article__idx__vs__users_point__article_idx` (`article_idx`),
  KEY `fk_bbs_comment__idx__vs__users_point__comment_idx` (`comment_idx`),
  KEY `fk_bbs_vote__idx__vs__users_point__vote_idx` (`vote_idx`),
  KEY `fk_users__idx__vs__users_point__exec_user_idx_delete` (`exec_user_idx_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원포인트';



# Dump of table tb_users_url
# ------------------------------------------------------------

CREATE TABLE `tb_users_url` (
  `idx` int unsigned NOT NULL AUTO_INCREMENT COMMENT '인덱스',
  `user_idx` int unsigned NOT NULL COMMENT '회원 idx',
  `article_idx` int unsigned DEFAULT NULL COMMENT '게시물 idx (스크랩)',
  `title` varchar(255) NOT NULL COMMENT '제목',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '타입 (0:스크랩, 1:즐겨찾기)',
  `timestamp_insert` int unsigned NOT NULL COMMENT '삽입 time()',
  `timestamp_update` int DEFAULT NULL COMMENT '수정 time()',
  PRIMARY KEY (`idx`),
  KEY `idx_users_url__type` (`type`),
  KEY `idx_users_url__title` (`title`),
  KEY `fk_users__idx__vs__users_url__user_idx` (`user_idx`),
  KEY `fk_bbs_article__idx__vs__users_url__article_idx` (`article_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='회원URL (스크랩/즐겨찾기) (삭제가능)';




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
