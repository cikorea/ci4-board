<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card board-card">
    <!-- breadcrumb -->
    <div class="card-header py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/" class="text-decoration-none"><?= lang('App.home') ?></a></li>
                <li class="breadcrumb-item">
                    <a href="/board/<?= esc($board['bbs_id']) ?>" class="text-decoration-none">
                        <?= esc_db($board['bbs_name'] ?? $board['bbs_id']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= lang('App.view_post') ?></li>
            </ol>
        </nav>
    </div>

    <!-- 제목 & 메타 -->
    <div class="card-body border-bottom">
        <?php if ($post['is_notice']): ?>
            <span class="badge bg-primary mb-2"><?= lang('App.notice') ?></span>
        <?php endif; ?>
        <h5 class="fw-bold mb-3"><?= esc_db($post['title']) ?></h5>
        <div class="d-flex flex-wrap gap-3 meta-text">
            <span>
                <i class="bi bi-person me-1"></i>
                <?php if (session()->get('logged_in') && session()->get('user_idx') != $post['user_idx'] && $post['user_id']): ?>
                    <?= esc_db($post['nickname'] ?? lang('App.anonymous')) ?>
                    <a href="/message/write?to=<?= esc($post['user_id']) ?>"
                       class="ms-1 text-decoration-none" title="<?= lang('App.send_message') ?>">
                        <i class="bi bi-envelope" style="font-size:.8rem"></i>
                    </a>
                <?php else: ?>
                    <?= esc_db($post['nickname'] ?? lang('App.anonymous')) ?>
                <?php endif; ?>
            </span>
            <span><i class="bi bi-eye me-1"></i><?= number_format($post['hit_count']) ?></span>
            <span><i class="bi bi-hand-thumbs-up me-1"></i><?= number_format($post['vote_count']) ?></span>
            <span><i class="bi bi-chat me-1"></i><?= number_format($post['comment_count']) ?></span>
            <span><i class="bi bi-clock me-1"></i><?= date('Y-m-d H:i', $post['timestamp_insert']) ?></span>
            <?php if ($post['timestamp_update']): ?>
                <span class="text-warning"><i class="bi bi-pencil me-1"></i><?= lang('App.edited') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- 본문 -->
    <div class="card-body py-4" style="min-height:200px; line-height:1.9;">
        <?php
        $contents = $post['contents'] ?? '';
        if ($post['html_used'] || strpos($contents, '&lt;') !== false) {
            echo html_entity_decode($contents, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            echo nl2br(esc_db($contents));
        }
        ?>
    </div>

    <!-- 첨부파일 -->
    <?php if (! empty($files)): ?>
        <div class="card-body border-top pt-3 pb-2">
            <div class="text-muted mb-2" style="font-size:.82rem">
                <i class="bi bi-paperclip me-1"></i><?= lang('App.attachments', [count($files)]) ?>
            </div>
            <div class="d-flex flex-column gap-1">
                <?php foreach ($files as $f):
                    $isImage = str_starts_with($f['mime'], 'image/');
                    $icon    = match(true) {
                        str_starts_with($f['mime'], 'image/')       => 'bi-file-earmark-image text-success',
                        str_contains($f['mime'], 'pdf')             => 'bi-file-earmark-pdf text-danger',
                        str_contains($f['mime'], 'zip') ||
                        str_contains($f['mime'], 'x-zip') ||
                        str_contains($f['mime'], 'x-7z')            => 'bi-file-earmark-zip text-warning',
                        str_contains($f['mime'], 'spreadsheet') ||
                        str_contains($f['mime'], 'excel')           => 'bi-file-earmark-spreadsheet text-success',
                        str_contains($f['mime'], 'word') ||
                        str_contains($f['mime'], 'msword')          => 'bi-file-earmark-word text-primary',
                        default                                      => 'bi-file-earmark text-secondary',
                    };
                    $sizeStr = $f['capacity'] >= 1024*1024
                        ? number_format($f['capacity'] / 1024 / 1024, 1) . ' MB'
                        : number_format($f['capacity'] / 1024, 0) . ' KB';
                ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/file/<?= $f['idx'] ?>"
                           class="text-decoration-none d-flex align-items-center gap-2 flex-grow-1"
                           style="font-size:.88rem">
                            <i class="bi <?= $icon ?>" style="font-size:1.1rem"></i>
                            <span class="text-dark"><?= esc($f['original_filename']) ?></span>
                            <span class="text-muted" style="font-size:.78rem">(<?= $sizeStr ?>)</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 태그 & URL -->
    <?php if (! empty($tags) || ! empty($urls)): ?>
        <div class="card-body pt-0 pb-3 d-flex flex-column gap-2">
            <?php if (! empty($tags)): ?>
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <i class="bi bi-tags text-muted me-1" style="font-size:.85rem"></i>
                    <?php foreach ($tags as $t): ?>
                        <span class="badge rounded-pill border text-secondary fw-normal"
                              style="font-size:.78rem; background:#f0f2f5">
                            <?= esc_db($t['tag']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (! empty($urls)): ?>
                <div class="d-flex flex-column gap-1">
                    <span class="text-muted" style="font-size:.8rem">
                        <i class="bi bi-link-45deg me-1"></i><?= lang('App.related_links') ?>
                    </span>
                    <?php foreach ($urls as $u): ?>
                        <a href="<?= esc($u['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="text-primary text-decoration-none text-truncate d-block"
                           style="font-size:.85rem; max-width:100%">
                            <i class="bi bi-box-arrow-up-right me-1" style="font-size:.75rem"></i><?= esc($u['url']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 하단 버튼 -->
    <div class="card-footer d-flex justify-content-between align-items-center py-3 bg-white border-0">
        <a href="/board/<?= esc($board['bbs_id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i><?= lang('App.list') ?>
        </a>

        <div class="d-flex gap-2">
            <?php if (user_can_in_groups($board['permissions']['write_article'] ?? [])): ?>
                <a href="/board/<?= esc($board['bbs_id']) ?>/write" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i><?= lang('App.board_write') ?>
                </a>
            <?php endif; ?>
            <?php if (session()->get('user_idx') == $post['user_idx']): ?>
                <a href="/board/<?= esc($board['bbs_id']) ?>/edit/<?= $post['idx'] ?>"
                   class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i><?= lang('App.edit') ?>
                </a>
                <a href="/board/<?= esc($board['bbs_id']) ?>/delete/<?= $post['idx'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('<?= lang('App.delete_confirm') ?>')">
                    <i class="bi bi-trash me-1"></i><?= lang('App.delete') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 댓글 섹션 -->
<div class="card board-card mt-3" id="comments">
    <div class="card-header py-3">
        <i class="bi bi-chat-left-text me-2 text-primary"></i>
        <?= lang('App.comments') ?> <span class="badge bg-secondary"><?= count($comments) ?></span>
    </div>

    <!-- 댓글 목록 -->
    <div class="card-body p-0">
        <?php if (empty($comments)): ?>
            <p class="text-center text-muted py-4 mb-0"><?= lang('App.no_comments') ?></p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="d-flex gap-3 px-4 py-3 border-bottom" id="comment-<?= $c['idx'] ?>">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                             style="width:36px; height:36px; color:#fff; font-size:.85rem; font-weight:600;">
                            <?= mb_substr(html_entity_decode($c['nickname'] ?? '?', ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 1) ?>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong style="font-size:.9rem"><?= esc_db($c['nickname'] ?? lang('App.anonymous')) ?></strong>
                                <?php if (session()->get('logged_in') && session()->get('user_idx') != $c['user_idx']): ?>
                                    <a href="/message/write?to=<?= esc($c['user_id'] ?? '') ?>"
                                       class="ms-1 text-decoration-none text-muted" title="<?= lang('App.send_message') ?>">
                                        <i class="bi bi-envelope" style="font-size:.75rem"></i>
                                    </a>
                                <?php endif; ?>
                                <span class="text-muted ms-2 meta-text">
                                    <?= date('Y-m-d H:i', $c['timestamp_insert']) ?>
                                </span>
                                <?php if ($c['timestamp_update']): ?>
                                    <span class="text-warning ms-1 meta-text"><?= lang('App.edited_mark') ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (session()->get('user_idx') == $c['user_idx']): ?>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-link btn-sm text-primary p-0 btn-comment-edit"
                                            data-idx="<?= $c['idx'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="/board/<?= esc($board['bbs_id']) ?>/view/<?= $post['idx'] ?>/comment/<?= $c['idx'] ?>/delete"
                                       class="btn btn-link btn-sm text-danger p-0"
                                       onclick="return confirm('<?= lang('App.comment_delete_confirm') ?>')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 댓글 본문 -->
                        <div class="comment-body" data-idx="<?= $c['idx'] ?>" style="line-height:1.7; white-space:pre-wrap; font-size:.92rem;">
                            <?php
                            $cText = $c['comment'];
                            if (strpos($cText, '&lt;') !== false || strpos($cText, '<br') !== false) {
                                echo html_entity_decode($cText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            } else {
                                echo nl2br(esc_db($cText));
                            }
                            ?>
                        </div>

                        <!-- 댓글 수정 폼 -->
                        <form class="comment-edit-form mt-2" data-idx="<?= $c['idx'] ?>"
                              action="/board/<?= esc($board['bbs_id']) ?>/view/<?= $post['idx'] ?>/comment/<?= $c['idx'] ?>/edit"
                              method="post" style="display:none">
                            <?= csrf_field() ?>
                            <textarea name="comment" class="form-control form-control-sm mb-2" rows="3"
                                      required><?= esc(html_entity_decode($c['comment'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></textarea>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-comment-cancel"
                                        data-idx="<?= $c['idx'] ?>">
                                    <?= lang('App.cancel') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 댓글 작성 폼 -->
    <?php if (user_can_in_groups($board['permissions']['write_comment'] ?? [])): ?>
        <div class="card-footer bg-white py-3 px-4">
            <form action="/board/<?= esc($board['bbs_id']) ?>/view/<?= $post['idx'] ?>/comment" method="post">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 align-items-start">
                    <div class="flex-grow-1">
                        <textarea name="comment" class="form-control" rows="3"
                                  placeholder="<?= lang('App.comment_placeholder') ?>" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap">
                        <i class="bi bi-send me-1"></i><?= lang('App.submit') ?>
                    </button>
                </div>
            </form>
        </div>
    <?php elseif (! session()->get('logged_in')): ?>
        <div class="card-footer bg-white py-3 px-4 text-center">
            <a href="/auth/login" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i><?= lang('App.login_to_comment') ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-comment-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        const idx = btn.dataset.idx;
        document.querySelector(`.comment-body[data-idx="${idx}"]`).style.display = 'none';
        document.querySelector(`.comment-edit-form[data-idx="${idx}"]`).style.display = '';
        document.querySelector(`.comment-edit-form[data-idx="${idx}"] textarea`).focus();
    });
});
document.querySelectorAll('.btn-comment-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
        const idx = btn.dataset.idx;
        document.querySelector(`.comment-edit-form[data-idx="${idx}"]`).style.display = 'none';
        document.querySelector(`.comment-body[data-idx="${idx}"]`).style.display = '';
    });
});
</script>

<?= $this->endSection() ?>
