<?php

namespace App\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    title: 'CI4 Board API',
    description: <<<'DESC'
CodeIgniter 4 기반 헤드리스 게시판 REST API

## 인증
로그인 후 발급된 `access_token`을 `Authorization: Bearer {token}` 헤더로 전달합니다.

## 기본 응답 형식
```json
{ "success": true,  "data": {},   "message": "" }
{ "success": false, "data": null, "message": "에러 메시지" }
```

## API 구조
| 경로 접두사 | 설명 | 인증 |
|------------|------|------|
| `/api/v1/*` | 사용자·게시판·쪽지·CMS | JWT (일부 공개) |
| `/api/admin/v1/*` | 관리자 전용 | Admin JWT (role: superadmin/manager) |
DESC
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: '개발 서버'
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT Bearer token'
)]
// 사용자 API
#[OA\Tag(name: 'Auth', description: '사용자 인증 (`/api/v1/auth/*`)')]
#[OA\Tag(name: 'Social Auth', description: '소셜 로그인 OAuth2 (`/api/v1/auth/social/*`)')]
#[OA\Tag(name: 'Board', description: '게시판 조회 (`/api/v1/boards/*`)')]
#[OA\Tag(name: 'Article', description: '게시글 CRUD (`/api/v1/boards/{id}/articles/*`)')]
#[OA\Tag(name: 'Comment', description: '댓글 CRUD (`/api/v1/boards/{id}/articles/{id}/comments/*`)')]
#[OA\Tag(name: 'File', description: '파일 업로드·다운로드 (`/api/v1/files/*`)')]
#[OA\Tag(name: 'Message', description: '쪽지 (`/api/v1/messages/*`)')]
#[OA\Tag(name: 'Config', description: '사이트 공개 설정 (`/api/v1/config`)')]
#[OA\Tag(name: 'CMS', description: 'CMS 공개 조회 (`/api/v1/cms/*`)')]
// 관리자 API
#[OA\Tag(name: 'Admin Auth', description: '관리자 인증 (`/api/admin/v1/auth/*`)')]
#[OA\Tag(name: 'Admin Board', description: '게시판 관리 (`/api/admin/v1/boards/*`)')]
#[OA\Tag(name: 'Admin Member', description: '회원 관리 (`/api/admin/v1/members/*`)')]
#[OA\Tag(name: 'Admin Article', description: '게시글 관리 (`/api/admin/v1/articles/*`)')]
#[OA\Tag(name: 'Admin Setting', description: '사이트 설정 (`/api/admin/v1/setting`)')]
#[OA\Tag(name: 'Admin Stats', description: '일별 통계 (`/api/admin/v1/stats`)')]
#[OA\Tag(name: 'Admin Log', description: '감사 로그 (`/api/admin/v1/logs`)')]
#[OA\Tag(name: 'Admin Notice', description: '관리자 공지 (`/api/admin/v1/notices`)')]
#[OA\Tag(name: 'Admin CMS', description: 'CMS 관리 (`/api/admin/v1/cms/*`)')]
class OpenApiSpec
{
}

#[OA\Schema(
    schema: 'ApiResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean'),
        new OA\Property(property: 'data', nullable: true),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class ApiResponseSchema
{
}

#[OA\Schema(
    schema: 'TokenResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
                    ]
                ),
            ]
        ),
    ]
)]
class TokenResponseSchema
{
}

#[OA\Schema(
    schema: 'AdminTokenResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                        new OA\Property(property: 'user', ref: '#/components/schemas/AdminUserSummary'),
                    ]
                ),
            ]
        ),
    ]
)]
class AdminTokenResponseSchema
{
}

#[OA\Schema(
    schema: 'UserSummary',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'string'),
        new OA\Property(property: 'nickname', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'group_idx', type: 'integer'),
        new OA\Property(property: 'group_name', type: 'string'),
    ]
)]
class UserSummarySchema
{
}

#[OA\Schema(
    schema: 'AdminUserSummary',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'string'),
        new OA\Property(property: 'nickname', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'role', type: 'string', enum: ['superadmin', 'manager'], example: 'superadmin'),
    ]
)]
class AdminUserSummarySchema
{
}

#[OA\Schema(
    schema: 'Article',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'bbs_idx', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'contents', type: 'string'),
        new OA\Property(property: 'comment_count', type: 'integer'),
        new OA\Property(property: 'hit_count', type: 'integer'),
        new OA\Property(property: 'timestamp_insert', type: 'integer'),
        new OA\Property(property: 'nickname', type: 'string'),
    ]
)]
class ArticleSchema
{
}

#[OA\Schema(
    schema: 'Comment',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'article_idx', type: 'integer'),
        new OA\Property(property: 'comment', type: 'string'),
        new OA\Property(property: 'nickname', type: 'string'),
        new OA\Property(property: 'timestamp_insert', type: 'integer'),
    ]
)]
class CommentSchema
{
}

#[OA\Schema(
    schema: 'Message',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'sender_user_idx', type: 'integer'),
        new OA\Property(property: 'receiver_user_idx', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'contents', type: 'string'),
        new OA\Property(property: 'is_read', type: 'integer'),
        new OA\Property(property: 'timestamp_insert', type: 'integer'),
    ]
)]
class MessageSchema
{
}

#[OA\Schema(
    schema: 'AdminLog',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'admin_idx', type: 'integer'),
        new OA\Property(property: 'action', type: 'string', example: 'board.update'),
        new OA\Property(property: 'target_table', type: 'string', nullable: true),
        new OA\Property(property: 'target_idx', type: 'integer', nullable: true),
        new OA\Property(property: 'before_data', type: 'string', nullable: true),
        new OA\Property(property: 'after_data', type: 'string', nullable: true),
        new OA\Property(property: 'client_ip', type: 'string'),
        new OA\Property(property: 'timestamp', type: 'integer'),
    ]
)]
class AdminLogSchema
{
}

#[OA\Schema(
    schema: 'AdminNotice',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'contents', type: 'string', nullable: true),
        new OA\Property(property: 'author_idx', type: 'integer'),
        new OA\Property(property: 'is_pinned', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'timestamp_insert', type: 'integer'),
    ]
)]
class AdminNoticeSchema
{
}

#[OA\Schema(
    schema: 'StatDaily',
    type: 'object',
    properties: [
        new OA\Property(property: 'idx', type: 'integer'),
        new OA\Property(property: 'stat_date', type: 'string', format: 'date', example: '2026-06-01'),
        new OA\Property(property: 'new_users', type: 'integer'),
        new OA\Property(property: 'new_articles', type: 'integer'),
        new OA\Property(property: 'new_comments', type: 'integer'),
        new OA\Property(property: 'total_hits', type: 'integer'),
        new OA\Property(property: 'active_users', type: 'integer'),
    ]
)]
class StatDailySchema
{
}

#[OA\Schema(
    schema: 'Pagination',
    type: 'object',
    properties: [
        new OA\Property(property: 'page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
    ]
)]
class PaginationSchema
{
}

#[OA\Response(
    response: 'NotFound',
    description: '리소스를 찾을 수 없습니다.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
)]
#[OA\Response(
    response: 'Unauthorized',
    description: '인증이 필요합니다.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
)]
#[OA\Response(
    response: 'Forbidden',
    description: '접근 권한이 없습니다.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
)]
#[OA\Response(
    response: 'ValidationError',
    description: '유효성 검사 오류입니다.',
    content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
)]
class CommonResponsesSchema
{
}
