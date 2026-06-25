<?php
/**
 * CodeByTushu — Contact Messages Inbox v2
 * Gmail-style split-panel inbox with full message detail,
 * search, filters, bulk actions, read/unread/spam/delete.
 */
declare(strict_types=1);

$adminSection = 'Messages';
$adminTitle   = 'Contact Messages — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Messages'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ━━ Params ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$perPage = 20;
$page    = max(1, (int)get('page', '1'));
$search  = trim(get('search', ''));
$filter  = get('filter', '');       // unread | read | spam | starred
$source  = get('source', '');
$sort    = get('sort', 'submitted_at');
$dir     = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
$openId  = (int)get('open', '0');

$validSort = ['submitted_at', 'name', 'email', 'subject'];
if (!in_array($sort, $validSort, true)) $sort = 'submitted_at';

/* ━━ WHERE clause ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$where  = [];
$params = [];

// Default: exclude spam unless explicitly viewing spam
if ($filter === 'spam') {
    $where[] = 'is_spam = 1';
} else {
    $where[] = 'is_spam = 0';
    if ($filter === 'unread')   { $where[] = 'is_read = 0'; }
    if ($filter === 'read')     { $where[] = 'is_read = 1'; }
    if ($filter === 'starred')  { $where[] = 'is_starred = 1'; }
}

if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $s = "%$search%"; array_push($params, $s, $s, $s, $s);
}
if ($source) {
    $where[] = 'source_page = ?'; $params[] = $source;
}

$wc = $where ? implode(' AND ', $where) : '1=1';

/* ━━ Summary counts ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
try {
    $sc = [
        'total'    => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_spam=0')->fetchColumn(),
        'unread'   => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read=0 AND is_spam=0')->fetchColumn(),
        'read'     => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read=1 AND is_spam=0')->fetchColumn(),
        'spam'     => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_spam=1')->fetchColumn(),
        'starred'  => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_starred=1 AND is_spam=0')->fetchColumn(),
        'today'    => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE DATE(submitted_at)=CURDATE() AND is_spam=0")->fetchColumn(),
    ];
} catch (\Throwable) {
    // is_starred may not exist yet — fallback gracefully
    $sc = ['total'=>0,'unread'=>0,'read'=>0,'spam'=>0,'starred'=>0,'today'=>0];
}

/* ━━ Paginated inbox ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$cs = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE $wc");
$cs->execute($params); $total = (int)$cs->fetchColumn();
$pager = paginate($total, $perPage, $page);

$stmt = $pdo->prepare(
    "SELECT cm.*, u.full_name AS user_display, u.profile_image AS user_avatar
       FROM contact_messages cm
       LEFT JOIN users u ON u.id = cm.user_id
      WHERE $wc
      ORDER BY cm.$sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$messages = $stmt->fetchAll();

/* ━━ Open a specific message ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$openMsg = null;
if ($openId) {
    $os = $pdo->prepare(
        "SELECT cm.*, u.full_name AS user_display, u.username, u.profile_image AS user_avatar,
                u.email AS user_email
           FROM contact_messages cm
           LEFT JOIN users u ON u.id = cm.user_id
          WHERE cm.id = ? LIMIT 1"
    );
    $os->execute([$openId]);
    $openMsg = $os->fetch() ?: null;
    // Auto-mark as read
    if ($openMsg && !$openMsg['is_read']) {
        $pdo->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([$openId]);
        $openMsg['is_read'] = 1;
        $sc['unread'] = max(0, $sc['unread'] - 1);
    }
}

/* ━━ Source history for the open message's sender ━━━━━━━━━━ */
$senderHistory = [];
if ($openMsg) {
    $sh = $pdo->prepare(
        "SELECT id, subject, message, submitted_at, is_read
           FROM contact_messages
          WHERE email = ? AND id != ?
          ORDER BY submitted_at DESC LIMIT 5"
    );
    $sh->execute([$openMsg['email'], $openId]);
    $senderHistory = $sh->fetchAll();
}

/* ━━ Helpers ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function sourceLabel(string $s): string {
    return match($s) {
        'main_portfolio' => 'Portfolio',
        'leetcode'       => 'LeetCode',
        'video_editor'   => 'Video Editor',
        default          => ucwords(str_replace('_',' ',$s)),
    };
}
function sourceBadge(string $s): string {
    return match($s) {
        'main_portfolio' => 'badge-accent',
        'leetcode'       => 'badge-success',
        'video_editor'   => 'badge-warning',
        default          => 'badge-muted',
    };
}
function avatarInitials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ━━ Split-panel layout ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.inbox-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 0;
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--card-bg);
    min-height: 72vh;
}
.inbox-sidebar {
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
}
.inbox-main {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* ━━ Sidebar: folder pills ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.folder-nav   { padding: 14px 12px; border-bottom: 1px solid var(--border); }
.folder-pill  { display: flex; align-items: center; justify-content: space-between;
                padding: 8px 12px; border-radius: var(--radius-sm); cursor: pointer;
                text-decoration: none; color: var(--text-muted); font-size: 13px;
                font-weight: 500; transition: .15s; margin-bottom: 2px; }
.folder-pill:hover  { background: var(--bg-hover); color: var(--text); }
.folder-pill.active { background: var(--accent-glow); color: var(--accent);
                      border-left: 3px solid var(--accent); border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
.folder-left  { display: flex; align-items: center; gap: 8px; }
.folder-count { font-size: 10px; font-weight: 700; background: var(--border);
                border-radius: 99px; padding: 2px 7px; color: var(--text-muted); }
.folder-pill.active .folder-count { background: var(--accent); color: #000; }
.unread-dot   { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }

/* ━━ Sidebar: recent senders ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.recent-sender { padding: 8px 14px; display: flex; align-items: center; gap:9px;
                 cursor: pointer; border-radius: var(--radius-sm); transition: .15s;
                 text-decoration: none; margin: 0 6px 2px; }
.recent-sender:hover { background: var(--bg-hover); }
.rs-av { width: 30px; height: 30px; border-radius: 50%; background: var(--accent-glow);
         border: 1.5px solid var(--accent); display: grid; place-items: center;
         font-size: 11px; font-weight: 700; color: var(--accent); flex-shrink: 0; }
.rs-name { font-size: 12px; font-weight: 600; color: var(--text); }
.rs-email { font-size: 10px; color: var(--text-dim); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ━━ Toolbar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.inbox-toolbar { display: flex; align-items: center; gap: 8px; padding: 10px 16px;
                 border-bottom: 1px solid var(--border); flex-wrap: wrap; }

/* ━━ Message list ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.msg-row   { display: flex; align-items: flex-start; gap: 12px; padding: 13px 18px 12px;
             border-bottom: 1px solid var(--border); cursor: pointer; transition: .15s;
             position: relative; text-decoration: none; color: inherit; }
.msg-row:hover { background: rgba(255,196,0,.03); }
.msg-row.unread { background: rgba(255,196,0,.04); }
.msg-row.unread .mr-sender { font-weight: 700; color: var(--text); }
.msg-row.active  { background: var(--accent-glow); border-left: 3px solid var(--accent); }
.mr-unread-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
                 background: var(--accent); display: none; }
.msg-row.unread .mr-unread-bar { display: block; }

.mr-avatar { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center;
             font-size: 13px; font-weight: 800; flex-shrink: 0; border: 1.5px solid transparent; }
.msg-row.unread .mr-avatar { border-color: var(--accent); background: var(--accent-glow); color: var(--accent); }
.msg-row:not(.unread) .mr-avatar { background: var(--border); color: var(--text-muted); }

.mr-body   { flex: 1; min-width: 0; }
.mr-top    { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; margin-bottom: 3px; }
.mr-sender { font-size: 13px; color: var(--text-muted); }
.mr-time   { font-size: 11px; color: var(--text-dim); white-space: nowrap; flex-shrink: 0; }
.mr-sub    { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 3px; }
.msg-row.unread .mr-sub { color: var(--text); font-weight: 600; }
.mr-prev   { font-size: 11px; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mr-acts   { display: flex; gap: 4px; flex-shrink: 0; align-items: center; }

/* ━━ Checkboxes ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.msg-cb   { accent-color: var(--accent); cursor: pointer; flex-shrink: 0; width: 14px; height: 14px; }
.star-btn { background: none; border: none; cursor: pointer; font-size: 15px;
            color: var(--text-dim); padding: 0; line-height: 1; transition: .15s; }
.star-btn.starred { color: #f59e0b; }
.star-btn:hover   { color: #f59e0b; transform: scale(1.2); }

/* ━━ Detail panel ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.detail-panel { display: flex; flex-direction: column; height: 100%; }
.dp-hd   { padding: 18px 22px 14px; border-bottom: 1px solid var(--border); }
.dp-sub  { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 12px;
           word-break: break-word; }
.dp-from { display: flex; align-items: center; gap: 12px; }
.dp-av   { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center;
           background: var(--accent-glow); border: 2px solid var(--accent);
           font-size: 15px; font-weight: 800; color: var(--accent); flex-shrink: 0; }
.dp-name { font-size: 14px; font-weight: 700; color: var(--text); }
.dp-email{ font-size: 12px; color: var(--text-muted); }
.dp-meta { font-size: 11px; color: var(--text-dim); margin-top: 2px; }
.dp-actions { display: flex; gap: 8px; margin-top: 14px; }

.dp-body { padding: 22px; flex: 1; overflow-y: auto; }
.dp-msg  { font-size: 14px; line-height: 1.85; color: var(--text-muted); white-space: pre-wrap;
           word-break: break-word; background: var(--input-bg); border: 1px solid var(--border);
           border-radius: var(--radius); padding: 20px 22px; }
.dp-tech { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
.dp-tech-row { background: var(--input-bg); border: 1px solid var(--border); border-radius: var(--radius-sm);
               padding: 10px 14px; font-size: 11px; }
.dp-tech-lbl { color: var(--text-dim); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
.dp-tech-val { color: var(--text-muted); font-family: monospace; word-break: break-all; }

/* ━━ Sender history ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.history-item { padding: 10px 12px; border-radius: var(--radius-sm); background: var(--input-bg);
                border: 1px solid var(--border); margin-bottom: 6px; cursor: pointer; transition: .15s;
                text-decoration: none; display: block; }
.history-item:hover { border-color: var(--accent); }
.hi-sub  { font-size: 12px; font-weight: 600; color: var(--text); }
.hi-prev { font-size: 11px; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.hi-time { font-size: 10px; color: var(--text-dim); margin-top: 4px; }

/* ━━ Empty/placeholder states ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.inbox-placeholder { flex: 1; display: flex; flex-direction: column; align-items: center;
                     justify-content: center; text-align: center; padding: 48px 24px;
                     color: var(--text-muted); }
.ip-icon { font-size: 52px; margin-bottom: 16px; filter: grayscale(.3); }
.ip-title { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.ip-desc  { font-size: 13px; line-height: 1.6; }

/* ━━ Bulk bar ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.bulk-bar { position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%);
            background: var(--card-bg); border: 1px solid var(--accent); border-radius: 99px;
            padding: 10px 20px; display: flex; align-items: center; gap: 12px; z-index: 900;
            box-shadow: 0 8px 32px rgba(0,0,0,.4); opacity: 0; pointer-events: none; transition: .25s; }
.bulk-bar.show { opacity: 1; pointer-events: all; }
.bulk-cnt { font-size: 13px; font-weight: 700; color: var(--accent); }
.bulk-div { width: 1px; height: 18px; background: var(--border); }

/* ━━ Responsive ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
@media (max-width: 900px) {
    .inbox-layout { grid-template-columns: 1fr; }
    .inbox-sidebar { border-right: none; border-bottom: 1px solid var(--border); }
    .folder-nav  { display: flex; flex-wrap: wrap; gap: 4px; padding: 10px; }
    .folder-pill { margin-bottom: 0; flex: 0 0 auto; }
}
</style>

<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">

      <!-- Page header -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">
            Contact Messages
            <?php if($sc['unread'] > 0): ?>
              <span class="badge badge-danger" style="font-size:12px;vertical-align:middle;margin-left:8px;"><?= $sc['unread'] ?> unread</span>
            <?php endif; ?>
          </h1>
          <p class="page-subtitle"><?= number_format($sc['total']) ?> total · <?= number_format($sc['today']) ?> today</p>
        </div>
        <div class="page-header-actions">
          <?php if($sc['unread'] > 0): ?>
            <button class="btn btn-secondary btn-sm" onclick="markAllRead()">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              Mark all read
            </button>
          <?php endif; ?>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- Inbox split layout -->
      <div class="inbox-layout">

        <!-- ══ LEFT SIDEBAR ══════════════════════════════════ -->
        <div class="inbox-sidebar">

          <!-- Folder navigation -->
          <div class="folder-nav">
            <?php
            $folders = [
                ''         => ['icon'=>'📥', 'label'=>'All Messages',  'count'=>$sc['total']],
                'unread'   => ['icon'=>'🔵', 'label'=>'Unread',        'count'=>$sc['unread']],
                'read'     => ['icon'=>'✓',  'label'=>'Read',          'count'=>$sc['read']],
                'starred'  => ['icon'=>'⭐', 'label'=>'Starred',       'count'=>$sc['starred']],
                'spam'     => ['icon'=>'🚫', 'label'=>'Spam',          'count'=>$sc['spam']],
            ];
            foreach ($folders as $key => $f):
                $isActive = $filter === $key && !$source;
            ?>
            <a href="?filter=<?= $key ?>&search=<?= urlencode($search) ?>" class="folder-pill <?= $isActive?'active':'' ?>">
              <div class="folder-left"><?= $f['icon'] ?> <?= $f['label'] ?></div>
              <span class="folder-count"><?= number_format($f['count']) ?></span>
            </a>
            <?php endforeach; ?>

            <!-- Source filters -->
            <div style="margin-top:12px;margin-bottom:4px;font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);padding:0 4px;">Sources</div>
            <?php
            $sources = ['main_portfolio'=>['icon'=>'🌐','label'=>'Portfolio'],'leetcode'=>['icon'=>'💡','label'=>'LeetCode'],'video_editor'=>['icon'=>'🎬','label'=>'Video Editor']];
            foreach ($sources as $sk => $sv):
                try {
                    $sCount = (int)$pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE source_page=? AND is_spam=0")->execute([$sk]) ? 0 : 0;
                    $sc2 = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE source_page=? AND is_spam=0");
                    $sc2->execute([$sk]); $sCount = (int)$sc2->fetchColumn();
                } catch(\Throwable) { $sCount = 0; }
            ?>
            <a href="?source=<?= $sk ?>&filter=<?= urlencode($filter) ?>" class="folder-pill <?= $source===$sk?'active':'' ?>">
              <div class="folder-left"><?= $sv['icon'] ?> <?= $sv['label'] ?></div>
              <span class="folder-count"><?= $sCount ?></span>
            </a>
            <?php endforeach; ?>
          </div>

          <!-- Recent unique senders -->
          <?php
          try {
              $rs = $pdo->query(
                  "SELECT name, email, COUNT(*) as msg_count, MAX(submitted_at) as last_at
                     FROM contact_messages WHERE is_spam=0
                    GROUP BY email ORDER BY last_at DESC LIMIT 7"
              )->fetchAll();
          } catch(\Throwable) { $rs = []; }
          ?>
          <?php if(!empty($rs)): ?>
          <div style="padding:10px 8px 4px;">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);padding:0 6px;margin-bottom:6px;">Recent Senders</div>
            <?php foreach($rs as $r): ?>
            <a href="?search=<?= urlencode($r['email']) ?>&filter=<?= urlencode($filter) ?>" class="recent-sender">
              <div class="rs-av"><?= avatarInitials($r['name']) ?></div>
              <div style="min-width:0;">
                <div class="rs-name"><?= e(truncate($r['name'],22)) ?></div>
                <div class="rs-email"><?= e($r['email']) ?></div>
              </div>
              <?php if($r['msg_count']>1): ?>
              <span style="font-size:9px;background:var(--border);border-radius:99px;padding:2px 5px;color:var(--text-muted);flex-shrink:0;"><?= $r['msg_count'] ?></span>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div><!-- /sidebar -->

        <!-- ══ MAIN: List + Detail ═══════════════════════════ -->
        <div class="inbox-main">

          <!-- Toolbar -->
          <div class="inbox-toolbar">
            <input type="checkbox" id="selAll" class="msg-cb" onchange="selectAll(this)" title="Select all">
            <div class="search-input-wrap" style="flex:1;min-width:140px;">
              <span class="si"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
              <input type="text" id="inboxSearch" placeholder="Search name, email, subject, message…" value="<?= e($search) ?>" autocomplete="off">
            </div>
            <select class="form-control" style="width:120px;" onchange="location.href=this.value">
              <option value="?sort=submitted_at&dir=DESC&filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>" <?= ($sort==='submitted_at'&&$dir==='DESC')?'selected':'' ?>>Newest</option>
              <option value="?sort=submitted_at&dir=ASC&filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>"  <?= ($sort==='submitted_at'&&$dir==='ASC') ?'selected':'' ?>>Oldest</option>
              <option value="?sort=name&dir=ASC&filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>"          <?= $sort==='name'    ?'selected':'' ?>>Name A→Z</option>
              <option value="?sort=email&dir=ASC&filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>"         <?= $sort==='email'   ?'selected':'' ?>>Email A→Z</option>
            </select>
            <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;"><?= number_format($total) ?> msg<?= $total!==1?'s':'' ?></span>
          </div>

          <!-- Two sub-panels: list | detail -->
          <div style="display:grid;grid-template-columns:<?= $openMsg?'360px 1fr':'1fr' ?>;flex:1;min-height:0;overflow:hidden;">

            <!-- Message list -->
            <div style="border-right:<?= $openMsg?'1px solid var(--border)':'' ?>;overflow-y:auto;">
              <?php if(empty($messages)): ?>
              <div class="inbox-placeholder">
                <div class="ip-icon">✉️</div>
                <p class="ip-title">No messages</p>
                <p class="ip-desc"><?= $search||$filter||$source ? 'No messages match your filters. <a href="<?= SITE_URL ?>/admin/messages.php" style="color:var(--accent)">Clear filters →</a>' : 'Your inbox is empty.' ?></p>
              </div>
              <?php else: ?>
              <?php foreach($messages as $m):
                $isOpen   = ($openId === (int)$m['id']);
                $isUnread = !$m['is_read'];
                $isStarred = !empty($m['is_starred']);
              ?>
              <div class="msg-row <?= $isUnread?'unread':'' ?> <?= $isOpen?'active':'' ?>"
                   id="mrow-<?= $m['id'] ?>" data-id="<?= $m['id'] ?>"
                   onclick="openMsg(<?= $m['id'] ?>, event)">
                <div class="mr-unread-bar"></div>
                <input type="checkbox" class="msg-cb msg-check" value="<?= $m['id'] ?>" onclick="event.stopPropagation()" onchange="onCheck()" style="margin-top:3px;">
                <button class="star-btn <?= $isStarred?'starred':'' ?>" id="star-<?= $m['id'] ?>"
                        onclick="event.stopPropagation();toggleStar(<?= $m['id'] ?>,this)" title="Star">★</button>
                <div class="mr-avatar"><?= avatarInitials($m['name']) ?></div>
                <div class="mr-body">
                  <div class="mr-top">
                    <span class="mr-sender"><?= e(truncate($m['name'],22)) ?></span>
                    <span class="mr-time"><?= timeAgo($m['submitted_at']) ?></span>
                  </div>
                  <div class="mr-sub"><?= e($m['subject'] ?: '(no subject)') ?></div>
                  <div class="mr-prev"><?= e(truncate($m['message'], 70)) ?></div>
                  <?php if($m['source_page']): ?>
                  <span class="badge <?= sourceBadge($m['source_page']) ?>" style="font-size:9px;margin-top:4px;"><?= sourceLabel($m['source_page']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="mr-acts">
                  <?php if($isUnread): ?>
                  <button class="btn btn-ghost btn-sm btn-icon" title="Mark read"
                          onclick="event.stopPropagation();markRead(<?= $m['id'] ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  <?php else: ?>
                  <button class="btn btn-ghost btn-sm btn-icon" title="Mark unread"
                          onclick="event.stopPropagation();markUnread(<?= $m['id'] ?>)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  </button>
                  <?php endif; ?>
                  <button class="btn btn-danger btn-sm btn-icon" title="Delete"
                          onclick="event.stopPropagation();deleteMsg(<?= $m['id'] ?>,'<?= e(addslashes($m['name'])) ?>')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                  </button>
                </div>
              </div>
              <?php endforeach; ?>

              <?php
              $queryParams = array_filter(compact('filter', 'search', 'source', 'sort', 'dir'), fn($v) => $v !== '');
              require __DIR__ . '/includes/pagination.php';
              ?>

              <?php endif; ?>
            </div><!-- /list -->

            <!-- ── Message detail panel ──────────────────────── -->
            <?php if($openMsg): ?>
            <div class="detail-panel" style="overflow-y:auto;">
              <div class="dp-hd">
                <div class="dp-sub"><?= e($openMsg['subject'] ?: '(no subject)') ?></div>
                <div class="dp-from">
                  <?php if(!empty($openMsg['user_avatar'])): ?>
                    <img src="<?= e($openMsg['user_avatar']) ?>" class="dp-av" style="object-fit:cover;" alt="">
                  <?php else: ?>
                    <div class="dp-av"><?= avatarInitials($openMsg['name']) ?></div>
                  <?php endif; ?>
                  <div style="min-width:0;">
                    <div class="dp-name"><?= e($openMsg['name']) ?></div>
                    <div class="dp-email">
                      <a href="mailto:<?= e($openMsg['email']) ?>" style="color:var(--accent);"><?= e($openMsg['email']) ?></a>
                    </div>
                    <div class="dp-meta">
                      <?= formatDate($openMsg['submitted_at'], 'd M Y, H:i') ?>
                      &nbsp;·&nbsp;
                      <span class="badge <?= sourceBadge($openMsg['source_page']) ?>" style="font-size:9px;"><?= sourceLabel($openMsg['source_page']) ?></span>
                      <?php if(!empty($openMsg['is_starred'])): ?>
                        &nbsp;⭐
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="dp-actions">
                  <a href="mailto:<?= e($openMsg['email']) ?>?subject=Re: <?= urlencode($openMsg['subject']?:'Your message') ?>"
                     class="btn btn-primary btn-sm">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Reply
                  </a>
                  <button class="btn btn-ghost btn-sm" onclick="toggleStar(<?= $openMsg['id'] ?>,this)" id="detailStar">
                    <span id="detailStarIcon"><?= !empty($openMsg['is_starred'])?'⭐ Starred':'☆ Star' ?></span>
                  </button>
                  <button class="btn btn-ghost btn-sm" onclick="markSpam(<?= $openMsg['id'] ?>)">🚫 Spam</button>
                  <button class="btn btn-danger btn-sm" onclick="deleteMsg(<?= $openMsg['id'] ?>,'<?= e(addslashes($openMsg['name'])) ?>')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                    Delete
                  </button>
                  <a href="?filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>&source=<?= urlencode($source) ?>&page=<?= $page ?>" class="btn btn-ghost btn-sm btn-icon" title="Close">✕</a>
                </div>
              </div>

              <div class="dp-body">

                <!-- Message body -->
                <div class="dp-msg"><?= e($openMsg['message']) ?></div>

                <!-- Technical details -->
                <div style="margin-top:18px;">
                  <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);margin-bottom:10px;">Technical Details</div>
                  <div class="dp-tech">
                    <div class="dp-tech-row">
                      <div class="dp-tech-lbl">IP Address</div>
                      <div class="dp-tech-val"><?= e($openMsg['ip_address'] ?: '—') ?></div>
                    </div>
                    <div class="dp-tech-row">
                      <div class="dp-tech-lbl">Source Page</div>
                      <div class="dp-tech-val"><?= sourceLabel($openMsg['source_page']) ?></div>
                    </div>
                    <div class="dp-tech-row" style="grid-column:1/-1;">
                      <div class="dp-tech-lbl">User Agent</div>
                      <div class="dp-tech-val"><?= e(truncate($openMsg['user_agent'] ?: '—', 120)) ?></div>
                    </div>
                    <?php if($openMsg['user_id']): ?>
                    <div class="dp-tech-row" style="grid-column:1/-1;">
                      <div class="dp-tech-lbl">Registered User</div>
                      <div class="dp-tech-val">
                        <?= e($openMsg['user_display'] ?? 'Unknown') ?>
                        <?php if($openMsg['username']): ?>
                          &nbsp;(@<?= e($openMsg['username']) ?>)
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Sender message history -->
                <?php if(!empty($senderHistory)): ?>
                <div style="margin-top:22px;">
                  <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);margin-bottom:10px;">
                    Other messages from <?= e($openMsg['name']) ?>
                  </div>
                  <?php foreach($senderHistory as $h): ?>
                  <a href="?open=<?= $h['id'] ?>&filter=<?= urlencode($filter) ?>&search=<?= urlencode($search) ?>" class="history-item">
                    <div class="hi-sub"><?= e($h['subject'] ?: '(no subject)') ?> <?= !$h['is_read']?'<span style="color:var(--accent);font-size:9px;">●</span>':'' ?></div>
                    <div class="hi-prev"><?= e(truncate($h['message'], 80)) ?></div>
                    <div class="hi-time"><?= timeAgo($h['submitted_at']) ?></div>
                  </a>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

              </div><!-- /dp-body -->
            </div><!-- /detail-panel -->

            <?php else: ?>
            <!-- No message selected -->
            <div class="inbox-placeholder" style="border-left:1px solid var(--border);">
              <div class="ip-icon">📬</div>
              <p class="ip-title">Select a message</p>
              <p class="ip-desc">Click any message on the left<br>to read it here.</p>
            </div>
            <?php endif; ?>

          </div><!-- /sub-panels grid -->
        </div><!-- /inbox-main -->

      </div><!-- /inbox-layout -->

      <!-- Bulk action bar -->
      <div class="bulk-bar" id="bulkBar">
        <span class="bulk-cnt"><span id="bulkCount">0</span> selected</span>
        <div class="bulk-div"></div>
        <button class="btn btn-ghost btn-sm" onclick="bulkMarkRead()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Mark Read
        </button>
        <button class="btn btn-ghost btn-sm" onclick="bulkMarkUnread()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Mark Unread
        </button>
        <button class="btn btn-ghost btn-sm" onclick="bulkSpam()">🚫 Spam</button>
        <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
          Delete
        </button>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="clearAll()">✕</button>
      </div>

    </main>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ══ NAVIGATE TO MESSAGE ══ */
function openMsg(id, event) {
  if (event.target.closest('.msg-cb,.star-btn,.mr-acts')) return;
  const params = new URLSearchParams(location.search);
  params.set('open', id);
  location.href = '?' + params.toString();
}

/* ══ SEARCH (debounced) ══ */
let st;
document.getElementById('inboxSearch').addEventListener('input', function() {
  clearTimeout(st);
  st = setTimeout(() => {
    const params = new URLSearchParams(location.search);
    params.set('search', this.value); params.set('page','1'); params.delete('open');
    location.href = '?' + params.toString();
  }, 600);
});

/* ══ MARK READ ══ */
async function markRead(id) {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'mark_read', id });
  if (r.success) {
    const row = document.getElementById('mrow-' + id);
    row?.classList.remove('unread');
    Toast.success('Marked read');
  } else Toast.error('Error', r.error);
}

/* ══ MARK UNREAD ══ */
async function markUnread(id) {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'mark_unread', id });
  if (r.success) {
    const row = document.getElementById('mrow-' + id);
    row?.classList.add('unread');
    Toast.success('Marked unread');
  } else Toast.error('Error', r.error);
}

/* ══ MARK ALL READ ══ */
async function markAllRead() {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'mark_all_read' });
  if (r.success) { Toast.success('Done','All messages marked read.'); setTimeout(() => location.reload(), 900); }
  else Toast.error('Error', r.error);
}

/* ══ STAR / UNSTAR ══ */
async function toggleStar(id, btn) {
  const wasStarred = btn.classList.contains('starred') ||
                     (btn.id === 'detailStar' && document.getElementById('detailStarIcon').textContent.includes('Starred'));
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action: wasStarred?'unstar':'star', id });
  if (r.success) {
    if (btn.classList.contains('star-btn')) {
      btn.classList.toggle('starred', !wasStarred);
    } else {
      document.getElementById('detailStarIcon').textContent = wasStarred ? '☆ Star' : '⭐ Starred';
    }
    Toast.success(wasStarred ? 'Unstarred' : '⭐ Starred');
  } else Toast.error('Error', r.error);
}

/* ══ SPAM ══ */
async function markSpam(id) {
  Modal.confirm('Move this message to Spam?', async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'mark_spam', id });
    if (r.success) {
      document.getElementById('mrow-' + id)?.remove();
      Toast.success('Marked spam');
    } else Toast.error('Error', r.error);
  }, { title:'Mark as Spam', confirmText:'Move to Spam', type:'warning' });
}

/* ══ DELETE ══ */
async function deleteMsg(id, name) {
  Modal.confirm(
    `Delete message from <strong>${escHtml(name)}</strong>?<br><small style="color:var(--text-muted)">This cannot be undone.</small>`,
    async () => {
      const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'delete', id });
      if (r.success) {
        document.getElementById('mrow-' + id)?.remove();
        Toast.success('Deleted', 'Message removed.');
        <?php if($openMsg): ?>
        // If we just deleted the open message, go back to inbox
        if (id == <?= $openId ?>) {
          const params = new URLSearchParams(location.search);
          params.delete('open');
          setTimeout(() => location.href = '?' + params.toString(), 600);
        }
        <?php endif; ?>
      } else Toast.error('Error', r.error);
    },
    { title:'Delete Message', confirmText:'Delete', type:'danger' }
  );
}

/* ══ SELECTION ══ */
function getCheckedIds() {
  return [...document.querySelectorAll('.msg-check:checked')].map(cb => cb.value);
}
function onCheck() {
  const ids = getCheckedIds();
  document.getElementById('bulkCount').textContent = ids.length;
  document.getElementById('bulkBar').classList.toggle('show', ids.length > 0);
}
function selectAll(master) {
  document.querySelectorAll('.msg-check').forEach(cb => cb.checked = master.checked);
  onCheck();
}
function clearAll() {
  document.querySelectorAll('.msg-check').forEach(cb => cb.checked = false);
  document.getElementById('selAll').checked = false;
  onCheck();
}

/* ══ BULK ACTIONS ══ */
async function bulkMarkRead() {
  const ids = getCheckedIds(); if(!ids.length) return;
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'bulk_mark_read', ids });
  if (r.success) { Toast.success('Done',`${ids.length} marked read.`); setTimeout(()=>location.reload(),900); }
  else Toast.error('Error', r.error);
}
async function bulkMarkUnread() {
  const ids = getCheckedIds(); if(!ids.length) return;
  const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'bulk_mark_unread', ids });
  if (r.success) { Toast.success('Done',`${ids.length} marked unread.`); setTimeout(()=>location.reload(),900); }
  else Toast.error('Error', r.error);
}
async function bulkSpam() {
  const ids = getCheckedIds(); if(!ids.length) return;
  Modal.confirm(`Move ${ids.length} message${ids.length!==1?'s':''} to Spam?`, async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'bulk_spam', ids });
    if (r.success) { Toast.success('Done',`${ids.length} moved to spam.`); setTimeout(()=>location.reload(),900); }
    else Toast.error('Error', r.error);
  }, { title:'Mark as Spam', confirmText:'Spam All', type:'warning' });
}
async function bulkDelete() {
  const ids = getCheckedIds(); if(!ids.length) return;
  Modal.confirm(
    `Delete <strong>${ids.length}</strong> message${ids.length!==1?'s':''}?<br><small style="color:var(--text-muted)">This cannot be undone.</small>`,
    async () => {
      const r = await apiPost('<?= SITE_URL ?>/api/admin/messages.php', { action:'bulk_delete', ids });
      if (r.success) { Toast.success('Deleted',`${ids.length} messages removed.`); setTimeout(()=>location.reload(),900); }
      else Toast.error('Error', r.error);
    },
    { title:'Delete Messages', confirmText:'Delete All', type:'danger' }
  );
}

/* ══ KEYBOARD ══ */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') clearAll();
});
</script>
</body>
</html>
