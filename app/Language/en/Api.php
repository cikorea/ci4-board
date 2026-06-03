<?php

/**
 * API response messages — English
 */
return [
    // Common — Generic CRUD
    'created'   => 'Created successfully.',
    'deleted'   => 'Deleted successfully.',
    'not_found' => 'The requested resource was not found.',

    // Common — Not Found
    'board_not_found'         => "Board '{0}' not found.",
    'article_not_found'       => 'Article not found.',
    'comment_not_found'       => 'Comment not found.',
    'file_not_found'          => 'File not found.',
    'file_physical_not_found' => 'The physical file does not exist.',
    'message_not_found'       => 'Message not found.',
    'user_not_found'          => 'User not found.',
    'member_not_found'        => 'Member not found.',
    'user_not_found_by_id'    => "User '{0}' not found.",
    'cms_page_not_found'      => 'Page not found.',
    'cms_banner_not_found'    => 'Banner not found.',
    'cms_popup_not_found'     => 'Popup not found.',
    'cms_menu_not_found'      => 'Menu not found.',

    // Common — Forbidden
    'access_forbidden'         => 'Access denied.',
    'article_write_forbidden'  => 'You do not have permission to write articles.',
    'comment_write_forbidden'  => 'You do not have permission to write comments.',
    'edit_forbidden'           => 'You do not have permission to edit.',
    'delete_forbidden'         => 'You do not have permission to delete.',

    // Auth
    'auth_credentials_required'    => 'Please enter your ID and password.',
    'auth_invalid_credentials'     => 'Invalid ID or password.',
    'auth_not_admin'               => 'This account does not have admin privileges.',
    'auth_logout_success'          => 'Logged out successfully.',
    'auth_refresh_token_required'  => 'refresh_token is required.',
    'auth_refresh_token_invalid'   => 'Invalid or expired refresh token.',
    'auth_refresh_token_expired'   => 'Invalid refresh token.',
    'auth_account_inactive'        => 'Account not found or deactivated.',

    // Article / Comment
    'article_title_required'   => 'Title and content are required.',
    'article_created'          => 'Article created successfully.',
    'article_updated'          => 'Article updated successfully.',
    'article_deleted'          => 'Article deleted successfully.',
    'comment_required'         => 'Comment content is required.',
    'comment_created'          => 'Comment created successfully.',
    'comment_updated'          => 'Comment updated successfully.',
    'comment_deleted'          => 'Comment deleted successfully.',

    // File
    'file_params_required'      => 'bbs_idx and article_idx are required.',
    'file_save_failed'          => 'Failed to save the file.',
    'file_deleted'              => 'File deleted successfully.',
    'file_max_count'            => 'You can upload up to {0} files.',
    'file_invalid_ext'          => '{0}: File extension not allowed.',
    'file_size_exceeded'        => '{0}: File size must be 2MB or less.',
    'wysiwyg_image_required'    => 'Please select an image file.',
    'wysiwyg_invalid_mime'      => 'Only image files (jpg, png, gif, webp) are allowed.',
    'wysiwyg_size_exceeded'     => 'File size must be 5MB or less.',

    // Message
    'message_required'          => 'Recipient and content are required.',
    'message_sent_to'           => 'Message sent to {0}.',
    'message_deleted'           => 'Message deleted successfully.',
    'message_self_send'         => 'You cannot send a message to yourself.',
    'message_access_forbidden'  => 'You do not have permission to access this message.',

    // CMS — Page
    'cms_page_required'           => 'Slug, title, and content are required.',
    'cms_page_slug_invalid'       => 'Slug may only contain lowercase letters, numbers, and hyphens.',
    'cms_page_slug_duplicate'     => 'This slug is already in use.',
    'cms_page_created'            => 'Page created successfully.',
    'cms_page_updated'            => 'Page updated successfully.',
    'cms_page_deleted'            => 'Page deleted successfully.',

    // CMS — Banner
    'cms_banner_required'         => 'Position and image path are required.',
    'cms_banner_created'          => 'Banner created successfully.',
    'cms_banner_updated'          => 'Banner updated successfully.',
    'cms_banner_deleted'          => 'Banner deleted successfully.',

    // CMS — Popup
    'cms_popup_required'          => 'Title and content are required.',
    'cms_popup_created'           => 'Popup created successfully.',
    'cms_popup_updated'           => 'Popup updated successfully.',
    'cms_popup_deleted'           => 'Popup deleted successfully.',

    // CMS — Menu
    'cms_menu_label_required'     => 'Menu name (label) is required.',
    'cms_menu_parent_not_found'   => 'Parent menu does not exist.',
    'cms_menu_self_parent'        => 'A menu cannot be its own parent.',
    'cms_menu_reorder_required'   => 'Order information is required.',
    'cms_menu_created'            => 'Menu created successfully.',
    'cms_menu_reordered'          => 'Menu order saved.',
    'cms_menu_updated'            => 'Menu updated successfully.',
    'cms_menu_deleted'            => 'Menu deleted successfully.',

    // Admin
    'admin_board_setting_saved'   => "Board '{0}' settings saved.",
    'admin_member_nickname_dup'   => 'This nickname is already in use.',
    'admin_member_email_dup'      => 'This email is already in use.',
    'admin_member_pw_min_length'  => 'Password must be at least 6 characters.',
    'admin_member_updated'        => 'Member information updated.',
    'admin_article_updated'       => 'Article updated successfully.',
    'admin_article_deleted'       => 'Article deleted successfully.',
    'admin_setting_saved'         => 'Site settings saved.',

    // Social Login
    'social_redirect_issued'      => '{0} authorization URL issued.',
    'social_code_missing'         => 'Authorization code is missing.',
    'social_state_invalid'        => 'Invalid state parameter.',
    'social_auth_error'           => 'An error occurred during {0} authentication.',
    'social_user_info_error'      => 'Failed to retrieve user information.',
    'social_login_success'        => 'Social login successful.',
];
