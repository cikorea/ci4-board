<?php

namespace App\Controllers\Api\V1;

use App\Models\BbsModel;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * 게시판 API
 *
 * GET /api/v1/boards          접근 가능한 게시판 목록
 * GET /api/v1/boards/:bbsId   게시판 정보 + 권한 맵
 */
class BoardController extends BaseApiController
{
    private BbsModel $bbs;

    public function __construct()
    {
        $this->bbs = new BbsModel();
    }

    #[OA\Get(
        path: '/api/v1/boards',
        summary: '게시판 목록',
        tags: ['Board'],
        responses: [
            new OA\Response(response: 200, description: '게시판 목록', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
        ]
    )]
    public function index(): ResponseInterface
    {
        return $this->success($this->bbs->getActiveBoards());
    }

    #[OA\Get(
        path: '/api/v1/boards/{bbsId}',
        summary: '게시판 상세',
        tags: ['Board'],
        parameters: [
            new OA\PathParameter(name: 'bbsId', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'free')),
        ],
        responses: [
            new OA\Response(response: 200, description: '게시판 정보', content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(string $bbsId): ResponseInterface
    {
        $board = $this->bbs->getByBbsId($bbsId);
        if (! $board) {
            // "게시판 '{0}'을 찾을 수 없습니다."
            return $this->failNotFound(lang('Api.board_not_found', [$bbsId]));
        }
        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return $this->failForbidden(lang('Api.access_forbidden'));
        }

        return $this->success($board);
    }
}
