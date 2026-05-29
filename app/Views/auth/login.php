<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-4 col-sm-8">
        <div class="card board-card">
            <div class="card-header py-3">
                <i class="bi bi-box-arrow-in-right me-2 text-primary"></i><?= lang('App.login') ?>
            </div>
            <div class="card-body p-4">
                <form action="/auth/login" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= lang('App.login_id_label') ?></label>
                        <input type="text" name="login_id" class="form-control"
                               value="<?= esc(old('login_id')) ?>" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold"><?= lang('App.password_label') ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary"><?= lang('App.login') ?></button>
                    </div>
                </form>
                <p class="text-center text-muted mb-0" style="font-size:.88rem">
                    <?= lang('App.no_account') ?> <a href="/auth/register"><?= lang('App.register') ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
