<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-5 col-sm-8">
        <div class="card board-card">
            <div class="card-header py-3">
                <i class="bi bi-person-plus me-2 text-primary"></i><?= lang('App.register') ?>
            </div>
            <div class="card-body p-4">
                <form action="/auth/register" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= lang('App.user_id_label') ?></label>
                        <input type="text" name="user_id" class="form-control"
                               value="<?= esc(old('user_id')) ?>" required minlength="3" maxlength="32">
                        <div class="form-text"><?= lang('App.user_id_hint') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= lang('App.nickname_label') ?></label>
                        <input type="text" name="nickname" class="form-control"
                               value="<?= esc(old('nickname')) ?>" required maxlength="64">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= lang('App.email_label') ?></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= lang('App.password_label') ?></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text"><?= lang('App.password_hint') ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold"><?= lang('App.password2_label') ?></label>
                        <input type="password" name="password2" class="form-control" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary"><?= lang('App.signup') ?></button>
                    </div>
                </form>
                <p class="text-center text-muted mb-0" style="font-size:.88rem">
                    <?= lang('App.have_account') ?> <a href="/auth/login"><?= lang('App.login') ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
