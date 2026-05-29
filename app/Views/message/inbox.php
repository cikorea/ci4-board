<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$total    = $pager['total'];
$page     = $pager['page'];
$perPage  = $pager['perPage'];
$lastPage = (int) ceil($total / $perPage) ?: 1;
?>

<div class="card board-card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex gap-3">
            <span class="fw-bold"><i class="bi bi-inbox me-1 text-primary"></i><?= lang('App.inbox') ?></span>
            <a href="/message/sent" class="text-muted text-decoration-none" style="font-size:.88rem"><?= lang('App.sent_box') ?></a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted"><?= lang('App.total_count', [number_format($total)]) ?></small>
            <a href="/message/write" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i><?= lang('App.write_message') ?>
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($messages)): ?>
            <p class="text-center text-muted py-5 mb-0"><?= lang('App.no_messages_inbox') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:110px" class="ps-3"><?= lang('App.col_sender') ?></th>
                            <th><?= lang('App.col_content') ?></th>
                            <th class="text-center d-none d-sm-table-cell" style="width:130px"><?= lang('App.col_received') ?></th>
                            <th class="text-center" style="width:60px"><?= lang('App.delete') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $m):
                            $preview = mb_substr(strip_tags($m['contents']), 0, 40);
                            $isUnread = ! $m['is_read'];
                        ?>
                            <tr <?= $isUnread ? 'class="table-primary bg-opacity-25"' : '' ?>>
                                <td class="ps-3 fw-semibold" style="font-size:.88rem">
                                    <?= esc_db($m['sender_nickname'] ?? lang('App.unknown')) ?>
                                </td>
                                <td>
                                    <a href="/message/<?= $m['idx'] ?>" class="text-decoration-none text-dark d-block">
                                        <?php if ($isUnread): ?>
                                            <span class="badge bg-danger me-1" style="font-size:.6rem">NEW</span>
                                        <?php endif; ?>
                                        <?php if ($m['title']): ?>
                                            <span class="fw-semibold"><?= esc_db($m['title']) ?></span>
                                            <span class="text-muted ms-2" style="font-size:.83rem"><?= esc($preview) ?></span>
                                        <?php else: ?>
                                            <?= esc($preview ?: lang('App.no_content')) ?>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-center text-muted d-none d-sm-table-cell" style="font-size:.82rem">
                                    <?= date('Y-m-d H:i', $m['timestamp_send']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="/message/<?= $m['idx'] ?>/delete"
                                       class="btn btn-link btn-sm text-danger p-0"
                                       onclick="return confirm('<?= lang('App.delete_message_confirm') ?>')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($lastPage > 1): ?>
        <div class="card-footer d-flex justify-content-center py-3 bg-white border-0">
            <nav><ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=1"><i class="bi bi-chevron-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 4); $i <= min($lastPage, $page + 4); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?page=<?= $lastPage ?>"><i class="bi bi-chevron-double-right"></i></a></li>
                <?php endif; ?>
            </ul></nav>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
