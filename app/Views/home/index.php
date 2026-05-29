<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-3">
<?php foreach ($boards as $board): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card board-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span>
                    <i class="bi bi-card-list me-1 text-primary"></i>
                    <strong style="font-size:.92rem"><?= esc_db($board['bbs_name'] ?? $board['bbs_id']) ?></strong>
                </span>
                <a href="/board/<?= esc($board['bbs_id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2"
                   style="font-size:.78rem">더보기 <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="card-body p-0">
                <?php if (empty($board['articles'])): ?>
                    <p class="text-center text-muted py-4 mb-0" style="font-size:.85rem">게시글이 없습니다.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($board['articles'] as $a):
                            $isNew = ($a['timestamp_insert'] > time() - 86400);
                        ?>
                            <li class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                <a href="/board/<?= esc($board['bbs_id']) ?>/view/<?= $a['idx'] ?>"
                                   class="post-link text-truncate me-2" style="font-size:.87rem; max-width:75%">
                                    <?php if ($a['is_notice']): ?>
                                        <span class="badge bg-primary badge-n me-1">공지</span>
                                    <?php endif; ?>
                                    <?= esc_db($a['title']) ?>
                                    <?php if (($a['comment_count'] ?? 0) > 0): ?>
                                        <span class="text-primary ms-1" style="font-size:.78rem">[<?= (int)$a['comment_count'] ?>]</span>
                                    <?php endif; ?>
                                    <?php if ($isNew): ?>
                                        <span class="badge bg-danger badge-n ms-1">N</span>
                                    <?php endif; ?>
                                </a>
                                <span class="text-muted text-truncate" style="font-size:.78rem; max-width:25%">
                                    <?= esc_db($a['nickname'] ?? '익명') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?= $this->endSection() ?>
