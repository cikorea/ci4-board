<!-- 첨부파일 -->
<div class="mb-4">
    <label class="form-label small fw-semibold">
        <i class="bi bi-paperclip me-1"></i><?= lang('App.attachments_label') ?>
        <span class="text-muted fw-normal ms-1">(<?= lang('App.attachments_hint') ?>)</span>
    </label>

    <?php if (! empty($existingFiles)): ?>
        <div class="mb-2 d-flex flex-column gap-1">
            <?php foreach ($existingFiles as $f):
                $sizeStr = $f['capacity'] >= 1024*1024
                    ? number_format($f['capacity'] / 1024 / 1024, 1) . ' MB'
                    : number_format($f['capacity'] / 1024, 0) . ' KB';
                $icon = match(true) {
                    str_starts_with($f['mime'], 'image/')     => 'bi-file-earmark-image text-success',
                    str_contains($f['mime'], 'pdf')           => 'bi-file-earmark-pdf text-danger',
                    str_contains($f['mime'], 'zip') ||
                    str_contains($f['mime'], 'x-zip') ||
                    str_contains($f['mime'], 'x-7z')          => 'bi-file-earmark-zip text-warning',
                    str_contains($f['mime'], 'spreadsheet') ||
                    str_contains($f['mime'], 'excel')         => 'bi-file-earmark-spreadsheet text-success',
                    str_contains($f['mime'], 'word') ||
                    str_contains($f['mime'], 'msword')        => 'bi-file-earmark-word text-primary',
                    default                                   => 'bi-file-earmark text-secondary',
                };
            ?>
                <div class="d-flex align-items-center gap-2 px-2 py-1 rounded"
                     style="background:#f8f9fa; font-size:.85rem">
                    <i class="bi <?= $icon ?>"></i>
                    <span class="flex-grow-1"><?= esc($f['original_filename']) ?></span>
                    <span class="text-muted"><?= $sizeStr ?></span>
                    <div class="form-check mb-0 ms-2">
                        <input class="form-check-input" type="checkbox"
                               name="delete_files[]" value="<?= $f['idx'] ?>"
                               id="del_file_<?= $f['idx'] ?>">
                        <label class="form-check-label text-danger small"
                               for="del_file_<?= $f['idx'] ?>"><?= lang('App.file_delete_label') ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-text text-danger mb-2"><?= lang('App.delete_file_hint') ?></div>
    <?php endif; ?>

    <input type="file" name="attachments[]" id="attachments"
           class="form-control form-control-sm" multiple
           accept=".jpg,.jpeg,.gif,.png,.txt,.doc,.docx,.xls,.xlsx,.pdf,.ppt,.pptx,.zip,.7z,.alz,.rar">
    <div class="form-text"><?= lang('App.allowed_files') ?></div>
</div>
