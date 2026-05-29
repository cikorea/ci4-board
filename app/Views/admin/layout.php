<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? '관리자') ?> — 관리자</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f2f4f6; }
        .admin-sidebar {
            width: 220px; min-height: 100vh;
            background: #1e2433;
            position: fixed; top: 0; left: 0; bottom: 0;
            overflow-y: auto; z-index: 100;
        }
        .admin-sidebar .brand {
            padding: 1.25rem 1rem;
            font-size: 1rem; font-weight: 700; color: #fff;
            border-bottom: 1px solid #2d3548;
        }
        .admin-sidebar .nav-link {
            color: #adb5bd; padding: .55rem 1rem;
            font-size: .88rem; border-radius: .35rem;
            margin: 2px .5rem;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #fff; background: #2d3a5a;
        }
        .admin-sidebar .nav-link i { margin-right: .4rem; }
        .admin-sidebar .section-label {
            padding: .6rem 1rem .2rem;
            font-size: .7rem; color: #5a6480;
            text-transform: uppercase; letter-spacing: .07em;
        }
        .admin-content {
            margin-left: 220px;
            padding: 1.5rem;
            min-height: 100vh;
        }
        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid #e3e6ef;
            padding: .6rem 1.5rem;
            margin: -1.5rem -1.5rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card { border: 0; border-radius: .5rem; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        .card-header { background: #fff; font-weight: 600; border-bottom: 2px solid #0d6efd; border-radius: .5rem .5rem 0 0 !important; }
        .badge-group { font-size: .72rem; }
    </style>
</head>
<body>

<!-- 사이드바 -->
<div class="admin-sidebar">
    <div class="brand"><i class="bi bi-shield-lock me-2"></i>관리자 패널</div>
    <nav class="mt-2">
        <div class="section-label">게시판</div>
        <a href="/admin/boards" class="nav-link <?= str_contains(current_url(), '/admin/boards') ? 'active' : '' ?>">
            <i class="bi bi-layout-text-sidebar-reverse"></i>게시판 관리
        </a>
        <a href="/admin/posts" class="nav-link <?= str_contains(current_url(), '/admin/posts') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i>게시물 관리
        </a>
        <div class="section-label mt-2">회원</div>
        <a href="/admin/members" class="nav-link <?= str_contains(current_url(), '/admin/members') ? 'active' : '' ?>">
            <i class="bi bi-people"></i>회원 관리
        </a>
        <div class="section-label mt-2">시스템</div>
        <a href="/admin/setting" class="nav-link <?= str_contains(current_url(), '/admin/setting') ? 'active' : '' ?>">
            <i class="bi bi-gear"></i>사이트 설정
        </a>
        <div class="section-label mt-2">바로가기</div>
        <a href="/" class="nav-link"><i class="bi bi-house"></i>사이트로 이동</a>
        <a href="/auth/logout" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i>로그아웃</a>
    </nav>
</div>

<!-- 본문 -->
<div class="admin-content">
    <div class="admin-topbar">
        <h6 class="mb-0 fw-bold"><?= esc($title ?? '') ?></h6>
        <span class="text-muted" style="font-size:.84rem">
            <i class="bi bi-person-circle me-1"></i><?= esc_db(session()->get('nickname')) ?>
            <span class="badge bg-danger ms-1" style="font-size:.65rem">최고관리자</span>
        </span>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'errors' => 'danger'] as $key => $cls): ?>
        <?php $msg = session()->getFlashdata($key); if ($msg): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show py-2" role="alert">
                <?php if (is_array($msg)): ?>
                    <ul class="mb-0"><?php foreach ($msg as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
                <?php else: ?>
                    <?= esc($msg) ?>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $this->renderSection('content') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
