<?php

/**
 * API 응답 메시지 — 한국어
 *
 * 사용법: lang('Api.key') 또는 lang('Api.key', [param1, param2])
 * 동적 파라미터: {0}, {1}, ... 형식
 */
return [
    // ============================================================
    // 공통 — 범용 CRUD 응답
    // ============================================================

    'created'   => '등록되었습니다.',
    'deleted'   => '삭제되었습니다.',
    'not_found' => '요청한 리소스를 찾을 수 없습니다.',

    // ============================================================
    // 공통 — 리소스 없음 (failNotFound)
    // ============================================================

    'board_not_found'         => "게시판 '{0}'을 찾을 수 없습니다.",
    'article_not_found'       => '게시글을 찾을 수 없습니다.',
    'comment_not_found'       => '댓글을 찾을 수 없습니다.',
    'file_not_found'          => '파일을 찾을 수 없습니다.',
    'file_physical_not_found' => '실제 파일이 존재하지 않습니다.',
    'message_not_found'       => '쪽지를 찾을 수 없습니다.',
    'user_not_found'          => '사용자를 찾을 수 없습니다.',
    'member_not_found'        => '회원을 찾을 수 없습니다.',
    'user_not_found_by_id'    => "'{0}' 사용자를 찾을 수 없습니다.",
    'cms_page_not_found'      => '페이지를 찾을 수 없습니다.',
    'cms_banner_not_found'    => '배너를 찾을 수 없습니다.',
    'cms_popup_not_found'     => '팝업을 찾을 수 없습니다.',
    'cms_menu_not_found'      => '메뉴를 찾을 수 없습니다.',

    // ============================================================
    // 공통 — 권한 없음 (failForbidden)
    // ============================================================

    'access_forbidden'         => '접근 권한이 없습니다.',
    'article_write_forbidden'  => '글 작성 권한이 없습니다.',
    'comment_write_forbidden'  => '댓글 작성 권한이 없습니다.',
    'edit_forbidden'           => '수정 권한이 없습니다.',
    'delete_forbidden'         => '삭제 권한이 없습니다.',

    // ============================================================
    // 인증 API (AuthController)
    // ============================================================

    'auth_credentials_required'    => '아이디와 비밀번호를 입력해주세요.',
    'auth_invalid_credentials'     => '아이디 또는 비밀번호가 올바르지 않습니다.',
    'auth_not_admin'               => '관리자 계정이 아닙니다.',
    'auth_logout_success'          => '로그아웃 되었습니다.',
    'auth_refresh_token_required'  => 'refresh_token 이 필요합니다.',
    'auth_refresh_token_invalid'   => '유효하지 않거나 만료된 Refresh Token입니다.',
    'auth_refresh_token_expired'   => '유효하지 않은 Refresh Token입니다.',
    'auth_account_inactive'        => '존재하지 않거나 비활성화된 계정입니다.',

    // ============================================================
    // 게시글/댓글 API (ArticleController, CommentController)
    // ============================================================

    'article_title_required'   => '제목과 내용을 입력해주세요.',
    'article_created'          => '게시글이 작성되었습니다.',
    'article_updated'          => '게시글이 수정되었습니다.',
    'article_deleted'          => '게시글이 삭제되었습니다.',

    'comment_required'         => '댓글 내용을 입력해주세요.',
    'comment_created'          => '댓글이 작성되었습니다.',
    'comment_updated'          => '댓글이 수정되었습니다.',
    'comment_deleted'          => '댓글이 삭제되었습니다.',

    // ============================================================
    // 파일 API (FileController, WysiwygController)
    // ============================================================

    'file_params_required'      => 'bbs_idx, article_idx 가 필요합니다.',
    'file_save_failed'          => '파일 저장에 실패했습니다.',
    'file_deleted'              => '파일이 삭제되었습니다.',
    'file_max_count'            => '최대 {0}개까지 업로드 가능합니다.',
    'file_invalid_ext'          => '{0}: 허용되지 않는 확장자입니다.',
    'file_size_exceeded'        => '{0}: 파일 크기는 2MB 이하여야 합니다.',

    'wysiwyg_image_required'    => '이미지 파일을 선택해주세요.',
    'wysiwyg_invalid_mime'      => '이미지 파일(jpg, png, gif, webp)만 업로드 가능합니다.',
    'wysiwyg_size_exceeded'     => '파일 크기는 5MB 이하여야 합니다.',

    // ============================================================
    // 쪽지 API (MessageController)
    // ============================================================

    'message_required'          => '수신자와 내용을 입력해주세요.',
    // '{0}님께 쪽지를 보냈습니다.'
    'message_sent_to'           => '{0}님께 쪽지를 보냈습니다.',
    'message_deleted'           => '쪽지가 삭제되었습니다.',
    'message_self_send'         => '자신에게 쪽지를 보낼 수 없습니다.',
    'message_access_forbidden'  => '이 쪽지에 접근할 권한이 없습니다.',

    // ============================================================
    // CMS Admin API (Page/Banner/Popup/Menu)
    // ============================================================

    // Page
    'cms_page_required'           => 'slug, 제목, 내용은 필수입니다.',
    'cms_page_slug_invalid'       => 'slug는 영문 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.',
    'cms_page_slug_duplicate'     => '이미 사용 중인 slug입니다.',
    'cms_page_created'            => '페이지가 생성되었습니다.',
    'cms_page_updated'            => '페이지가 수정되었습니다.',
    'cms_page_deleted'            => '페이지가 삭제되었습니다.',

    // Banner
    'cms_banner_required'         => '위치(position)와 이미지 경로(image_path)는 필수입니다.',
    'cms_banner_created'          => '배너가 생성되었습니다.',
    'cms_banner_updated'          => '배너가 수정되었습니다.',
    'cms_banner_deleted'          => '배너가 삭제되었습니다.',

    // Popup
    'cms_popup_required'          => '제목과 내용은 필수입니다.',
    'cms_popup_created'           => '팝업이 생성되었습니다.',
    'cms_popup_updated'           => '팝업이 수정되었습니다.',
    'cms_popup_deleted'           => '팝업이 삭제되었습니다.',

    // Menu
    'cms_menu_label_required'     => '메뉴명(label)은 필수입니다.',
    'cms_menu_parent_not_found'   => '존재하지 않는 부모 메뉴입니다.',
    'cms_menu_self_parent'        => '자기 자신을 부모 메뉴로 설정할 수 없습니다.',
    'cms_menu_reorder_required'   => '순서 정보가 없습니다.',
    'cms_menu_created'            => '메뉴가 생성되었습니다.',
    'cms_menu_reordered'          => '메뉴 순서가 저장되었습니다.',
    'cms_menu_updated'            => '메뉴가 수정되었습니다.',
    'cms_menu_deleted'            => '메뉴가 삭제되었습니다.',

    // File Library (Admin/Cms/LibraryController, Cms/LibraryController)
    'library_not_found'           => '파일을 찾을 수 없습니다.',
    'library_uploaded'            => '파일이 업로드되었습니다.',
    'library_updated'             => '파일 정보가 수정되었습니다.',
    'library_deleted'             => '파일이 삭제되었습니다.',
    'library_file_required'       => '업로드할 파일을 선택해주세요.',
    'library_invalid_mime'        => '허용되지 않는 파일 형식입니다.',
    // "파일 크기는 {0}MB 이하여야 합니다."
    'library_size_exceeded'       => '파일 크기는 {0}MB 이하여야 합니다.',
    // "삭제 전 {0}건의 사용처에서 파일 참조를 제거해주세요."
    'library_delete_in_use'       => '삭제 전 {0}건의 사용처에서 파일 참조를 제거해주세요.',

    // ============================================================
    // Admin 관리 API (Board/Member/Article/Setting)
    // ============================================================

    // Board
    // "게시판 '{0}' 설정이 저장되었습니다."
    'admin_board_setting_saved'   => "게시판 '{0}' 설정이 저장되었습니다.",

    // Member
    'admin_member_nickname_dup'   => '이미 사용 중인 닉네임입니다.',
    'admin_member_email_dup'      => '이미 사용 중인 이메일입니다.',
    'admin_member_pw_min_length'  => '비밀번호는 6자 이상이어야 합니다.',
    'admin_member_updated'        => '회원 정보가 수정되었습니다.',

    // Article (admin)
    'admin_article_updated'       => '게시글이 수정되었습니다.',
    'admin_article_deleted'       => '게시글이 삭제되었습니다.',

    // Setting
    'admin_setting_saved'         => '사이트 설정이 저장되었습니다.',

    // ============================================================
    // 소셜 로그인 (SocialAuthController)
    // ============================================================

    // '{0} 인증 URL을 발급했습니다.' (Google/Naver/Kakao)
    'social_redirect_issued'      => '{0} 인증 URL을 발급했습니다.',
    'social_code_missing'         => '인증 코드가 없습니다.',
    'social_state_invalid'        => '유효하지 않은 state 값입니다.',
    // '{0} 인증 처리 중 오류가 발생했습니다.'
    'social_auth_error'           => '{0} 인증 처리 중 오류가 발생했습니다.',
    'social_user_info_error'      => '사용자 정보를 불러올 수 없습니다.',
    'social_login_success'        => '소셜 로그인 성공',
];
