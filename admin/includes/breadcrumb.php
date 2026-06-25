<?php
/**
 * Admin Panel — Breadcrumb Navigation
 *
 * Renders a breadcrumb trail from $breadcrumbs array.
 * Array format: [['label' => 'Home', 'url' => '/admin/'], ['label' => 'Users']]
 * The last item (current page) has no URL and is styled as active.
 *
 * Usage in pages:
 *   $breadcrumbs = [
 *     ['label' => 'Dashboard', 'url' => '/admin/'],
 *     ['label' => 'Blog Articles', 'url' => '/admin/blogs.php'],
 *     ['label' => 'Edit Post'],
 *   ];
 */
$breadcrumbs ??= [['label' => 'Dashboard', 'url' => '/admin/']];
?>
<?php if (!empty($breadcrumbs)): ?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
  <?php foreach ($breadcrumbs as $i => $crumb):
    $isLast = ($i === count($breadcrumbs) - 1);
  ?>
    <div class="breadcrumb-item <?= $isLast ? 'active' : '' ?>">
      <?php if ($i > 0): ?>
        <span class="breadcrumb-sep" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </span>
      <?php endif; ?>

      <?php if (!$isLast && !empty($crumb['url'])): ?>
        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
      <?php else: ?>
        <span <?= $isLast ? 'aria-current="page"' : '' ?>><?= e($crumb['label']) ?></span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
