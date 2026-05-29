<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
$contentsVal = $post['contents'] ?? '';
if (strpos($contentsVal, '&lt;') !== false) {
    $contentsVal = html_entity_decode($contentsVal, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
$backUrl = $_SERVER['HTTP_REFERER'] ?? '/admin/posts';
if (str_contains($backUrl, '/edit')) {
    $backUrl = '/admin/posts';
}
?>

<div class="mb-3">
    <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= lang('App.admin_posts_title') ?>
    </a>
</div>

<div class="card">
    <div class="card-header py-2">
        <i class="bi bi-pencil-square me-1 text-primary"></i><?= lang('App.admin_post_edit') ?>
        <span class="text-muted ms-2" style="font-size:.82rem">
            [<?= esc_db($post['bbs_name']) ?>] #<?= $post['idx'] ?>
        </span>
    </div>
    <div class="card-body p-4">
        <form action="/admin/posts/<?= $post['idx'] ?>/edit" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= esc($backUrl) ?>">

            <div class="mb-3 d-flex gap-3 align-items-center">
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold"><?= lang('App.field_title') ?> <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required maxlength="255"
                           value="<?= esc(old('title', html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?>">
                </div>
                <div class="pt-4 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_notice"
                               id="is_notice" value="1" <?= $post['is_notice'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="is_notice"><?= lang('App.notice') ?></label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold"><?= lang('App.field_content') ?> <span class="text-danger">*</span></label>
                <textarea name="contents" class="form-control" rows="22" required
                          style="font-family: monospace; font-size: .9rem"><?= esc(old('contents', $contentsVal)) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
                </button>
                <a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary px-4"><?= lang('App.cancel') ?></a>
                <a href="/board/<?= esc($post['bbs_id']) ?>/view/<?= $post['idx'] ?>"
                   target="_blank" class="btn btn-outline-secondary ms-auto">
                    <i class="bi bi-box-arrow-up-right me-1"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
