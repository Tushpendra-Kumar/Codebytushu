<?php
/**
 * CodeByTushu — Admin Shared Pagination Partial
 * ------------------------------------------------
 * Include this file wherever pagination is needed in admin list pages.
 *
 * REQUIRED variables (set by the including page before require_once):
 *   $pager       — array returned by paginate() from includes/functions.php
 *   $page        — int, current page number
 *   $perPage     — int, items per page
 *   $total       — int, total filtered result count
 *   $queryParams — array, current filter/sort params to preserve across page links
 *                  e.g. compact('search', 'status', 'sort', 'dir')
 *
 * OPTIONAL variables:
 *   $paginationId — string, unique HTML id suffix (defaults to 'pg')
 *
 * Usage example:
 *   $queryParams = array_filter(compact('search', 'status', 'role'));
 *   require __DIR__ . '/includes/pagination.php';
 */

declare(strict_types=1);

// Guard: only render if more than one page
if (empty($pager) || $pager['total_pages'] <= 1) return;

$_pgId     = $paginationId ?? 'pg';
$_params   = $queryParams  ?? [];
$_start    = $pager['offset'] + 1;
$_end      = min($pager['offset'] + $perPage, $total);
$_from     = max(1, $page - 2);
$_to       = min($pager['total_pages'], $page + 2);
?>
<div class="card-footer" id="<?= e((string)$_pgId) ?>-footer">
  <span style="font-size:12px;color:var(--text-muted);">
    Showing <?= number_format($_start) ?>–<?= number_format($_end) ?> of <?= number_format($total) ?>
  </span>
  <div class="pagination">
    <?php if ($pager['has_prev']): ?>
      <a href="?page=<?= $pager['prev_page'] ?>&<?= http_build_query(array_filter($_params)) ?>"
         class="page-btn" aria-label="Previous page">← Prev</a>
    <?php endif; ?>

    <?php if ($_from > 1): ?>
      <a href="?page=1&<?= http_build_query(array_filter($_params)) ?>" class="page-btn">1</a>
      <span class="page-btn" style="cursor:default;color:var(--text-dim);">…</span>
    <?php endif; ?>

    <?php for ($p = $_from; $p <= $_to; $p++): ?>
      <a href="?page=<?= $p ?>&<?= http_build_query(array_filter($_params)) ?>"
         class="page-btn <?= $p === $page ? 'active' : '' ?>"
         <?= $p === $page ? 'aria-current="page"' : '' ?>>
        <?= $p ?>
      </a>
    <?php endfor; ?>

    <?php if ($_to < $pager['total_pages']): ?>
      <span class="page-btn" style="cursor:default;color:var(--text-dim);">…</span>
      <a href="?page=<?= $pager['total_pages'] ?>&<?= http_build_query(array_filter($_params)) ?>"
         class="page-btn"><?= $pager['total_pages'] ?></a>
    <?php endif; ?>

    <?php if ($pager['has_next']): ?>
      <a href="?page=<?= $pager['next_page'] ?>&<?= http_build_query(array_filter($_params)) ?>"
         class="page-btn" aria-label="Next page">Next →</a>
    <?php endif; ?>
  </div>
</div>
