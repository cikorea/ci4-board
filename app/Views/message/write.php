<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card board-card">
            <div class="card-header py-2">
                <i class="bi bi-pencil-square me-1 text-primary"></i><strong>쪽지 쓰기</strong>
            </div>
            <div class="card-body">
                <form action="/message/write" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">받는 사람 <span class="text-danger">*</span></label>
                        <input type="text" name="to" class="form-control" required
                               placeholder="아이디 또는 닉네임"
                               value="<?= esc(old('to', $to)) ?>">
                        <div class="form-text">상대방의 아이디 또는 닉네임을 정확히 입력하세요.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">제목</label>
                        <input type="text" name="title" class="form-control" maxlength="255"
                               placeholder="(선택사항)"
                               value="<?= esc(old('title')) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">내용 <span class="text-danger">*</span></label>
                        <textarea name="contents" class="form-control" rows="10" required
                                  placeholder="쪽지 내용을 입력하세요."><?= esc(old('contents')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i>보내기
                        </button>
                        <a href="/message" class="btn btn-outline-secondary px-4">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
