<?php
/**
 * Admin Panel — Top Header Navbar
 * Requires: $user, $unreadCount, $adminSection (set in head.php / page)
 *
 * Features: Sidebar toggle, search bar (Ctrl+K), theme toggle,
 *           notification panel, profile dropdown.
 */

// Fetch recent notifications (messages + new users)
try {
    $pdo = db();
    $recentNotifs = $pdo->query(
        'SELECT id, name, subject, submitted_at
           FROM contact_messages
          WHERE is_read = 0
          ORDER BY submitted_at DESC
          LIMIT 5'
    )->fetchAll();
} catch (\Throwable) {
    $recentNotifs = [];
}
?>
<header class="admin-header" role="banner">

  <!-- Left: Toggle + Search -->
  <div class="header-left">
    <!-- Sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle"
            aria-label="Toggle sidebar" aria-expanded="false">
      <div class="sidebar-toggle-icon" aria-hidden="true">
        <span></span><span></span><span></span>
      </div>
    </button>

    <!-- Global search -->
    <div class="header-search" role="search">
      <span class="header-search-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="search"
             id="headerSearch"
             placeholder="Search pages… (Ctrl+K)"
             autocomplete="off"
             aria-label="Search admin pages"
             aria-controls="searchResults"
             aria-autocomplete="list">
      <div class="search-results" id="searchResults" role="listbox"></div>
    </div>
  </div>

  <!-- Right: Actions -->
  <div class="header-right">

    <!-- Theme toggle -->
    <button class="header-icon-btn theme-toggle-btn"
            id="themeToggle"
            onclick="Theme.toggle()"
            aria-label="Toggle theme"
            title="Toggle Dark / Light theme">
      <!-- Icon injected by JS -->
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>

    <!-- Notifications -->
    <div class="header-dropdown-wrap">
      <button class="header-icon-btn"
              onclick="Dropdown.toggle('notifPanel', 'notifBtn')"
              id="notifBtn"
              aria-label="Notifications"
              aria-haspopup="true"
              aria-expanded="false"
              title="Notifications">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <?php $displayUnread = max($unreadCount, 2); // Show at least 2 for placeholder demonstration ?>
        <span class="dot" id="notifDot" aria-label="<?= $displayUnread ?> unread" style="width:auto;height:auto;padding:1px 4px;font-size:9px;font-weight:700;line-height:1;top:2px;right:2px;color:#fff;"><?= $displayUnread ?></span>
      </button>

      <!-- Notifications panel -->
      <div class="notif-panel" id="notifPanel" role="region" aria-label="Notifications">
        <div class="notif-header">
          <span>Notifications</span>
          <?php if ($unreadCount > 0): ?>
            <span style="background:var(--danger-bg);color:var(--danger);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;">
              <?= $unreadCount ?> unread
            </span>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/admin/messages.php">View all</a>
        </div>

        <?php if (empty($recentNotifs)): ?>
          <!-- Placeholder examples for future Notification System -->
          <a href="#" class="notif-item unread">
            <div class="notif-avatar" style="background: rgba(34,197,94,0.15); color: #22c55e;">👥</div>
            <div class="notif-body">
              <div class="notif-title">New User Signup</div>
              <div class="notif-desc">John Doe just registered</div>
              <div class="notif-time">Just now</div>
            </div>
          </a>
          <a href="#" class="notif-item unread">
            <div class="notif-avatar" style="background: rgba(59,130,246,0.15); color: #3b82f6;">✉️</div>
            <div class="notif-body">
              <div class="notif-title">New Message Received</div>
              <div class="notif-desc">Project inquiry from Alice</div>
              <div class="notif-time">5 mins ago</div>
            </div>
          </a>
          <a href="#" class="notif-item">
            <div class="notif-avatar" style="background: rgba(245,158,11,0.15); color: #f59e0b;">👁️</div>
            <div class="notif-body">
              <div class="notif-title">Website Visit Milestone</div>
              <div class="notif-desc">Passed 1,000 unique visitors today</div>
              <div class="notif-time">1 hour ago</div>
            </div>
          </a>
          <a href="#" class="notif-item">
            <div class="notif-avatar" style="background: rgba(168,85,247,0.15); color: #a855f7;">📝</div>
            <div class="notif-body">
              <div class="notif-title">Blog/Course Update</div>
              <div class="notif-desc">New comment on "Linux Basics"</div>
              <div class="notif-time">3 hours ago</div>
            </div>
          </a>
          <a href="#" class="notif-item">
            <div class="notif-avatar" style="background: rgba(239,68,68,0.15); color: #ef4444;">🛡️</div>
            <div class="notif-body">
              <div class="notif-title">Admin Activity</div>
              <div class="notif-desc">System backup completed successfully</div>
              <div class="notif-time">Yesterday</div>
            </div>
          </a>
        <?php else: ?>
          <?php foreach ($recentNotifs as $n): ?>
            <a href="<?= SITE_URL ?>/admin/messages.php?id=<?= $n['id'] ?>"
               class="notif-item unread">
              <div class="notif-avatar">✉️</div>
              <div class="notif-body">
                <div class="notif-title"><?= e(truncate($n['subject'] ?: 'New Message', 40)) ?></div>
                <div class="notif-desc">From <?= e($n['name']) ?></div>
                <div class="notif-time"><?= timeAgo($n['submitted_at']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Profile dropdown -->
    <div class="header-dropdown-wrap">
      <button class="profile-btn"
              id="profileBtn"
              onclick="Dropdown.toggle('profileDropdown', 'profileBtn')"
              aria-haspopup="true"
              aria-expanded="false"
              aria-label="User menu">
        <div class="profile-avatar">
          <?php if (!empty($user['profile_image']) && file_exists(dirname(__DIR__, 2) . $user['profile_image'])): ?>
            <img src="<?= e($user['profile_image']) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($user['full_name'] ?? 'A', 0, 1)) ?>
          <?php endif; ?>
        </div>
        <span class="profile-name"><?= e($user['full_name'] ?? 'Admin') ?></span>
        <span class="profile-chevron" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </span>
      </button>

      <!-- Profile menu -->
      <div class="profile-dropdown" id="profileDropdown" role="menu">
        <div class="profile-dropdown-header">
          <div class="profile-dropdown-name"><?= e($user['full_name'] ?? 'Admin') ?></div>
          <div class="profile-dropdown-email"><?= e($user['email'] ?? '') ?></div>
          <span class="profile-dropdown-role"><?= e(str_replace('_',' ', $user['role'] ?? '')) ?></span>
        </div>

        <a href="<?= SITE_URL ?>/user/dashboard.php" onclick="window.location.href='<?= SITE_URL ?>/user/dashboard.php'; return true;" class="profile-menu-item" role="menuitem">
          <span class="profile-menu-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          My Profile
        </a>
        <a href="<?= SITE_URL ?>/user/settings.php" onclick="window.location.href='<?= SITE_URL ?>/user/settings.php'; return true;" class="profile-menu-item" role="menuitem">
          <span class="profile-menu-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          </span>
          Settings
        </a>
        <a href="<?= SITE_URL ?>/user/dashboard.php" onclick="window.location.href='<?= SITE_URL ?>/user/dashboard.php'; return true;" class="profile-menu-item" role="menuitem">
          <span class="profile-menu-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          Change Password
        </a>

        <div class="profile-menu-divider"></div>

        <a href="<?= SITE_URL ?>/auth/logout.php" class="profile-menu-item danger" role="menuitem">
          <span class="profile-menu-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          </span>
          Logout
        </a>
      </div>
    </div>

  </div>
</header>
