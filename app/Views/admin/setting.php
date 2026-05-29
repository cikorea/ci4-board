<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php $s = $setting; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <form action="/admin/setting" method="post">
            <?= csrf_field() ?>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-globe me-1 text-primary"></i><?= lang('App.admin_site_basic') ?>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.admin_site_title_label') ?></label>
                        <input type="text" name="browser_title_fix_value" class="form-control"
                               value="<?= esc(html_entity_decode($s['browser_title_fix_value'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>">
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="join_used"
                               id="join_used" <?= ($s['join_used'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="join_used">
                            <strong><?= lang('App.admin_join_used_label') ?></strong>
                            <small class="text-muted ms-1"><?= lang('App.admin_join_used_hint') ?></small>
                        </label>
                    </div>

                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-shield-exclamation me-1 text-warning"></i><?= lang('App.admin_block_title') ?>
                </div>
                <div class="card-body">

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="site_block_used"
                               id="site_block_used" <?= ($s['site_block_used'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="site_block_used">
                            <strong><?= lang('App.admin_block_used_label') ?></strong>
                            <small class="text-muted ms-1"><?= lang('App.admin_block_used_hint') ?></small>
                        </label>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold"><?= lang('App.admin_block_msg_label') ?></label>
                        <textarea name="site_block_contents" class="form-control" rows="3"><?= esc(html_entity_decode($s['site_block_contents'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></textarea>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
                </button>
            </div>

        </form>
    </div>
</div>

<?= $this->endSection() ?>
