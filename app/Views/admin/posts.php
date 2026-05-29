<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<?php
$total    = $pager['total'];
$page     = $pager['page'];
$perPage  = $pager['perPage'];
$lastPage = (int) ceil($total / $perPage) ?: 1;

function buildPostQuery(array $extra = []): string {
    $params = array_filter(array_merge([
        'keyword' => $_GET['keyword'] ?? '',
        'bbs_id'  => $_GET['bbs_id'] ?? '',
        'page'    => $_GET['page'] ?? '',
    ], $extra), fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}
?>

<!-- 검색·필터 -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="bbs_id" class="form-select form-select-sm" style="max-width:160px">
                <option value="">전체 게시판</option>
                <?php foreach ($boards as $b): ?>
                    <option value="<?= esc($b['bbs_id']) ?>"
                        <?= $bbsId === $b['bbs_id'] ? 'selected' : '' ?>>
                        <?= esc_db($b['bbs_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="keyword" class="form-control form-control-sm" style="max-width:220px"
                   placeholder="제목·작성자 검색" value="<?= esc($keyword) ?>">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>검색</button>
            <?php if ($keyword || $bbsId): ?>
                <a href="/admin/posts" class="btn btn-sm btn-outline-secondary">초기화</a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:.83rem">총 <?= number_format($total) ?>개</span>
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
                        <th class="ps-3" style="width:55px">#</th>
                        <th style="width:110px">게시판</th>
                        <th>제목</th>
                        <th style="width:100px">작성자</th>
                        <th class="text-center d-none d-sm-table-cell" style="width:55px">조회</th>
                        <th class="text-center d-none d-sm-table-cell" style="width:55px">댓글</th>
                        <th class="text-center d-none d-lg-table-cell" style="width:105px">작성일</th>
                        <th class="text-center" style="width:90px">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">검색 결과가 없습니다.</td></tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $p['idx'] ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size:.75rem">
                                        <?= esc_db($p['bbs_name'] ?? $p['bbs_id']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['is_notice']): ?>
                                        <span class="badge bg-primary me-1" style="font-size:.68rem">공지</span>
                                    <?php endif; ?>
                                    <a href="/board/<?= esc($p['bbs_id']) ?>/view/<?= $p['idx'] ?>"
                                       target="_blank" class="text-decoration-none text-dark">
                                        <?= esc_db($p['title']) ?>
                                    </a>
                                </td>
                                <td class="text-muted"><?= esc_db($p['nickname'] ?? '익명') ?></td>
                                <td class="text-center text-muted d-none d-sm-table-cell">
                                    <?= number_format($p['hit_count']) ?>
                                </td>
                                <td class="text-center text-muted d-none d-sm-table-cell">
                                    <?= number_format($p['comment_count']) ?>
                                </td>
                                <td class="text-center text-muted d-none d-lg-table-cell">
                                    <?= date('Y-m-d H:i', $p['timestamp_insert']) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="/admin/posts/<?= $p['idx'] ?>/edit"
                                           class="btn btn-sm btn-outline-primary py-0 px-2">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="/admin/posts/<?= $p['idx'] ?>/delete<?= buildPostQuery() ?>"
                                           class="btn btn-sm btn-outline-danger py-0 px-2"
                                           onclick="return confirm('이 게시물을 삭제하시겠습니까?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
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
                    <li class="page-item"><a class="page-link" href="<?= '/admin/posts' . buildPostQuery(['page' => 1]) ?>"><i class="bi bi-chevron-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/posts' . buildPostQuery(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 4); $i <= min($lastPage, $page + 4); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= '/admin/posts' . buildPostQuery(['page' => $i]) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/posts' . buildPostQuery(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="<?= '/admin/posts' . buildPostQuery(['page' => $lastPage]) ?>"><i class="bi bi-chevron-double-right"></i></a></li>
                <?php endif; ?>
            </ul></nav>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
