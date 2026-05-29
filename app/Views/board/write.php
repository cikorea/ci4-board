<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card board-card">
    <div class="card-header py-3">
        <i class="bi bi-pencil-square me-2 text-primary"></i>
        <?= esc($board['bbs_name'] ?? $board['bbs_id']) ?> — 글쓰기
    </div>
    <div class="card-body p-4">
        <form action="/board/<?= esc($board['bbs_id']) ?>/write" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">제목 <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= esc(old('title')) ?>" required maxlength="255" placeholder="제목을 입력하세요">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">내용 <span class="text-danger">*</span></label>
                <textarea name="contents" class="form-control" rows="18" required
                          placeholder="내용을 입력하세요"><?= esc(old('contents')) ?></textarea>
            </div>

            <?= view('board/_tag_url_fields', ['existingTags' => [], 'existingUrls' => []]) ?>
            <?= view('board/_file_fields', ['existingFiles' => []]) ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i>등록
                </button>
                <a href="/board/<?= esc($board['bbs_id']) ?>" class="btn btn-outline-secondary px-4">취소</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
