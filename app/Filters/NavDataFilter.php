<?php

namespace App\Filters;

use App\Models\BbsModel;
use App\Models\MessageModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @apiDefine NavData
 * @apiSuccess {Array}  navBoards    접근 가능한 게시판 목록 (뷰 공유 변수)
 * @apiSuccess {Number} unreadCount  로그인 사용자의 안 읽은 쪽지 수 (뷰 공유 변수)
 */
class NavDataFilter implements FilterInterface
{
    /**
     * @api {filter} before NavData 공유 변수 주입
     * @apiGroup Filter
     * @apiName NavDataFilter
     * @apiDescription 모든 웹 요청 전에 실행되어 네비게이션 게시판 목록과 안 읽은
     *   쪽지 수를 뷰 공유 변수로 설정한다. 뷰 파일에서 직접 모델을 호출하지 않아도 된다.
     */
    public function before(RequestInterface $request, $arguments = null): void
    {
        $bbsModel  = new BbsModel();
        $navBoards = $bbsModel->getActiveBoards();

        $unreadCount = 0;
        if (session()->get('logged_in')) {
            $msgModel    = new MessageModel();
            $unreadCount = $msgModel->getUnreadCount((int) session()->get('user_idx'));
        }

        view()->setData([
            'navBoards'   => $navBoards,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
