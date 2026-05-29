<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card board-card">
            <div class="card-header py-2">
                <i class="bi bi-pencil-square me-1 text-primary"></i><strong><?= lang('App.write_message') ?></strong>
            </div>
            <div class="card-body">
                <form action="/message/write" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.recipient_label') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="to" class="form-control" required
                               placeholder="<?= lang('App.recipient_placeholder') ?>"
                               value="<?= esc(old('to', $to)) ?>">
                        <div class="form-text"><?= lang('App.recipient_hint') ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.title_optional_label') ?></label>
                        <input type="text" name="title" class="form-control" maxlength="255"
                               placeholder="<?= lang('App.optional') ?>"
                               value="<?= esc(old('title')) ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= lang('App.field_content') ?> <span class="text-danger">*</span></label>
                        <textarea name="contents" class="form-control" rows="10" required
                                  placeholder="<?= lang('App.message_placeholder') ?>"><?= esc(old('contents')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i><?= lang('App.send') ?>
                        </button>
                        <a href="/message" class="btn btn-outline-secondary px-4"><?= lang('App.cancel') ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
