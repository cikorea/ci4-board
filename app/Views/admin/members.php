<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
$total    = $pager['total'];
$page     = $pager['page'];
$perPage  = $pager['perPage'];
$lastPage = (int) ceil($total / $perPage) ?: 1;

function memberGroupBadge(string $name): string {
    $map = ['최고관리자' => 'danger', '개발자' => 'warning text-dark'];
    $c   = $map[$name] ?? 'primary';
    return "<span class=\"badge bg-{$c}\" style=\"font-size:.7rem\">{$name}</span>";
}

function buildQuery(array $extra = []): string {
    $params = array_filter(array_merge([
        'keyword' => $_GET['keyword'] ?? '',
        'status'  => $_GET['status'] ?? '',
        'page'    => $_GET['page'] ?? '',
    ], $extra), fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}
?>

<!-- 검색·필터 -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="keyword" class="form-control form-control-sm" style="max-width:220px"
                   placeholder="<?= lang('App.search_member_ph') ?>" value="<?= esc($keyword) ?>">
            <select name="status" class="form-select form-select-sm" style="max-width:120px">
                <option value=""><?= lang('App.all_status') ?></option>
                <option value="1" <?= $status === '1' ? 'selected' : '' ?>><?= lang('App.status_active') ?></option>
                <option value="0" <?= $status === '0' ? 'selected' : '' ?>><?= lang('App.status_inactive') ?></option>
            </select>
            <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i></button>
            <?php if ($keyword || $status !== ''): ?>
                <a href="/admin/members" class="btn btn-sm btn-outline-secondary"><?= lang('App.reset') ?></a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:.83rem"><?= lang('App.total_count', [number_format($total)]) ?></span>
        </form>
    </div>
</div>

<!-- 목록 -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.86rem">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:60px">#</th>
                        <th style="width:130px"><?= lang('App.col_user_id') ?></th>
                        <th style="width:130px"><?= lang('App.col_nickname') ?></th>
                        <th><?= lang('App.col_email') ?></th>
                        <th class="text-center" style="width:90px"><?= lang('App.col_group') ?></th>
                        <th class="text-center" style="width:65px"><?= lang('App.col_status') ?></th>
                        <th class="text-center d-none d-md-table-cell" style="width:60px"><?= lang('App.col_articles') ?></th>
                        <th class="text-center d-none d-md-table-cell" style="width:60px"><?= lang('App.col_comments_count') ?></th>
                        <th class="text-center d-none d-lg-table-cell" style="width:110px"><?= lang('App.col_joined') ?></th>
                        <th class="text-center" style="width:60px"><?= lang('App.edit') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4"><?= lang('App.board_no_posts') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $u['idx'] ?></td>
                                <td><code style="font-size:.82rem"><?= esc($u['user_id']) ?></code></td>
                                <td><?= esc_db($u['nickname']) ?></td>
                                <td class="text-muted" style="font-size:.82rem"><?= esc($u['email']) ?></td>
                                <td class="text-center">
                                    <?= memberGroupBadge(html_entity_decode($u['group_name'] ?? '일반회원', ENT_QUOTES, 'UTF-8')) ?>
                                </td>
                                <td class="text-center">
                                    <?= $u['status'] == 1
                                        ? '<span class="badge bg-success" style="font-size:.7rem">' . lang('App.status_active') . '</span>'
                                        : '<span class="badge bg-secondary" style="font-size:.7rem">' . lang('App.status_inactive') . '</span>' ?>
                                </td>
                                <td class="text-center text-muted"><?= number_format($u['article_count']) ?></td>
                                <td class="text-center text-muted"><?= number_format($u['comment_count']) ?></td>
                                <td class="text-center text-muted d-none d-lg-table-cell">
                                    <?= date('Y-m-d', $u['timestamp_insert']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="/admin/members/<?= $u['idx'] ?>"
                                       class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($lastPage > 1): ?>
        <div class="card-footer d-flex justify-content-center py-2 bg-white border-0">
            <nav><ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/members' . buildQuery(['page' => 1]) ?>"><i class="bi bi-chevron-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/members' . buildQuery(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 4); $i <= min($lastPage, $page + 4); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= '/admin/members' . buildQuery(['page' => $i]) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/members' . buildQuery(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/members' . buildQuery(['page' => $lastPage]) ?>"><i class="bi bi-chevron-double-right"></i></a></li>
                <?php endif; ?>
            </ul></nav>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
