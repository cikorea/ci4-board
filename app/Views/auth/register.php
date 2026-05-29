<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-5 col-sm-8">
        <div class="card board-card">
            <div class="card-header py-3">
                <i class="bi bi-person-plus me-2 text-primary"></i>회원가입
            </div>
            <div class="card-body p-4">
                <form action="/auth/register" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">아이디</label>
                        <input type="text" name="user_id" class="form-control"
                               value="<?= esc(old('user_id')) ?>" required minlength="3" maxlength="32">
                        <div class="form-text">3~32자, 영문/숫자</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">닉네임</label>
                        <input type="text" name="nickname" class="form-control"
                               value="<?= esc(old('nickname')) ?>" required maxlength="64">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">이메일</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">비밀번호</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text">6자 이상</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">비밀번호 확인</label>
                        <input type="password" name="password2" class="form-control" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">가입하기</button>
                    </div>
                </form>
                <p class="text-center text-muted mb-0" style="font-size:.88rem">
                    이미 계정이 있으신가요? <a href="/auth/login">로그인</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
