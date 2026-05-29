<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card board-card">
    <div class="card-header py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/message" class="text-decoration-none">받은 쪽지함</a></li>
                <li class="breadcrumb-item active">쪽지 읽기</li>
            </ol>
        </nav>
    </div>

    <!-- 메타 -->
    <div class="card-body border-bottom">
        <h6 class="fw-bold mb-3">
            <?= $msg['title'] ? esc_db($msg['title']) : '<span class="text-muted">(제목 없음)</span>' ?>
        </h6>
        <div class="d-flex flex-wrap gap-3 meta-text">
            <span><i class="bi bi-person me-1"></i>
                보낸 사람: <strong><?= esc_db($msg['sender_nickname'] ?? '알 수 없음') ?></strong>
                <small class="text-muted">(<?= esc($msg['sender_user_id'] ?? '') ?>)</small>
            </span>
            <span><i class="bi bi-person-check me-1"></i>
                받는 사람: <strong><?= esc_db($msg['receiver_nickname'] ?? '알 수 없음') ?></strong>
            </span>
            <span><i class="bi bi-clock me-1"></i><?= date('Y-m-d H:i', $msg['timestamp_send']) ?></span>
            <?php if ($msg['is_read'] && $msg['timestamp_receive']): ?>
                <span class="text-success"><i class="bi bi-check2-circle me-1"></i>
                    <?= date('Y-m-d H:i', $msg['timestamp_receive']) ?> 읽음
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
            <i class="bi bi-list-ul me-1"></i>목록
        </a>
        <div class="d-flex gap-2">
            <?php if (! $isSender): ?>
                <a href="/message/write?to=<?= esc($msg['sender_user_id'] ?? '') ?>"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-reply me-1"></i>답장
                </a>
            <?php endif; ?>
            <a href="/message/<?= $msg['idx'] ?>/delete<?= $isSender ? '?from=sent' : '' ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('이 쪽지를 삭제하시겠습니까?')">
                <i class="bi bi-trash me-1"></i>삭제
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
