<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// 기존 HTML 인코딩된 내용 복원
$contentsVal = $post['contents'] ?? '';
if (strpos($contentsVal, '&lt;') !== false) {
    $contentsVal = html_entity_decode($contentsVal, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>

<div class="card board-card">
    <div class="card-header py-3">
        <i class="bi bi-pencil me-2 text-primary"></i>
        <?= esc($board['bbs_name'] ?? $board['bbs_id']) ?> — 글수정
    </div>
    <div class="card-body p-4">
        <form action="/board/<?= esc($board['bbs_id']) ?>/edit/<?= $post['idx'] ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">제목 <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= esc(old('title', $post['title'])) ?>" required maxlength="255">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">내용 <span class="text-danger">*</span></label>
                <textarea name="contents" class="form-control" rows="18" required><?= esc(old('contents', $contentsVal)) ?></textarea>
            </div>

            <?= view('board/_tag_url_fields', ['existingTags' => $tags ?? [], 'existingUrls' => $urls ?? []]) ?>
            <?= view('board/_file_fields', ['existingFiles' => $files ?? []]) ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i>수정 완료
                </button>
                <a href="/board/<?= esc($board['bbs_id']) ?>/view/<?= $post['idx'] ?>"
                   class="btn btn-outline-secondary px-4">취소</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
