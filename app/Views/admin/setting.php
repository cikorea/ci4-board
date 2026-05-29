<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php $s = $setting; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <form action="/admin/setting" method="post">
            <?= csrf_field() ?>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-globe me-1 text-primary"></i>사이트 기본 설정
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">사이트 제목</label>
                        <input type="text" name="browser_title_fix_value" class="form-control"
                               value="<?= esc(html_entity_decode($s['browser_title_fix_value'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>">
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="join_used"
                               id="join_used" <?= ($s['join_used'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="join_used">
                            <strong>회원가입 허용</strong>
                            <small class="text-muted ms-1">— 체크 해제 시 신규 가입 불가</small>
                        </label>
                    </div>

                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-shield-exclamation me-1 text-warning"></i>사이트 차단 (공사중 모드)
                </div>
                <div class="card-body">

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="site_block_used"
                               id="site_block_used" <?= ($s['site_block_used'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="site_block_used">
                            <strong>사이트 차단 활성화</strong>
                            <small class="text-muted ms-1">— 관리자 외 모든 접근 차단</small>
                        </label>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">차단 메시지</label>
                        <textarea name="site_block_contents" class="form-control" rows="3"><?= esc(html_entity_decode($s['site_block_contents'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></textarea>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>저장
                </button>
            </div>

        </form>
    </div>
</div>

<?= $this->endSection() ?>
