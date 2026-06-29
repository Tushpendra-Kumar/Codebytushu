<?php
/**
 * CodeByTushu — Admin LeetCode CMS v2
 * Premium list page: summary cards, sortable table, filters, bulk actions.
 */
declare(strict_types=1);

$adminSection = 'LeetCode';
$adminTitle   = 'LeetCode CMS — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'LeetCode CMS'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ══ Params ════════════════════════════════════════════════ */
$perPage  = 20;
$page     = max(1, (int)get('page', '1'));
$search   = trim(get('search', ''));
$diff     = get('difficulty', '');
$status   = get('status', '');
$tag      = (int)get('tag', '0');
$platform = get('platform', '');
$sort     = get('sort', 'solution_date');
$dir      = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

$sortCols = ['problem_number','problem_title','difficulty','is_published','view_count','solution_date','created_at'];
if (!in_array($sort, $sortCols, true)) $sort = 'solution_date';

/* ══ Filters ══════════════════════════════════════════════ */
$where  = ['1=1'];
$params = [];
if ($search)   { $where[] = '(s.problem_title LIKE ? OR s.slug LIKE ? OR s.problem_number LIKE ?)'; $w="%$search%"; $params=array_merge($params,[$w,$w,$w]); }
if ($diff)     { $where[] = 's.difficulty = ?';   $params[] = $diff; }

if ($status === 'published')   { $where[] = 's.is_published = 1'; }
if ($status === 'draft')       { $where[] = 's.is_published = 0'; }
if ($tag > 0)  { $where[] = 'EXISTS (SELECT 1 FROM solution_tag_map tm WHERE tm.solution_id=s.id AND tm.tag_id=?)'; $params[] = $tag; }

$whereClause = implode(' AND ', $where);

/* ══ Summary stats ════════════════════════════════════════ */
try {
    $counts = [
        'total'     => (int)$pdo->query('SELECT COUNT(*) FROM leetcode_solutions')->fetchColumn(),
        'published' => (int)$pdo->query('SELECT COUNT(*) FROM leetcode_solutions WHERE is_published=1')->fetchColumn(),
        'draft'     => (int)$pdo->query('SELECT COUNT(*) FROM leetcode_solutions WHERE is_published=0')->fetchColumn(),
        'easy'      => (int)$pdo->query("SELECT COUNT(*) FROM leetcode_solutions WHERE difficulty='Easy'")->fetchColumn(),
        'medium'    => (int)$pdo->query("SELECT COUNT(*) FROM leetcode_solutions WHERE difficulty='Medium'")->fetchColumn(),
        'hard'      => (int)$pdo->query("SELECT COUNT(*) FROM leetcode_solutions WHERE difficulty='Hard'")->fetchColumn(),
        'featured'  => (int)$pdo->query("SELECT COUNT(*) FROM leetcode_solutions WHERE is_featured=1")->fetchColumn(),
        'views'     => (int)$pdo->query("SELECT SUM(view_count) FROM leetcode_solutions")->fetchColumn(),
    ];
} catch (\Throwable) {
    $counts = ['total'=>0,'published'=>0,'draft'=>0,'easy'=>0,'medium'=>0,'hard'=>0,'featured'=>0,'views'=>0];
}

/* ══ Paginated rows ═══════════════════════════════════════ */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leetcode_solutions s WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = paginate($total, $perPage, $page);

$stmt = $pdo->prepare(
    "SELECT s.id, s.problem_number, s.problem_title, s.slug, s.difficulty, 
            s.is_published, s.is_featured, s.view_count, s.solution_date,
            s.time_complexity, s.space_complexity, s.youtube_url, s.youtube_thumbnail AS thumbnail_path,
            (SELECT COUNT(*) FROM solution_code_blocks cb WHERE cb.solution_id=s.id) AS code_count,
            (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ',')
               FROM solution_tag_map tm JOIN solution_tags t ON t.id=tm.tag_id
              WHERE tm.solution_id=s.id LIMIT 3) AS tag_names
       FROM leetcode_solutions s
      WHERE $whereClause
      ORDER BY s.$sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$solutions = $stmt->fetchAll();

/* ══ Sidebar data ═════════════════════════════════════════ */
$allTags  = $pdo->query('SELECT id, name, color_hex FROM solution_tags WHERE is_active=1 ORDER BY usage_count DESC LIMIT 30')->fetchAll();

/* ══ Helper ════════════════════════════════════════════════ */
function lsortUrl(string $col, string $cur, string $dir, array $extra=[]): string {
    $nd = ($col===$cur && $dir==='DESC') ? 'ASC' : 'DESC';
    return '?'.http_build_query(array_merge($extra,['sort'=>$col,'dir'=>$nd]));
}
$urlX = array_filter(compact('search','diff','status','tag','platform'));
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── Summary cards ── */
.lc-summary { display:grid; grid-template-columns:repeat(4,1fr) repeat(4,1fr); gap:12px; margin-bottom:22px; }
.lc-sm-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
              padding:14px 16px; cursor:pointer; transition:all var(--transition); text-decoration:none;
              display:block; position:relative; overflow:hidden; }
.lc-sm-card:hover,.lc-sm-card.af { border-color:var(--accent); }
.lc-sm-card.af { background:var(--accent-glow); }
.lc-sm-card::before { content:''; position:absolute; bottom:0; left:0; right:0; height:2px; background:var(--accent); transform:scaleX(0); transition:transform .2s; }
.lc-sm-card.af::before,.lc-sm-card:hover::before { transform:scaleX(1); }
.lc-sm-val { font-size:20px; font-weight:800; color:var(--text); line-height:1.1; }
.lc-sm-lbl { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }

/* ── Difficulty badges ── */
.badge-easy   { background:rgba(34,197,94,.12); color:#22c55e; }
.badge-medium { background:rgba(245,158,11,.12); color:#f59e0b; }
.badge-hard   { background:rgba(239,68,68,.12);  color:#ef4444; }

/* ── Platform badge ── */
.badge-platform { background:rgba(59,130,246,.12); color:#3b82f6; }

/* ── Sort link ── */
th a.slnk { color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:3px; white-space:nowrap; }
th a.slnk:hover { color:var(--accent); }
.sa { font-size:9px; color:var(--text-dim); }
.sa.on { color:var(--accent); }

/* ── Tags mini ── */
.tag-mini { display:inline-flex; align-items:center; gap:3px; background:var(--input-bg);
            border:1px solid var(--border); border-radius:4px; padding:2px 7px;
            font-size:10px; color:var(--text-muted); white-space:nowrap; }

/* ── Thumbnail thumb ── */
.sol-thumb { width:40px; height:28px; border-radius:4px; object-fit:cover;
             border:1px solid var(--border); display:block; }
.sol-thumb-ph { width:40px; height:28px; border-radius:4px; background:var(--border);
                display:flex; align-items:center; justify-content:center; font-size:10px;
                color:var(--text-dim); }
/* ── Complexity chip ── */
.cx-chip { font-size:10px; font-family:monospace; color:var(--text-muted);
           background:var(--input-bg); padding:1px 6px; border-radius:4px;
           border:1px solid var(--border); white-space:nowrap; }

/* ── Inline toggle ── */
.pub-toggle { display:flex; align-items:center; gap:6px; }
.pub-toggle input { display:none; }
.pub-pill { width:36px; height:20px; border-radius:10px; background:var(--border); cursor:pointer;
            position:relative; transition:background .2s; flex-shrink:0; }
.pub-pill::after { content:''; position:absolute; width:14px; height:14px; border-radius:50%;
                   background:#fff; top:3px; left:3px; transition:left .2s; }
.pub-toggle input:checked ~ .pub-pill { background:var(--accent); }
.pub-toggle input:checked ~ .pub-pill::after { left:19px; }
.pub-label { font-size:11px; font-weight:600; }

@media(max-width:1100px) { .lc-summary { grid-template-columns:repeat(4,1fr); } }
@media(max-width:700px)  { .lc-summary { grid-template-columns:repeat(2,1fr); } }
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
          <h1 class="page-title">LeetCode CMS</h1>
          <p class="page-subtitle">
            <?= number_format($counts['published']) ?> published ·
            <?= number_format($counts['draft']) ?> drafts ·
            <?= number_format($counts['views']) ?> total views
          </p>
        </div>
        <div class="page-header-actions">
          <a href="<?= SITE_URL ?>/admin/leetcode-edit.php" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Problem
          </a>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- ══ Summary Cards ══════════════════════════════════ -->
      <div class="lc-summary">
        <a href="<?= SITE_URL ?>/admin/leetcode.php" class="lc-sm-card <?= !$diff && !$status ? 'af' : '' ?>">
          <div class="lc-sm-val"><?= number_format($counts['total']) ?></div>
          <div class="lc-sm-lbl">Total Problems</div>
        </a>
        <a href="?status=published" class="lc-sm-card <?= $status==='published' ? 'af' : '' ?>">
          <div class="lc-sm-val" style="color:#22c55e;"><?= number_format($counts['published']) ?></div>
          <div class="lc-sm-lbl">Published</div>
        </a>
        <a href="?status=draft" class="lc-sm-card <?= $status==='draft' ? 'af' : '' ?>">
          <div class="lc-sm-val" style="color:#f59e0b;"><?= number_format($counts['draft']) ?></div>
          <div class="lc-sm-lbl">Drafts</div>
        </a>
        <a href="?difficulty=Easy" class="lc-sm-card <?= $diff==='Easy' ? 'af' : '' ?>">
          <div class="lc-sm-val" style="color:#22c55e;"><?= number_format($counts['easy']) ?></div>
          <div class="lc-sm-lbl">Easy</div>
        </a>
        <a href="?difficulty=Medium" class="lc-sm-card <?= $diff==='Medium' ? 'af' : '' ?>">
          <div class="lc-sm-val" style="color:#f59e0b;"><?= number_format($counts['medium']) ?></div>
          <div class="lc-sm-lbl">Medium</div>
        </a>
        <a href="?difficulty=Hard" class="lc-sm-card <?= $diff==='Hard' ? 'af' : '' ?>">
          <div class="lc-sm-val" style="color:#ef4444;"><?= number_format($counts['hard']) ?></div>
          <div class="lc-sm-lbl">Hard</div>
        </a>
        <a href="?sort=is_featured&dir=DESC" class="lc-sm-card">
          <div class="lc-sm-val" style="color:var(--accent);"><?= number_format($counts['featured']) ?></div>
          <div class="lc-sm-lbl">⭐ Featured</div>
        </a>
        <a href="?sort=view_count&dir=DESC" class="lc-sm-card">
          <div class="lc-sm-val"><?= $counts['views'] >= 1000 ? round($counts['views']/1000,1).'k' : $counts['views'] ?></div>
          <div class="lc-sm-lbl">👁 Total Views</div>
        </a>
      </div>

      <!-- ══ Toolbar ══════════════════════════════════════════ -->
      <div class="card" style="padding:0;border-radius:var(--radius-lg) var(--radius-lg) 0 0;border-bottom:none;">
        <form id="filterForm" method="GET" action="<?= SITE_URL ?>/admin/leetcode.php"
              style="display:flex;align-items:center;gap:10px;padding:12px 18px;flex-wrap:wrap;">
          <div class="search-input-wrap" style="flex:1;min-width:200px;">
            <span class="si">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" name="search" id="lcSearch" placeholder="Search problems, #number, slug…"
                   value="<?= e($search) ?>" autocomplete="off">
          </div>
          <select name="difficulty" class="form-control" style="width:120px;" onchange="this.form.submit()">
            <option value="">All Difficulty</option>
            <option value="Easy"   <?= $diff==='Easy'   ? 'selected' : '' ?>>Easy</option>
            <option value="Medium" <?= $diff==='Medium' ? 'selected' : '' ?>>Medium</option>
            <option value="Hard"   <?= $diff==='Hard'   ? 'selected' : '' ?>>Hard</option>
          </select>
          <select name="status" class="form-control" style="width:120px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="published" <?= $status==='published' ? 'selected' : '' ?>>Published</option>
            <option value="draft"     <?= $status==='draft'     ? 'selected' : '' ?>>Draft</option>
          </select>
          <select name="tag" class="form-control" style="width:140px;" onchange="this.form.submit()">
            <option value="">All Tags</option>
            <?php foreach ($allTags as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $tag===$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
          <input type="hidden" name="dir"  value="<?= e($dir) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
          <?php if ($search || $diff || $status || $tag): ?>
            <a href="<?= SITE_URL ?>/admin/leetcode.php" class="btn btn-ghost btn-sm">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Clear
            </a>
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
                <th style="width:44px;">
                  <input type="checkbox" id="selAll" style="cursor:pointer;accent-color:var(--accent);" title="Select all">
                </th>
                <?php
                $cols=[['key'=>'problem_number','label'=>'#'],['key'=>'problem_title','label'=>'Problem'],
                       ['key'=>'difficulty','label'=>'Diff'],['key'=>'','label'=>'Complexity'],
                       ['key'=>'','label'=>'Tags'],['key'=>'view_count','label'=>'Views'],
                       ['key'=>'solution_date','label'=>'Date'],['key'=>'is_published','label'=>'Status']];
                foreach($cols as $c):
                  $ia=$sort===$c['key']&&$c['key'];
                ?>
                <th><?php if($c['key']): ?><a class="slnk" href="<?= lsortUrl($c['key'],$sort,$dir,array_filter($urlX)) ?>">
                  <?= $c['label'] ?><span class="sa <?= $ia?'on':'' ?>"><?= $ia?($dir==='DESC'?'▼':'▲'):'⇅' ?></span></a>
                <?php else: echo $c['label']; endif; ?></th>
                <?php endforeach; ?>
                <th style="text-align:right;width:110px;">Actions</th>
              </tr>
            </thead>
            <tbody id="lcBody">
              <?php foreach ($solutions as $s): ?>
              <?php
                $dClass = match($s['difficulty']) { 'Easy'=>'easy', 'Medium'=>'medium', 'Hard'=>'hard', default=>'muted' };
                $tags   = $s['tag_names'] ? array_slice(explode(',', $s['tag_names']), 0, 3) : [];
              ?>
              <tr data-id="<?= $s['id'] ?>">
                <td><input type="checkbox" class="cb-row" value="<?= $s['id'] ?>" style="cursor:pointer;accent-color:var(--accent);"></td>
                <td>
                  <span style="font-weight:700;font-size:13px;color:var(--accent);">
                    <?= $s['problem_number'] ? '#'.str_pad((string)$s['problem_number'],4,'0',STR_PAD_LEFT) : '—' ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <?php if ($s['thumbnail_path']): ?>
                      <img src="<?= e($s['thumbnail_path']) ?>" class="sol-thumb" alt="">
                    <?php else: ?>
                      <div class="sol-thumb-ph">💡</div>
                    <?php endif; ?>
                    <div>
                      <div style="font-weight:600;font-size:13px;">
                        <?= e(truncate($s['problem_title'], 38)) ?>
                        <?php if ($s['is_featured']): ?>
                          <span title="Featured" style="color:var(--accent);font-size:11px;">⭐</span>
                        <?php endif; ?>
                        <?php if ($s['youtube_url']): ?>
                          <span title="Has video" style="font-size:11px;">▶️</span>
                        <?php endif; ?>
                      </div>
                      <div style="font-size:10px;color:var(--text-dim);font-family:monospace;"><?= e($s['slug']) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge badge-<?= $dClass ?>"><?= e($s['difficulty']) ?></span></td>
                <td>
                  <?php if ($s['time_complexity'] || $s['space_complexity']): ?>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                      <?php if ($s['time_complexity']): ?><span class="cx-chip" title="Time">T: <?= e($s['time_complexity']) ?></span><?php endif; ?>
                      <?php if ($s['space_complexity']): ?><span class="cx-chip" title="Space">S: <?= e($s['space_complexity']) ?></span><?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span style="color:var(--text-dim);font-size:11px;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;flex-wrap:wrap;gap:3px;max-width:140px;">
                    <?php foreach ($tags as $tn): ?>
                      <span class="tag-mini"><?= e(trim($tn)) ?></span>
                    <?php endforeach; ?>
                    <?php if (empty($tags)): ?><span style="color:var(--text-dim);font-size:11px;">—</span><?php endif; ?>
                  </div>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= number_format($s['view_count']) ?></td>
                <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;"><?= e($s['solution_date']) ?></td>
                <td>
                  <label class="pub-toggle" title="<?= $s['is_published'] ? 'Published — click to unpublish' : 'Draft — click to publish' ?>">
                    <input type="checkbox" <?= $s['is_published'] ? 'checked' : '' ?>
                           onchange="togglePublish(<?= $s['id'] ?>, this.checked, this)">
                    <div class="pub-pill"></div>
                    <span class="pub-label" id="pub-lbl-<?= $s['id'] ?>"
                          style="color:<?= $s['is_published'] ? 'var(--success)' : 'var(--text-muted)' ?>">
                      <?= $s['is_published'] ? 'Live' : 'Draft' ?>
                    </span>
                  </label>
                </td>
                <td>
                  <div style="display:flex;gap:4px;justify-content:flex-end;">
                    <!-- Edit -->
                    <a href="<?= SITE_URL ?>/admin/leetcode-edit.php?id=<?= $s['id'] ?>"
                       class="btn btn-ghost btn-sm btn-icon" title="Edit">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </a>
                    <!-- View live (if published) -->
                    <?php if ($s['is_published']): ?>
                    <a href="<?= SITE_URL ?>/leetcode/problem/<?= e($s['slug']) ?>" target="_blank" rel="noopener"
                       class="btn btn-ghost btn-sm btn-icon" title="View live">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <?php endif; ?>
                    <!-- Delete -->
                    <button class="btn btn-danger btn-sm btn-icon" title="Delete"
                            onclick="deleteSolution(<?= $s['id'] ?>, '<?= e(addslashes($s['problem_title'])) ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($solutions)): ?>
              <tr><td colspan="10">
                <div class="empty-state">
                  <div class="empty-icon">💡</div>
                  <p class="empty-title">No solutions found</p>
                  <p class="empty-desc">
                    <?= ($search||$diff||$status||$tag) ? 'Try clearing your filters.' : '' ?>
                    <a href="<?= SITE_URL ?>/admin/leetcode-edit.php" style="color:var(--accent);">Add your first solution →</a>
                  </p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php
        $queryParams = array_filter(compact('search', 'diff', 'status', 'tag', 'sort', 'dir'),
            fn($v) => $v !== '' && $v !== 0);
        require __DIR__ . '/includes/pagination.php';
        ?>
      </div>

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ── Publish toggle ── */
async function togglePublish(id, publish, checkbox) {
  const lbl = document.getElementById(`pub-lbl-${id}`);
  const r = await apiPost('<?= SITE_URL ?>/api/admin/leetcode.php', {
    action: 'toggle_publish', id, value: publish ? 1 : 0
  });
  if (r.success) {
    Toast.success('Updated', publish ? 'Published.' : 'Set to draft.');
    if (lbl) { lbl.textContent = publish ? 'Live' : 'Draft'; lbl.style.color = publish ? 'var(--success)' : 'var(--text-muted)'; }
  } else {
    Toast.error('Error', r.error);
    checkbox.checked = !publish; // revert
  }
}

/* ── Delete ── */
async function deleteSolution(id, title) {
  Modal.confirm(`Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted);">This removes all code blocks, tags, and analytics data.</small>`, async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/leetcode.php', { action: 'delete', id });
    if (r.success) {
      document.querySelector(`[data-id="${id}"]`)?.remove();
      Toast.success('Deleted', 'Solution removed.');
    } else Toast.error('Error', r.error);
  }, { title: 'Delete Solution', confirmText: 'Delete', type: 'danger' });
}

/* ── Live search ── */
let st;
document.getElementById('lcSearch').addEventListener('input', function() {
  clearTimeout(st); st = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});
</script>
</body>
</html>
