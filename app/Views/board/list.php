<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$total   = $pager['total'];
$page    = $pager['page'];
$perPage = $pager['perPage'];
$lastPage = (int) ceil($total / $perPage) ?: 1;
$keyword = $keyword ?? '';
?>

<div class="card board-card">
    <!-- 헤더 -->
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span>
            <i class="bi bi-card-list me-1 text-primary"></i>
            <strong><?= esc_db($board['bbs_name'] ?? $board['bbs_id']) ?></strong>
            <small class="text-muted ms-2">총 <?= number_format($total) ?>개</small>
        </span>
        <?php if (user_can_in_groups($board['permissions']['write_article'] ?? [])): ?>
            <a href="/board/<?= esc($board['bbs_id']) ?>/write" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i>글쓰기
            </a>
        <?php endif; ?>
    </div>

    <!-- 검색 -->
    <div class="card-body border-bottom py-2">
        <form method="get" class="d-flex gap-2 justify-content-end">
            <input type="text" name="keyword" class="form-control form-control-sm" style="max-width:220px"
                   placeholder="제목 검색" value="<?= esc($keyword) ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($keyword): ?>
                <a href="/board/<?= esc($board['bbs_id']) ?>" class="btn btn-sm btn-outline-danger">초기화</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 목록 -->
    <div class="card-body p-0">
        <?php if (empty($articles)): ?>
            <p class="text-center text-muted py-5 mb-0">게시글이 없습니다.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:60px">번호</th>
                            <th>제목</th>
                            <th class="text-center d-none d-md-table-cell" style="width:100px">작성자</th>
                            <th class="text-center d-none d-sm-table-cell" style="width:70px">조회</th>
                            <th class="text-center d-none d-sm-table-cell" style="width:95px">날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a):
                            $isNew = ($a['timestamp_insert'] > time() - 86400);
                        ?>
                            <tr>
                                <td class="text-center text-muted" style="font-size:.85rem">
                                    <?= $a['is_notice'] ? '<span class="badge bg-primary">공지</span>' : $a['idx'] ?>
                                </td>
                                <td>
                                    <a href="/board/<?= esc($board['bbs_id']) ?>/view/<?= $a['idx'] ?>" class="post-link">
                                        <?= esc_db($a['title']) ?>
                                    </a>
                                    <?php if ($a['comment_count'] > 0): ?>
                                        <span class="text-primary ms-1" style="font-size:.82rem">[<?= $a['comment_count'] ?>]</span>
                                    <?php endif; ?>
                                    <?php if ($isNew): ?>
                                        <span class="badge bg-danger badge-n ms-1">N</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell meta-text">
                                    <?= esc_db($a['nickname'] ?? '익명') ?>
                                </td>
                                <td class="text-center d-none d-sm-table-cell meta-text">
                                    <?= number_format($a['hit_count']) ?>
                                </td>
                                <td class="text-center d-none d-sm-table-cell meta-text">
                                    <?= date('Y-m-d', $a['timestamp_insert']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($lastPage > 1): ?>
    <div class="card-footer d-flex justify-content-center py-3 bg-white border-0">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 4);
                $end   = min($lastPage, $page + 4);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $lastPage): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $lastPage ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
