<?php

namespace App\Controllers\Api\V1;

use App\Models\BbsModel;
use CodeIgniter\HTTP\ResponseInterface;

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

    public function index(): ResponseInterface
    {
        return $this->success($this->bbs->getActiveBoards());
    }

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
