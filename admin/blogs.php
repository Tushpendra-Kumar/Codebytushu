<?php
/**
 * CodeByTushu — Admin Blog Management v2
 * Premium list: summary cards, sortable table, filters, bulk actions.
 */
declare(strict_types=1);

$adminSection = 'Blogs';
$adminTitle   = 'Blog Management — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Blog Management'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ══ Params ════════════════════════════════════════════════ */
$perPage  = 15;
$page     = max(1, (int)get('page', '1'));
$search   = trim(get('search', ''));
$status   = get('status', '');
$catId    = (int)get('cat', '0');
$featured = get('featured', '');
$sort     = get('sort', 'created_at');
$dir      = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
$validSort = ['title','view_count','read_time_mins','published_at','created_at','is_featured'];
if (!in_array($sort, $validSort, true)) $sort = 'created_at';

/* ══ WHERE builder ══════════════════════════════════════════ */
$where  = ['1=1'];
$params = [];
if ($search)         { $where[] = '(b.title LIKE ? OR b.excerpt LIKE ? OR b.slug LIKE ?)'; $w="%$search%"; array_push($params,$w,$w,$w); }
if ($status === 'published')   { $where[] = 'b.is_published = 1'; }
if ($status === 'draft')       { $where[] = 'b.is_published = 0'; }
if ($catId)          { $where[] = 'b.category_id = ?'; $params[] = $catId; }
if ($featured === '1') { $where[] = 'b.is_featured = 1'; }
$whereClause = implode(' AND ', $where);

/* ══ Summary counts ════════════════════════════════════════ */
try {
    $sc = [
        'total'     => (int)$pdo->query('SELECT COUNT(*) FROM blog_articles')->fetchColumn(),
        'published' => (int)$pdo->query('SELECT COUNT(*) FROM blog_articles WHERE is_published=1')->fetchColumn(),
        'draft'     => (int)$pdo->query('SELECT COUNT(*) FROM blog_articles WHERE is_published=0')->fetchColumn(),
        'featured'  => (int)$pdo->query('SELECT COUNT(*) FROM blog_articles WHERE is_featured=1')->fetchColumn(),
        'views'     => (int)$pdo->query('SELECT COALESCE(SUM(view_count),0) FROM blog_articles')->fetchColumn(),
    ];
} catch (\Throwable) { $sc=['total'=>0,'published'=>0,'draft'=>0,'featured'=>0,'views'=>0]; }

/* ══ Filtered count & rows ══════════════════════════════════ */
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_articles b WHERE $whereClause");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$pager = paginate($total, $perPage, $page);

$stmt = $pdo->prepare(
    "SELECT b.id, b.title, b.slug, b.thumbnail_path, b.is_published, b.is_featured,
            b.view_count, b.read_time_mins, b.published_at, b.created_at, b.excerpt,
            c.name AS category_name,
            u.full_name AS author_name
       FROM blog_articles b
       LEFT JOIN categories c ON c.id = b.category_id
       LEFT JOIN users u ON u.id = b.author_id
      WHERE $whereClause
      ORDER BY b.$sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$blogs = $stmt->fetchAll();

$allCats = $pdo->query("SELECT id, name FROM categories WHERE type='blog' ORDER BY name")->fetchAll();

/* ══ Sort URL helper ════════════════════════════════════════ */
function bsort(string $col, string $cur, string $dir, array $extra=[]): string {
    return '?'.http_build_query(array_merge($extra,['sort'=>$col,'dir'=>$col===$cur&&$dir==='DESC'?'ASC':'DESC']));
}
$urlX = array_filter(compact('search','status','catId','featured'), fn($v)=>$v!==''&&$v!==0);
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── Summary strip ── */
.blog-summary { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:22px; }
.bsm { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
       padding:16px; text-decoration:none; display:block; cursor:pointer; transition:all var(--transition);
       position:relative; overflow:hidden; }
.bsm:hover,.bsm.af { border-color:var(--accent); }
.bsm.af { background:var(--accent-glow); }
.bsm::after { content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
              background:var(--accent); transform:scaleX(0); transition:.2s; }
.bsm:hover::after,.bsm.af::after { transform:scaleX(1); }
.bsm-val { font-size:22px; font-weight:800; color:var(--text); line-height:1.1; }
.bsm-lbl { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }
/* ── Post card in table ── */
.blog-thumb { width:60px; height:40px; object-fit:cover; border-radius:6px; border:1px solid var(--border); display:block; }
.blog-thumb-ph { width:60px; height:40px; background:var(--border); border-radius:6px;
                 display:flex; align-items:center; justify-content:center; font-size:18px; }
/* ── Pub toggle pill ── */
.pub-pill-wrap { display:flex; align-items:center; gap:8px; cursor:pointer; }
.pub-pill-wrap input { display:none; }
.ppill { width:34px; height:18px; border-radius:9px; background:var(--border);
         position:relative; transition:background .2s; flex-shrink:0; }
.ppill::after { content:''; position:absolute; width:12px; height:12px; background:#fff;
                border-radius:50%; top:3px; left:3px; transition:left .2s; }
.pub-pill-wrap input:checked ~ .ppill { background:var(--accent); }
.pub-pill-wrap input:checked ~ .ppill::after { left:19px; }
.plbl { font-size:11px; font-weight:600; }
/* ── Sort ── */
th a.sl { color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:3px; }
th a.sl:hover { color:var(--accent); }
.sa { font-size:9px; color:var(--text-dim); }
.sa.on { color:var(--accent); }
@media(max-width:900px) { .blog-summary { grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px) { .blog-summary { grid-template-columns:repeat(2,1fr); } }
</style>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">

      <!-- ══ Page Header ════════════════════════════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Blog Management</h1>
          <p class="page-subtitle">
            <?= number_format($sc['published']) ?> published ·
            <?= number_format($sc['draft']) ?> drafts ·
            <?= $sc['views'] >= 1000 ? round($sc['views']/1000,1).'k' : $sc['views'] ?> total views
          </p>
        </div>
        <div class="page-header-actions">
          <a href="<?= SITE_URL ?>/admin/blog-edit.php" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Post
          </a>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- ══ Summary Cards ══════════════════════════════════ -->
      <div class="blog-summary">
        <a href="<?= SITE_URL ?>/admin/blogs.php" class="bsm <?= !$search&&!$status&&!$catId&&$featured===''?'af':'' ?>">
          <div class="bsm-val"><?= number_format($sc['total']) ?></div>
          <div class="bsm-lbl">📝 All Posts</div>
        </a>
        <a href="?status=published" class="bsm <?= $status==='published'?'af':'' ?>">
          <div class="bsm-val" style="color:#22c55e;"><?= number_format($sc['published']) ?></div>
          <div class="bsm-lbl">✓ Published</div>
        </a>
        <a href="?status=draft" class="bsm <?= $status==='draft'?'af':'' ?>">
          <div class="bsm-val" style="color:#f59e0b;"><?= number_format($sc['draft']) ?></div>
          <div class="bsm-lbl">✎ Drafts</div>
        </a>
        <a href="?featured=1" class="bsm <?= $featured==='1'?'af':'' ?>">
          <div class="bsm-val" style="color:var(--accent);"><?= number_format($sc['featured']) ?></div>
          <div class="bsm-lbl">⭐ Featured</div>
        </a>
        <a href="?sort=view_count&dir=DESC" class="bsm">
          <div class="bsm-val"><?= $sc['views'] >= 1000 ? round($sc['views']/1000,1).'k' : $sc['views'] ?></div>
          <div class="bsm-lbl">👁 Total Views</div>
        </a>
      </div>

      <!-- ══ Toolbar ══════════════════════════════════════════ -->
      <div class="card" style="padding:0;border-radius:var(--radius-lg) var(--radius-lg) 0 0;border-bottom:none;">
        <form id="filterForm" method="GET" action="<?= SITE_URL ?>/admin/blogs.php"
              style="display:flex;align-items:center;gap:10px;padding:12px 18px;flex-wrap:wrap;">
          <div class="search-input-wrap" style="flex:1;min-width:200px;">
            <span class="si"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
            <input type="text" name="search" id="blogSearch" placeholder="Search title, slug, excerpt…" value="<?= e($search) ?>" autocomplete="off">
          </div>
          <select name="status" class="form-control" style="width:120px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
            <option value="draft"     <?= $status==='draft'    ?'selected':'' ?>>Draft</option>
          </select>
          <select name="cat" class="form-control" style="width:150px;" onchange="this.form.submit()">
            <option value="0">All Categories</option>
            <?php foreach ($allCats as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $catId===$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="featured" class="form-control" style="width:120px;" onchange="this.form.submit()">
            <option value="">All Posts</option>
            <option value="1" <?= $featured==='1'?'selected':'' ?>>⭐ Featured</option>
          </select>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
          <input type="hidden" name="dir"  value="<?= e($dir) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
          <?php if ($search||$status||$catId||$featured): ?>
            <a href="<?= SITE_URL ?>/admin/blogs.php" class="btn btn-ghost btn-sm">✕ Clear</a>
          <?php endif; ?>
          <span style="margin-left:auto;font-size:12px;color:var(--text-muted);"><?= number_format($total) ?> result<?= $total!==1?'s':'' ?></span>
        </form>
      </div>

      <!-- ══ Table ════════════════════════════════════════════ -->
      <div class="card" style="border-radius:0 0 var(--radius-lg) var(--radius-lg);border-top:none;padding:0;">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:40px;"><input type="checkbox" id="selAll" style="cursor:pointer;accent-color:var(--accent);"></th>
                <th style="width:70px;">Cover</th>
                <?php
                $hdr=[['key'=>'title','label'=>'Post'],['key'=>'','label'=>'Category'],
                      ['key'=>'','label'=>'Author'],['key'=>'view_count','label'=>'Views'],
                      ['key'=>'read_time_mins','label'=>'Read'],['key'=>'published_at','label'=>'Published'],
                      ['key'=>'is_featured','label'=>'Featured'],['key'=>'is_published','label'=>'Status']];
                foreach($hdr as $h):$ia=$sort===$h['key']&&$h['key'];?>
                <th><?php if($h['key']): ?>
                  <a class="sl" href="<?= bsort($h['key'],$sort,$dir,array_filter($urlX)) ?>"><?= $h['label'] ?>
                    <span class="sa <?= $ia?'on':'' ?>"><?= $ia?($dir==='DESC'?'▼':'▲'):'⇅' ?></span></a>
                <?php else: echo $h['label']; endif; ?></th>
                <?php endforeach; ?>
                <th style="text-align:right;width:100px;">Actions</th>
              </tr>
            </thead>
            <tbody id="blogsBody">
              <?php foreach ($blogs as $b): ?>
              <tr data-id="<?= $b['id'] ?>">
                <td><input type="checkbox" class="cb-row" value="<?= $b['id'] ?>" style="cursor:pointer;accent-color:var(--accent);"></td>
                <td>
                  <?php if ($b['thumbnail_path']): ?>
                    <img src="<?= e($b['thumbnail_path']) ?>" class="blog-thumb" alt="">
                  <?php else: ?>
                    <div class="blog-thumb-ph">📝</div>
                  <?php endif; ?>
                </td>
                <td style="max-width:280px;">
                  <div style="font-weight:600;font-size:13px;color:var(--text);"><?= e(truncate($b['title'],48)) ?></div>
                  <div style="font-size:10px;color:var(--text-dim);font-family:monospace;margin-top:2px;">/blog/<?= e($b['slug']) ?></div>
                  <?php if ($b['excerpt']): ?><div style="font-size:11px;color:var(--text-muted);margin-top:3px;line-height:1.4;"><?= e(truncate($b['excerpt'],80)) ?></div><?php endif; ?>
                </td>
                <td><span class="badge badge-muted" style="font-size:10px;"><?= e($b['category_name']??'—') ?></span></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= e($b['author_name']??'—') ?></td>
                <td style="color:var(--text-muted);font-size:12px;"><?= number_format($b['view_count']) ?></td>
                <td style="font-size:11px;color:var(--text-muted);"><?= $b['read_time_mins'] ? $b['read_time_mins'].' min' : '—' ?></td>
                <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;">
                  <?= $b['published_at'] ? e(formatDate($b['published_at'],'d M Y')) : '<span style="color:var(--text-dim)">—</span>' ?>
                </td>
                <td>
                  <label class="pub-pill-wrap" title="Toggle featured">
                    <input type="checkbox" <?= $b['is_featured']?'checked':'' ?> onchange="toggleFeat(<?= $b['id'] ?>, this.checked, this)">
                    <div class="ppill"></div>
                  </label>
                </td>
                <td>
                  <label class="pub-pill-wrap" title="Toggle publish/draft">
                    <input type="checkbox" <?= $b['is_published']?'checked':'' ?> onchange="togglePub(<?= $b['id'] ?>, this.checked, this)">
                    <div class="ppill"></div>
                    <span class="plbl" id="pub-lbl-<?= $b['id'] ?>"
                          style="color:<?= $b['is_published']?'var(--success)':'var(--text-muted)' ?>">
                      <?= $b['is_published']?'Live':'Draft' ?>
                    </span>
                  </label>
                </td>
                <td>
                  <div style="display:flex;gap:4px;justify-content:flex-end;">
                    <a href="<?= SITE_URL ?>/admin/blog-edit.php?id=<?= $b['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </a>
                    <?php if ($b['is_published']): ?>
                    <a href="/blog/<?= e($b['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm btn-icon" title="View live">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteBlog(<?= $b['id'] ?>, '<?= e(addslashes($b['title'])) ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($blogs)): ?>
              <tr><td colspan="11">
                <div class="empty-state">
                  <div class="empty-icon">📝</div>
                  <p class="empty-title">No blog posts found</p>
                  <p class="empty-desc">
                    <?= ($search||$status||$catId)?'Try clearing your filters. ': '' ?>
                    <a href="<?= SITE_URL ?>/admin/blog-edit.php" style="color:var(--accent);">Write your first post →</a>
                  </p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php
        $queryParams = array_filter(compact('search', 'status', 'catId', 'featured', 'sort', 'dir'),
            fn($v) => $v !== '' && $v !== 0);
        require __DIR__ . '/includes/pagination.php';
        ?>
      </div>

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ── Checkbox select-all ── */
const selAll = document.getElementById('selAll');
selAll.addEventListener('change', () => {
  document.querySelectorAll('.cb-row').forEach(cb => cb.checked = selAll.checked);
});

/* ── Toggle publish ── */
async function togglePub(id, pub, cb) {
  const lbl = document.getElementById(`pub-lbl-${id}`);
  const r = await apiPost('<?= SITE_URL ?>/api/admin/blogs.php', { action:'toggle_publish', id, value: pub?1:0 });
  if (r.success) {
    Toast.success('Updated', pub ? 'Post published.' : 'Set to draft.');
    if (lbl) { lbl.textContent = pub ? 'Live' : 'Draft'; lbl.style.color = pub ? 'var(--success)' : 'var(--text-muted)'; }
  } else { Toast.error('Error', r.error); cb.checked = !pub; }
}

/* ── Toggle featured ── */
async function toggleFeat(id, val, cb) {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/blogs.php', { action:'toggle_featured', id, value: val?1:0 });
  if (r.success) Toast.success('Updated', val ? '⭐ Marked featured.' : 'Removed from featured.');
  else { Toast.error('Error', r.error); cb.checked = !val; }
}

/* ── Delete ── */
async function deleteBlog(id, title) {
  Modal.confirm(
    `Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">This cannot be undone.</small>`,
    async () => {
      const r = await apiPost('<?= SITE_URL ?>/api/admin/blogs.php', { action:'delete', id });
      if (r.success) { document.querySelector(`[data-id="${id}"]`)?.remove(); Toast.success('Deleted', 'Post removed.'); }
      else Toast.error('Error', r.error);
    },
    { title:'Delete Blog Post', confirmText:'Delete', type:'danger' }
  );
}

/* ── Live search ── */
let st;
document.getElementById('blogSearch').addEventListener('input', function() {
  clearTimeout(st); st = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});
</script>
</body>
</html>
