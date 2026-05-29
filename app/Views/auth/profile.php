<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">

        <!-- 회원정보 수정 -->
        <div class="card board-card mb-4">
            <div class="card-header py-2">
                <i class="bi bi-person-gear me-1 text-primary"></i><strong>회원정보 수정</strong>
            </div>
            <div class="card-body">
                <form action="/auth/profile" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">아이디</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= esc($user['user_id']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">닉네임 <span class="text-danger">*</span></label>
                        <input type="text" name="nickname" class="form-control" required
                               maxlength="64"
                               value="<?= esc(old('nickname', html_entity_decode($user['nickname'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">이메일 <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= esc(old('email', $user['email'] ?? '')) ?>">
                    </div>

                    <hr class="my-3">

                    <p class="text-muted small mb-2">비밀번호를 변경하려면 아래에 입력하세요. 변경하지 않으려면 비워두세요.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">새 비밀번호</label>
                        <input type="password" name="new_password" class="form-control"
                               autocomplete="new-password" placeholder="6자 이상">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">새 비밀번호 확인</label>
                        <input type="password" name="new_password2" class="form-control"
                               autocomplete="new-password">
                    </div>

                    <hr class="my-3">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">현재 비밀번호 <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required
                               autocomplete="current-password" placeholder="변경 사항을 저장하려면 현재 비밀번호를 입력하세요">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>저장
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 회원 탈퇴 -->
        <div class="card border-danger">
            <div class="card-header bg-danger bg-opacity-10 py-2 border-danger">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i>
                <strong class="text-danger">회원 탈퇴</strong>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    탈퇴 시 계정 정보에 접근할 수 없게 됩니다.
                    작성한 게시글과 댓글은 유지됩니다.
                </p>

                <button class="btn btn-outline-danger btn-sm" type="button"
                        data-bs-toggle="collapse" data-bs-target="#withdrawForm">
                    <i class="bi bi-person-x me-1"></i>탈퇴하기
                </button>

                <div class="collapse mt-3" id="withdrawForm">
                    <form action="/auth/withdraw" method="post"
                          onsubmit="return confirm('정말로 탈퇴하시겠습니까? 이 작업은 되돌릴 수 없습니다.')">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">비밀번호 확인</label>
                            <input type="password" name="withdraw_password" class="form-control"
                                   autocomplete="current-password"
                                   placeholder="비밀번호를 입력하면 즉시 탈퇴됩니다" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-person-x me-1"></i>탈퇴 확인
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
