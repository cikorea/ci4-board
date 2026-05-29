<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="mb-3">
    <a href="/admin/members" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= lang('App.member_list') ?>
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <form action="/admin/members/<?= $user['idx'] ?>" method="post">
            <?= csrf_field() ?>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-person-gear me-1 text-primary"></i>
                    <?= lang('App.admin_member_info') ?>
                    <code class="ms-2 text-muted" style="font-size:.8rem">#<?= $user['idx'] ?></code>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.user_id_label') ?></label>
                        <input type="text" class="form-control bg-light" value="<?= esc($user['user_id']) ?>" readonly>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold"><?= lang('App.nickname_label') ?></label>
                            <input type="text" name="nickname" class="form-control" required maxlength="64"
                                   value="<?= esc(old('nickname', html_entity_decode($user['nickname'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold"><?= lang('App.email_label') ?></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= esc(old('email', $user['email'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold"><?= lang('App.col_group') ?></label>
                            <select name="group_idx" class="form-select">
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['idx'] ?>"
                                        <?= $user['group_idx'] == $g['idx'] ? 'selected' : '' ?>>
                                        <?= esc(html_entity_decode($g['group_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold"><?= lang('App.col_status') ?></label>
                            <select name="status" class="form-select">
                                <option value="1" <?= $user['status'] == 1 ? 'selected' : '' ?>><?= lang('App.status_active') ?></option>
                                <option value="0" <?= $user['status'] == 0 ? 'selected' : '' ?>><?= lang('App.status_inactive_note') ?></option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-key me-1 text-warning"></i><?= lang('App.admin_password_reset') ?>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2"><?= lang('App.admin_password_reset_hint') ?></p>
                    <input type="password" name="new_password" class="form-control"
                           autocomplete="new-password" placeholder="<?= lang('App.admin_new_password_ph') ?>">
                </div>
            </div>

            <div class="card mb-3 border-0 bg-light">
                <div class="card-body py-2" style="font-size:.83rem; color:#6c757d">
                    <div class="row g-1">
                        <div class="col-sm-4"><?= lang('App.admin_joined_at') ?>: <?= date('Y-m-d H:i', $user['timestamp_insert']) ?></div>
                        <div class="col-sm-4"><?= lang('App.admin_article_count') ?>: <?= number_format($user['article_count']) ?></div>
                        <div class="col-sm-4"><?= lang('App.admin_comment_count') ?>: <?= number_format($user['comment_count']) ?></div>
                        <?php if ($user['timestamp_login']): ?>
                            <div class="col-sm-4"><?= lang('App.admin_last_login') ?>: <?= date('Y-m-d H:i', $user['timestamp_login']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
                </button>
                <a href="/admin/members" class="btn btn-outline-secondary"><?= lang('App.cancel') ?></a>
            </div>

        </form>
    </div>
</div>

<?= $this->endSection() ?>
