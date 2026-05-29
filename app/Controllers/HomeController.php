<?php

namespace App\Controllers;

use App\Models\BbsModel;
use App\Models\ArticleModel;
use CodeIgniter\Controller;

class HomeController extends Controller
{
    /** 캐시 TTL (초) */
    private const CACHE_TTL = 300; // 5분

    /**
     * 그룹별 캐시 키 반환
     * 권한에 따라 보이는 게시판이 다르므로 그룹 단위로 분리
     */
    public static function cacheKey(): string
    {
        $groupIdx = (int) (session()->get('group_idx') ?? 0);
        return 'home_boards_g' . $groupIdx;
    }

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

            // 지정 순서: 앞 5개 고정, 나머지는 기존 순서 유지
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
