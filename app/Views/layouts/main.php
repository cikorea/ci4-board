<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? '게시판') ?> — CI4 Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* ── 기본 ── */
        body { background: #f2f4f6; min-height: 100vh; }

        /* ── 네비게이션 불꽃 테마 ── */
        .ci-navbar {
            background: linear-gradient(135deg, #0f0500 0%, #1e0800 40%, #2d0e00 70%, #1a0600 100%);
            border-bottom: 2px solid #dd4814;
            box-shadow: 0 2px 20px rgba(221, 72, 20, .45);
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        /* 브랜드 로고 */
        .ci-brand {
            display: flex;
            align-items: center;
            gap: .55rem;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -.3px;
            background: linear-gradient(135deg, #ff9a3c 0%, #ff6b1a 40%, #dd4814 70%, #ff3d00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 8px rgba(221, 72, 20, .6));
            transition: filter .2s;
        }
        .ci-brand:hover {
            filter: drop-shadow(0 0 14px rgba(255, 107, 26, .9));
        }
        .ci-brand svg {
            flex-shrink: 0;
            filter: drop-shadow(0 0 6px rgba(255, 100, 20, .7));
        }

        /* 네비 링크 */
        .ci-navbar .nav-link {
            color: #c8c0b8 !important;
            font-size: .88rem;
            padding: .35rem .65rem;
            border-radius: .35rem;
            transition: color .15s, background .15s, text-shadow .15s;
        }
        .ci-navbar .nav-link:hover {
            color: #ffb067 !important;
            background: rgba(221, 72, 20, .15);
            text-shadow: 0 0 8px rgba(255, 140, 50, .6);
        }
        .ci-navbar .nav-link.active {
            color: #ff7c35 !important;
            background: rgba(221, 72, 20, .2);
        }

        /* 사용자 정보 */
        .ci-navbar .user-info {
            color: #d4ccc4;
            font-size: .86rem;
        }

        /* toggler */
        .ci-navbar .navbar-toggler {
            border-color: rgba(221, 72, 20, .5);
        }
        .ci-navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,120,50,0.85)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ── 카드 ── */
        .board-card     { border: 0; border-radius: .5rem; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        .board-card .card-header {
            background: #fff;
            border-bottom: 2px solid #dd4814;
            font-weight: 600;
            border-radius: .5rem .5rem 0 0 !important;
        }

        /* ── 기타 공통 ── */
        .post-link       { text-decoration: none; color: #1a1a1a; }
        .post-link:hover { color: #dd4814; }
        .badge-n         { font-size: .6rem; vertical-align: middle; padding: 2px 4px; }
        .meta-text       { font-size: .8rem; color: #6c757d; white-space: nowrap; }
        table tbody tr:hover { background: #fff8f5; }
        .pagination .page-link { color: #dd4814; }
        .pagination .page-item.active .page-link { background: #dd4814; border-color: #dd4814; }

        /* ── 드롭다운 ── */
        .ci-navbar .dropdown-menu {
            background: #1a0800;
            border: 1px solid rgba(221, 72, 20, .45);
            box-shadow: 0 6px 20px rgba(221, 72, 20, .3);
            min-width: 14rem;
            padding: .35rem 0;
            margin-top: .25rem !important;
        }
        .ci-navbar .dropdown-item {
            color: #c8c0b8;
            font-size: .86rem;
            padding: .42rem 1rem;
            transition: color .15s, background .15s, text-shadow .15s;
        }
        .ci-navbar .dropdown-item:hover,
        .ci-navbar .dropdown-item:focus {
            color: #ffb067;
            background: rgba(221, 72, 20, .18);
            text-shadow: 0 0 8px rgba(255, 140, 50, .5);
        }
        .ci-navbar .dropdown-item.active {
            color: #ff7c35 !important;
            background: rgba(221, 72, 20, .28);
        }
        .ci-navbar .dropdown-item .bi-box-arrow-up-right {
            opacity: .5;
            font-size: .72rem;
        }
        .ci-navbar .nav-link.dropdown-toggle:after {
            border-top-color: rgba(200, 192, 184, .7);
            vertical-align: .18em;
        }
        .ci-navbar .nav-link.dropdown-toggle.show:after {
            border-top-color: #ffb067;
        }

        /* ── 푸터 ── */
        .ci-footer {
            background: linear-gradient(135deg, #0f0500, #1e0800);
            border-top: 1px solid rgba(221, 72, 20, .3);
        }
    </style>
</head>
<body>

<nav class="ci-navbar navbar navbar-expand-lg">
    <div class="container">

        <!-- 브랜드 로고 -->
        <a class="ci-brand" href="/">
            <!-- CodeIgniter 불꽃 SVG 로고 -->
            <svg width="28" height="34" viewBox="0 0 28 34" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="flameGrad" x1="0%" y1="100%" x2="50%" y2="0%">
                        <stop offset="0%"   stop-color="#c0290a"/>
                        <stop offset="40%"  stop-color="#dd4814"/>
                        <stop offset="75%"  stop-color="#ff6b1a"/>
                        <stop offset="100%" stop-color="#ffad4a"/>
                    </linearGradient>
                    <linearGradient id="innerFlame" x1="0%" y1="100%" x2="50%" y2="0%">
                        <stop offset="0%"   stop-color="#ff6b1a"/>
                        <stop offset="100%" stop-color="#ffe066"/>
                    </linearGradient>
                </defs>
                <!-- 외부 불꽃 -->
                <path d="M14 1 C9 7 2 13 3 21 C4 27 8 32 14 33 C20 32 24 27 25 21 C26 13 19 7 14 1Z"
                      fill="url(#flameGrad)"/>
                <!-- 내부 불꽃 (밝은 심지) -->
                <path d="M14 10 C11 15 9 19 10 24 C11 27 13 29 14 29 C15 29 17 27 18 24 C19 19 17 15 14 10Z"
                      fill="url(#innerFlame)" opacity=".9"/>
                <!-- 하이라이트 -->
                <ellipse cx="11" cy="18" rx="2" ry="4" fill="rgba(255,220,120,.35)" transform="rotate(-15 11 18)"/>
            </svg>
            CodeIgniter 한국사용자포럼
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto ms-2 gap-1">
                <?php
                $bbsModel    = new \App\Models\BbsModel();
                $navBoards   = $bbsModel->getActiveBoards();
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

                // bbs_id 기준으로 인덱싱
                $boardsIndex = array_column($navBoards, null, 'bbs_id');

                // 그룹 정의: 포럼 / 개발 은 DB 게시판, 매뉴얼은 DB + 외부링크 fallback
                $navGroupDefs = [
                    '포럼' => [
                        ['id' => 'notice'],
                        ['id' => 'news'],
                        ['id' => 'free'],
                        ['id' => 'ad'],
                        ['id' => 'job'],
                        ['id' => 'cibook'],
                    ],
                    '개발' => [
                        ['id' => 'qna'],
                        ['id' => 'source'],
                        ['id' => 'etc_qna'],
                        ['id' => 'tip'],
                        ['id' => 'file'],
                    ],
                    '매뉴얼' => [
                        ['id' => 'ci4manual', 'label' => 'CodeIgniter4 매뉴얼', 'fallback' => 'https://codeigniter.com/user_guide/'],
                        ['id' => 'ci4sample',  'label' => 'CodeIgniter4 샘플',   'fallback' => 'https://codeigniter4.github.io/userguide/'],
                        ['id' => 'ci3manual',  'label' => 'CodeIgniter3 매뉴얼', 'fallback' => 'https://codeigniter.com/userguide3/'],
                    ],
                    '운영자' => [
                        ['id' => 'su'],   // 운영자 게시판 — 최고관리자(1)만
                        ['id' => 'ci'],   // 포럼 개발자  — 최고관리자(1) + 개발자(3)
                    ],
                ];

                foreach ($navGroupDefs as $groupLabel => $items):
                    // 이 그룹에서 실제로 표시할 항목 수집
                    $visibleItems = [];
                    foreach ($items as $item) {
                        $inDB      = isset($boardsIndex[$item['id']]);
                        $hasLabel  = isset($item['label']);   // 매뉴얼 고정 항목
                        $hasFallback = isset($item['fallback']);

                        if ($inDB) {
                            $board = $boardsIndex[$item['id']];
                            $visibleItems[] = [
                                'label'    => $board['bbs_name'] ?? $board['bbs_id'],
                                'href'     => '/board/' . $board['bbs_id'],
                                'external' => false,
                            ];
                        } elseif ($hasLabel && $hasFallback) {
                            // 매뉴얼처럼 DB에 없어도 항상 표시
                            $visibleItems[] = [
                                'label'    => $item['label'],
                                'href'     => $item['fallback'],
                                'external' => true,
                            ];
                        }
                        // DB에 없고 fallback도 없으면 표시 안 함
                    }

                    if (empty($visibleItems)) continue;

                    // 현재 경로가 이 그룹 내 어떤 게시판에 속하는지 확인
                    $groupActive = false;
                    foreach ($visibleItems as $vi) {
                        if (!$vi['external'] && str_starts_with($currentPath, $vi['href'])) {
                            $groupActive = true;
                            break;
                        }
                    }
                ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $groupActive ? 'active' : '' ?>"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= esc($groupLabel) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($visibleItems as $vi):
                                $itemActive = (!$vi['external'] && str_starts_with($currentPath, $vi['href'])) ? 'active' : '';
                            ?>
                                <li>
                                    <a class="dropdown-item <?= $itemActive ?>"
                                       href="<?= esc($vi['href']) ?>"
                                       <?= $vi['external'] ? 'target="_blank" rel="noopener"' : '' ?>>
                                        <?= esc_db($vi['label']) ?>
                                        <?php if ($vi['external']): ?>
                                            <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <?php if (session()->get('logged_in')):
                    $groupName  = session()->get('group_name') ?? '';
                    $groupColor = match($groupName) {
                        '최고관리자' => 'danger',
                        '개발자'     => 'warning text-dark',
                        default      => 'secondary',
                    };
                    $msgModel    = new \App\Models\MessageModel();
                    $unreadCount = $msgModel->getUnreadCount((int) session()->get('user_idx'));
                ?>
                    <li class="nav-item d-flex align-items-center">
                        <span class="user-info px-2">
                            <i class="bi bi-person-circle me-1"></i><?= esc_db(session()->get('nickname')) ?>
                            <span class="badge bg-<?= $groupColor ?> ms-1" style="font-size:.62rem"><?= esc($groupName) ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="/message">
                            <i class="bi bi-envelope me-1"></i>쪽지
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                      style="font-size:.58rem"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if ($groupName === '최고관리자'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/boards" style="color:#ffb067 !important">
                                <i class="bi bi-shield-lock me-1"></i>관리자
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/auth/profile">정보수정</a></li>
                    <li class="nav-item"><a class="nav-link" href="/auth/logout">로그아웃</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/auth/login">로그인</a></li>
                    <li class="nav-item"><a class="nav-link" href="/auth/register">회원가입</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php foreach (['success' => 'success', 'error' => 'danger', 'errors' => 'danger'] as $key => $cls): ?>
        <?php $msg = session()->getFlashdata($key); if ($msg): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show py-2" role="alert">
                <?php if (is_array($msg)): ?>
                    <ul class="mb-0">
                        <?php foreach ($msg as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <?= esc($msg) ?>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $this->renderSection('content') ?>
</main>

<footer class="ci-footer py-3 text-center text-secondary" style="font-size:.82rem">
    &copy; <?= date('Y') ?> CI4 Board &mdash; Powered by
    <span style="color:#dd4814; font-weight:600">CodeIgniter 4</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
