<?php
/**
 * CodeByTushu — Admin Categories Manager
 * Manage blog/leetcode/course/portfolio categories and solution tags.
 */
declare(strict_types=1);

$adminSection = 'Categories';
$adminTitle   = 'Categories & Tags — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Categories & Tags'],
];
require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

$categories = $pdo->query('SELECT *, (SELECT COUNT(*) FROM blog_articles WHERE category_id=c.id) as blog_count,
    (SELECT COUNT(*) FROM courses WHERE category_id=c.id) as course_count
    FROM categories c ORDER BY type, sort_order, name')->fetchAll();

$tags = $pdo->query('SELECT *, usage_count FROM solution_tags ORDER BY usage_count DESC, name ASC')->fetchAll();
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Categories & Tags</h1>
          <p class="page-subtitle">Manage content categories and LeetCode solution tags.</p>
        </div>
        <div class="page-header-right">
          <button class="btn btn-primary" onclick="Modal.open('addCategoryModal')">+ Add Category</button>
          <button class="btn btn-ghost" onclick="Modal.open('addTagModal')">+ Add Tag</button>
        </div>
      </div>

      <?= flashHtml() ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- Categories -->
        <div class="card">
          <div class="card-header"><h2 class="card-title">Content Categories</h2></div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Name</th><th>Type</th><th>Blogs</th><th>Courses</th><th>Active</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach($categories as $c): ?>
                <tr data-id="cat-<?= $c['id'] ?>">
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <?php if($c['icon_name']): ?><span><?= e($c['icon_name']) ?></span><?php endif; ?>
                      <span style="font-weight:500;"><?= e($c['name']) ?></span>
                    </div>
                  </td>
                  <td><span class="badge badge-muted"><?= e($c['type']) ?></span></td>
                  <td style="color:var(--text-muted);"><?= $c['blog_count'] ?></td>
                  <td style="color:var(--text-muted);"><?= $c['course_count'] ?></td>
                  <td>
                    <label class="toggle-wrap">
                      <div class="toggle">
                        <input type="checkbox" <?= $c['is_active']?'checked':'' ?>
                               onchange="toggleCategory(<?= $c['id'] ?>, this.checked)">
                        <span class="toggle-slider"></span>
                      </div>
                    </label>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <button class="btn btn-ghost btn-sm btn-icon"
                              onclick="openEditCategory(<?= $c['id'] ?>,'<?= e(addslashes($c['name'])) ?>','<?= e($c['type']) ?>','<?= e(addslashes($c['icon_name']??'')) ?>')"
                              title="Edit">✏️</button>
                      <button class="btn btn-danger btn-sm btn-icon"
                              onclick="deleteCategory(<?= $c['id'] ?>)" title="Delete">🗑️</button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Solution Tags -->
        <div class="card">
          <div class="card-header"><h2 class="card-title">LeetCode Solution Tags</h2></div>
          <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
              <?php foreach($tags as $t): ?>
              <div style="display:flex;align-items:center;gap:6px;background:var(--input-bg);
                          border:1px solid <?= e($t['color_hex']??"rgba(255,196,0,.14)") ?>33;
                          border-radius:20px;padding:5px 12px;font-size:12px;">
                <span style="width:8px;height:8px;border-radius:50%;background:<?= e($t['color_hex']??'#ffc400') ?>;display:inline-block;flex-shrink:0;"></span>
                <span><?= e($t['name']) ?></span>
                <span style="color:var(--text-dim);font-size:11px;">(<?= $t['usage_count'] ?>)</span>
                <button class="btn btn-danger btn-sm" style="padding:2px 6px;font-size:10px;border-radius:4px;"
                        onclick="deleteTag(<?= $t['id'] ?>)" title="Delete">✕</button>
              </div>
              <?php endforeach; ?>
              <?php if(empty($tags)): ?>
                <p style="color:var(--text-muted);font-size:13px;">No tags yet. Add one above.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<!-- Add Category Modal -->
<div class="modal-overlay" id="addCategoryModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="catModalTitle">Add Category</h2>
      <button class="modal-close" onclick="Modal.close('addCategoryModal')">✕</button>
    </div>
    <form id="categoryForm">
      <div class="modal-body">
        <input type="hidden" name="action" value="save_category">
        <input type="hidden" name="cat_id" id="catId" value="0">
        <?= csrfField() ?>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Category Name <span class="req">*</span></label>
            <input type="text" name="name" id="catName" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Type <span class="req">*</span></label>
            <select name="type" id="catType" class="form-control" required>
              <option value="blog">Blog</option>
              <option value="course">Course</option>
              <option value="leetcode">LeetCode</option>
              <option value="portfolio">Portfolio</option>
            </select>
          </div>
        </div>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Icon (emoji)</label>
            <input type="text" name="icon_name" id="catIcon" class="form-control" maxlength="10" placeholder="📝">
          </div>
          <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" id="catSort" class="form-control" value="0" min="0">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="catDesc" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="Modal.close('addCategoryModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Tag Modal -->
<div class="modal-overlay" id="addTagModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Add LeetCode Tag</h2>
      <button class="modal-close" onclick="Modal.close('addTagModal')">✕</button>
    </div>
    <form id="tagForm">
      <div class="modal-body">
        <input type="hidden" name="action" value="save_tag">
        <?= csrfField() ?>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Tag Name <span class="req">*</span></label>
            <input type="text" name="tag_name" class="form-control" required placeholder="Dynamic Programming">
          </div>
          <div class="form-group">
            <label class="form-label">Color</label>
            <input type="color" name="color_hex" class="form-control" value="#ffc400" style="height:44px;padding:4px;">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="Modal.close('addTagModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Tag</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
function openEditCategory(id, name, type, icon) {
  document.getElementById('catModalTitle').textContent = 'Edit Category';
  document.getElementById('catId').value   = id;
  document.getElementById('catName').value = name;
  document.getElementById('catType').value = type;
  document.getElementById('catIcon').value = icon;
  Modal.open('addCategoryModal');
}

ajaxForm('categoryForm', () => { Modal.close('addCategoryModal'); setTimeout(() => location.reload(), 600); });
ajaxForm('tagForm',      () => { Modal.close('addTagModal');      setTimeout(() => location.reload(), 600); });

async function toggleCategory(id, val) {
  await apiPost('<?= SITE_URL ?>/api/admin/categories.php', { action:'toggle', id, value: val?1:0 });
}
async function deleteCategory(id) {
  if (!confirm('Delete this category? Blogs/courses using it will be uncategorized.')) return;
  const r = await apiPost('<?= SITE_URL ?>/api/admin/categories.php', { action:'delete_category', id });
  if (r.success) { document.querySelector(`[data-id="cat-${id}"]`)?.remove(); Toast.success('Deleted'); }
  else Toast.error('Error', r.error);
}
async function deleteTag(id) {
  if (!confirm('Delete this tag?')) return;
  const r = await apiPost('<?= SITE_URL ?>/api/admin/categories.php', { action:'delete_tag', id });
  if (r.success) location.reload(); else Toast.error('Error', r.error);
}
</script>
</body>
</html>
