<?php
/**
 * CodeByTushu — Analytics Dashboard v2
 * Daily/Monthly Visitors · Top Pages · Top Downloads · New Users
 * Devices · Browsers · Referrers — Charts + Tables
 */
declare(strict_types=1);

$adminSection = 'Analytics';
$adminTitle   = 'Analytics Dashboard — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Analytics'],
];
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>';

require_once __DIR__ . '/includes/auth_check.php';

$pdo  = db();
$days = (int)get('days', '30');
if (!in_array($days, [7, 14, 30, 90, 180], true)) $days = 30;

/* ── Helpers ──────────────────────────────────────────────────── */
function sq(PDO $p, string $sql, array $b = []): int {
    try { $s = $p->prepare($sql); $s->execute($b); return (int)$s->fetchColumn(); }
    catch (\Throwable) { return 0; }
}
function safeQ(PDO $p, string $sql, array $b = []): array {
    try { $s = $p->prepare($sql); $s->execute($b); return $s->fetchAll(); }
    catch (\Throwable) { return []; }
}

/* parseBrowser() is globally available from includes/functions.php */

/* ── Date range strings ───────────────────────────────────────── */
$rangeStart = date('Y-m-d', strtotime("-{$days} days"));

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   1. Summary KPIs
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$totalViews    = sq($pdo, "SELECT COUNT(*) FROM page_visits WHERE visited_at >= ? AND device_type!='bot'", [$rangeStart]);
$uniqueVisitors = sq($pdo, "SELECT COUNT(DISTINCT ip_address) FROM page_visits WHERE visited_at >= ? AND device_type!='bot'", [$rangeStart]);
$todayViews    = sq($pdo, "SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at)=CURDATE() AND device_type!='bot'");
$yesterdayViews = sq($pdo, "SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at)=DATE_SUB(CURDATE(),INTERVAL 1 DAY) AND device_type!='bot'");
$monthViews    = sq($pdo, "SELECT COUNT(*) FROM page_visits WHERE MONTH(visited_at)=MONTH(CURDATE()) AND YEAR(visited_at)=YEAR(CURDATE()) AND device_type!='bot'");
$avgPerDay     = $days > 0 ? round($totalViews / $days) : 0;
$bounceApprox  = 0; // placeholder
$newUsers      = sq($pdo, 'SELECT COUNT(*) FROM users WHERE created_at >= ? AND role="user"', [$rangeStart]);
$totalUsers    = sq($pdo, 'SELECT COUNT(*) FROM users');
$newUsersToday = sq($pdo, "SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()");

// Delta vs prev period
$prevStart   = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
$prevViews   = sq($pdo, "SELECT COUNT(*) FROM page_visits WHERE visited_at >= ? AND visited_at < ? AND device_type!='bot'", [$prevStart, $rangeStart]);
$deltaViews  = $prevViews > 0 ? round((($totalViews - $prevViews) / $prevViews) * 100, 1) : null;

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   2. Daily chart
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$dailyRows = safeQ($pdo,
    "SELECT DATE(visited_at) AS day,
            COUNT(*) AS views,
            COUNT(DISTINCT ip_address) AS unique_v
       FROM page_visits
      WHERE visited_at >= ? AND device_type!='bot'
      GROUP BY day ORDER BY day ASC",
    [$rangeStart]
);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   3. Monthly chart (last 12 months)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$monthlyRows = safeQ($pdo,
    "SELECT DATE_FORMAT(visited_at,'%b %Y') AS label,
            DATE_FORMAT(visited_at,'%Y-%m') AS ym,
            COUNT(*) AS views,
            COUNT(DISTINCT ip_address) AS unique_v
       FROM page_visits
      WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND device_type!='bot'
      GROUP BY ym ORDER BY ym ASC"
);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   4. New Users per day (last $days days)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$userGrowthRows = safeQ($pdo,
    "SELECT DATE(created_at) AS day, COUNT(*) AS count
       FROM users WHERE created_at >= ? GROUP BY day ORDER BY day ASC",
    [$rangeStart]
);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   5. Top pages
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$topPages = safeQ($pdo,
    "SELECT page_url,
            COALESCE(MAX(page_title),'') AS page_title,
            COUNT(*) AS views,
            COUNT(DISTINCT ip_address) AS unique_v
       FROM page_visits
      WHERE visited_at >= ? AND device_type!='bot'
      GROUP BY page_url ORDER BY views DESC LIMIT 15",
    [$rangeStart]
);
$maxPageViews = max(1, (int)($topPages[0]['views'] ?? 1));

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   6. Top downloads (file_uploads table — downloads tracked by visit URL)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$topDownloads = safeQ($pdo,
    "SELECT pv.page_url,
            COALESCE(fu.original_name, SUBSTRING_INDEX(pv.page_url,'/',-1)) AS file_name,
            COALESCE(fu.file_category,'file') AS category,
            fu.file_size,
            COUNT(*) AS downloads
       FROM page_visits pv
       LEFT JOIN file_uploads fu ON fu.file_path = pv.page_url AND fu.is_active=1
      WHERE pv.page_url LIKE '/uploads/%' AND pv.visited_at >= ? AND pv.device_type!='bot'
      GROUP BY pv.page_url ORDER BY downloads DESC LIMIT 10",
    [$rangeStart]
);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   7. Devices
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$deviceRows = safeQ($pdo,
    "SELECT device_type, COUNT(*) AS cnt
       FROM page_visits WHERE visited_at >= ? AND device_type!='bot'
      GROUP BY device_type ORDER BY cnt DESC",
    [$rangeStart]
);
$totalDevices = max(1, array_sum(array_column($deviceRows, 'cnt')));

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   8. Browsers (Native Column)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$browserRows = safeQ($pdo,
    "SELECT browser, COUNT(*) AS cnt 
       FROM page_visits 
      WHERE visited_at >= ? AND device_type!='bot' AND browser IS NOT NULL
      GROUP BY browser ORDER BY cnt DESC",
    [$rangeStart]
);
$browserCounts = [];
foreach ($browserRows as $r) {
    $browserCounts[$r['browser']] = (int)$r['cnt'];
}
$totalBrowsers = max(1, array_sum($browserCounts));

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   9. Top referrers
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$referrerRows = safeQ($pdo,
    "SELECT referer AS ref, COUNT(*) AS cnt
       FROM page_visits
      WHERE referer IS NOT NULL AND referer != '' AND visited_at >= ? AND device_type!='bot'
      GROUP BY referer ORDER BY cnt DESC LIMIT 10",
    [$rangeStart]
);
function refDomain(string $url): string {
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    return preg_replace('/^www\./', '', $host);
}
$maxRefCnt = max(1, (int)($referrerRows[0]['cnt'] ?? 1));

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   10. New Users (table)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$newUserRows = safeQ($pdo,
    "SELECT id, full_name, email, role, status, profile_image, created_at
       FROM users WHERE created_at >= ? ORDER BY created_at DESC LIMIT 10",
    [$rangeStart]
);

/* ── Chart colour palette ─────────────────────────────────────── */
$palette = ['#ffc400','#3b82f6','#22c55e','#a855f7','#ef4444','#f97316','#06b6d4','#ec4899'];
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── KPI cards ──────────────────────────────────────────────────── */
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
@media(max-width:1100px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:600px){ .kpi-grid{grid-template-columns:1fr 1fr;} }
.kpi-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-lg);
            padding:18px 20px; position:relative; overflow:hidden; transition:.2s; }
.kpi-card:hover { border-color:var(--accent); transform:translateY(-1px); box-shadow:0 6px 24px rgba(0,0,0,.3); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:2px;
                    background:linear-gradient(90deg,var(--kpi-color,#ffc400),transparent); }
.kpi-top  { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px; }
.kpi-icon { width:38px; height:38px; border-radius:10px; display:grid; place-items:center;
            font-size:18px; background:var(--accent-glow); border:1px solid var(--accent-mid); flex-shrink:0; }
.kpi-delta { font-size:11px; font-weight:700; padding:3px 8px; border-radius:99px; }
.kpi-delta.up   { background:rgba(34,197,94,.12); color:#22c55e; }
.kpi-delta.down { background:rgba(239,68,68,.12); color:#ef4444; }
.kpi-delta.flat { background:var(--border); color:var(--text-muted); }
.kpi-val  { font-size:28px; font-weight:800; color:var(--text); letter-spacing:-.5px; line-height:1; margin-bottom:4px; }
.kpi-lbl  { font-size:12px; color:var(--text-muted); font-weight:500; }
.kpi-sub  { font-size:10px; color:var(--text-dim); margin-top:3px; }

/* ── Charts grid ────────────────────────────────────────────────── */
.chart-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.chart-grid-3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:20px; margin-bottom:20px; }
@media(max-width:1100px){ .chart-grid-2{grid-template-columns:1fr;} .chart-grid-3{grid-template-columns:1fr;} }

/* ── Analytics card ─────────────────────────────────────────────── */
.ac { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-lg);
      overflow:hidden; margin-bottom:20px; }
.ac-hd { display:flex; align-items:center; justify-content:space-between; padding:14px 18px;
         border-bottom:1px solid var(--border); }
.ac-title { font-size:13px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.ac-badge { font-size:10px; background:var(--border); color:var(--text-muted); padding:2px 8px;
            border-radius:99px; }
.ac-body  { padding:18px; }

/* ── Bar chart inside cards ─────────────────────────────────────── */
.bar-row  { display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:12px; }
.bar-row:last-child { margin-bottom:0; }
.bar-lbl  { width:95px; flex-shrink:0; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bar-track { flex:1; height:7px; background:var(--border); border-radius:4px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:4px; transition:width .6s ease; }
.bar-cnt  { width:42px; text-align:right; font-weight:700; color:var(--text); flex-shrink:0; font-size:11px; }
.bar-pct  { width:32px; text-align:right; color:var(--text-dim); font-size:10px; flex-shrink:0; }

/* ── Top pages table ────────────────────────────────────────────── */
.tp-row { display:flex; align-items:center; gap:12px; padding:9px 0;
          border-bottom:1px solid var(--border); font-size:12px; }
.tp-row:last-child { border-bottom:none; }
.tp-num { width:20px; color:var(--text-dim); flex-shrink:0; font-weight:700; font-size:10px; }
.tp-url { flex:1; min-width:0; }
.tp-page { color:var(--text); font-family:monospace; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tp-title { font-size:10px; color:var(--text-dim); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tp-bar { width:70px; flex-shrink:0; }
.tp-views { width:48px; text-align:right; font-weight:700; color:var(--text); flex-shrink:0; }
.tp-uv    { width:42px; text-align:right; color:var(--text-muted); flex-shrink:0; font-size:10px; }

/* ── Device icon colors ─────────────────────────────────────────── */
.dev-mobile  { color:#3b82f6; } .dev-desktop { color:#22c55e; }
.dev-tablet  { color:#a855f7; } .dev-unknown { color:#888; }

/* ── Referrer row ───────────────────────────────────────────────── */
.ref-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--border); font-size:12px; }
.ref-row:last-child { border-bottom:none; }
.ref-domain { width:130px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex-shrink:0; }
.ref-bar    { flex:1; }
.ref-cnt    { width:42px; text-align:right; font-weight:700; color:var(--accent); flex-shrink:0; }

/* ── User row ───────────────────────────────────────────────────── */
.nu-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--border); font-size:12px; }
.nu-row:last-child { border-bottom:none; }
.nu-av  { width:30px; height:30px; border-radius:50%; background:var(--accent-glow); border:1.5px solid var(--accent);
          display:grid; place-items:center; font-size:11px; font-weight:800; color:var(--accent); flex-shrink:0; overflow:hidden; }
.nu-info { flex:1; min-width:0; }
.nu-name { font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.nu-email { font-size:10px; color:var(--text-dim); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.nu-time  { font-size:10px; color:var(--text-dim); flex-shrink:0; white-space:nowrap; }

/* ── Range selector ─────────────────────────────────────────────── */
.range-pills { display:flex; gap:4px; }
.rp { padding:5px 13px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer;
      background:var(--border); color:var(--text-muted); text-decoration:none; transition:.15s; }
.rp.active, .rp:hover { background:var(--accent); color:#000; }

/* ── Download table ─────────────────────────────────────────────── */
.dl-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--border); font-size:12px; }
.dl-row:last-child { border-bottom:none; }
.dl-icon { font-size:16px; flex-shrink:0; }
.dl-name { flex:1; min-width:0; color:var(--text); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dl-cnt  { width:48px; text-align:right; font-weight:700; color:var(--accent); flex-shrink:0; }

/* ── Chart wrap ─────────────────────────────────────────────────── */
.cw220 { height:220px; } .cw260 { height:260px; } .cw180 { height:180px; }

/* ── Live dot ───────────────────────────────────────────────────── */
.live-dot { width:7px; height:7px; background:#22c55e; border-radius:50%; display:inline-block;
            margin-right:4px; box-shadow:0 0 0 3px rgba(34,197,94,.2); animation:ldot 2s infinite; }
@keyframes ldot { 0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.2);}50%{box-shadow:0 0 0 7px rgba(34,197,94,0);} }
</style>

<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>

    <main class="page-content">

      <!-- Page header + range selector -->
      <div class="page-header" style="margin-bottom:20px;">
        <div class="page-header-left">
          <h1 class="page-title">
            <span class="live-dot"></span>Analytics Dashboard
          </h1>
          <p class="page-subtitle">
            <?= date('d M Y', strtotime($rangeStart)) ?> → <?= date('d M Y') ?>
            &nbsp;·&nbsp; <?= $days ?>-day window
          </p>
        </div>
        <div class="page-header-actions">
          <div class="range-pills">
            <?php foreach([7=>'7d',14=>'14d',30=>'30d',90=>'90d',180=>'6m'] as $d=>$lbl): ?>
            <a href="?days=<?= $d ?>" class="rp <?= $days===$d?'active':'' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ════ KPI CARDS ════════════════════════════════════════ -->
      <div class="kpi-grid">

        <!-- Total Page Views -->
        <div class="kpi-card" style="--kpi-color:#ffc400;">
          <div class="kpi-top">
            <div class="kpi-icon">👁️</div>
            <?php if($deltaViews !== null): ?>
            <span class="kpi-delta <?= $deltaViews>=0?'up':'down' ?>">
              <?= $deltaViews>=0?'↑':'↓' ?><?= abs($deltaViews) ?>%
            </span>
            <?php endif; ?>
          </div>
          <div class="kpi-val"><?= number_format($totalViews) ?></div>
          <div class="kpi-lbl">Total Page Views</div>
          <div class="kpi-sub">Last <?= $days ?> days</div>
        </div>

        <!-- Unique Visitors -->
        <div class="kpi-card" style="--kpi-color:#3b82f6;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.3);">👤</div>
          </div>
          <div class="kpi-val"><?= number_format($uniqueVisitors) ?></div>
          <div class="kpi-lbl">Unique Visitors</div>
          <div class="kpi-sub">Distinct IPs · <?= $days ?>d</div>
        </div>

        <!-- Today -->
        <div class="kpi-card" style="--kpi-color:#22c55e;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);">📅</div>
            <?php $todayDelta = $yesterdayViews > 0 ? round((($todayViews - $yesterdayViews)/$yesterdayViews)*100) : null; ?>
            <?php if($todayDelta !== null): ?>
            <span class="kpi-delta <?= $todayDelta>=0?'up':'down' ?>">
              <?= $todayDelta>=0?'↑':'↓' ?><?= abs($todayDelta) ?>% vs yday
            </span>
            <?php endif; ?>
          </div>
          <div class="kpi-val"><?= number_format($todayViews) ?></div>
          <div class="kpi-lbl">Today's Views</div>
          <div class="kpi-sub">Yesterday: <?= number_format($yesterdayViews) ?></div>
        </div>

        <!-- Monthly -->
        <div class="kpi-card" style="--kpi-color:#a855f7;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(168,85,247,.1);border-color:rgba(168,85,247,.3);">📆</div>
          </div>
          <div class="kpi-val"><?= number_format($monthViews) ?></div>
          <div class="kpi-lbl">This Month</div>
          <div class="kpi-sub"><?= date('F Y') ?></div>
        </div>

        <!-- Avg per day -->
        <div class="kpi-card" style="--kpi-color:#f97316;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(249,115,22,.1);border-color:rgba(249,115,22,.3);">📊</div>
          </div>
          <div class="kpi-val"><?= number_format($avgPerDay) ?></div>
          <div class="kpi-lbl">Avg. Views / Day</div>
          <div class="kpi-sub">Over <?= $days ?> days</div>
        </div>

        <!-- New Users -->
        <div class="kpi-card" style="--kpi-color:#06b6d4;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(6,182,212,.1);border-color:rgba(6,182,212,.3);">🧑‍💻</div>
            <?php if($newUsersToday > 0): ?>
            <span class="kpi-delta up">+<?= $newUsersToday ?> today</span>
            <?php endif; ?>
          </div>
          <div class="kpi-val"><?= number_format($newUsers) ?></div>
          <div class="kpi-lbl">New Users</div>
          <div class="kpi-sub">Total registered: <?= number_format($totalUsers) ?></div>
        </div>

        <!-- Mobile share -->
        <?php
        $mobileCount = 0; $desktopCount = 0;
        foreach($deviceRows as $d) {
            if($d['device_type']==='mobile') $mobileCount=$d['cnt'];
            if($d['device_type']==='desktop') $desktopCount=$d['cnt'];
        }
        $mobileShare = $totalViews>0 ? round($mobileCount/$totalViews*100) : 0;
        ?>
        <div class="kpi-card" style="--kpi-color:#ec4899;">
          <div class="kpi-top">
            <div class="kpi-icon" style="background:rgba(236,72,153,.1);border-color:rgba(236,72,153,.3);">📱</div>
          </div>
          <div class="kpi-val"><?= $mobileShare ?>%</div>
          <div class="kpi-lbl">Mobile Share</div>
          <div class="kpi-sub">Desktop: <?= $totalViews>0?round($desktopCount/$totalViews*100):0 ?>%</div>
        </div>

        <!-- Top browser -->
        <?php $topBrowser = array_key_first($browserCounts) ?? 'N/A'; $topBrowserShare = $totalBrowsers>0 ? round(($browserCounts[$topBrowser]??0)/$totalBrowsers*100) : 0; ?>
        <div class="kpi-card" style="--kpi-color:#ffc400;">
          <div class="kpi-top">
            <div class="kpi-icon">🌐</div>
          </div>
          <div class="kpi-val"><?= $topBrowserShare ?>%</div>
          <div class="kpi-lbl"><?= e($topBrowser) ?></div>
          <div class="kpi-sub">Top browser this period</div>
        </div>

      </div><!-- /kpi-grid -->

      <!-- ════ DAILY TRAFFIC CHART ══════════════════════════════ -->
      <div class="ac" style="margin-bottom:20px;">
        <div class="ac-hd">
          <div class="ac-title">
            📈 Daily Traffic
            <span class="ac-badge"><?= $days ?>d</span>
          </div>
          <div style="display:flex;gap:18px;font-size:11px;color:var(--text-muted);">
            <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#ffc400;display:inline-block;"></span>Page Views</span>
            <span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>Unique Visitors</span>
          </div>
        </div>
        <div class="ac-body">
          <div class="cw260"><canvas id="dailyChart"></canvas></div>
        </div>
      </div>

      <!-- ════ MONTHLY + USER GROWTH CHARTS ════════════════════ -->
      <div class="chart-grid-2">
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">📅 Monthly Traffic <span class="ac-badge">12mo</span></div>
          </div>
          <div class="ac-body">
            <div class="cw220"><canvas id="monthlyChart"></canvas></div>
          </div>
        </div>
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">🧑‍💻 User Growth <span class="ac-badge"><?= $days ?>d</span></div>
          </div>
          <div class="ac-body">
            <div class="cw220"><canvas id="userGrowthChart"></canvas></div>
          </div>
        </div>
      </div>

      <!-- ════ TOP PAGES + DEVICES + BROWSERS ══════════════════ -->
      <div style="display:grid;grid-template-columns:1fr 220px 220px;gap:20px;margin-top:20px;margin-bottom:20px;align-items:start;">

        <!-- Top Pages -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">🔥 Top Pages <span class="ac-badge"><?= count($topPages) ?></span></div>
            <span style="font-size:10px;color:var(--text-dim);">Views · Visitors</span>
          </div>
          <div class="ac-body" style="padding:0;">
            <?php if(empty($topPages)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px;">No data yet.</div>
            <?php else: ?>
            <?php foreach($topPages as $i => $p): ?>
            <div class="tp-row" style="padding:9px 18px;">
              <div class="tp-num"><?= $i+1 ?></div>
              <div class="tp-url">
                <div class="tp-page" title="<?= e($p['page_url']) ?>"><?= e(truncate($p['page_url'],45)) ?></div>
                <?php if($p['page_title']): ?>
                <div class="tp-title"><?= e(truncate($p['page_title'],50)) ?></div>
                <?php endif; ?>
              </div>
              <div class="tp-bar">
                <div style="background:var(--border);border-radius:4px;height:5px;overflow:hidden;">
                  <div style="width:<?= round($p['views']/$maxPageViews*100) ?>%;height:100%;background:var(--accent);border-radius:4px;"></div>
                </div>
              </div>
              <div class="tp-views"><?= number_format($p['views']) ?></div>
              <div class="tp-uv"><?= number_format($p['unique_v']) ?>u</div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Devices -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">📱 Devices</div>
          </div>
          <div class="ac-body">
            <div class="cw180"><canvas id="deviceChart"></canvas></div>
            <div style="margin-top:14px;">
              <?php
              $devIcons = ['mobile'=>'📱','desktop'=>'💻','tablet'=>'📟','unknown'=>'❓'];
              $devColors= ['mobile'=>'#3b82f6','desktop'=>'#22c55e','tablet'=>'#a855f7','unknown'=>'#888'];
              foreach($deviceRows as $d):
                $pct = round($d['cnt']/$totalDevices*100);
              ?>
              <div class="bar-row">
                <div class="bar-lbl" style="width:72px;"><?= ($devIcons[$d['device_type']]??'🖥️') ?> <?= ucfirst(e($d['device_type'])) ?></div>
                <div class="bar-track">
                  <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $devColors[$d['device_type']]??'#888' ?>;"></div>
                </div>
                <div class="bar-pct"><?= $pct ?>%</div>
              </div>
              <?php endforeach; ?>
              <?php if(empty($deviceRows)): ?>
              <div style="text-align:center;color:var(--text-muted);font-size:12px;">No data</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Browsers -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">🌐 Browsers</div>
          </div>
          <div class="ac-body">
            <div class="cw180"><canvas id="browserChart"></canvas></div>
            <div style="margin-top:14px;">
              <?php
              $brColors = ['Chrome'=>'#ffc400','Firefox'=>'#f97316','Safari'=>'#3b82f6','Edge'=>'#06b6d4','Opera'=>'#ef4444','IE'=>'#6366f1','Other'=>'#888','Bot/Script'=>'#4b5563'];
              $bi=0;
              foreach($browserCounts as $br => $cnt):
                $pct = round($cnt/$totalBrowsers*100);
                $col = $brColors[$br] ?? $palette[$bi % count($palette)];
              ?>
              <div class="bar-row">
                <div class="bar-lbl" style="width:72px;font-size:11px;"><?= e($br) ?></div>
                <div class="bar-track">
                  <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
                </div>
                <div class="bar-pct"><?= $pct ?>%</div>
              </div>
              <?php $bi++; endforeach; ?>
              <?php if(empty($browserCounts)): ?>
              <div style="text-align:center;color:var(--text-muted);font-size:12px;">No data</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- /3-col grid -->

      <!-- ════ REFERRERS + NEW USERS + DOWNLOADS ══════════════ -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;align-items:start;">

        <!-- Referrers -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">🔗 Top Referrers <span class="ac-badge"><?= count($referrerRows) ?></span></div>
          </div>
          <div class="ac-body" style="padding:0;">
            <?php if(empty($referrerRows)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:12px;">No referral traffic yet.</div>
            <?php else: ?>
            <?php foreach($referrerRows as $i => $r):
              $domain = refDomain($r['ref']);
              $pct    = round($r['cnt']/$maxRefCnt*100);
            ?>
            <div class="ref-row" style="padding:9px 18px;">
              <div class="ref-domain" title="<?= e($r['ref']) ?>"><?= e(truncate($domain,18)) ?></div>
              <div class="ref-bar">
                <div style="background:var(--border);border-radius:4px;height:5px;overflow:hidden;">
                  <div style="width:<?= $pct ?>%;height:100%;background:<?= $palette[$i % count($palette)] ?>;border-radius:4px;"></div>
                </div>
              </div>
              <div class="ref-cnt"><?= number_format($r['cnt']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- New Users -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">🧑‍💻 New Users <span class="ac-badge"><?= count($newUserRows) ?></span></div>
            <a href="<?= SITE_URL ?>/admin/users.php" style="font-size:11px;color:var(--accent);text-decoration:none;">View all →</a>
          </div>
          <div class="ac-body" style="padding:0;">
            <?php if(empty($newUserRows)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:12px;">No new registrations.</div>
            <?php else: ?>
            <?php foreach($newUserRows as $u): ?>
            <div class="nu-row" style="padding:8px 18px;">
              <div class="nu-av">
                <?php if($u['profile_image']): ?>
                <img src="<?= e($u['profile_image']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                <?php else: ?>
                <?= strtoupper(substr($u['full_name'],0,1)) ?>
                <?php endif; ?>
              </div>
              <div class="nu-info">
                <div class="nu-name"><?= e(truncate($u['full_name'],22)) ?></div>
                <div class="nu-email"><?= e($u['email']) ?></div>
              </div>
              <div class="nu-time"><?= timeAgo($u['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Top Downloads -->
        <div class="ac" style="margin-bottom:0;">
          <div class="ac-hd">
            <div class="ac-title">⬇️ Top Downloads <span class="ac-badge"><?= count($topDownloads) ?></span></div>
            <a href="<?= SITE_URL ?>/admin/uploads.php" style="font-size:11px;color:var(--accent);text-decoration:none;">Media →</a>
          </div>
          <div class="ac-body" style="padding:0;">
            <?php
            $catIcons = ['image'=>'🖼️','video'=>'🎬','pdf'=>'📄','zip'=>'📦','code'=>'💻','doc'=>'📝','other'=>'📁','file'=>'📁'];
            if(empty($topDownloads)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:12px;">No download data.<br><span style="font-size:10px;">Tracked via /uploads/ visits.</span></div>
            <?php else: ?>
            <?php foreach($topDownloads as $dl): ?>
            <div class="dl-row" style="padding:9px 18px;">
              <div class="dl-icon"><?= $catIcons[$dl['category']] ?? '📁' ?></div>
              <div class="dl-name" title="<?= e($dl['file_name']) ?>"><?= e(truncate($dl['file_name'],28)) ?></div>
              <div class="dl-cnt"><?= number_format($dl['downloads']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- /3-col grid -->

      <!-- ════ HOURLY HEATMAP (sessions by hour) ════════════════ -->
      <?php
      $hourlyRows = safeQ($pdo,
          "SELECT HOUR(visited_at) AS hr, COUNT(*) AS cnt
             FROM page_visits WHERE visited_at >= ? AND device_type!='bot'
            GROUP BY hr ORDER BY hr ASC",
          [$rangeStart]
      );
      $hourlyByHr = [];
      foreach($hourlyRows as $h) $hourlyByHr[(int)$h['hr']] = (int)$h['cnt'];
      $maxHrCnt = max(1, max($hourlyByHr ?: [1]));
      ?>
      <div class="ac" style="margin-bottom:20px;">
        <div class="ac-hd">
          <div class="ac-title">🕐 Activity by Hour <span class="ac-badge">UTC</span></div>
          <span style="font-size:10px;color:var(--text-dim);">Darker = more visits</span>
        </div>
        <div class="ac-body">
          <div style="display:grid;grid-template-columns:repeat(24,1fr);gap:3px;">
            <?php for($hr=0;$hr<24;$hr++):
              $cnt = $hourlyByHr[$hr] ?? 0;
              $intensity = $maxHrCnt>0 ? $cnt/$maxHrCnt : 0;
              $alpha = round(0.1 + $intensity*0.9, 2);
            ?>
            <div style="text-align:center;" title="<?= $hr ?>:00 — <?= number_format($cnt) ?> visits">
              <div style="height:52px;background:rgba(255,196,0,<?= $alpha ?>);border-radius:5px;margin-bottom:4px;"></div>
              <div style="font-size:9px;color:var(--text-dim);"><?= $hr ?></div>
            </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <!-- ════ COUNTRY TABLE ════════════════════════════════════ -->
      <?php
      $countryRows = safeQ($pdo,
          "SELECT country, COUNT(*) AS cnt FROM page_visits
            WHERE country IS NOT NULL AND country != '' AND visited_at >= ? AND device_type!='bot'
           GROUP BY country ORDER BY cnt DESC LIMIT 10",
          [$rangeStart]
      );
      $maxCountryCnt = max(1, (int)($countryRows[0]['cnt'] ?? 1));
      ?>
      <?php if(!empty($countryRows)): ?>
      <div class="ac" style="margin-bottom:20px;">
        <div class="ac-hd">
          <div class="ac-title">🌍 Top Countries <span class="ac-badge"><?= count($countryRows) ?></span></div>
        </div>
        <div class="ac-body" style="padding:0;">
          <?php foreach($countryRows as $i=>$c): $pct=round($c['cnt']/$totalViews*100); ?>
          <div class="ref-row" style="padding:9px 18px;">
            <div class="ref-domain" style="width:140px;"><?= e($c['country']) ?></div>
            <div class="ref-bar">
              <div style="background:var(--border);border-radius:4px;height:5px;overflow:hidden;">
                <div style="width:<?= round($c['cnt']/$maxCountryCnt*100) ?>%;height:100%;background:<?= $palette[$i%count($palette)] ?>;border-radius:4px;"></div>
              </div>
            </div>
            <div style="width:36px;text-align:right;font-size:10px;color:var(--text-muted);flex-shrink:0;"><?= $pct ?>%</div>
            <div class="ref-cnt"><?= number_format($c['cnt']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ── Chart defaults ──────────────────────────────────────────── */
Chart.defaults.color          = 'rgba(255,255,255,0.45)';
Chart.defaults.borderColor    = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family    = "'Inter', sans-serif";
Chart.defaults.font.size      = 11;
Chart.defaults.plugins.legend.display = false;

function mkTooltip() {
  return {
    backgroundColor: 'rgba(12,12,18,0.95)',
    borderColor: 'rgba(255,196,0,.3)',
    borderWidth: 1,
    titleColor: '#ffffff',
    bodyColor: 'rgba(255,255,255,0.7)',
    padding: 10,
    cornerRadius: 8,
  };
}

/* ── Daily chart ─────────────────────────────────────────────── */
new Chart(document.getElementById('dailyChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($dailyRows,'day')) ?>,
    datasets: [
      {
        label: 'Page Views',
        data: <?= json_encode(array_column($dailyRows,'views')) ?>,
        borderColor: '#ffc400', backgroundColor: 'rgba(255,196,0,.08)',
        fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 6,
        pointBackgroundColor: '#ffc400', borderWidth: 2,
      },
      {
        label: 'Unique Visitors',
        data: <?= json_encode(array_column($dailyRows,'unique_v')) ?>,
        borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.06)',
        fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 5, borderWidth: 1.5,
      },
    ],
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: true, position: 'top',
        labels: { usePointStyle: true, boxWidth: 8, color: 'rgba(255,255,255,0.55)', padding: 16 } },
      tooltip: mkTooltip(),
    },
    scales: {
      x: { grid: { color:'rgba(255,255,255,.04)' }, ticks: { maxTicksLimit: 10 } },
      y: { grid: { color:'rgba(255,255,255,.04)' }, ticks: { maxTicksLimit: 6 }, beginAtZero: true },
    },
  }
});

/* ── Monthly chart ───────────────────────────────────────────── */
new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($monthlyRows,'label')) ?>,
    datasets: [
      {
        label: 'Views',
        data: <?= json_encode(array_column($monthlyRows,'views')) ?>,
        backgroundColor: 'rgba(255,196,0,.75)', borderRadius: 5, borderSkipped: false,
      },
      {
        label: 'Unique',
        data: <?= json_encode(array_column($monthlyRows,'unique_v')) ?>,
        backgroundColor: 'rgba(59,130,246,.6)', borderRadius: 5, borderSkipped: false,
      },
    ],
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: true, position: 'top',
        labels: { usePointStyle: true, boxWidth: 7, color:'rgba(255,255,255,.55)', padding:12 } },
      tooltip: mkTooltip(),
    },
    scales: {
      x: { grid: { display:false }, ticks: { maxTicksLimit: 6 } },
      y: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true },
    },
  }
});

/* ── User growth chart ───────────────────────────────────────── */
new Chart(document.getElementById('userGrowthChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($userGrowthRows,'day')) ?>,
    datasets: [{
      label: 'New Users',
      data: <?= json_encode(array_column($userGrowthRows,'count')) ?>,
      borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,.1)',
      fill: true, tension: .4, pointRadius: 3, borderWidth: 2,
      pointBackgroundColor: '#06b6d4',
    }],
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { tooltip: mkTooltip(), legend: { display: false } },
    scales: {
      x: { grid: { color:'rgba(255,255,255,.04)' }, ticks: { maxTicksLimit: 8 } },
      y: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true, ticks: { stepSize: 1 } },
    },
  }
});

/* ── Device doughnut ─────────────────────────────────────────── */
<?php if(!empty($deviceRows)): ?>
new Chart(document.getElementById('deviceChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_map(fn($d)=>ucfirst($d['device_type']),$deviceRows)) ?>,
    datasets: [{
      data: <?= json_encode(array_column($deviceRows,'cnt')) ?>,
      backgroundColor: ['#3b82f6','#22c55e','#a855f7','#888'],
      borderWidth: 2, borderColor: 'rgba(9,9,14,1)',
      hoverBorderColor: '#ffc400',
    }],
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { display: true, position: 'bottom',
        labels: { color:'rgba(255,255,255,.55)', usePointStyle: true, boxWidth: 7, padding: 10 } },
      tooltip: mkTooltip(),
    },
  }
});
<?php endif; ?>

/* ── Browser doughnut ────────────────────────────────────────── */
<?php if(!empty($browserCounts)): ?>
new Chart(document.getElementById('browserChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($browserCounts)) ?>,
    datasets: [{
      data: <?= json_encode(array_values($browserCounts)) ?>,
      backgroundColor: ['#ffc400','#f97316','#3b82f6','#06b6d4','#ef4444','#6366f1','#888'],
      borderWidth: 2, borderColor: 'rgba(9,9,14,1)',
      hoverBorderColor: '#ffc400',
    }],
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { display: true, position: 'bottom',
        labels: { color:'rgba(255,255,255,.55)', usePointStyle: true, boxWidth: 7, padding: 10 } },
      tooltip: mkTooltip(),
    },
  }
});
<?php endif; ?>
</script>
</body>
</html>
