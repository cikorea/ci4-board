<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">

        <!-- 회원정보 수정 -->
        <div class="card board-card mb-4">
            <div class="card-header py-2">
                <i class="bi bi-person-gear me-1 text-primary"></i><strong><?= lang('App.profile_title') ?></strong>
            </div>
            <div class="card-body">
                <form action="/auth/profile" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.user_id_label') ?></label>
                        <input type="text" class="form-control bg-light"
                               value="<?= esc($user['user_id']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.nickname_label') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="nickname" class="form-control" required
                               maxlength="64"
                               value="<?= esc(old('nickname', html_entity_decode($user['nickname'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.email_label') ?> <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= esc(old('email', $user['email'] ?? '')) ?>">
                    </div>

                    <hr class="my-3">

                    <p class="text-muted small mb-2"><?= lang('App.password_change_hint') ?></p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.new_password') ?></label>
                        <input type="password" name="new_password" class="form-control"
                               autocomplete="new-password" placeholder="<?= lang('App.new_password_hint') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.new_password2') ?></label>
                        <input type="password" name="new_password2" class="form-control"
                               autocomplete="new-password">
                    </div>

                    <hr class="my-3">

                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= lang('App.current_password') ?> <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required
                               autocomplete="current-password"
                               placeholder="<?= lang('App.current_password_placeholder') ?>">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 회원 탈퇴 -->
        <div class="card border-danger">
            <div class="card-header bg-danger bg-opacity-10 py-2 border-danger">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i>
                <strong class="text-danger"><?= lang('App.withdraw_title') ?></strong>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3"><?= lang('App.withdraw_hint') ?></p>

                <button class="btn btn-outline-danger btn-sm" type="button"
                        data-bs-toggle="collapse" data-bs-target="#withdrawForm">
                    <i class="bi bi-person-x me-1"></i><?= lang('App.withdraw_btn') ?>
                </button>

                <div class="collapse mt-3" id="withdrawForm">
                    <form action="/auth/withdraw" method="post"
                          onsubmit="return confirm('<?= lang('App.withdraw_confirm_msg') ?>')">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= lang('App.withdraw_confirm_label') ?></label>
                            <input type="password" name="withdraw_password" class="form-control"
                                   autocomplete="current-password"
                                   placeholder="<?= lang('App.withdraw_password_placeholder') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-person-x me-1"></i><?= lang('App.withdraw_submit') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
