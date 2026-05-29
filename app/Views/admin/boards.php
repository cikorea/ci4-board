<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
$groupMap = [0 => lang('App.group_guest')];
foreach ($groups as $g) {
    $groupMap[(int)$g['idx']] = html_entity_decode($g['group_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$groupBadgeColor = [0 => 'secondary', 1 => 'danger', 2 => 'primary', 3 => 'warning text-dark'];

function renderGroupBadges(string $serialized, array $groupMap, array $colors): string {
    $idxs = parse_group_setting($serialized);
    if (empty($idxs)) return '<span class="text-muted">-</span>';
    $badges = [];
    foreach ($idxs as $i) {
        $name  = $groupMap[$i] ?? "Group{$i}";
        $color = $colors[$i] ?? 'secondary';
        $badges[] = "<span class=\"badge bg-{$color} badge-group\">{$name}</span>";
    }
    return implode(' ', $badges);
}
?>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-layout-text-sidebar-reverse me-1 text-primary"></i><?= lang('App.board_list') ?></span>
        <small class="text-muted"><?= lang('App.total_count', [count($boards)]) ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:50px">#</th>
                        <th style="width:100px">ID</th>
                        <th><?= lang('App.col_board_name') ?></th>
                        <th class="text-center" style="width:70px"><?= lang('App.col_status') ?></th>
                        <th class="text-center" style="width:60px"><?= lang('App.comments') ?></th>
                        <th class="text-center" style="width:60px"><?= lang('App.col_list_count') ?></th>
                        <th><?= lang('App.col_view_perm') ?></th>
                        <th><?= lang('App.col_write_perm') ?></th>
                        <th class="text-center" style="width:80px"><?= lang('App.col_settings') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($boards as $b): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= $b['idx'] ?></td>
                            <td><code><?= esc($b['bbs_id']) ?></code></td>
                            <td class="fw-semibold"><?= esc_db($b['bbs_name'] ?? $b['bbs_id']) ?></td>
                            <td class="text-center">
                                <?php if ($b['bbs_used'] == '1'): ?>
                                    <span class="badge bg-success"><?= lang('App.active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= lang('App.inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= $b['comment_used'] == '1'
                                    ? '<i class="bi bi-check-circle text-success"></i>'
                                    : '<i class="bi bi-x-circle text-secondary"></i>' ?>
                            </td>
                            <td class="text-center"><?= esc($b['list_count'] ?? '-') ?></td>
                            <td><?= renderGroupBadges($b['perm_view_list'] ?? '', $groupMap, $groupBadgeColor) ?></td>
                            <td><?= renderGroupBadges($b['perm_write_article'] ?? '', $groupMap, $groupBadgeColor) ?></td>
                            <td class="text-center">
                                <a href="/admin/boards/<?= esc($b['bbs_id']) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
