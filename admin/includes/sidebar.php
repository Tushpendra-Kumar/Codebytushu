<?php
/**
 * Admin Panel — Sidebar Navigation
 * Reusable across all admin pages.
 * Requires: $user, $unreadCount, $newUsers (set in head.php or page)
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function navActive(string ...$pages): string {
    global $currentPage;
    return in_array($currentPage, $pages, true) ? ' active' : '';
}
?>
<aside class="sidebar" id="adminSidebar" role="navigation" aria-label="Admin navigation">

  <!-- Brand -->
  <a href="<?= SITE_URL ?>/admin/" class="sidebar-brand" aria-label="CodeByTushu Admin Home">
    <div class="brand-logo" aria-hidden="true">CB</div>
    <div class="brand-text">
      <span class="brand-name">CodeByTushu</span>
      <span class="brand-sub">Admin Panel</span>
    </div>
  </a>

  <!-- Navigation -->
  <nav class="sidebar-nav" aria-label="Main navigation">

    <!-- Main -->
    <div class="nav-group">
      <div class="nav-group-label">Main</div>
      <a href="<?= SITE_URL ?>/admin/"
         class="nav-item<?= navActive('index') ?>"
         data-label="Dashboard"
         aria-current="<?= $currentPage === 'index' ? 'page' : 'false' ?>">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        </span>
        <span class="nav-label">Dashboard</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/users.php"
         class="nav-item<?= navActive('users') ?>"
         data-label="Users">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <span class="nav-label">Users</span>
        <?php if ($newUsers > 0): ?>
          <span class="nav-badge" title="<?= $newUsers ?> new this week"><?= $newUsers ?></span>
        <?php endif; ?>
      </a>
    </div>

    <!-- Content -->
    <div class="nav-group">
      <div class="nav-group-label">Content</div>
      <a href="<?= SITE_URL ?>/admin/leetcode.php"
         class="nav-item<?= navActive('leetcode','leetcode-edit') ?>"
         data-label="LeetCode">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </span>
        <span class="nav-label">LeetCode</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/blogs.php"
         class="nav-item<?= navActive('blogs','blog-edit') ?>"
         data-label="Blogs">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </span>
        <span class="nav-label">Blogs</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/courses.php"
         class="nav-item<?= navActive('courses','course-edit') ?>"
         data-label="Courses">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </span>
        <span class="nav-label">Courses</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/categories.php"
         class="nav-item<?= navActive('categories') ?>"
         data-label="Categories">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        </span>
        <span class="nav-label">Categories & Tags</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/uploads.php"
         class="nav-item<?= navActive('uploads') ?>"
         data-label="Uploads">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </span>
        <span class="nav-label">File Uploads</span>
      </a>
    </div>

    <!-- Insights -->
    <div class="nav-group">
      <div class="nav-group-label">Insights</div>
      <a href="<?= SITE_URL ?>/admin/analytics.php"
         class="nav-item<?= navActive('analytics') ?>"
         data-label="Analytics">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </span>
        <span class="nav-label">Analytics</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/messages.php"
         class="nav-item<?= navActive('messages') ?>"
         data-label="Messages">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </span>
        <span class="nav-label">Messages</span>
        <?php if ($unreadCount > 0): ?>
          <span class="nav-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= SITE_URL ?>/admin/newsletter.php"
         class="nav-item<?= navActive('newsletter') ?>"
         data-label="Newsletter">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 17v1c0 .5-.5 1-1 1H3c-.5 0-1-.5-1-1v-1"/><path d="M2 9a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6H2V9z"/><path d="M12 12V3"/><path d="M8 7l4-4 4 4"/></svg>
        </span>
        <span class="nav-label">Newsletter</span>
      </a>
    </div>

    <!-- System -->
    <div class="nav-group">
      <div class="nav-group-label">System</div>
      <a href="<?= SITE_URL ?>/admin/settings.php"
         class="nav-item<?= navActive('settings') ?>"
         data-label="Settings">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </span>
        <span class="nav-label">Settings</span>
      </a>
      <a href="<?= SITE_URL ?>/" target="_blank" rel="noopener"
         class="nav-item"
         data-label="View Site">
        <span class="nav-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </span>
        <span class="nav-label">View Site</span>
        <span class="nav-icon" style="font-size:10px;margin-left:auto;" aria-hidden="true">↗</span>
      </a>
    </div>

  </nav>

  <!-- User footer -->
  <div class="sidebar-footer">
    <div class="sidebar-user-card" onclick="window.location.href='<?= SITE_URL ?>/user/dashboard.php'" title="My Profile" role="button" tabindex="0">
      <div class="sidebar-user-avatar">
        <?php if (!empty($user['profile_image']) && file_exists(dirname(__DIR__, 2) . $user['profile_image'])): ?>
          <img src="<?= e($user['profile_image']) ?>" alt="<?= e($user['full_name']) ?>">
        <?php else: ?>
          <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-meta">
        <div class="name"><?= e($user['full_name'] ?? 'Admin') ?></div>
        <div class="role"><?= e(str_replace('_',' ', $user['role'] ?? '')) ?></div>
      </div>
    </div>
  </div>

</aside>
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
