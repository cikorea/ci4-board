<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card board-card">
    <div class="card-header py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/message" class="text-decoration-none"><?= lang('App.inbox') ?></a></li>
                <li class="breadcrumb-item active"><?= lang('App.read_message') ?></li>
            </ol>
        </nav>
    </div>

    <!-- 메타 -->
    <div class="card-body border-bottom">
        <h6 class="fw-bold mb-3">
            <?= $msg['title'] ? esc_db($msg['title']) : '<span class="text-muted">' . lang('App.no_title') . '</span>' ?>
        </h6>
        <div class="d-flex flex-wrap gap-3 meta-text">
            <span><i class="bi bi-person me-1"></i>
                <?= lang('App.from_label') ?>: <strong><?= esc_db($msg['sender_nickname'] ?? lang('App.unknown')) ?></strong>
                <small class="text-muted">(<?= esc($msg['sender_user_id'] ?? '') ?>)</small>
            </span>
            <span><i class="bi bi-person-check me-1"></i>
                <?= lang('App.to_label') ?>: <strong><?= esc_db($msg['receiver_nickname'] ?? lang('App.unknown')) ?></strong>
            </span>
            <span><i class="bi bi-clock me-1"></i><?= date('Y-m-d H:i', $msg['timestamp_send']) ?></span>
            <?php if ($msg['is_read'] && $msg['timestamp_receive']): ?>
                <span class="text-success"><i class="bi bi-check2-circle me-1"></i>
                    <?= date('Y-m-d H:i', $msg['timestamp_receive']) ?> <?= lang('App.read_at') ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- 본문 -->
    <div class="card-body py-4" style="min-height:160px; line-height:1.9; white-space:pre-wrap;">
        <?= nl2br(esc_db($msg['contents'])) ?>
    </div>

    <!-- 하단 버튼 -->
    <div class="card-footer d-flex justify-content-between align-items-center py-3 bg-white border-0">
        <a href="<?= $isSender ? '/message/sent' : '/message' ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i><?= lang('App.list') ?>
        </a>
        <div class="d-flex gap-2">
            <?php if (! $isSender): ?>
                <a href="/message/write?to=<?= esc($msg['sender_user_id'] ?? '') ?>"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-reply me-1"></i><?= lang('App.reply') ?>
                </a>
            <?php endif; ?>
            <a href="/message/<?= $msg['idx'] ?>/delete<?= $isSender ? '?from=sent' : '' ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('<?= lang('App.delete_message_confirm') ?>')">
                <i class="bi bi-trash me-1"></i><?= lang('App.delete') ?>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
