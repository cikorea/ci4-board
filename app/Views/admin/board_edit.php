<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
$s = $setting;

$permKeys = [
    'view_list'    => 'bbs_allow_group_view_list',
    'view_article' => 'bbs_allow_group_view_article',
    'write_article'=> 'bbs_allow_group_write_article',
    'write_comment'=> 'bbs_allow_group_write_comment',
];

$perms = [];
foreach ($permKeys as $key => $param) {
    $perms[$key] = parse_group_setting($s[$param] ?? '');
}

$groupColors = [0 => 'secondary', 1 => 'danger', 2 => 'primary', 3 => 'warning text-dark'];
?>

<div class="mb-3">
    <a href="/admin/boards" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= lang('App.board_list') ?>
    </a>
</div>

<form action="/admin/boards/<?= esc($bbs['bbs_id']) ?>" method="post">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- 기본 설정 -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header py-2">
                    <i class="bi bi-info-circle me-1 text-primary"></i><?= lang('App.board_basic_settings') ?>
                    <code class="ms-2 text-muted" style="font-size:.8rem"><?= esc($bbs['bbs_id']) ?></code>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.board_name_label') ?></label>
                        <input type="text" name="bbs_name" class="form-control" required
                               value="<?= esc(html_entity_decode($s['bbs_name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= lang('App.list_count_label') ?></label>
                        <input type="number" name="bbs_count_list_article" class="form-control"
                               min="1" max="100" value="<?= esc($s['bbs_count_list_article'] ?? 15) ?>">
                    </div>

                    <div class="d-flex gap-4 mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="bbs_used"
                                   id="bbs_used" <?= ($s['bbs_used'] ?? '0') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="bbs_used"><?= lang('App.board_active_label') ?></label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="bbs_comment_used"
                                   id="bbs_comment_used" <?= ($s['bbs_comment_used'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="bbs_comment_used"><?= lang('App.comment_allowed_label') ?></label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- 접근 권한 -->
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header py-2">
                    <i class="bi bi-shield-check me-1 text-primary"></i><?= lang('App.access_perms_title') ?>
                </div>
                <div class="card-body">

                    <?php
                    $permLabels = [
                        'view_list'     => ['icon' => 'bi-list-ul',           'key' => 'perm_view_list'],
                        'view_article'  => ['icon' => 'bi-file-earmark-text', 'key' => 'perm_view_article'],
                        'write_article' => ['icon' => 'bi-pencil-square',     'key' => 'perm_write_article'],
                        'write_comment' => ['icon' => 'bi-chat-left-text',    'key' => 'perm_write_comment'],
                    ];
                    $allGroups = array_merge([[
                        'idx' => 0, 'group_name' => lang('App.group_guest'),
                    ]], $groups);
                    ?>

                    <div class="row g-3">
                        <?php foreach ($permLabels as $key => $meta): ?>
                            <div class="col-12 col-sm-6">
                                <div class="border rounded p-3" style="background:#fafbff">
                                    <div class="fw-semibold mb-2" style="font-size:.88rem">
                                        <i class="bi <?= $meta['icon'] ?> me-1 text-primary"></i><?= lang('App.' . $meta['key']) ?>
                                    </div>
                                    <?php foreach ($allGroups as $g):
                                        $gIdx   = (int) $g['idx'];
                                        $gName  = html_entity_decode($g['group_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                        $color  = $groupColors[$gIdx] ?? 'secondary';
                                        $checked = in_array($gIdx, $perms[$key], true) ? 'checked' : '';
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="<?= $key ?>[]"
                                                   value="<?= $gIdx ?>"
                                                   id="perm_<?= $key ?>_<?= $gIdx ?>"
                                                   <?= $checked ?>>
                                            <label class="form-check-label" for="perm_<?= $key ?>_<?= $gIdx ?>">
                                                <span class="badge bg-<?= $color ?> badge-group"><?= esc($gName) ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= lang('App.save') ?>
        </button>
        <a href="/admin/boards" class="btn btn-outline-secondary"><?= lang('App.cancel') ?></a>
    </div>

</form>

<?= $this->endSection() ?>
