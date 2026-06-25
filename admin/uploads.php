<?php
/**
 * CodeByTushu — Media Library v2
 * Premium grid/list media manager with drag-drop upload, preview
 * lightbox, copy URL, bulk actions, and per-type filtering.
 */
declare(strict_types=1);

$adminSection = 'Uploads';
$adminTitle   = 'Media Library — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Media Library'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ━━ Query params ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$perPage = 40;
$page    = max(1, (int)get('page', '1'));
$search  = trim(get('search', ''));
$cat     = get('cat', '');
$sort    = get('sort', 'uploaded_at');
$dir     = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

$validCats  = ['image','video','pdf','zip','code','doc','other'];
$validSorts = ['original_name','file_size','uploaded_at'];
if (!in_array($cat,  $validCats,  true)) $cat  = '';
if (!in_array($sort, $validSorts, true)) $sort  = 'uploaded_at';

/* ━━ WHERE clause ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$where  = ['f.is_active = 1'];
$params = [];
if ($search) {
    $where[]  = '(f.original_name LIKE ? OR f.context LIKE ? OR f.file_type LIKE ?)';
    $w = "%$search%"; array_push($params, $w, $w, $w);
}
if ($cat) { $where[] = 'f.file_category = ?'; $params[] = $cat; }
$wc = implode(' AND ', $where);

/* ━━ Stats ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
try {
    $stats = $pdo->query(
        "SELECT file_category, COUNT(*) AS cnt, COALESCE(SUM(file_size),0) AS sz
           FROM file_uploads WHERE is_active=1 GROUP BY file_category"
    )->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_GROUP);

    $totalFiles = (int)$pdo->query('SELECT COUNT(*) FROM file_uploads WHERE is_active=1')->fetchColumn();
    $totalSize  = (int)$pdo->query('SELECT COALESCE(SUM(file_size),0) FROM file_uploads WHERE is_active=1')->fetchColumn();

    $byCat = [];
    foreach ($pdo->query("SELECT file_category,COUNT(*) as c,COALESCE(SUM(file_size),0) as s FROM file_uploads WHERE is_active=1 GROUP BY file_category")->fetchAll() as $r) {
        $byCat[$r['file_category']] = ['count'=>(int)$r['c'],'size'=>(int)$r['s']];
    }
} catch (\Throwable) {
    $totalFiles = $totalSize = 0; $byCat = [];
}

/* ━━ Paginated data ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$cs = $pdo->prepare("SELECT COUNT(*) FROM file_uploads f WHERE $wc");
$cs->execute($params); $total = (int)$cs->fetchColumn();
$pager  = paginate($total, $perPage, $page);

$stmt = $pdo->prepare(
    "SELECT f.*, u.full_name AS uploader
       FROM file_uploads f
       LEFT JOIN users u ON u.id = f.uploaded_by
      WHERE $wc ORDER BY f.$sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$files = $stmt->fetchAll();

/* ━━ Helpers ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function catIcon(string $cat, string $mime = ''): string {
    return match($cat) {
        'image'  => '🖼️',
        'video'  => '🎬',
        'pdf'    => '📄',
        'zip'    => '📦',
        'code'   => '💻',
        'doc'    => '📝',
        default  => '📁',
    };
}
function catColor(string $cat): string {
    return match($cat) {
        'image'  => '#3b82f6',
        'video'  => '#ef4444',
        'pdf'    => '#f59e0b',
        'zip'    => '#8b5cf6',
        'code'   => '#22c55e',
        'doc'    => '#06b6d4',
        default  => 'var(--text-muted)',
    };
}
function isImage(string $mime): bool {
    return str_starts_with($mime, 'image/');
}
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ━━ Layout ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.ml-layout    { display:grid; grid-template-columns:220px 1fr; gap:18px; align-items:start; }
.ml-sidebar   { position:sticky; top:16px; }
.ml-main      { min-width:0; }

/* ━━ Sidebar filter pills ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.cat-list     { list-style:none; margin:0; padding:0; }
.cat-list li  { margin-bottom:2px; }
.cat-pill     { display:flex; align-items:center; justify-content:space-between;
                padding:8px 12px; border-radius:var(--radius-sm); cursor:pointer;
                text-decoration:none; color:var(--text-muted); font-size:13px;
                font-weight:500; transition:.15s; border:none; background:none;
                width:100%; text-align:left; }
.cat-pill:hover  { background:var(--bg-hover); color:var(--text); }
.cat-pill.active { background:var(--accent-glow); color:var(--accent); border-left:3px solid var(--accent); border-radius:0 var(--radius-sm) var(--radius-sm) 0; }
.cat-pill .ci    { display:flex; align-items:center; gap:8px; }
.cat-badge   { font-size:10px; background:var(--border); border-radius:99px;
               padding:2px 7px; font-weight:600; color:var(--text-muted); }
.cat-pill.active .cat-badge { background:var(--accent); color:#000; }

/* ━━ Drop Zone ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.drop-zone   { border:2px dashed var(--border); border-radius:var(--radius-lg);
               padding:22px 16px; text-align:center; cursor:pointer; transition:.2s;
               background:var(--input-bg); position:relative; }
.drop-zone.drag-over { border-color:var(--accent); background:var(--accent-glow); transform:scale(1.01); }
.drop-zone input { display:none; }
.dz-icon { font-size:28px; margin-bottom:8px; }
.dz-text { font-size:12px; font-weight:600; color:var(--text-muted); }
.dz-hint { font-size:10px; color:var(--text-dim); margin-top:4px; }

/* ━━ Upload queue ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.uq-item  { background:var(--input-bg); border:1px solid var(--border); border-radius:var(--radius-sm);
            padding:8px 12px; margin-bottom:6px; font-size:12px; }
.uq-name  { font-weight:500; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
.uq-bar   { height:3px; border-radius:2px; background:var(--border); margin-top:6px; overflow:hidden; }
.uq-fill  { height:100%; background:var(--accent); width:0%; transition:width .2s; }
.uq-status{ font-size:10px; color:var(--text-muted); }

/* ━━ Toolbar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.ml-toolbar { display:flex; align-items:center; gap:10px; padding:12px 16px;
              background:var(--card-bg); border:1px solid var(--border);
              border-radius:var(--radius) var(--radius) 0 0; flex-wrap:wrap; }
.view-btns  { display:flex; gap:3px; flex-shrink:0; }
.vb { width:32px; height:32px; border:1px solid var(--border); border-radius:6px;
      background:none; cursor:pointer; color:var(--text-muted); font-size:14px;
      display:grid; place-items:center; transition:.15s; }
.vb:hover   { background:var(--bg-hover); color:var(--text); }
.vb.active  { background:var(--accent-glow); border-color:var(--accent); color:var(--accent); }

/* ━━ Grid view ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.media-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; padding:14px; }
.media-card { border:1.5px solid var(--border); border-radius:var(--radius); overflow:hidden;
              background:var(--input-bg); cursor:pointer; transition:all .15s; position:relative; }
.media-card:hover { border-color:var(--accent); transform:translateY(-1px);
                    box-shadow:0 4px 16px rgba(0,0,0,.3); }
.media-card.sel   { border-color:var(--accent); background:var(--accent-glow); }
.media-card .sel-box { position:absolute; top:7px; left:7px; width:18px; height:18px;
                       border-radius:4px; background:var(--accent); display:none;
                       align-items:center; justify-content:center; z-index:2;
                       color:#000; font-size:11px; font-weight:700; border:2px solid var(--accent); }
.media-card.sel .sel-box { display:flex; }
.mc-thumb  { width:100%; aspect-ratio:4/3; object-fit:cover; display:block; }
.mc-icon   { width:100%; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;
             font-size:36px; background:var(--card-bg); }
.mc-info   { padding:8px 10px; }
.mc-name   { font-size:11px; font-weight:500; color:var(--text); overflow:hidden;
             text-overflow:ellipsis; white-space:nowrap; }
.mc-meta   { font-size:10px; color:var(--text-dim); margin-top:2px; display:flex; gap:6px; }
.mc-badge  { display:inline-block; font-size:9px; font-weight:700; border-radius:3px;
             padding:1px 5px; text-transform:uppercase; }
.mc-actions { position:absolute; top:6px; right:6px; display:flex; gap:4px;
              opacity:0; transition:.15s; }
.media-card:hover .mc-actions { opacity:1; }
.mc-btn    { width:26px; height:26px; border-radius:5px; border:none; cursor:pointer;
             display:grid; place-items:center; font-size:11px; transition:.15s; }
.mc-btn-copy { background:rgba(255,255,255,.15); color:#fff; backdrop-filter:blur(4px); }
.mc-btn-prev { background:rgba(255,255,255,.15); color:#fff; backdrop-filter:blur(4px); }
.mc-btn-del  { background:rgba(239,68,68,.85); color:#fff; }
.mc-btn:hover { transform:scale(1.1); }

/* ━━ List view ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
#listView { display:none; }

/* ━━ Preview Lightbox ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.lightbox { position:fixed; inset:0; background:rgba(0,0,0,.88); z-index:1100;
            display:flex; align-items:center; justify-content:center; padding:16px; }
.lightbox.hidden { display:none; }
.lb-box   { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-lg);
            max-width:760px; width:100%; max-height:90vh; overflow:auto; }
.lb-hd    { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border); }
.lb-hd h3 { font-size:14px; font-weight:600; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.lb-body  { padding:20px; }
.lb-img   { width:100%; max-height:400px; object-fit:contain; border-radius:var(--radius); background:#000; display:block; }
.lb-icon  { width:100%; height:200px; display:flex; align-items:center; justify-content:center; font-size:72px; background:var(--input-bg); border-radius:var(--radius); }
.lb-meta  { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:16px; }
.lb-row   { font-size:12px; display:flex; flex-direction:column; gap:2px; }
.lb-lbl   { color:var(--text-dim); font-size:10px; text-transform:uppercase; letter-spacing:.5px; }
.lb-val   { color:var(--text); font-weight:500; word-break:break-all; }
.lb-url   { background:var(--input-bg); border:1px solid var(--border); border-radius:var(--radius-sm);
            padding:8px 12px; font-size:11px; font-family:monospace; color:var(--accent);
            word-break:break-all; margin-top:12px; cursor:text; user-select:all; }
.lb-ft    { padding:14px 20px; border-top:1px solid var(--border); display:flex; gap:8px; justify-content:flex-end; }

/* ━━ Bulk bar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.bulk-bar { position:fixed; bottom:22px; left:50%; transform:translateX(-50%);
            background:var(--card-bg); border:1px solid var(--accent); border-radius:99px;
            padding:10px 20px; display:flex; align-items:center; gap:12px; z-index:900;
            box-shadow:0 8px 32px rgba(0,0,0,.4); transition:all .25s;
            opacity:0; pointer-events:none; }
.bulk-bar.show { opacity:1; pointer-events:all; }
.bulk-cnt { font-size:13px; font-weight:700; color:var(--accent); }
.bulk-sep { width:1px; height:18px; background:var(--border); }

/* ━━ Stats strip ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.sz-strip { display:flex; gap:0; background:var(--card-bg); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden; margin-bottom:16px; }
.sz-seg   { flex:1; padding:12px 14px; border-right:1px solid var(--border); }
.sz-seg:last-child { border-right:none; }
.sz-num   { font-size:18px; font-weight:800; color:var(--text); line-height:1; }
.sz-lbl   { font-size:10px; color:var(--text-muted); margin-top:3px; text-transform:uppercase; }

/* ━━ Upload progress list ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
#uploadQueue { margin-top:12px; max-height:200px; overflow-y:auto; }

/* ━━ Context chip ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.ctx-chip  { display:inline-block; font-size:9px; padding:2px 6px; border-radius:3px;
             background:rgba(255,196,0,.1); color:var(--accent); border:1px solid rgba(255,196,0,.2);
             text-transform:lowercase; font-weight:500; }
.empty-state { text-align:center; padding:40px 20px; color:var(--text-muted); }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:600; color:var(--text); }
.empty-desc  { font-size:13px; margin-top:6px; }

/* ━━ Responsive ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
@media(max-width:900px) { .ml-layout { grid-template-columns:1fr; } .ml-sidebar { position:static; } }
</style>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">

      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Media Library</h1>
          <p class="page-subtitle"><?= number_format($totalFiles) ?> files · <?= formatFileSize($totalSize) ?> used</p>
        </div>
        <div class="page-header-actions">
          <button class="btn btn-primary" onclick="document.getElementById('globalDropInput').click()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Upload Files
          </button>
        </div>
      </div>

      <?= flashHtml() ?>

      <div class="ml-layout">

        <!-- ━━ Left Sidebar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="ml-sidebar">

          <!-- Drop Zone -->
          <div class="card" style="padding:16px;margin-bottom:14px;">
            <div class="drop-zone" id="mainDropZone">
              <input type="file" id="globalDropInput" multiple accept="image/*,video/*,.pdf,.zip,.rar,.docx,.doc,.xlsx,.txt,.js,.php,.py,.css,.html,.json,.ts,.java,.cpp,.c,.cs">
              <div class="dz-icon">☁️</div>
              <div class="dz-text">Drop files here</div>
              <div class="dz-hint">or click to browse</div>
              <div class="dz-hint" style="margin-top:4px;">Images, PDFs, ZIP, Code, Docs</div>
            </div>
            <div id="uploadQueue"></div>
          </div>

          <!-- Category filter -->
          <div class="card" style="padding:14px;">
            <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">File Types</div>
            <ul class="cat-list">
              <li>
                <a href="?cat=&search=<?= urlencode($search) ?>" class="cat-pill <?= !$cat?'active':'' ?>">
                  <span class="ci">📁 All Files</span>
                  <span class="cat-badge"><?= number_format($totalFiles) ?></span>
                </a>
              </li>
              <?php
              $catMeta = [
                'image' => ['label'=>'Images',    'icon'=>'🖼️'],
                'video' => ['label'=>'Videos',    'icon'=>'🎬'],
                'pdf'   => ['label'=>'PDFs',      'icon'=>'📄'],
                'zip'   => ['label'=>'ZIPs',      'icon'=>'📦'],
                'code'  => ['label'=>'Code Files','icon'=>'💻'],
                'doc'   => ['label'=>'Documents', 'icon'=>'📝'],
                'other' => ['label'=>'Other',     'icon'=>'📁'],
              ];
              foreach ($catMeta as $key => $m):
                $cnt = $byCat[$key]['count'] ?? 0;
              ?>
              <li>
                <a href="?cat=<?= $key ?>&search=<?= urlencode($search) ?>" class="cat-pill <?= $cat===$key?'active':'' ?>">
                  <span class="ci"><?= $m['icon'] ?> <?= $m['label'] ?></span>
                  <span class="cat-badge"><?= number_format($cnt) ?></span>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Storage breakdown -->
          <div class="card" style="padding:14px;margin-top:14px;">
            <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Storage</div>
            <?php
            $colors = ['image'=>'#3b82f6','video'=>'#ef4444','pdf'=>'#f59e0b','zip'=>'#8b5cf6','code'=>'#22c55e','doc'=>'#06b6d4','other'=>'var(--text-muted)'];
            foreach ($catMeta as $key => $m):
                $sz = $byCat[$key]['size'] ?? 0;
                if (!$sz) continue;
                $pct = $totalSize > 0 ? round($sz/$totalSize*100) : 0;
            ?>
            <div style="margin-bottom:10px;">
              <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;">
                <span style="color:var(--text-muted);"><?= $m['icon'] ?> <?= $m['label'] ?></span>
                <span style="color:var(--text);font-weight:600;"><?= formatFileSize($sz) ?></span>
              </div>
              <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                <div style="height:100%;background:<?= $colors[$key]??'var(--accent)' ?>;width:<?= $pct ?>%;transition:.5s;"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ━━ Main content ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="ml-main">

          <!-- Toolbar -->
          <div class="ml-toolbar">
            <div class="search-input-wrap" style="flex:1;min-width:180px;">
              <span class="si"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
              <input type="text" id="mlSearch" placeholder="Search files…" value="<?= e($search) ?>" autocomplete="off">
            </div>

            <select id="sortSel" class="form-control" style="width:140px;" onchange="applySort()">
              <option value="uploaded_at" <?= $sort==='uploaded_at'?'selected':'' ?>>Newest First</option>
              <option value="original_name" <?= $sort==='original_name'?'selected':'' ?>>Name A→Z</option>
              <option value="file_size" <?= $sort==='file_size'?'selected':'' ?>>Largest First</option>
            </select>

            <div class="view-btns">
              <button class="vb active" id="btnGrid" title="Grid View" onclick="setView('grid')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
              </button>
              <button class="vb" id="btnList" title="List View" onclick="setView('list')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
              </button>
            </div>

            <span style="font-size:12px;color:var(--text-muted);flex-shrink:0;"><?= number_format($total) ?> file<?= $total!==1?'s':'' ?></span>
          </div>

          <!-- ━━ GRID VIEW ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
          <div id="gridView" style="background:var(--card-bg);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);">
            <?php if(empty($files)): ?>
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <p class="empty-title">No files found</p>
              <p class="empty-desc">Try a different filter or upload your first file.</p>
            </div>
            <?php else: ?>
            <div class="media-grid" id="mediaGrid">
              <?php foreach($files as $f): ?>
              <?php $isImg = isImage($f['file_type']??''); ?>
              <div class="media-card" id="mc-<?= $f['id'] ?>" data-id="<?= $f['id'] ?>"
                   data-path="<?= e($f['file_path']) ?>"
                   data-name="<?= e($f['original_name']) ?>"
                   data-cat="<?= e($f['file_category']) ?>"
                   data-mime="<?= e($f['file_type']) ?>"
                   data-size="<?= e(formatFileSize($f['file_size'])) ?>"
                   data-date="<?= e(formatDate($f['uploaded_at'],'d M Y H:i')) ?>"
                   data-uploader="<?= e($f['uploader']??'—') ?>"
                   data-context="<?= e($f['context']??'') ?>"
                   onclick="handleCardClick(event,<?= $f['id'] ?>)">
                <div class="sel-box">✓</div>
                <?php if($isImg): ?>
                  <img src="<?= e($f['file_path']) ?>" alt="" class="mc-thumb" loading="lazy"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                  <div class="mc-icon" style="display:none;"><?= catIcon($f['file_category']) ?></div>
                <?php else: ?>
                  <div class="mc-icon" style="font-size:<?= $f['file_category']==='pdf'?'32px':'36px' ?>;"><?= catIcon($f['file_category']) ?></div>
                <?php endif; ?>
                <div class="mc-actions">
                  <button class="mc-btn mc-btn-copy" title="Copy URL" onclick="event.stopPropagation();copyUrl('<?= e($f['file_path']) ?>')">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  </button>
                  <button class="mc-btn mc-btn-prev" title="Preview" onclick="event.stopPropagation();previewFile(<?= $f['id'] ?>)">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button class="mc-btn mc-btn-del" title="Delete" onclick="event.stopPropagation();deleteFile(<?= $f['id'] ?>,'<?= e(addslashes($f['original_name'])) ?>')">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                  </button>
                </div>
                <div class="mc-info">
                  <div class="mc-name" title="<?= e($f['original_name']) ?>"><?= e(truncate($f['original_name'],22)) ?></div>
                  <div class="mc-meta">
                    <span style="color:<?= catColor($f['file_category']) ?>;font-weight:600;font-size:9px;text-transform:uppercase;"><?= e($f['file_category']) ?></span>
                    <span><?= formatFileSize($f['file_size']) ?></span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- ━━ LIST VIEW ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
          <div id="listView" style="background:var(--card-bg);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);">
            <?php if(empty($files)): ?>
            <div class="empty-state"><div class="empty-icon">📭</div><p class="empty-title">No files found</p></div>
            <?php else: ?>
            <div class="table-wrap">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th style="width:36px;"><input type="checkbox" id="selAllList" style="accent-color:var(--accent);cursor:pointer;" onchange="selectAllList(this)"></th>
                    <th style="width:44px;"></th>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Context</th>
                    <th>Uploader</th>
                    <th>Date</th>
                    <th style="text-align:right;width:120px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="listBody">
                  <?php foreach($files as $f): ?>
                  <?php $isImg = isImage($f['file_type']??''); ?>
                  <tr data-id="<?= $f['id'] ?>">
                    <td><input type="checkbox" class="list-cb" value="<?= $f['id'] ?>" style="accent-color:var(--accent);cursor:pointer;"></td>
                    <td>
                      <?php if($isImg): ?>
                        <img src="<?= e($f['file_path']) ?>" style="width:38px;height:28px;object-fit:cover;border-radius:4px;border:1px solid var(--border);" alt=""
                             onerror="this.style.display='none'">
                      <?php else: ?>
                        <div style="width:38px;height:28px;background:var(--input-bg);border-radius:4px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px;"><?= catIcon($f['file_category']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-size:13px;font-weight:500;"><?= e(truncate($f['original_name'],44)) ?></div>
                      <div style="font-size:10px;color:var(--text-dim);font-family:monospace;margin-top:1px;"><?= e(truncate($f['file_path'],48)) ?></div>
                    </td>
                    <td><span class="badge badge-muted" style="font-size:10px;"><?= e($f['file_category']) ?></span></td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= formatFileSize($f['file_size']) ?></td>
                    <td>
                      <?php if($f['context']): ?>
                        <span class="ctx-chip"><?= e($f['context']) ?></span>
                      <?php else: ?>
                        <span style="color:var(--text-dim);font-size:12px;">—</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= e($f['uploader']??'—') ?></td>
                    <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;"><?= formatDate($f['uploaded_at'],'d M Y') ?></td>
                    <td>
                      <div style="display:flex;gap:4px;justify-content:flex-end;">
                        <button class="btn btn-ghost btn-sm btn-icon" title="Preview" onclick="previewFile(<?= $f['id'] ?>)">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-sm btn-icon" title="Copy URL" onclick="copyUrl('<?= e($f['file_path']) ?>')">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <a href="<?= e($f['file_path']) ?>" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Open">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteFile(<?= $f['id'] ?>,'<?= e(addslashes($f['original_name'])) ?>')">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>

          <?php
          $queryParams = array_filter(compact('cat', 'search', 'sort'), fn($v) => $v !== '');
          require __DIR__ . '/includes/pagination.php';
          ?>
        </div><!-- /.ml-main -->
      </div><!-- /.ml-layout -->

      <!-- ━━ BULK ACTION BAR ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <div class="bulk-bar" id="bulkBar">
        <span class="bulk-cnt"><span id="selCount">0</span> selected</span>
        <div class="bulk-sep"></div>
        <button class="btn btn-ghost btn-sm" onclick="bulkCopyUrls()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          Copy URLs
        </button>
        <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
          Delete All
        </button>
        <button class="btn btn-ghost btn-sm" onclick="clearSelection()">✕ Clear</button>
      </div>

    </main>
  </div>
</div>

<!-- ━━ PREVIEW LIGHTBOX ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
<div class="lightbox hidden" id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <div class="lb-box">
    <div class="lb-hd">
      <h3 id="lbTitle">File Preview</h3>
      <div style="display:flex;gap:6px;flex-shrink:0;">
        <button class="btn btn-ghost btn-sm" id="lbCopyBtn" onclick="copyUrl(currentFile?.path)">Copy URL</button>
        <a class="btn btn-ghost btn-sm" id="lbOpenBtn" href="#" target="_blank">Open ↗</a>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="closeLightbox()">✕</button>
      </div>
    </div>
    <div class="lb-body">
      <div id="lbMedia"></div>
      <div class="lb-url" id="lbUrl" onclick="copyUrl(currentFile?.path)"></div>
      <div class="lb-meta" id="lbMeta"></div>
    </div>
    <div class="lb-ft">
      <button class="btn btn-danger btn-sm" onclick="deleteFile(currentFile?.id,currentFile?.name);closeLightbox()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
        Delete File
      </button>
    </div>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ══ State ══════════════════════════════════════════════════ */
const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content || '';
const fileData = {};
let currentFile = null;
let selectedIds = new Set();

/* ══ Build file data index from DOM ═════════════════════════ */
document.querySelectorAll('.media-card').forEach(c => {
  fileData[c.dataset.id] = {
    id:       c.dataset.id,
    path:     c.dataset.path,
    name:     c.dataset.name,
    cat:      c.dataset.cat,
    mime:     c.dataset.mime,
    size:     c.dataset.size,
    date:     c.dataset.date,
    uploader: c.dataset.uploader,
    context:  c.dataset.context,
  };
});

/* ══ VIEW TOGGLE ════════════════════════════════════════════ */
function setView(v) {
  document.getElementById('gridView').style.display = v==='grid' ? '' : 'none';
  document.getElementById('listView').style.display = v==='list' ? '' : 'none';
  document.getElementById('btnGrid').classList.toggle('active', v==='grid');
  document.getElementById('btnList').classList.toggle('active', v==='list');
  localStorage.setItem('mlView', v);
}
const sv = localStorage.getItem('mlView') || 'grid';
setView(sv);

/* ══ SORT ═══════════════════════════════════════════════════ */
function applySort() {
  const s = document.getElementById('sortSel').value;
  const params = new URLSearchParams(location.search);
  params.set('sort', s); params.set('page', '1');
  location.search = params.toString();
}

/* ══ SEARCH (debounced) ═════════════════════════════════════ */
let st;
document.getElementById('mlSearch').addEventListener('input', function() {
  clearTimeout(st);
  st = setTimeout(() => {
    const params = new URLSearchParams(location.search);
    params.set('search', this.value); params.set('page','1');
    location.search = params.toString();
  }, 500);
});

/* ══ DRAG-DROP UPLOAD ═══════════════════════════════════════ */
const dropZone = document.getElementById('mainDropZone');
const dropInput = document.getElementById('globalDropInput');

['dragenter','dragover'].forEach(ev => {
  dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
});
['dragleave','drop'].forEach(ev => {
  dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); });
});

// Also allow drop on the whole page
document.addEventListener('dragover', e => e.preventDefault());
document.addEventListener('drop', e => {
  e.preventDefault();
  if (e.dataTransfer.files.length) processFiles(e.dataTransfer.files);
});

dropZone.addEventListener('drop', e => { e.preventDefault(); processFiles(e.dataTransfer.files); });
dropInput.addEventListener('change', () => { if (dropInput.files.length) processFiles(dropInput.files); });

function processFiles(fileList) {
  Array.from(fileList).forEach(file => uploadFile(file));
}

/* ━━ Detect category from file ━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function detectCat(file) {
  const m = file.type;
  const n = file.name.toLowerCase();
  if (m.startsWith('image/'))  return 'image';
  if (m.startsWith('video/'))  return 'video';
  if (m === 'application/pdf') return 'pdf';
  if (m === 'application/zip' || n.endsWith('.zip') || n.endsWith('.rar')) return 'zip';
  const codeExts = ['.js','.ts','.php','.py','.java','.cpp','.c','.cs','.html','.css','.json','.sh','.sql','.rb','.go'];
  if (codeExts.some(ext => n.endsWith(ext))) return 'code';
  const docExts = ['.doc','.docx','.xls','.xlsx','.ppt','.pptx','.txt','.csv','.md'];
  if (docExts.some(ext => n.endsWith(ext))) return 'doc';
  return 'other';
}

/* ━━ Upload a single file ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
async function uploadFile(file) {
  const queue  = document.getElementById('uploadQueue');
  const itemId = 'uq-' + Date.now() + Math.random().toString(36).slice(2);
  const cat    = detectCat(file);

  // Check size limits
  const limits = { image:10, video:500, pdf:50, zip:100, code:5, doc:20, other:20 };
  const maxMB  = limits[cat] ?? 20;
  if (file.size > maxMB * 1024 * 1024) {
    Toast.error('Too Large', `${file.name} exceeds ${maxMB}MB limit for ${cat} files.`);
    return;
  }

  // Queue item
  const item = document.createElement('div');
  item.className = 'uq-item'; item.id = itemId;
  item.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <span class="uq-name" title="${escHtml(file.name)}">${escHtml(file.name.substring(0,28))}${file.name.length>28?'…':''}</span>
      <span class="uq-status" id="${itemId}-s">0%</span>
    </div>
    <div class="uq-bar"><div class="uq-fill" id="${itemId}-b"></div></div>`;
  queue.appendChild(item);

  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('file', file);
  fd.append('category', cat);
  fd.append('context', 'media-library');
  fd.append('action', 'upload');

  const xhr = new XMLHttpRequest();
  xhr.upload.onprogress = e => {
    const p = Math.round(e.loaded / e.total * 100);
    document.getElementById(`${itemId}-b`).style.width = p + '%';
    document.getElementById(`${itemId}-s`).textContent = p + '%';
  };
  xhr.onload = () => {
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        document.getElementById(`${itemId}-s`).textContent = '✓';
        document.getElementById(`${itemId}-b`).style.background = '#22c55e';
        item.style.borderColor = '#22c55e';
        Toast.success('Uploaded!', `${file.name} uploaded.`);
        setTimeout(() => { item.remove(); location.reload(); }, 1400);
      } else {
        document.getElementById(`${itemId}-s`).textContent = '✗';
        document.getElementById(`${itemId}-b`).style.background = '#ef4444';
        item.style.borderColor = '#ef4444';
        Toast.error('Upload Failed', res.error || 'Unknown error.');
        setTimeout(() => item.remove(), 3000);
      }
    } catch {
      Toast.error('Error', 'Invalid server response.');
      item.remove();
    }
  };
  xhr.onerror = () => { Toast.error('Network Error', 'Upload failed.'); item.remove(); };
  xhr.open('POST', '/api/upload.php', true);
  xhr.send(fd);
}

/* ══ SELECTION (grid) ═══════════════════════════════════════ */
function handleCardClick(event, id) {
  if (event.target.closest('.mc-btn')) return;
  if (event.shiftKey || event.ctrlKey || event.metaKey) {
    toggleSelect(id);
  } else if (selectedIds.size > 0) {
    toggleSelect(id);
  } else {
    previewFile(id);
  }
}
function toggleSelect(id) {
  const card = document.getElementById('mc-' + id);
  if (selectedIds.has(String(id))) {
    selectedIds.delete(String(id));
    card?.classList.remove('sel');
  } else {
    selectedIds.add(String(id));
    card?.classList.add('sel');
  }
  updateBulkBar();
}
function clearSelection() {
  selectedIds.clear();
  document.querySelectorAll('.media-card.sel').forEach(c => c.classList.remove('sel'));
  document.querySelectorAll('.list-cb').forEach(cb => cb.checked = false);
  updateBulkBar();
}
function updateBulkBar() {
  const n = selectedIds.size;
  document.getElementById('selCount').textContent = n;
  document.getElementById('bulkBar').classList.toggle('show', n > 0);
}

/* ══ LIST CHECKBOXES ════════════════════════════════════════ */
document.querySelectorAll('.list-cb').forEach(cb => {
  cb.addEventListener('change', function() {
    if (this.checked) selectedIds.add(this.value); else selectedIds.delete(this.value);
    updateBulkBar();
  });
});
function selectAllList(cb) {
  document.querySelectorAll('.list-cb').forEach(c => {
    c.checked = cb.checked;
    if (cb.checked) selectedIds.add(c.value); else selectedIds.delete(c.value);
  });
  updateBulkBar();
}

/* ══ PREVIEW LIGHTBOX ═══════════════════════════════════════ */
function previewFile(id) {
  const f = fileData[id];
  if (!f) { Toast.error('Error','File data not found.'); return; }
  currentFile = f;
  const lb = document.getElementById('lightbox');
  const fullUrl = location.origin + f.path;

  document.getElementById('lbTitle').textContent = f.name;
  document.getElementById('lbUrl').textContent = fullUrl;
  document.getElementById('lbOpenBtn').href = f.path;
  document.getElementById('lbOpenBtn').setAttribute('download', f.name);

  // Media preview
  const med = document.getElementById('lbMedia');
  if (f.mime.startsWith('image/')) {
    med.innerHTML = `<img src="${escHtml(f.path)}" class="lb-img" alt="${escHtml(f.name)}">`;
  } else if (f.mime.startsWith('video/')) {
    med.innerHTML = `<video src="${escHtml(f.path)}" class="lb-img" controls style="background:#000;"></video>`;
  } else if (f.mime === 'application/pdf') {
    med.innerHTML = `<div class="lb-icon">📄</div>
    <div style="text-align:center;margin-top:10px;">
      <a href="${escHtml(f.path)}" target="_blank" class="btn btn-primary btn-sm">Open PDF ↗</a>
    </div>`;
  } else {
    med.innerHTML = `<div class="lb-icon">${getIcon(f.cat)}</div>`;
  }

  // Metadata
  document.getElementById('lbMeta').innerHTML = `
    <div class="lb-row"><span class="lb-lbl">File Name</span><span class="lb-val">${escHtml(f.name)}</span></div>
    <div class="lb-row"><span class="lb-lbl">Type</span><span class="lb-val">${escHtml(f.cat)} · ${escHtml(f.mime)}</span></div>
    <div class="lb-row"><span class="lb-lbl">Size</span><span class="lb-val">${escHtml(f.size)}</span></div>
    <div class="lb-row"><span class="lb-lbl">Uploaded</span><span class="lb-val">${escHtml(f.date)}</span></div>
    <div class="lb-row"><span class="lb-lbl">By</span><span class="lb-val">${escHtml(f.uploader)}</span></div>
    ${f.context?`<div class="lb-row"><span class="lb-lbl">Context</span><span class="lb-val">${escHtml(f.context)}</span></div>`:''}
  `;

  lb.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.add('hidden');
  document.body.style.overflow = '';
  currentFile = null;
}
function getIcon(cat) {
  const m = {'image':'🖼️','video':'🎬','pdf':'📄','zip':'📦','code':'💻','doc':'📝'};
  return m[cat] || '📁';
}

/* ══ COPY URL ════════════════════════════════════════════════ */
function copyUrl(path) {
  if (!path) return;
  const url = location.origin + path;
  navigator.clipboard.writeText(url).then(() => Toast.success('Copied!', 'URL copied to clipboard.'))
    .catch(() => {
      // Fallback
      const el = document.createElement('textarea');
      el.value = url; document.body.appendChild(el);
      el.select(); document.execCommand('copy'); el.remove();
      Toast.success('Copied!', 'URL copied.');
    });
}

/* ══ DELETE ══════════════════════════════════════════════════ */
async function deleteFile(id, name) {
  Modal.confirm(
    `Delete "<strong>${escHtml(name||'this file')}</strong>"?<br><small style="color:var(--text-muted)">This will permanently remove the file from disk.</small>`,
    async () => {
      const r = await apiPost('/api/upload.php', { action:'delete', id });
      if (r.success) {
        document.getElementById('mc-'+id)?.remove();
        document.querySelector(`[data-id="${id}"]`)?.remove();
        delete fileData[id];
        Toast.success('Deleted', 'File removed.');
      } else Toast.error('Error', r.error);
    },
    { title:'Delete File', confirmText:'Delete', type:'danger' }
  );
}

/* ══ BULK DELETE ══════════════════════════════════════════════ */
async function bulkDelete() {
  const ids = [...selectedIds];
  if (!ids.length) return;
  Modal.confirm(
    `Delete <strong>${ids.length}</strong> file${ids.length!==1?'s':''}?<br><small style="color:var(--text-muted)">Files will be permanently removed from disk.</small>`,
    async () => {
      let ok = 0;
      for (const id of ids) {
        const r = await apiPost('/api/upload.php', { action:'delete', id });
        if (r.success) {
          document.getElementById('mc-'+id)?.remove();
          document.querySelector(`[data-id="${id}"]`)?.remove();
          delete fileData[id]; ok++;
        }
      }
      clearSelection();
      Toast.success('Done!', `${ok} file${ok!==1?'s':''} deleted.`);
    },
    { title:`Delete ${ids.length} Files`, confirmText:'Delete All', type:'danger' }
  );
}

/* ══ BULK COPY URLS ══════════════════════════════════════════ */
function bulkCopyUrls() {
  const urls = [...selectedIds].map(id => location.origin + (fileData[id]?.path || '')).filter(Boolean).join('\n');
  navigator.clipboard.writeText(urls).then(() => Toast.success('Copied!', `${selectedIds.size} URLs copied.`));
}

/* ══ KEYBOARD SHORTCUTS ══════════════════════════════════════ */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeLightbox(); clearSelection(); }
});
</script>
</body>
</html>
