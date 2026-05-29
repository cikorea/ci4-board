<?php

namespace App\Controllers;

use App\Models\BbsModel;
use App\Models\ArticleModel;
use CodeIgniter\Controller;

/**
 * @api {group} Home 홈
 * @apiGroup Home
 * @apiDescription 메인 페이지를 처리한다. 게시판별 최신글을 그룹화하여 표시한다.
 */
class HomeController extends Controller
{
    /** 홈 캐시 TTL (초) */
    private const CACHE_TTL = 300;

    /**
     * @api {function} HomeController::cacheKey 그룹별 캐시 키 반환
     * @apiGroup Home
     * @apiPrivate
     * @apiDescription 로그인 그룹에 따라 보이는 게시판이 다르므로 group_idx 단위로 캐시를 분리한다.
     *
     * @apiSuccess {String}  캐시 키 (예: home_boards_g2)
     */
    public static function cacheKey(): string
    {
        $groupIdx = (int) (session()->get('group_idx') ?? 0);
        return 'home_boards_g' . $groupIdx;
    }

    /**
     * @api {get} / 메인 페이지
     * @apiGroup Home
     * @apiName HomeIndex
     * @apiDescription 접근 가능한 게시판 목록과 각 게시판의 최신 글 10개를 캐시(5분)하여 반환한다.
     *   게시판 표시 순서는 pinnedOrder 배열 기준이며, 미포함 게시판은 DB 순서로 뒤에 추가된다.
     *
     * @apiSuccess {Array} boards  게시판 배열, 각 항목에 articles(최신글 목록) 포함
     */
    public function index(): string
    {
        $cache    = \Config\Services::cache();
        $cacheKey = self::cacheKey();

        $boards = $cache->get($cacheKey);

        if ($boards === null) {
            $bbsModel     = new BbsModel();
            $articleModel = new ArticleModel();

            $boards = $bbsModel->getActiveBoards();

            foreach ($boards as &$board) {
                $board['articles'] = $articleModel->getLatest($board['idx'], 10);
            }
            unset($board);

            $pinnedOrder = ['notice', 'news', 'qna', 'source', 'free'];

            $pinned  = [];
            $indexed = array_column($boards, null, 'bbs_id');

            foreach ($pinnedOrder as $id) {
                if (isset($indexed[$id])) {
                    $pinned[] = $indexed[$id];
                    unset($indexed[$id]);
                }
            }
            $boards = array_merge($pinned, array_values($indexed));

            $cache->save($cacheKey, $boards, self::CACHE_TTL);
        }

        return view('home/index', [
            'title'  => '메인',
            'boards' => $boards,
        ]);
    }
}
