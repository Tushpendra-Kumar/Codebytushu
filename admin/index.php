<?php
/**
 * CodeByTushu — Admin Dashboard Homepage
 * Extended with: 6 KPI cards, monthly visitor chart, device doughnut,
 * recent activity feed, latest users table, latest messages list,
 * quick actions, and AJAX live-refresh every 60 seconds.
 */
declare(strict_types=1);

$adminSection = 'Dashboard';
$adminTitle   = 'Dashboard — CodeByTushu Admin';
$breadcrumbs  = [['label' => 'Dashboard']];

require_once __DIR__ . '/includes/auth_check.php';

/* ══════════════════════════════════════════════════════════════
   SERVER-SIDE DATA (initial render — fast, no waterfalls)
   ══════════════════════════════════════════════════════════════ */
$pdo = db();

function sq(PDO $p, string $sql): int {
    try { return (int)$p->query($sql)->fetchColumn(); }
    catch (\Throwable) { return 0; }
}

// ── KPI Stats ──────────────────────────────────────────────────
$stats = [
    'total_users'    => sq($pdo, 'SELECT COUNT(*) FROM users WHERE role="user"'),
    'new_users_7d'   => sq($pdo, 'SELECT COUNT(*) FROM users WHERE role="user" AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)'),
    'total_blogs'    => sq($pdo, 'SELECT COUNT(*) FROM blog_articles'),
    'pub_blogs'      => sq($pdo, 'SELECT COUNT(*) FROM blog_articles WHERE is_published=1'),
    'total_courses'  => sq($pdo, 'SELECT COUNT(*) FROM courses'),
    'pub_courses'    => sq($pdo, 'SELECT COUNT(*) FROM courses WHERE is_published=1'),
    'total_leet'     => sq($pdo, 'SELECT COUNT(*) FROM leetcode_solutions'),
    'pub_leet'       => sq($pdo, 'SELECT COUNT(*) FROM leetcode_solutions WHERE is_published=1'),
    'total_msgs'     => sq($pdo, 'SELECT COUNT(*) FROM contact_messages'),
    'unread_msgs'    => sq($pdo, 'SELECT COUNT(*) FROM contact_messages WHERE is_read=0'),
    'visitors_today' => sq($pdo, 'SELECT COUNT(*) FROM page_visits WHERE DATE(visited_at)=CURDATE() AND device_type!="bot"'),
    'visitors_30d'   => sq($pdo, 'SELECT COUNT(*) FROM page_visits WHERE visited_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND device_type!="bot"'),
];

// ── Monthly Visitor Chart (12 months) ──────────────────────────
try {
    $monthlyRows = $pdo->query(
        "SELECT DATE_FORMAT(visited_at,'%b %Y') AS label,
                DATE_FORMAT(visited_at,'%Y-%m') AS sort_key,
                COUNT(*) AS visits,
                COUNT(DISTINCT ip_address) AS unique_v
           FROM page_visits
          WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND device_type != 'bot'
          GROUP BY sort_key, label ORDER BY sort_key ASC"
    )->fetchAll();
} catch (\Throwable) { $monthlyRows = []; }

// ── Device Breakdown Chart ─────────────────────────────────────
try {
    $deviceRows = $pdo->query(
        "SELECT device_type, COUNT(*) AS cnt
           FROM page_visits
          WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND device_type != 'bot'
          GROUP BY device_type ORDER BY cnt DESC"
    )->fetchAll();
} catch (\Throwable) { $deviceRows = []; }

// ── Latest Users ───────────────────────────────────────────────
try {
    $latestUsers = $pdo->query(
        'SELECT id, full_name, email, role, status, created_at, profile_image, last_login
           FROM users ORDER BY created_at DESC LIMIT 8'
    )->fetchAll();
} catch (\Throwable) { $latestUsers = []; }

// ── Latest Messages ────────────────────────────────────────────
try {
    $latestMessages = $pdo->query(
        'SELECT id, name, email, subject, is_read, submitted_at, source_page
           FROM contact_messages ORDER BY submitted_at DESC LIMIT 8'
    )->fetchAll();
} catch (\Throwable) { $latestMessages = []; }

// ── Combined Activity Feed (users + messages + solutions) ──────
$activityFeed = [];
try {
    // New users
    $rows = $pdo->query(
        'SELECT id, full_name AS title, email AS sub, created_at AS time,
                "user_joined" AS type, profile_image
           FROM users ORDER BY created_at DESC LIMIT 6'
    )->fetchAll();
    foreach ($rows as $r) $activityFeed[] = array_merge($r, ['icon' => 'user', 'color' => '#3b82f6']);

    // Messages
    $rows = $pdo->query(
        'SELECT id, CONCAT(name," sent a message") AS title, subject AS sub,
                submitted_at AS time, "message" AS type, is_read
           FROM contact_messages ORDER BY submitted_at DESC LIMIT 5'
    )->fetchAll();
    foreach ($rows as $r) $activityFeed[] = array_merge($r, [
        'profile_image' => null,
        'icon'  => 'mail',
        'color' => $r['is_read'] ? 'var(--text-dim)' : '#f59e0b',
        'url'   => '/admin/messages.php?id=' . $r['id'],
    ]);

    // Solutions
    $rows = $pdo->query(
        'SELECT id,
                CONCAT("#",problem_number," ",title," added") AS title,
                CONCAT("Difficulty: ", difficulty) AS sub,
                created_at AS time, "solution" AS type
           FROM leetcode_solutions ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();
    foreach ($rows as $r) $activityFeed[] = array_merge($r, [
        'profile_image' => null,
        'icon'  => 'code',
        'color' => 'var(--accent)',
        'is_read' => 1,
    ]);

    // Blog posts
    $rows = $pdo->query(
        'SELECT id, CONCAT("\"",title,"\" ",IF(is_published,"published","drafted")) AS title,
                IF(is_published,"Published article","Saved draft") AS sub,
                created_at AS time, "blog" AS type
           FROM blog_articles ORDER BY created_at DESC LIMIT 4'
    )->fetchAll();
    foreach ($rows as $r) $activityFeed[] = array_merge($r, [
        'profile_image' => null,
        'icon'  => 'edit',
        'color' => '#22c55e',
        'is_read' => 1,
    ]);

    usort($activityFeed, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    $activityFeed = array_slice($activityFeed, 0, 15);
} catch (\Throwable) { $activityFeed = []; }

// ── Quick Actions ──────────────────────────────────────────────
$quickActions = [
    ['icon' => 'code',    'label' => 'New Solution',   'desc' => 'Add LeetCode problem',  'url' => '/admin/leetcode-edit.php', 'color' => '#ffc400', 'bg' => 'rgba(255,196,0,.12)'],
    ['icon' => 'edit',    'label' => 'New Blog Post',  'desc' => 'Write an article',       'url' => '/admin/blog-edit.php',    'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.12)'],
    ['icon' => 'book',    'label' => 'New Course',     'desc' => 'Create a course',        'url' => '/admin/courses.php',      'color' => '#22c55e', 'bg' => 'rgba(34,197,94,.12)'],
    ['icon' => 'users',   'label' => 'Manage Users',   'desc' => 'View all members',       'url' => '/admin/users.php',        'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,.12)'],
    ['icon' => 'mail',    'label' => 'Messages',       'desc' => $stats['unread_msgs'] . ' unread',    'url' => '/admin/messages.php',     'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.12)'],
    ['icon' => 'settings','label' => 'Site Settings',  'desc' => 'Configure options',      'url' => '/admin/settings.php',     'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
];
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>

    <main class="page-content">

      <!-- ══ Page Header ══════════════════════════════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">
            Welcome back, <?= e(explode(' ', $user['full_name'] ?? 'Admin')[0]) ?> 👋
          </h1>
          <p class="page-subtitle">
            <?= date('l, d F Y') ?> &nbsp;·&nbsp;
            <?php if ($stats['unread_msgs'] > 0): ?>
              <span style="color:var(--danger);font-weight:600;">
                <?= $stats['unread_msgs'] ?> unread message<?= $stats['unread_msgs'] > 1 ? 's' : '' ?>
              </span>
            <?php else: ?>
              <span style="color:var(--success);">All caught up ✓</span>
            <?php endif; ?>
          </p>
        </div>
        <div class="page-header-actions">
          <!-- Live refresh indicator -->
          <div id="refreshStatus" style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);padding:0 4px;">
            <span id="refreshDot" style="width:8px;height:8px;border-radius:50%;background:var(--success);display:inline-block;animation:pulse 2s infinite;"></span>
            <span id="refreshLabel">Live</span>
          </div>
          <a href="/" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            View Site
          </a>
          <a href="<?= SITE_URL ?>/admin/analytics.php" class="btn btn-secondary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Analytics
          </a>
        </div>
      </div>

      <!-- ══ KPI Cards ════════════════════════════════════════ -->
      <div class="stats-grid" id="kpiGrid">

        <!-- Total Users -->
        <div class="stat-card" onclick="location.href='/admin/users.php'" tabindex="0" role="button" aria-label="Users">
          <div class="stat-card-top">
            <span class="stat-label">Total Users</span>
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-users"><?= number_format($stats['total_users']) ?></div>
          <div class="stat-sub">
            <?php if ($stats['new_users_7d'] > 0): ?>
              <span class="stat-badge up">+<?= $stats['new_users_7d'] ?> this week</span>
            <?php endif; ?>
            <span style="color:var(--text-muted);">registered members</span>
          </div>
          <div class="stat-progress-wrap">
            <div class="stat-mini-bar" style="--bar-color:#3b82f6;--bar-pct:<?= min(100, $stats['total_users']) ?>%;"></div>
          </div>
        </div>

        <!-- Total Blogs -->
        <div class="stat-card" onclick="location.href='/admin/blogs.php'" tabindex="0" role="button" aria-label="Blogs">
          <div class="stat-card-top">
            <span class="stat-label">Blog Articles</span>
            <div class="stat-icon-wrap" style="background:rgba(139,92,246,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-blogs"><?= number_format($stats['total_blogs']) ?></div>
          <div class="stat-sub">
            <span class="stat-badge up" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><?= $stats['pub_blogs'] ?> published</span>
            <span style="color:var(--text-muted);"><?= $stats['total_blogs'] - $stats['pub_blogs'] ?> drafts</span>
          </div>
        </div>

        <!-- Total Courses -->
        <div class="stat-card" onclick="location.href='/admin/courses.php'" tabindex="0" role="button" aria-label="Courses">
          <div class="stat-card-top">
            <span class="stat-label">Courses</span>
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-courses"><?= number_format($stats['total_courses']) ?></div>
          <div class="stat-sub">
            <span class="stat-badge up" style="background:rgba(34,197,94,.12);color:#22c55e;"><?= $stats['pub_courses'] ?> live</span>
            <span style="color:var(--text-muted);"><?= $stats['total_courses'] - $stats['pub_courses'] ?> hidden</span>
          </div>
        </div>

        <!-- Total LeetCode -->
        <div class="stat-card" onclick="location.href='/admin/leetcode.php'" tabindex="0" role="button" aria-label="LeetCode">
          <div class="stat-card-top">
            <span class="stat-label">LeetCode Problems</span>
            <div class="stat-icon-wrap" style="background:rgba(255,196,0,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffc400" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-leet"><?= number_format($stats['total_leet']) ?></div>
          <div class="stat-sub">
            <span class="stat-badge up"><?= $stats['pub_leet'] ?> published</span>
            <span style="color:var(--text-muted);"><?= $stats['total_leet'] - $stats['pub_leet'] ?> drafts</span>
          </div>
        </div>

        <!-- Contact Messages -->
        <div class="stat-card" onclick="location.href='/admin/messages.php'" tabindex="0" role="button" aria-label="Messages">
          <div class="stat-card-top">
            <span class="stat-label">Contact Messages</span>
            <div class="stat-icon-wrap" style="background:rgba(239,68,68,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-msgs"><?= number_format($stats['total_msgs']) ?></div>
          <div class="stat-sub">
            <?php if ($stats['unread_msgs'] > 0): ?>
              <span class="stat-badge down" id="kpi-unread-badge"><?= $stats['unread_msgs'] ?> unread</span>
              <span style="color:var(--danger);font-size:11px;">needs attention</span>
            <?php else: ?>
              <span class="stat-badge up">all read ✓</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Total Visitors -->
        <div class="stat-card" onclick="location.href='/admin/analytics.php'" tabindex="0" role="button" aria-label="Visitors">
          <div class="stat-card-top">
            <span class="stat-label">Visitors (30 days)</span>
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,.12);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
          </div>
          <div class="stat-value" id="kpi-visitors"><?= number_format($stats['visitors_30d']) ?></div>
          <div class="stat-sub">
            <span class="stat-badge flat" id="kpi-today-badge"><?= number_format($stats['visitors_today']) ?> today</span>
          </div>
        </div>

      </div><!-- /.stats-grid -->

      <!-- ══ Charts Row ════════════════════════════════════════ -->
      <div style="display:grid;grid-template-columns:1fr 300px;gap:22px;margin-bottom:24px;" class="chart-row">

        <!-- Monthly Visitor Chart -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Monthly Visitor Overview</div>
              <div class="card-subtitle">Page views &amp; unique visitors — last 12 months</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
              <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);">
                <span style="width:10px;height:10px;border-radius:50%;background:#ffc400;flex-shrink:0;"></span> Views
              </div>
              <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-muted);">
                <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;flex-shrink:0;"></span> Unique
              </div>
              <a href="<?= SITE_URL ?>/admin/analytics.php" class="btn btn-ghost btn-sm">Full report →</a>
            </div>
          </div>
          <div class="card-body" style="padding:16px 20px 20px;">
            <?php if (empty($monthlyRows)): ?>
              <div class="empty-state" style="padding:40px 20px;">
                <div class="empty-icon">📈</div>
                <p class="empty-title">No visitor data yet</p>
                <p class="empty-desc">Add the analytics tracker to your public pages to start collecting data.</p>
              </div>
            <?php else: ?>
              <div class="chart-container" style="height:260px;">
                <canvas id="monthlyChart"></canvas>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Device Breakdown Doughnut -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Device Types</div>
              <div class="card-subtitle">Last 30 days</div>
            </div>
          </div>
          <div class="card-body" style="padding:16px;">
            <?php if (empty($deviceRows)): ?>
              <div class="empty-state" style="padding:20px;">
                <div class="empty-icon" style="font-size:36px;">📱</div>
                <p class="empty-desc" style="font-size:12px;">No data yet</p>
              </div>
            <?php else: ?>
              <div class="chart-container" style="height:200px;">
                <canvas id="deviceChart"></canvas>
              </div>
              <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;">
                <?php
                $deviceColors = ['desktop' => '#ffc400', 'mobile' => '#3b82f6', 'tablet' => '#22c55e', 'unknown' => '#6b7280'];
                $totalDevice  = array_sum(array_column($deviceRows, 'cnt'));
                foreach ($deviceRows as $dr):
                    $pct = $totalDevice > 0 ? round(($dr['cnt'] / $totalDevice) * 100) : 0;
                    $color = $deviceColors[$dr['device_type']] ?? '#6b7280';
                ?>
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></span>
                  <span style="font-size:12px;color:var(--text-muted);flex:1;text-transform:capitalize;"><?= e($dr['device_type']) ?></span>
                  <span style="font-size:12px;font-weight:700;color:var(--text);"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- ══ Main Content Grid ══════════════════════════════════ -->
      <div style="display:grid;grid-template-columns:1fr 360px;gap:22px;margin-bottom:24px;" class="main-grid">

        <!-- Latest Registered Users Table -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Latest Registered Users</div>
              <div class="card-subtitle">Most recently signed up members</div>
            </div>
            <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">View all users →</a>
          </div>
          <div class="table-wrap">
            <table class="admin-table" id="latestUsersTable">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Last Login</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($latestUsers)): ?>
                  <tr><td colspan="5">
                    <div class="empty-state" style="padding:32px;">
                      <div class="empty-icon">👥</div>
                      <p class="empty-desc">No users registered yet.</p>
                    </div>
                  </td></tr>
                <?php else: ?>
                  <?php foreach ($latestUsers as $u): ?>
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-glow);border:1.5px solid var(--accent);display:grid;place-items:center;font-size:13px;font-weight:700;color:var(--accent);overflow:hidden;flex-shrink:0;">
                          <?php if ($u['profile_image']): ?>
                            <img src="<?= e($u['profile_image']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                          <?php else: ?>
                            <?= strtoupper(substr($u['full_name'],0,1)) ?>
                          <?php endif; ?>
                        </div>
                        <div>
                          <div style="font-weight:600;font-size:13px;color:var(--text);"><?= e(truncate($u['full_name'],24)) ?></div>
                          <div style="font-size:11px;color:var(--text-muted);"><?= e(truncate($u['email'],28)) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge <?= match($u['role']) { 'super_admin' => 'badge-accent', 'admin' => 'badge-accent', 'editor' => 'badge-info', default => 'badge-muted' } ?>">
                        <?= e(str_replace('_',' ',$u['role'])) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : ($u['status'] === 'banned' ? 'badge-danger' : 'badge-warning') ?>">
                        <?= e($u['status']) ?>
                      </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">
                      <?= $u['last_login'] ? timeAgo($u['last_login']) : '<span style="color:var(--text-dim);">Never</span>' ?>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                      <?= formatDate($u['created_at'], 'd M Y') ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="card" style="display:flex;flex-direction:column;">
          <div class="card-header" style="flex-shrink:0;">
            <div>
              <div class="card-title">Recent Activity</div>
              <div class="card-subtitle">Latest across all sections</div>
            </div>
            <button class="btn btn-ghost btn-sm" id="refreshActivityBtn"
                    onclick="refreshActivity()" title="Refresh">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
              </svg>
              Refresh
            </button>
          </div>
          <div id="activityFeed" style="flex:1;overflow-y:auto;max-height:420px;">
            <?php if (empty($activityFeed)): ?>
              <div class="empty-state" style="padding:40px 20px;">
                <div class="empty-icon">🔔</div>
                <p class="empty-title">No activity yet</p>
                <p class="empty-desc">Activity will appear here as users register and content is added.</p>
              </div>
            <?php else: ?>
              <?php foreach ($activityFeed as $act): ?>
              <?php
                $iconSvg = match($act['icon'] ?? 'dot') {
                  'user' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                  'mail' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                  'code' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
                  'edit' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                  default => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>',
                };
                $isUnread = isset($act['is_read']) && !$act['is_read'];
              ?>
              <a href="<?= e($act['url'] ?? '#') ?>" class="activity-item <?= $isUnread ? 'unread' : '' ?>">
                <div class="activity-icon" style="background:<?= $isUnread ? 'rgba(245,158,11,.12)' : 'var(--bg-2)' ?>;color:<?= e($act['color'] ?? 'var(--text-muted)') ?>;">
                  <?= $iconSvg ?>
                </div>
                <div class="activity-body">
                  <div class="activity-title"><?= e(truncate($act['title'] ?? '',48)) ?></div>
                  <div class="activity-sub"><?= e(truncate($act['sub'] ?? '',40)) ?></div>
                </div>
                <div class="activity-time"><?= timeAgo($act['time']) ?></div>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- ══ Latest Messages + Quick Actions ════════════════════ -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:24px;" class="bottom-grid">

        <!-- Latest Contact Messages -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">
                Latest Messages
                <?php if ($stats['unread_msgs'] > 0): ?>
                  <span class="badge badge-danger" style="margin-left:6px;font-size:10px;"><?= $stats['unread_msgs'] ?> new</span>
                <?php endif; ?>
              </div>
              <div class="card-subtitle">Contact form submissions</div>
            </div>
            <a href="<?= SITE_URL ?>/admin/messages.php" class="btn btn-ghost btn-sm">View all →</a>
          </div>
          <div id="messagesPanel">
            <?php if (empty($latestMessages)): ?>
              <div class="empty-state" style="padding:40px 20px;">
                <div class="empty-icon">✉️</div>
                <p class="empty-title">No messages yet</p>
                <p class="empty-desc">When visitors submit your contact form, they'll appear here.</p>
              </div>
            <?php else: ?>
              <?php foreach ($latestMessages as $m): ?>
              <a href="<?= SITE_URL ?>/admin/messages.php?id=<?= (int)$m['id'] ?>"
                 class="msg-item <?= !$m['is_read'] ? 'msg-unread' : '' ?>">
                <div class="msg-avatar" style="background:<?= !$m['is_read'] ? 'rgba(239,68,68,.12)' : 'var(--bg-2)' ?>;">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="<?= !$m['is_read'] ? '#ef4444' : 'var(--text-muted)' ?>" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div class="msg-body">
                  <div class="msg-row">
                    <span class="msg-name"><?= e(truncate($m['name'],22)) ?></span>
                    <span class="msg-time"><?= timeAgo($m['submitted_at']) ?></span>
                  </div>
                  <div class="msg-subject"><?= e(truncate($m['subject'] ?: 'No subject',44)) ?></div>
                  <?php if (!$m['is_read']): ?>
                    <span class="msg-new-badge">NEW</span>
                  <?php endif; ?>
                </div>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Quick Actions</div>
              <div class="card-subtitle">Jump to common tasks</div>
            </div>
          </div>
          <div class="card-body">
            <div class="quick-actions-grid">
              <?php foreach ($quickActions as $qa): ?>
              <?php
                $qSvg = match($qa['icon']) {
                  'code'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
                  'edit'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                  'book'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
                  'users'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                  'mail'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
                  'settings' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                  default    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>',
                };
              ?>
              <a href="<?= e($qa['url']) ?>" class="quick-action-tile">
                <div class="qa-icon-wrap" style="background:<?= e($qa['bg']) ?>;color:<?= e($qa['color']) ?>;">
                  <?= $qSvg ?>
                </div>
                <div class="qa-text">
                  <div class="qa-label"><?= e($qa['label']) ?></div>
                  <div class="qa-desc"><?= e($qa['desc']) ?></div>
                </div>
                <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      </div>

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<!-- ══ Dashboard-specific styles ════════════════════════════ -->
<style>
/* KPI mini progress bar */
.stat-progress-wrap { margin-top: 10px; }
.stat-mini-bar {
  height: 3px; background: var(--border); border-radius: 99px; overflow: hidden;
}
.stat-mini-bar::after {
  content: '';
  display: block;
  height: 100%;
  width: var(--bar-pct, 50%);
  background: var(--bar-color, var(--accent));
  border-radius: 99px;
  transition: width 1s ease;
}

/* Activity feed */
.activity-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 18px;
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  text-decoration: none;
  cursor: pointer;
}
.activity-item:hover { background: var(--table-alt); }
.activity-item.unread { background: rgba(245,158,11,.05); }
.activity-item:last-child { border-bottom: none; }
.activity-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.activity-body { flex: 1; overflow: hidden; }
.activity-title {
  font-size: 12.5px; font-weight: 600; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.activity-sub {
  font-size: 11px; color: var(--text-muted); margin-top: 2px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.activity-time {
  font-size: 11px; color: var(--text-dim); white-space: nowrap; flex-shrink: 0;
}

/* Message list */
.msg-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
  transition: background var(--transition);
  text-decoration: none; cursor: pointer;
}
.msg-item:hover { background: var(--table-alt); }
.msg-item.msg-unread { background: rgba(239,68,68,.04); }
.msg-item:last-child { border-bottom: none; }
.msg-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  display: grid; place-items: center; flex-shrink: 0;
}
.msg-body { flex: 1; overflow: hidden; }
.msg-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.msg-name { font-size: 13px; font-weight: 700; color: var(--text); }
.msg-time { font-size: 11px; color: var(--text-dim); white-space: nowrap; }
.msg-subject {
  font-size: 12px; color: var(--text-muted); margin-top: 3px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.msg-new-badge {
  display: inline-block; margin-top: 5px;
  background: var(--danger); color: #fff;
  padding: 1px 7px; border-radius: 4px;
  font-size: 9px; font-weight: 700; letter-spacing: .5px;
}

/* Quick actions grid */
.quick-actions-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
}
.quick-action-tile {
  display: flex; align-items: center; gap: 12px;
  padding: 13px 14px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  text-decoration: none;
  transition: all var(--transition);
  position: relative; overflow: hidden;
}
.quick-action-tile:hover {
  border-color: var(--border-mid);
  background: var(--card-hover);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}
.qa-icon-wrap {
  width: 38px; height: 38px; border-radius: var(--radius-sm);
  display: grid; place-items: center; flex-shrink: 0;
}
.qa-text { flex: 1; overflow: hidden; }
.qa-label { font-size: 13px; font-weight: 700; color: var(--text); }
.qa-desc { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.qa-arrow { flex-shrink: 0; color: var(--text-dim); transition: transform var(--transition); }
.quick-action-tile:hover .qa-arrow { transform: translateX(3px); color: var(--text-muted); }

/* Live pulse animation */
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(.85); }
}

/* Responsive adjustments */
@media (max-width: 1100px) {
  .chart-row { grid-template-columns: 1fr !important; }
  .chart-row > *:last-child { display: none; } /* hide device chart on small screens */
}
@media (max-width: 900px) {
  .main-grid, .bottom-grid { grid-template-columns: 1fr !important; }
  .quick-actions-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- ══ Scripts ════════════════════════════════════════════════ -->
<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<script>
/* ── PHP data → JS ───────────────────────────────────────────── */
const MONTHLY_LABELS  = <?= json_encode(array_column($monthlyRows, 'label')) ?>;
const MONTHLY_VIEWS   = <?= json_encode(array_map('intval', array_column($monthlyRows, 'visits'))) ?>;
const MONTHLY_UNIQUE  = <?= json_encode(array_map('intval', array_column($monthlyRows, 'unique_v'))) ?>;
const DEVICE_LABELS   = <?= json_encode(array_column($deviceRows, 'device_type')) ?>;
const DEVICE_DATA     = <?= json_encode(array_map('intval', array_column($deviceRows, 'cnt'))) ?>;

/* ── Chart initialization ──────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Monthly chart
  if (MONTHLY_LABELS.length > 0) {
    makeLineChart('monthlyChart', MONTHLY_LABELS, [
      {
        label: 'Page Views',
        data: MONTHLY_VIEWS,
        borderColor: '#ffc400',
        backgroundColor: 'rgba(255,196,0,.07)',
        fill: true, tension: .4,
        pointRadius: 4, pointHoverRadius: 6,
        pointBackgroundColor: '#ffc400',
        pointBorderColor: 'var(--card-bg)',
        pointBorderWidth: 2,
      },
      {
        label: 'Unique Visitors',
        data: MONTHLY_UNIQUE,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,.05)',
        fill: true, tension: .4,
        pointRadius: 3, pointHoverRadius: 5,
        pointBackgroundColor: '#3b82f6',
      }
    ]);
  }

  // Device doughnut
  if (DEVICE_LABELS.length > 0) {
    makeDoughnutChart(
      'deviceChart',
      DEVICE_LABELS.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
      DEVICE_DATA,
      ['#ffc400','#3b82f6','#22c55e','#6b7280']
    );
  }

  // Flash messages
  <?php $flash = flash(); if ($flash): ?>
    Toast.<?= $flash['type'] === 'success' ? 'success' : 'error' ?>(
      <?= json_encode($flash['message']) ?>
    );
  <?php endif; ?>
});

/* ── AJAX Live Refresh (every 60 seconds) ─────────────────── */
let refreshTimer = null;
let countdown    = 60;
const refreshLabel = document.getElementById('refreshLabel');

function startCountdown() {
  clearInterval(refreshTimer);
  countdown = 60;
  refreshTimer = setInterval(() => {
    countdown--;
    if (refreshLabel) refreshLabel.textContent = `Refresh in ${countdown}s`;
    if (countdown <= 0) {
      refreshStats();
      countdown = 60;
    }
  }, 1000);
}

async function refreshStats() {
  const dot = document.getElementById('refreshDot');
  if (dot) dot.style.background = 'var(--warning)';
  if (refreshLabel) refreshLabel.textContent = 'Refreshing…';
  try {
    const data = await apiGet('<?= SITE_URL ?>/api/admin/dashboard.php?section=stats');
    if (!data.success) throw new Error(data.error);
    const s = data.stats;

    // Update KPI values with animation
    animateCount('kpi-users',    s.total_users);
    animateCount('kpi-blogs',    s.total_blogs);
    animateCount('kpi-courses',  s.total_courses);
    animateCount('kpi-leet',     s.total_leet);
    animateCount('kpi-msgs',     s.total_msgs);
    animateCount('kpi-visitors', s.visitors_30d);

    // Update unread badge
    const unreadBadge = document.getElementById('kpi-unread-badge');
    if (unreadBadge) {
      unreadBadge.textContent = s.unread_msgs + ' unread';
      unreadBadge.style.display = s.unread_msgs > 0 ? '' : 'none';
    }

    if (dot) dot.style.background = 'var(--success)';
    if (refreshLabel) refreshLabel.textContent = 'Live';
  } catch (err) {
    if (dot) dot.style.background = 'var(--danger)';
    if (refreshLabel) refreshLabel.textContent = 'Offline';
    console.warn('Dashboard refresh failed:', err);
  }
  startCountdown();
}

async function refreshActivity() {
  const btn = document.getElementById('refreshActivityBtn');
  if (btn) { btn.disabled = true; btn.style.opacity = '.5'; }

  try {
    const data = await apiGet('<?= SITE_URL ?>/api/admin/dashboard.php?section=activity');
    if (!data.success || !data.activity) throw new Error('No activity data');

    const feed = document.getElementById('activityFeed');
    if (!feed) return;

    const icons = {
      user_joined: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
      message_received: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`,
      solution_added: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>`,
      blog_added: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>`,
    };
    const colors = {
      user_joined: '#3b82f6', message_received: '#f59e0b',
      solution_added: '#ffc400', blog_added: '#22c55e',
    };

    feed.innerHTML = data.activity.map(a => `
      <a href="${escHtml(a.url || '#')}" class="activity-item ${a.unread ? 'unread' : ''}">
        <div class="activity-icon" style="background:var(--bg-2);color:${colors[a.type] || 'var(--text-muted)'};">
          ${icons[a.type] || ''}
        </div>
        <div class="activity-body">
          <div class="activity-title">${escHtml((a.title||'').substring(0,48))}</div>
          <div class="activity-sub">${escHtml((a.sub||'').substring(0,40))}</div>
        </div>
        <div class="activity-time">${escHtml(a.time_ago || '')}</div>
      </a>
    `).join('');

    Toast.success('Activity refreshed', 'Latest data loaded.');
  } catch (err) {
    Toast.error('Refresh failed', 'Could not load activity.');
  } finally {
    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
  }
}

/* ── Animated counter ─────────────────────────────────────── */
function animateCount(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  const current = parseInt(el.textContent.replace(/,/g,''), 10) || 0;
  const diff = target - current;
  if (diff === 0) return;
  const steps = 20;
  const step  = diff / steps;
  let count   = current;
  let i = 0;
  const timer = setInterval(() => {
    count += step;
    i++;
    el.textContent = new Intl.NumberFormat().format(Math.round(count));
    if (i >= steps) {
      el.textContent = new Intl.NumberFormat().format(target);
      clearInterval(timer);
    }
  }, 30);
}

// Start live refresh
startCountdown();

// Keyboard shortcut R to refresh
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'r' && !e.shiftKey) {
    // don't intercept hard refresh (Shift+R or default)
    return;
  }
  if (e.key === 'F5') refreshStats();
});
</script>
</body>
</html>
