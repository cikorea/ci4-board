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
            return $this->failNotFound("게시판 '{$bbsId}'을 찾을 수 없습니다.");
        }
        if (! user_can_in_groups($board['permissions']['view_list'] ?? [])) {
            return $this->failForbidden('접근 권한이 없습니다.');
        }

        return $this->success($board);
    }
}
