<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
// 그룹 인덱스 → 이름 맵
$groupMap = [0 => '비회원'];
foreach ($groups as $g) {
    $groupMap[(int)$g['idx']] = html_entity_decode($g['group_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$groupBadgeColor = [0 => 'secondary', 1 => 'danger', 2 => 'primary', 3 => 'warning text-dark'];

function renderGroupBadges(string $serialized, array $groupMap, array $colors): string {
    $idxs = parse_group_setting($serialized);
    if (empty($idxs)) return '<span class="text-muted">-</span>';
    $badges = [];
    foreach ($idxs as $i) {
        $name  = $groupMap[$i] ?? "그룹{$i}";
        $color = $colors[$i] ?? 'secondary';
        $badges[] = "<span class=\"badge bg-{$color} badge-group\">{$name}</span>";
    }
    return implode(' ', $badges);
}
?>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-layout-text-sidebar-reverse me-1 text-primary"></i>게시판 목록</span>
        <small class="text-muted">총 <?= count($boards) ?>개</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:50px">#</th>
                        <th style="width:100px">ID</th>
                        <th>게시판명</th>
                        <th class="text-center" style="width:70px">상태</th>
                        <th class="text-center" style="width:60px">댓글</th>
                        <th class="text-center" style="width:60px">목록수</th>
                        <th>목록 권한</th>
                        <th>쓰기 권한</th>
                        <th class="text-center" style="width:80px">설정</th>
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
                                    <span class="badge bg-success">활성</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">비활성</span>
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
