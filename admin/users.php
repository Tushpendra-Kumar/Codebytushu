<?php
/**
 * CodeByTushu — Admin User Management (Complete Module v2)
 * Features: listing, search, multi-filter, add, edit, delete, ban/activate,
 *           change role, view profile, bulk actions (delete/activate/ban).
 */
declare(strict_types=1);

$adminSection = 'Users';
$adminTitle   = 'User Management — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'User Management'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ══ Query params ═══════════════════════════════════════════ */
$perPage  = 20;
$page     = max(1, (int)get('page', '1'));
$search   = trim(get('search', ''));
$status   = get('status', '');
$role     = get('role', '');
$verified = get('verified', ''); // '' | '1' | '0'
$sort     = get('sort', 'created_at');
$dir      = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

$sortCols = ['id','full_name','email','role','status','login_count','last_login','created_at'];
if (!in_array($sort, $sortCols)) $sort = 'created_at';

/* ══ WHERE builder ══════════════════════════════════════════ */
$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = '(full_name LIKE ? OR email LIKE ? OR username LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}
if ($status)   { $where[] = 'status = ?';         $params[] = $status; }
if ($role)     { $where[] = 'role = ?';            $params[] = $role; }
if ($verified !== '') { $where[] = 'email_verified = ?'; $params[] = (int)$verified; }

$whereClause = implode(' AND ', $where);

/* ══ Counts (summary cards) ════════════════════════════════ */
try {
    $counts = [
        'total'   => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
        'banned'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='banned'")->fetchColumn(),
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn(),
        'admins'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','super_admin')")->fetchColumn(),
        'new7d'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    ];
} catch (\Throwable) { $counts = ['total'=>0,'active'=>0,'banned'=>0,'pending'=>0,'admins'=>0,'new7d'=>0]; }

/* ══ Total filtered ════════════════════════════════════════ */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = paginate($total, $perPage, $page);

/* ══ User rows ══════════════════════════════════════════════ */
$stmt = $pdo->prepare(
    "SELECT id, full_name, username, email, role, status, email_verified,
            profile_image, login_count, last_login, created_at
       FROM users
      WHERE $whereClause
      ORDER BY $sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

/* ══ Active filters flag ════════════════════════════════════ */
$hasFilters = $search || $status || $role || $verified !== '';
$filterCount = (int)(bool)$search + (int)(bool)$status + (int)(bool)$role + (int)($verified !== '');

/* ══ Helper: build sort URL ════════════════════════════════ */
function sortUrl(string $col, string $curSort, string $curDir, array $extra = []): string {
    $newDir = ($col === $curSort && $curDir === 'DESC') ? 'ASC' : 'DESC';
    return '?' . http_build_query(array_merge($extra, ['sort' => $col, 'dir' => $newDir]));
}

$urlExtras = array_filter(compact('search', 'status', 'role', 'verified', 'page'));
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── Summary strip ── */
.user-summary { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:22px; }
.usm-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
            padding:14px 16px; text-align:center; cursor:pointer; transition:all var(--transition); }
.usm-card:hover { border-color:var(--accent); background:var(--card-hover); transform:translateY(-1px); }
.usm-card.active-filter { border-color:var(--accent); background:var(--accent-glow); }
.usm-count { font-size:22px; font-weight:800; color:var(--text); line-height:1; margin-bottom:4px; }
.usm-label { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; }
.usm-icon  { font-size:18px; margin-bottom:6px; }

/* ── Toolbar ── */
.users-toolbar { display:flex; align-items:center; gap:10px; padding:14px 20px;
                 background:var(--card-bg); border:1px solid var(--border);
                 border-radius:var(--radius-lg) var(--radius-lg) 0 0;
                 flex-wrap:wrap; }
.users-toolbar .search-input-wrap { flex:1; min-width:200px; }

/* ── Bulk bar ── */
.bulk-bar { display:none; align-items:center; gap:10px; padding:10px 20px;
            background:var(--accent-glow); border:1px solid var(--accent);
            border-radius:var(--radius); margin-bottom:10px;
            font-size:13px; font-weight:600; color:var(--accent); }
.bulk-bar.visible { display:flex; }

/* ── Sort indicators ── */
th a.sort-lnk { color:inherit; text-decoration:none; display:flex; align-items:center; gap:4px; white-space:nowrap; }
th a.sort-lnk:hover { color:var(--accent); }
.sort-arrow { font-size:10px; color:var(--text-dim); }
.sort-arrow.active { color:var(--accent); }

/* ── User profile avatar ── */
.u-avatar { width:36px; height:36px; border-radius:50%; background:var(--accent-glow);
            border:1.5px solid var(--accent); display:grid; place-items:center;
            font-size:13px; font-weight:700; color:var(--accent);
            overflow:hidden; flex-shrink:0; cursor:pointer; transition:opacity var(--transition); }
.u-avatar:hover { opacity:.8; }

/* ── Action buttons ── */
.row-actions { display:flex; gap:4px; justify-content:flex-end; }

/* ── Filter chips ── */
.filter-chips { display:flex; gap:6px; flex-wrap:wrap; padding:0 20px 12px; }
.chip { display:inline-flex; align-items:center; gap:6px; background:var(--accent-glow);
        border:1px solid var(--accent); border-radius:99px; padding:3px 10px;
        font-size:11px; color:var(--accent); font-weight:600; }
.chip button { background:none; border:none; cursor:pointer; color:var(--accent);
               font-size:13px; line-height:1; padding:0; display:flex; align-items:center; }

/* ── Password strength ── */
.pw-strength { height:3px; border-radius:99px; margin-top:6px; transition:all .3s;
               background:var(--border); }
.pw-strength-fill { height:100%; border-radius:99px; transition:all .3s; }

/* ── Profile slide panel ── */
.profile-panel { position:fixed; top:0; right:-440px; width:420px; height:100vh;
                 background:var(--card-bg); border-left:1px solid var(--border);
                 z-index:1100; transition:right .3s cubic-bezier(.4,0,.2,1);
                 overflow-y:auto; display:flex; flex-direction:column; box-shadow:var(--shadow-xl); }
.profile-panel.open { right:0; }
.profile-panel-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4);
                         z-index:1099; opacity:0; pointer-events:none; transition:opacity .3s; }
.profile-panel-overlay.open { opacity:1; pointer-events:all; }
.pp-header { padding:20px; border-bottom:1px solid var(--border); display:flex;
             align-items:center; justify-content:space-between; flex-shrink:0; }
.pp-avatar { width:72px; height:72px; border-radius:50%; background:var(--accent-glow);
             border:2px solid var(--accent); display:grid; place-items:center;
             font-size:28px; font-weight:700; color:var(--accent); overflow:hidden; margin:0 auto 12px; }
.pp-body { padding:20px; flex:1; }
.pp-stat { display:flex; justify-content:space-between; padding:10px 0;
           border-bottom:1px solid var(--border); font-size:13px; }
.pp-stat:last-child { border-bottom:none; }
.pp-stat-label { color:var(--text-muted); }
.pp-stat-value { font-weight:600; color:var(--text); }

/* ── Responsive ── */
@media (max-width:900px) {
  .user-summary { grid-template-columns:repeat(3,1fr); }
  .users-toolbar { flex-direction:column; align-items:stretch; }
}
@media (max-width:600px) {
  .user-summary { grid-template-columns:repeat(2,1fr); }
  .profile-panel { width:100%; right:-100%; }
}
</style>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">

      <!-- ══ Page Header ══════════════════════════════════ -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">User Management</h1>
          <p class="page-subtitle">
            <?= number_format($total) ?> user<?= $total !== 1 ? 's' : '' ?>
            <?= $hasFilters ? 'matched — <a href="<?= SITE_URL ?>/admin/users.php" style="color:var(--accent);">clear filters</a>' : 'total' ?>
          </p>
        </div>
        <div class="page-header-actions">
          <button class="btn btn-primary" onclick="openAddUser()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add User
          </button>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- ══ Summary Strip ════════════════════════════════ -->
      <div class="user-summary">
        <a href="<?= SITE_URL ?>/admin/users.php" class="usm-card <?= !$hasFilters ? 'active-filter' : '' ?>" style="text-decoration:none;">
          <div class="usm-icon">👥</div>
          <div class="usm-count"><?= number_format($counts['total']) ?></div>
          <div class="usm-label">All Users</div>
        </a>
        <a href="?status=active" class="usm-card <?= $status==='active' ? 'active-filter' : '' ?>" style="text-decoration:none;">
          <div class="usm-icon" style="color:#22c55e;">●</div>
          <div class="usm-count" style="color:#22c55e;"><?= number_format($counts['active']) ?></div>
          <div class="usm-label">Active</div>
        </a>
        <a href="?status=banned" class="usm-card <?= $status==='banned' ? 'active-filter' : '' ?>" style="text-decoration:none;">
          <div class="usm-icon" style="color:#ef4444;">●</div>
          <div class="usm-count" style="color:#ef4444;"><?= number_format($counts['banned']) ?></div>
          <div class="usm-label">Banned</div>
        </a>
        <a href="?status=pending" class="usm-card <?= $status==='pending' ? 'active-filter' : '' ?>" style="text-decoration:none;">
          <div class="usm-icon" style="color:#f59e0b;">●</div>
          <div class="usm-count" style="color:#f59e0b;"><?= number_format($counts['pending']) ?></div>
          <div class="usm-label">Pending</div>
        </a>
        <a href="?role=admin" class="usm-card <?= in_array($role,['admin','super_admin']) ? 'active-filter' : '' ?>" style="text-decoration:none;">
          <div class="usm-icon">🛡️</div>
          <div class="usm-count"><?= number_format($counts['admins']) ?></div>
          <div class="usm-label">Admins</div>
        </a>
        <a href="?sort=created_at&dir=DESC" class="usm-card" style="text-decoration:none;">
          <div class="usm-icon">✨</div>
          <div class="usm-count" style="color:var(--accent);"><?= number_format($counts['new7d']) ?></div>
          <div class="usm-label">New (7d)</div>
        </a>
      </div>

      <!-- ══ Bulk Bar ══════════════════════════════════════ -->
      <div class="bulk-bar" id="bulkBar">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span id="bulkCount">0</span> selected
        <div style="margin-left:auto;display:flex;gap:8px;">
          <button class="btn btn-success btn-sm" onclick="bulkAction('bulk_activate')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Activate All
          </button>
          <button class="btn btn-warning btn-sm" onclick="bulkAction('bulk_ban')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Ban All
          </button>
          <button class="btn btn-danger btn-sm" onclick="bulkAction('bulk_delete')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
            Delete All
          </button>
          <button class="btn btn-ghost btn-sm" onclick="clearSelection()">✕ Cancel</button>
        </div>
      </div>

      <!-- ══ Toolbar ═══════════════════════════════════════ -->
      <div class="users-toolbar">
        <form id="filterForm" method="GET" action="<?= SITE_URL ?>/admin/users.php" style="display:contents;">
          <div class="search-input-wrap">
            <span class="si">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" name="search" id="searchInput"
                   placeholder="Search name, email, username…"
                   value="<?= e($search) ?>" autocomplete="off">
          </div>
          <select name="status" class="form-control" style="width:130px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active"  <?= $status==='active'  ? 'selected' : '' ?>>Active</option>
            <option value="banned"  <?= $status==='banned'  ? 'selected' : '' ?>>Banned</option>
            <option value="pending" <?= $status==='pending' ? 'selected' : '' ?>>Pending</option>
          </select>
          <select name="role" class="form-control" style="width:130px;" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="user"        <?= $role==='user'        ? 'selected' : '' ?>>User</option>
            <option value="editor"      <?= $role==='editor'      ? 'selected' : '' ?>>Editor</option>
            <option value="admin"       <?= $role==='admin'       ? 'selected' : '' ?>>Admin</option>
            <option value="super_admin" <?= $role==='super_admin' ? 'selected' : '' ?>>Super Admin</option>
          </select>
          <select name="verified" class="form-control" style="width:130px;" onchange="this.form.submit()">
            <option value="">All Verified</option>
            <option value="1" <?= $verified==='1' ? 'selected' : '' ?>>✓ Verified</option>
            <option value="0" <?= $verified==='0' ? 'selected' : '' ?>>✗ Unverified</option>
          </select>
          <!-- Hidden sort passthrough -->
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
          <input type="hidden" name="dir"  value="<?= e($dir) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
          <?php if ($hasFilters): ?>
            <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Clear (<?= $filterCount ?>)
            </a>
          <?php endif; ?>
          <span style="margin-left:auto;font-size:12px;color:var(--text-muted);">
            <?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?>
          </span>
        </form>
      </div>

      <!-- ══ Active filter chips ═══════════════════════════ -->
      <?php if ($hasFilters): ?>
      <div class="filter-chips" style="padding:8px 0 12px;">
        <?php if ($search): ?>
          <span class="chip">Search: "<?= e($search) ?>" <button onclick="removeFilter('search')">×</button></span>
        <?php endif; ?>
        <?php if ($status): ?>
          <span class="chip">Status: <?= e($status) ?> <button onclick="removeFilter('status')">×</button></span>
        <?php endif; ?>
        <?php if ($role): ?>
          <span class="chip">Role: <?= e(str_replace('_',' ',$role)) ?> <button onclick="removeFilter('role')">×</button></span>
        <?php endif; ?>
        <?php if ($verified !== ''): ?>
          <span class="chip">Verified: <?= $verified ? 'Yes' : 'No' ?> <button onclick="removeFilter('verified')">×</button></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- ══ Table ═════════════════════════════════════════ -->
      <div class="card" style="border-radius:0 0 var(--radius-lg) var(--radius-lg);border-top:none;padding:0;">
        <div class="table-wrap">
          <table class="admin-table" id="usersTable">
            <thead>
              <tr>
                <th style="width:40px;">
                  <input type="checkbox" id="selectAll" class="cb-master"
                         title="Select all" style="cursor:pointer;accent-color:var(--accent);">
                </th>
                <?php
                $cols = [
                  ['key'=>'full_name','label'=>'User'],
                  ['key'=>'username','label'=>'Username'],
                  ['key'=>'role','label'=>'Role'],
                  ['key'=>'status','label'=>'Status'],
                  ['key'=>'email_verified','label'=>'Verified'],
                  ['key'=>'login_count','label'=>'Logins'],
                  ['key'=>'last_login','label'=>'Last Login'],
                  ['key'=>'created_at','label'=>'Joined'],
                ];
                foreach ($cols as $col):
                  $isActive = $sort === $col['key'];
                  $arrow = $isActive ? ($dir === 'DESC' ? ' ▼' : ' ▲') : ' ⇅';
                ?>
                <th>
                  <a class="sort-lnk" href="<?= sortUrl($col['key'], $sort, $dir, array_filter(compact('search','status','role','verified'))) ?>">
                    <?= $col['label'] ?>
                    <span class="sort-arrow <?= $isActive ? 'active' : '' ?>"><?= $arrow ?></span>
                  </a>
                </th>
                <?php endforeach; ?>
                <th style="text-align:right;width:120px;">Actions</th>
              </tr>
            </thead>
            <tbody id="usersBody">
              <?php foreach ($users as $i => $u): ?>
              <?php
                $roleBadge = match($u['role']) {
                  'super_admin' => 'accent', 'admin' => 'warning', 'editor' => 'info', default => 'muted'
                };
                $statusBadge = match($u['status']) {
                  'active' => 'success', 'banned' => 'danger', default => 'warning'
                };
                $initial = strtoupper(substr($u['full_name'], 0, 1));
                $isSelf  = $u['id'] === Auth::id();
              ?>
              <tr data-id="<?= $u['id'] ?>" class="user-row">
                <td>
                  <?php if (!$isSelf): ?>
                  <input type="checkbox" class="cb-row" value="<?= $u['id'] ?>"
                         style="cursor:pointer;accent-color:var(--accent);">
                  <?php else: ?>
                  <span title="Cannot select yourself" style="color:var(--text-dim);font-size:10px;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div class="u-avatar" onclick="viewProfile(<?= $u['id'] ?>)" title="View profile">
                      <?php if ($u['profile_image']): ?>
                        <img src="<?= e($u['profile_image']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                      <?php else: ?>
                        <?= $initial ?>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div style="font-weight:600;font-size:13px;color:var(--text);">
                        <?= e($u['full_name']) ?>
                        <?php if ($isSelf): ?><span class="badge badge-accent" style="font-size:9px;margin-left:4px;">You</span><?php endif; ?>
                      </div>
                      <div style="font-size:11px;color:var(--text-muted);"><?= e($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="color:var(--text-muted);font-size:12px;">@<?= e($u['username']) ?></td>
                <td><span class="badge badge-<?= $roleBadge ?>"><?= e(str_replace('_',' ',$u['role'])) ?></span></td>
                <td><span class="badge badge-<?= $statusBadge ?>"><?= e($u['status']) ?></span></td>
                <td>
                  <?php if ($u['email_verified']): ?>
                    <span class="badge badge-success" style="font-size:10px;">✓ Yes</span>
                  <?php else: ?>
                    <span class="badge badge-muted" style="font-size:10px;">No</span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px;"><?= number_format($u['login_count']) ?></td>
                <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;">
                  <?= $u['last_login'] ? timeAgo($u['last_login']) : '<span style="color:var(--text-dim)">Never</span>' ?>
                </td>
                <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;">
                  <?= formatDate($u['created_at'], 'd M Y') ?>
                </td>
                <td>
                  <div class="row-actions">
                    <!-- View -->
                    <button class="btn btn-ghost btn-sm btn-icon" title="View profile"
                            onclick="viewProfile(<?= $u['id'] ?>)">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <!-- Edit -->
                    <button class="btn btn-ghost btn-sm btn-icon" title="Edit user"
                            onclick="openEditUser(<?= $u['id'] ?>,'<?= e(addslashes($u['full_name'])) ?>','<?= e(addslashes($u['email'])) ?>','<?= e(addslashes($u['username'])) ?>','<?= $u['role'] ?>','<?= $u['status'] ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </button>
                    <?php if (!$isSelf): ?>
                      <!-- Ban / Activate toggle -->
                      <?php if ($u['status'] === 'banned'): ?>
                        <button class="btn btn-success btn-sm btn-icon" title="Activate user"
                                onclick="setStatus(<?= $u['id'] ?>,'active')">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                      <?php else: ?>
                        <button class="btn btn-warning btn-sm btn-icon" title="Ban user"
                                onclick="setStatus(<?= $u['id'] ?>,'banned')">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        </button>
                      <?php endif; ?>
                      <!-- Delete -->
                      <button class="btn btn-danger btn-sm btn-icon" title="Delete user"
                              onclick="deleteUser(<?= $u['id'] ?>)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                      </button>
                    <?php else: ?>
                      <span style="font-size:10px;color:var(--text-dim);padding:0 4px;">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($users)): ?>
              <tr>
                <td colspan="10">
                  <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <p class="empty-title">No users found</p>
                    <p class="empty-desc">
                      <?= $hasFilters ? 'Try adjusting your filters.' : 'No users registered yet.' ?>
                      <?php if ($hasFilters): ?>
                        <a href="<?= SITE_URL ?>/admin/users.php" style="color:var(--accent);">Clear filters →</a>
                      <?php endif; ?>
                    </p>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php
        $queryParams = array_filter(compact('search', 'status', 'role', 'verified', 'sort', 'dir'));
        require __DIR__ . '/includes/pagination.php';
        ?>
      </div>

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<!-- ══════════════════════════════════════════════════════════
     PROFILE SLIDE PANEL
     ════════════════════════════════════════════════════════ -->
<div class="profile-panel-overlay" id="ppOverlay" onclick="closeProfile()"></div>
<div class="profile-panel" id="profilePanel">
  <div class="pp-header">
    <span style="font-size:14px;font-weight:700;color:var(--text);">User Profile</span>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-ghost btn-sm btn-icon" id="ppEditBtn" title="Edit this user">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </button>
      <button class="btn btn-ghost btn-sm btn-icon" onclick="closeProfile()">✕</button>
    </div>
  </div>
  <div id="ppContent" style="padding:20px;text-align:center;">
    <div style="color:var(--text-muted);font-size:13px;">Loading…</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ADD USER MODAL
     ════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2 class="modal-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        Add New User
      </h2>
      <button class="modal-close" onclick="Modal.close('addUserModal')">✕</button>
    </div>
    <form id="addUserForm" novalidate>
      <div class="modal-body">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" id="addFullName" class="form-control"
                   placeholder="John Doe" required autocomplete="off">
            <span class="form-error" id="err-full_name"></span>
          </div>
          <div class="form-group">
            <label class="form-label">Username <span class="req">*</span></label>
            <input type="text" name="username" id="addUsername" class="form-control"
                   placeholder="johndoe" required autocomplete="off">
            <span class="form-error" id="err-username"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address <span class="req">*</span></label>
          <input type="email" name="email" id="addEmail" class="form-control"
                 placeholder="john@example.com" required autocomplete="off">
          <span class="form-error" id="err-email"></span>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Password <span class="req">*</span></label>
            <div style="position:relative;">
              <input type="password" name="password" id="addPassword" class="form-control"
                     placeholder="Min 8 characters" required autocomplete="new-password"
                     oninput="checkPwStrength(this.value,'addPwStrength')">
              <button type="button" onclick="togglePw('addPassword',this)"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="pw-strength" style="margin-top:6px;"><div class="pw-strength-fill" id="addPwStrength"></div></div>
            <span class="form-error" id="err-password"></span>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password <span class="req">*</span></label>
            <input type="password" name="confirm_password" id="addConfirmPw" class="form-control"
                   placeholder="Re-enter password" required>
            <span class="form-error" id="err-confirm_password"></span>
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" id="addRole" class="form-control">
              <option value="user" selected>User</option>
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
              <?php if (Auth::isSuperAdmin()): ?>
              <option value="super_admin">Super Admin</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="addStatus" class="form-control">
              <option value="active" selected>Active</option>
              <option value="pending">Pending</option>
              <option value="banned">Banned</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="Modal.close('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="addUserSubmit">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Create User
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     EDIT USER MODAL
     ════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2 class="modal-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Edit User
      </h2>
      <button class="modal-close" onclick="Modal.close('editUserModal')">✕</button>
    </div>
    <form id="editUserForm" novalidate>
      <div class="modal-body">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editUserId">
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" id="editFullName" class="form-control" required>
            <span class="form-error" id="err-edit-full_name"></span>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address <span class="req">*</span></label>
            <input type="email" name="email" id="editEmail" class="form-control" required>
            <span class="form-error" id="err-edit-email"></span>
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" id="editRole" class="form-control">
              <option value="user">User</option>
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
              <?php if (Auth::isSuperAdmin()): ?>
              <option value="super_admin">Super Admin</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="editStatus" class="form-control">
              <option value="active">Active</option>
              <option value="banned">Banned</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">
            New Password
            <span style="font-weight:400;text-transform:none;font-size:11px;">(leave blank to keep current)</span>
          </label>
          <div style="position:relative;">
            <input type="password" name="new_password" id="editPassword" class="form-control"
                   placeholder="Optional — min 8 characters" autocomplete="new-password"
                   oninput="checkPwStrength(this.value,'editPwStrength')">
            <button type="button" onclick="togglePw('editPassword',this)"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="pw-strength"><div class="pw-strength-fill" id="editPwStrength"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="Modal.close('editUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ──────────────────────────────────────────────────────────
   CHECKBOX / BULK SELECTION
   ────────────────────────────────────────────────────────── */
const selectAll  = document.getElementById('selectAll');
const bulkBar    = document.getElementById('bulkBar');
const bulkCount  = document.getElementById('bulkCount');
const usersBody  = document.getElementById('usersBody');

function getChecked() {
  return [...document.querySelectorAll('.cb-row:checked')].map(cb => parseInt(cb.value));
}

function updateBulkBar() {
  const ids = getChecked();
  if (ids.length > 0) {
    bulkBar.classList.add('visible');
    bulkCount.textContent = ids.length;
  } else {
    bulkBar.classList.remove('visible');
  }
}

function clearSelection() {
  document.querySelectorAll('.cb-row').forEach(cb => cb.checked = false);
  selectAll.checked = false;
  selectAll.indeterminate = false;
  updateBulkBar();
}

selectAll.addEventListener('change', () => {
  document.querySelectorAll('.cb-row').forEach(cb => cb.checked = selectAll.checked);
  updateBulkBar();
});

usersBody.addEventListener('change', e => {
  if (!e.target.classList.contains('cb-row')) return;
  const all  = document.querySelectorAll('.cb-row');
  const checked = document.querySelectorAll('.cb-row:checked');
  selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
  selectAll.checked = checked.length === all.length;
  updateBulkBar();
});

/* ──────────────────────────────────────────────────────────
   BULK ACTIONS
   ────────────────────────────────────────────────────────── */
async function bulkAction(action) {
  const ids = getChecked();
  if (!ids.length) return;

  const labels = {
    bulk_delete:   { title:'Delete Selected', msg:`Permanently delete ${ids.length} user(s)?`, btn:'Delete', type:'danger' },
    bulk_activate: { title:'Activate Selected', msg:`Activate ${ids.length} user(s)?`, btn:'Activate', type:'success' },
    bulk_ban:      { title:'Ban Selected', msg:`Ban ${ids.length} user(s)?`, btn:'Ban', type:'danger' },
  };
  const cfg = labels[action];

  Modal.confirm(cfg.msg, async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/users.php', { action, ids: ids.join(',') });
    if (r.success) {
      Toast.success('Done', r.message);
      if (action === 'bulk_delete') {
        ids.forEach(id => document.querySelector(`[data-id="${id}"]`)?.remove());
      } else {
        setTimeout(() => location.reload(), 800);
      }
      clearSelection();
    } else {
      Toast.error('Error', r.error);
    }
  }, { title: cfg.title, confirmText: cfg.btn, type: cfg.type });
}

/* ──────────────────────────────────────────────────────────
   FILTER CHIPS — remove individual filter
   ────────────────────────────────────────────────────────── */
function removeFilter(name) {
  const form = document.getElementById('filterForm');
  const el   = form.querySelector(`[name="${name}"]`);
  if (el) { el.value = ''; form.submit(); }
}

/* ──────────────────────────────────────────────────────────
   ADD USER
   ────────────────────────────────────────────────────────── */
function openAddUser() {
  document.getElementById('addUserForm').reset();
  document.getElementById('addPwStrength').style.width = '0';
  document.getElementById('addPwStrength').style.background = 'transparent';
  // Clear errors
  document.querySelectorAll('[id^="err-"]').forEach(el => el.textContent = '');
  Modal.open('addUserModal');
  setTimeout(() => document.getElementById('addFullName').focus(), 200);
}

/* ──────────────────────────────────────────────────────────
   EDIT USER
   ────────────────────────────────────────────────────────── */
function openEditUser(id, name, email, username, role, status) {
  document.getElementById('editUserId').value   = id;
  document.getElementById('editFullName').value = name;
  document.getElementById('editEmail').value    = email;
  document.getElementById('editRole').value     = role;
  document.getElementById('editStatus').value   = status;
  document.getElementById('editPassword').value = '';
  document.getElementById('editPwStrength').style.width = '0';
  document.getElementById('editPwStrength').style.background = 'transparent';
  Modal.open('editUserModal');
}

/* ──────────────────────────────────────────────────────────
   SET STATUS (single)
   ────────────────────────────────────────────────────────── */
async function setStatus(id, status) {
  const label = status === 'banned' ? 'ban' : 'activate';
  Modal.confirm(`Are you sure you want to ${label} this user?`, async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/users.php', { action: 'set_status', id, status });
    if (r.success) {
      Toast.success('Updated', `User ${status}.`);
      setTimeout(() => location.reload(), 800);
    } else Toast.error('Error', r.error);
  }, {
    title: status === 'banned' ? 'Ban User' : 'Activate User',
    confirmText: status === 'banned' ? 'Ban' : 'Activate',
    type: status === 'banned' ? 'danger' : 'success'
  });
}

/* ──────────────────────────────────────────────────────────
   DELETE (single)
   ────────────────────────────────────────────────────────── */
async function deleteUser(id) {
  Modal.confirm('Permanently delete this user? All their data will be removed.', async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/users.php', { action: 'delete', id });
    if (r.success) {
      document.querySelector(`[data-id="${id}"]`)?.remove();
      Toast.success('Deleted', 'User removed successfully.');
    } else Toast.error('Error', r.error);
  }, { title: 'Delete User', confirmText: 'Delete', type: 'danger' });
}

/* ──────────────────────────────────────────────────────────
   VIEW PROFILE (slide panel)
   ────────────────────────────────────────────────────────── */
async function viewProfile(id) {
  const panel   = document.getElementById('profilePanel');
  const overlay = document.getElementById('ppOverlay');
  const content = document.getElementById('ppContent');

  content.innerHTML = '<div style="padding:40px 0;text-align:center;color:var(--text-muted);">Loading…</div>';
  panel.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  try {
    const r = await apiGet(`/api/admin/users.php?action=get&id=${id}`);
    if (!r.success) throw new Error(r.error);
    const u = r.data;
    const initial  = (u.full_name||'?')[0].toUpperCase();
    const roleMap  = { user:'badge-muted', editor:'badge-info', admin:'badge-warning', super_admin:'badge-accent' };
    const statMap  = { active:'badge-success', banned:'badge-danger', pending:'badge-warning' };
    const roleClass  = roleMap[u.role]  || 'badge-muted';
    const statClass  = statMap[u.status] || 'badge-muted';

    content.innerHTML = `
      <div style="text-align:center;margin-bottom:20px;">
        <div class="pp-avatar">${u.profile_image
          ? `<img src="${escHtml(u.profile_image)}" style="width:100%;height:100%;object-fit:cover;">`
          : initial}</div>
        <div style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:6px;">${escHtml(u.full_name)}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">@${escHtml(u.username||'')} &middot; ${escHtml(u.email)}</div>
        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
          <span class="badge ${roleClass}">${escHtml((u.role||'').replace(/_/g,' '))}</span>
          <span class="badge ${statClass}">${escHtml(u.status||'')}</span>
          <span class="badge ${u.email_verified ? 'badge-success' : 'badge-muted'}">${u.email_verified ? '✓ Verified' : 'Unverified'}</span>
        </div>
      </div>
      <div style="background:var(--input-bg);border-radius:var(--radius);overflow:hidden;">
        <div class="pp-stat"><span class="pp-stat-label">User ID</span><span class="pp-stat-value">#${escHtml(String(u.id))}</span></div>
        <div class="pp-stat"><span class="pp-stat-label">Total Logins</span><span class="pp-stat-value">${Number(u.login_count||0).toLocaleString()}</span></div>
        <div class="pp-stat"><span class="pp-stat-label">Last Login</span><span class="pp-stat-value">${escHtml(u.last_login||'Never')}</span></div>
        <div class="pp-stat"><span class="pp-stat-label">Registered</span><span class="pp-stat-value">${escHtml(u.created_at||'')}</span></div>
        <div class="pp-stat"><span class="pp-stat-label">Last Updated</span><span class="pp-stat-value">${escHtml(u.updated_at||'')}</span></div>
      </div>
      ${u.id != <?= Auth::id() ?> ? `
      <div style="display:flex;gap:8px;margin-top:16px;justify-content:center;flex-wrap:wrap;">
        <button class="btn btn-ghost btn-sm" onclick="closeProfile();openEditUser(${u.id},'${escHtml(u.full_name).replace(/'/g,"\\'")}','${escHtml(u.email).replace(/'/g,"\\'")}','${escHtml(u.username||'').replace(/'/g,"\\'")}','${u.role}','${u.status}')">
          ✏️ Edit
        </button>
        ${u.status === 'banned'
          ? `<button class="btn btn-success btn-sm" onclick="closeProfile();setStatus(${u.id},'active')">✓ Activate</button>`
          : `<button class="btn btn-warning btn-sm" onclick="closeProfile();setStatus(${u.id},'banned')">🚫 Ban</button>`
        }
        <button class="btn btn-danger btn-sm" onclick="closeProfile();deleteUser(${u.id})">🗑️ Delete</button>
      </div>` : '<p style="font-size:12px;color:var(--text-muted);margin-top:12px;">This is your account.</p>'}
    `;

    // Wire edit button in panel header
    const ppEditBtn = document.getElementById('ppEditBtn');
    ppEditBtn.onclick = () => {
      closeProfile();
      openEditUser(u.id, u.full_name, u.email, u.username||'', u.role, u.status);
    };
  } catch (e) {
    content.innerHTML = `<div style="color:var(--danger);text-align:center;padding:40px 0;">${escHtml(e.message)}</div>`;
  }
}

function closeProfile() {
  document.getElementById('profilePanel').classList.remove('open');
  document.getElementById('ppOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProfile(); });

/* ──────────────────────────────────────────────────────────
   PASSWORD STRENGTH METER
   ────────────────────────────────────────────────────────── */
function checkPwStrength(pw, barId) {
  const bar = document.getElementById(barId);
  if (!bar) return;
  let score = 0;
  if (pw.length >= 8)   score++;
  if (pw.length >= 12)  score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const pct    = Math.min(100, score * 20);
  const colors = ['#ef4444','#f59e0b','#f59e0b','#22c55e','#22c55e','#22c55e'];
  bar.style.width     = pct + '%';
  bar.style.background = colors[score] || '#22c55e';
}

/* ──────────────────────────────────────────────────────────
   TOGGLE PASSWORD VISIBILITY
   ────────────────────────────────────────────────────────── */
function togglePw(inputId, btn) {
  const inp = document.getElementById(inputId);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.title = show ? 'Hide password' : 'Show password';
}

/* ──────────────────────────────────────────────────────────
   ADD USER FORM — client-side validation
   ────────────────────────────────────────────────────────── */
document.getElementById('addUserForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  // Clear errors
  document.querySelectorAll('[id^="err-"]').forEach(el => el.textContent = '');

  const pw  = document.getElementById('addPassword').value;
  const cpw = document.getElementById('addConfirmPw').value;
  let ok = true;

  if (pw.length < 8) {
    document.getElementById('err-password').textContent = 'Minimum 8 characters required.';
    ok = false;
  }
  if (pw !== cpw) {
    document.getElementById('err-confirm_password').textContent = 'Passwords do not match.';
    ok = false;
  }
  if (!ok) return;

  const btn = document.getElementById('addUserSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span style="opacity:.6">Creating…</span>';

  const fd = new FormData(this);
  const body = {};
  fd.forEach((v, k) => body[k] = v);

  const r = await apiPost('<?= SITE_URL ?>/api/admin/users.php', body);
  btn.disabled = false;
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Create User';

  if (r.success) {
    Modal.close('addUserModal');
    Toast.success('Created!', 'New user added successfully.');
    setTimeout(() => location.reload(), 1000);
  } else {
    // Show field-level error if API returns field
    if (r.field) {
      const errEl = document.getElementById(`err-${r.field}`);
      if (errEl) { errEl.textContent = r.error; return; }
    }
    Toast.error('Error', r.error);
  }
});

/* ──────────────────────────────────────────────────────────
   EDIT USER FORM — submit
   ────────────────────────────────────────────────────────── */
document.getElementById('editUserForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = this.querySelector('[type="submit"]');
  btn.disabled = true;

  const fd = new FormData(this);
  const body = {};
  fd.forEach((v, k) => body[k] = v);

  const r = await apiPost('<?= SITE_URL ?>/api/admin/users.php', body);
  btn.disabled = false;

  if (r.success) {
    Modal.close('editUserModal');
    Toast.success('Saved!', 'User updated successfully.');
    setTimeout(() => location.reload(), 800);
  } else {
    if (r.field) {
      const errEl = document.getElementById(`err-edit-${r.field}`);
      if (errEl) { errEl.textContent = r.error; return; }
    }
    Toast.error('Error', r.error);
  }
});

/* ──────────────────────────────────────────────────────────
   LIVE SEARCH (debounced)
   ────────────────────────────────────────────────────────── */
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    document.getElementById('filterForm').submit();
  }, 500);
});

/* ──────────────────────────────────────────────────────────
   FLASH MESSAGES
   ────────────────────────────────────────────────────────── */
<?php $flash = flash(); if ($flash): ?>
Toast.<?= $flash['type'] === 'success' ? 'success' : 'error' ?>(<?= json_encode($flash['message']) ?>);
<?php endif; ?>
</script>
</body>
</html>
