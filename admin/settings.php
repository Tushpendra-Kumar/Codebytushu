<?php
/**
 * CodeByTushu — Website Settings v2
 * 7-tab settings panel: General, Branding, Social, SEO, Footer, Email, Maintenance.
 * All changes stored in MySQL site_config / social_links tables.
 */
declare(strict_types=1);

$adminSection = 'Settings';
$adminTitle   = 'Website Settings — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Settings'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ━━ Load all config keys into $cfg ━━━━━━━━━━━━━━━━━━━━━━━━ */
$cfg = [];
foreach ($pdo->query('SELECT config_key, config_value FROM site_config')->fetchAll() as $r) {
    $cfg[$r['config_key']] = $r['config_value'] ?? '';
}

/* ━━ Load social links ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$socials = $pdo->query('SELECT * FROM social_links ORDER BY sort_order, id')->fetchAll();

/* ━━ Helper: get config value safely ━━━━━━━━━━━━━━━━━━━━━━━ */
function c(string $key, array $cfg, string $default = ''): string {
    return $cfg[$key] ?? $default;
}

/* ━━ SMTP display (read-only, from constants) ━━━━━━━━━━━━━━ */
$smtpHost  = defined('SMTP_HOST')       ? SMTP_HOST       : '(not set)';
$smtpFrom  = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '(not set)';
$smtpTo    = defined('SMTP_TO_EMAIL')   ? SMTP_TO_EMAIL   : '(not set)';
$smtpPort  = defined('SMTP_PORT')       ? SMTP_PORT       : '587';

/* ━━ Maintenance mode state ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$maintenanceOn = c('maintenance_mode', $cfg) === '1';

/* ━━ Active tab from URL ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$activeTab = get('tab', 'general');
$validTabs = ['general','branding','social','seo','footer','email','maintenance'];
if (!in_array($activeTab, $validTabs, true)) $activeTab = 'general';

/* ━━ Platform icons (SVG inline or emoji) ━━━━━━━━━━━━━━━━━ */
$platformIcons = [
    'youtube'   => '▶️',
    'instagram' => '📸',
    'linkedin'  => '💼',
    'whatsapp'  => '💬',
    'twitter'   => '🐦',
    'github'    => '🐙',
    'facebook'  => '📘',
    'tiktok'    => '🎵',
    'discord'   => '🎮',
    'telegram'  => '✈️',
];
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ━━ Settings layout ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.settings-layout { display:grid; grid-template-columns:220px 1fr; gap:20px; align-items:start; }
.settings-nav    { position:sticky; top:16px; background:var(--card-bg); border:1px solid var(--border);
                   border-radius:var(--radius-lg); overflow:hidden; }
.settings-nav-hd { padding:14px 16px; border-bottom:1px solid var(--border);
                   font-size:10px; font-weight:700; color:var(--text-dim);
                   text-transform:uppercase; letter-spacing:.6px; }
.snav-item { display:flex; align-items:center; gap:10px; padding:10px 16px; cursor:pointer;
             text-decoration:none; color:var(--text-muted); font-size:13px; font-weight:500;
             transition:.15s; border-left:3px solid transparent; }
.snav-item:hover  { background:var(--bg-hover); color:var(--text); border-left-color:var(--border-mid); }
.snav-item.active { background:var(--accent-glow); color:var(--accent);
                    border-left-color:var(--accent); font-weight:600; }
.snav-icon { font-size:16px; flex-shrink:0; }

/* ━━ Panels ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.settings-panel    { display:none; }
.settings-panel.on { display:block; }

/* ━━ Section groups ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.s-card  { background:var(--card-bg); border:1px solid var(--border);
           border-radius:var(--radius-lg); overflow:hidden; margin-bottom:16px; }
.s-card-hd { display:flex; align-items:center; gap:10px; padding:16px 20px;
             border-bottom:1px solid var(--border); }
.s-card-icon { width:34px; height:34px; border-radius:var(--radius-sm); background:var(--accent-glow);
               display:grid; place-items:center; font-size:17px; flex-shrink:0; }
.s-card-title { font-size:14px; font-weight:700; color:var(--text); }
.s-card-desc  { font-size:11px; color:var(--text-muted); margin-top:1px; }
.s-card-body  { padding:20px; }

/* ━━ Upload preview ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.asset-preview { display:flex; align-items:center; gap:16px; margin-bottom:16px; }
.ap-box { width:80px; height:56px; border:2px dashed var(--border); border-radius:var(--radius);
          display:flex; align-items:center; justify-content:center; overflow:hidden;
          background:var(--input-bg); transition:.2s; flex-shrink:0; }
.ap-box img { max-width:100%; max-height:100%; object-fit:contain; }
.ap-box.has-img { border-style:solid; border-color:var(--accent); }
.ap-info  { flex:1; }
.ap-name  { font-size:12px; font-weight:600; color:var(--text); margin-bottom:3px; }
.ap-hint  { font-size:11px; color:var(--text-muted); }

/* ━━ Favicon preview (smaller) ━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.fav-box { width:40px; height:40px; border:2px dashed var(--border); border-radius:8px;
           display:flex; align-items:center; justify-content:center; overflow:hidden;
           background:var(--input-bg); transition:.2s; flex-shrink:0; }

/* ━━ Social card ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.social-row { display:flex; align-items:center; gap:12px; padding:14px 20px;
              border-bottom:1px solid var(--border); }
.social-row:last-child { border-bottom:none; }
.soc-icon { width:36px; height:36px; border-radius:8px; display:grid; place-items:center;
            font-size:18px; flex-shrink:0; border:1px solid var(--border); background:var(--input-bg); }
.soc-name { font-size:13px; font-weight:600; color:var(--text); width:100px; flex-shrink:0; }
.soc-toggles { display:flex; gap:12px; flex-shrink:0; }
.tog-wrap { display:flex; flex-direction:column; align-items:center; gap:3px; }
.tog-lbl  { font-size:9px; color:var(--text-dim); text-transform:uppercase; letter-spacing:.4px; }

/* ━━ Mini toggle ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.tog { position:relative; display:inline-block; width:34px; height:20px; flex-shrink:0; }
.tog input { opacity:0; width:0; height:0; }
.tog-sl { position:absolute; cursor:pointer; inset:0; border-radius:10px;
          background:var(--border); transition:.2s; }
.tog-sl::before { content:''; position:absolute; width:14px; height:14px; background:#fff;
                  border-radius:50%; left:3px; top:3px; transition:.2s; }
.tog input:checked + .tog-sl { background:var(--accent); }
.tog input:checked + .tog-sl::before { transform:translateX(14px); }

/* ━━ Maintenance danger card ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.maintenance-status { border-radius:var(--radius); padding:16px 20px; margin-bottom:20px;
                      display:flex; align-items:center; gap:14px; }
.maintenance-status.live   { background:rgba(34,197,94,.06); border:1px solid rgba(34,197,94,.25); }
.maintenance-status.down   { background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.35); }
.ms-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.ms-dot.live { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); animation:pulse-g 2s infinite; }
.ms-dot.down { background:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.2); animation:pulse-r 2s infinite; }
@keyframes pulse-g { 0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.2);} 50%{box-shadow:0 0 0 7px rgba(34,197,94,.0);} }
@keyframes pulse-r { 0%,100%{box-shadow:0 0 0 3px rgba(239,68,68,.2);} 50%{box-shadow:0 0 0 7px rgba(239,68,68,.0);} }
.ms-text .ms-title { font-size:14px; font-weight:700; }
.ms-text .ms-sub   { font-size:12px; color:var(--text-muted); margin-top:2px; }

/* ━━ Big maintenance toggle ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.big-tog { display:flex; align-items:center; justify-content:space-between; padding:18px 20px;
           background:var(--input-bg); border:1px solid var(--border); border-radius:var(--radius);
           cursor:pointer; transition:.2s; }
.big-tog:hover { border-color:var(--accent); }
.big-tog-left  { display:flex; flex-direction:column; gap:3px; }
.big-tog-title { font-size:14px; font-weight:700; color:var(--text); }
.big-tog-sub   { font-size:12px; color:var(--text-muted); }
.big-tog-sw    { width:52px; height:28px; position:relative; }
.big-tog-sw input { opacity:0; width:0; height:0; }
.big-tog-sl { position:absolute; cursor:pointer; inset:0; background:var(--border);
              border-radius:14px; transition:.3s; }
.big-tog-sl::before { content:''; position:absolute; width:22px; height:22px; background:#fff;
                      border-radius:50%; left:3px; top:3px; transition:.3s; }
.big-tog-sw input:checked + .big-tog-sl { background:#ef4444; }
.big-tog-sw input:checked + .big-tog-sl::before { transform:translateX(24px); }

/* ━━ Color picker row ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.color-row { display:flex; align-items:center; gap:10px; }
.color-swatch { width:38px; height:38px; border-radius:8px; border:2px solid var(--border);
                cursor:pointer; overflow:hidden; flex-shrink:0; }
.color-swatch input[type=color] { width:150%; height:150%; border:none; cursor:pointer; margin:-25%; }

/* ━━ SEO preview card ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.serp-preview { border:1px solid var(--border); border-radius:var(--radius); padding:16px 18px;
                background:var(--input-bg); margin-top:14px; }
.serp-url  { font-size:12px; color:#22c55e; margin-bottom:3px; }
.serp-title { font-size:16px; color:#93c5fd; font-weight:600; margin-bottom:4px; cursor:pointer; }
.serp-title:hover { text-decoration:underline; }
.serp-desc { font-size:12px; color:var(--text-muted); line-height:1.5; }

/* ━━ Char counter ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.char-cnt { font-size:10px; color:var(--text-dim); text-align:right; margin-top:3px; }
.char-cnt.warn  { color:#f59e0b; }
.char-cnt.over  { color:#ef4444; }

/* ━━ Form rows ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:900px) { .settings-layout { grid-template-columns:1fr; } .settings-nav { position:static; } }
@media(max-width:640px) { .form-row-2,.form-row-3 { grid-template-columns:1fr; } }
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
          <h1 class="page-title">Website Settings</h1>
          <p class="page-subtitle">Manage global configuration, branding, social links, SEO, and more.</p>
        </div>
        <div class="page-header-actions">
          <?php if($maintenanceOn): ?>
            <span class="badge badge-danger" style="font-size:11px;padding:5px 10px;">
              ⚠️ Maintenance Mode ACTIVE
            </span>
          <?php else: ?>
            <span class="badge badge-success" style="font-size:11px;padding:5px 10px;">✅ Site Live</span>
          <?php endif; ?>
        </div>
      </div>

      <?= flashHtml() ?>

      <div class="settings-layout">

        <!-- ━━ Left navigation ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="settings-nav">
          <div class="settings-nav-hd">Configuration</div>
          <?php
          $navItems = [
              'general'     => ['icon'=>'🌐', 'label'=>'General',     'desc'=>'Name, contact, address'],
              'branding'    => ['icon'=>'🎨', 'label'=>'Branding',    'desc'=>'Logo, favicon, colors'],
              'social'      => ['icon'=>'📲', 'label'=>'Social Links','desc'=>'URLs, visibility'],
              'seo'         => ['icon'=>'🔍', 'label'=>'SEO',         'desc'=>'Title, description, OG'],
              'footer'      => ['icon'=>'📄', 'label'=>'Footer',      'desc'=>'Footer text, links'],
              'email'       => ['icon'=>'📧', 'label'=>'Email / SMTP','desc'=>'Mail configuration'],
              'maintenance' => ['icon'=>'🔧', 'label'=>'Maintenance', 'desc'=>'Put site offline'],
          ];
          foreach ($navItems as $key => $n):
          ?>
          <a href="?tab=<?= $key ?>" class="snav-item <?= $activeTab===$key?'active':'' ?>" data-tab="<?= $key ?>">
            <span class="snav-icon"><?= $n['icon'] ?></span>
            <div>
              <div style="line-height:1.2;"><?= $n['label'] ?></div>
              <div style="font-size:10px;color:var(--text-dim);font-weight:400;"><?= $n['desc'] ?></div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <!-- ━━ Right panels ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div id="settingsPanels">

          <!-- ████ GENERAL ████████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='general'?'on':'' ?>" id="panel-general">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">🌐</div>
                <div>
                  <div class="s-card-title">Website Identity</div>
                  <div class="s-card-desc">Basic site name and tagline shown across the site</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="generalForm">
                  <?= csrfField() ?>
                  <div class="form-row-2" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Website Name <span class="req">*</span></label>
                      <input type="text" name="site_name" class="form-control" id="siteNameInput"
                             value="<?= e(c('site_name',$cfg,'CodeByTushu')) ?>" required>
                      <div class="form-hint">Shown in browser tab and header</div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Tagline / Slogan</label>
                      <input type="text" name="site_tagline" class="form-control"
                             value="<?= e(c('site_tagline',$cfg)) ?>">
                      <div class="form-hint">Short phrase below the site name</div>
                    </div>
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                      Save Identity
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">📞</div>
                <div>
                  <div class="s-card-title">Contact Details</div>
                  <div class="s-card-desc">Displayed on the contact page and footer</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="contactForm">
                  <?= csrfField() ?>
                  <div class="form-row-2" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Contact Email</label>
                      <input type="email" name="contact_email" class="form-control"
                             value="<?= e(c('contact_email',$cfg)) ?>" placeholder="hello@yoursite.com">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Phone Number</label>
                      <input type="text" name="contact_phone" class="form-control"
                             value="<?= e(c('contact_phone',$cfg)) ?>" placeholder="+91-XXXXXXXXXX">
                    </div>
                  </div>
                  <div class="form-row-2" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Address / Location</label>
                      <input type="text" name="contact_address" class="form-control"
                             value="<?= e(c('contact_address',$cfg)) ?>" placeholder="City, Country">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Google Maps Embed URL</label>
                      <input type="url" name="google_maps_embed" class="form-control"
                             value="<?= e(c('google_maps_embed',$cfg)) ?>" placeholder="https://maps.google.com/...">
                    </div>
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                      Save Contact Info
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- ████ BRANDING ██████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='branding'?'on':'' ?>" id="panel-branding">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">🖼️</div>
                <div>
                  <div class="s-card-title">Logo</div>
                  <div class="s-card-desc">Main site logo shown in header and emails</div>
                </div>
              </div>
              <div class="s-card-body">
                <div class="asset-preview">
                  <div class="ap-box <?= c('logo_path',$cfg)?'has-img':'' ?>" id="logoBox">
                    <?php if(c('logo_path',$cfg)): ?>
                      <img src="<?= e(c('logo_path',$cfg)) ?>" id="logoPreviewImg" alt="Logo">
                    <?php else: ?>
                      <span style="font-size:22px;">🖼️</span>
                    <?php endif; ?>
                  </div>
                  <div class="ap-info">
                    <div class="ap-name"><?= c('logo_path',$cfg) ? basename(c('logo_path',$cfg)) : 'No logo uploaded' ?></div>
                    <div class="ap-hint">Recommended: PNG/SVG, transparent background, max 2MB</div>
                    <?php if(c('logo_path',$cfg)): ?>
                      <div style="margin-top:6px;font-size:11px;font-family:monospace;color:var(--accent);"><?= e(c('logo_path',$cfg)) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <form id="logoForm" enctype="multipart/form-data">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="upload_logo">
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Upload New Logo</label>
                    <input type="file" name="logo" id="logoInput" class="form-control"
                           accept="image/png,image/svg+xml,image/jpeg,image/webp">
                    <div class="form-hint">PNG, SVG, WebP — max 2MB · Ideal: 200×60px or similar</div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Or enter URL</label>
                    <input type="url" name="logo_path" class="form-control"
                           id="logoUrlInput"
                           value="<?= e(c('logo_path',$cfg)) ?>" placeholder="/uploads/logo.png">
                  </div>
                  <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <?php if(c('logo_path',$cfg)): ?>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="removeBrand('logo_path')">Remove Logo</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Update Logo</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">⭐</div>
                <div>
                  <div class="s-card-title">Favicon</div>
                  <div class="s-card-desc">Small icon shown in browser tabs and bookmarks</div>
                </div>
              </div>
              <div class="s-card-body">
                <div class="asset-preview">
                  <div class="fav-box <?= c('favicon_path',$cfg)?'has-img':'' ?>" id="faviconBox">
                    <?php if(c('favicon_path',$cfg)): ?>
                      <img src="<?= e(c('favicon_path',$cfg)) ?>" id="faviconPreviewImg" style="max-width:32px;max-height:32px;" alt="Favicon">
                    <?php else: ?>
                      <span style="font-size:18px;">⭐</span>
                    <?php endif; ?>
                  </div>
                  <div class="ap-info">
                    <div class="ap-name"><?= c('favicon_path',$cfg) ? basename(c('favicon_path',$cfg)) : 'No favicon' ?></div>
                    <div class="ap-hint">ICO, PNG (16×16, 32×32, or 64×64) · max 512KB</div>
                  </div>
                </div>
                <form id="faviconForm" enctype="multipart/form-data">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="upload_favicon">
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Upload New Favicon</label>
                    <input type="file" name="favicon" id="faviconInput" class="form-control"
                           accept="image/x-icon,image/png,image/gif,image/svg+xml">
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Or enter URL</label>
                    <input type="url" name="favicon_path" class="form-control"
                           value="<?= e(c('favicon_path',$cfg)) ?>" placeholder="/uploads/favicon.ico">
                  </div>
                  <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <?php if(c('favicon_path',$cfg)): ?>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="removeBrand('favicon_path')">Remove</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Update Favicon</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">🎨</div>
                <div>
                  <div class="s-card-title">Accent Colors</div>
                  <div class="s-card-desc">Primary and secondary brand colors (used in emails, OG cards)</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="colorsForm">
                  <?= csrfField() ?>
                  <div class="form-row-3" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Primary Color</label>
                      <div class="color-row">
                        <div class="color-swatch">
                          <input type="color" id="colorPrimary" value="<?= e(c('color_primary',$cfg,'#ffc400')) ?>">
                        </div>
                        <input type="text" name="color_primary" id="colorPrimaryText" class="form-control"
                               value="<?= e(c('color_primary',$cfg,'#ffc400')) ?>" maxlength="7" style="width:100px;">
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Secondary Color</label>
                      <div class="color-row">
                        <div class="color-swatch">
                          <input type="color" id="colorSecondary" value="<?= e(c('color_secondary',$cfg,'#09090e')) ?>">
                        </div>
                        <input type="text" name="color_secondary" id="colorSecondaryText" class="form-control"
                               value="<?= e(c('color_secondary',$cfg,'#09090e')) ?>" maxlength="7" style="width:100px;">
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Background Color</label>
                      <div class="color-row">
                        <div class="color-swatch">
                          <input type="color" id="colorBg" value="<?= e(c('color_bg',$cfg,'#09090e')) ?>">
                        </div>
                        <input type="text" name="color_bg" id="colorBgText" class="form-control"
                               value="<?= e(c('color_bg',$cfg,'#09090e')) ?>" maxlength="7" style="width:100px;">
                      </div>
                    </div>
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">Save Colors</button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- ████ SOCIAL LINKS ██████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='social'?'on':'' ?>" id="panel-social">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">📲</div>
                <div>
                  <div class="s-card-title">Social Media Links</div>
                  <div class="s-card-desc">Control URLs and visibility in footer and contact page</div>
                </div>
              </div>
              <?php foreach($socials as $s): ?>
              <div class="social-row" id="srow-<?= $s['id'] ?>">
                <div class="soc-icon" style="border-color:<?= e($s['brand_color_hex']??'var(--border)') ?>20;background:<?= e($s['brand_color_hex']??'transparent') ?>18;">
                  <?= $platformIcons[$s['platform']] ?? '🔗' ?>
                </div>
                <div class="soc-name"><?= e($s['display_name']) ?></div>
                <input type="url" class="form-control" style="flex:1;" id="surl_<?= $s['id'] ?>"
                       value="<?= e($s['url']) ?>" placeholder="https://...">
                <div class="soc-toggles">
                  <div class="tog-wrap">
                    <div class="tog-lbl">Footer</div>
                    <label class="tog">
                      <input type="checkbox" id="sfooter_<?= $s['id'] ?>" <?= $s['show_in_footer']?'checked':'' ?>>
                      <span class="tog-sl"></span>
                    </label>
                  </div>
                  <div class="tog-wrap">
                    <div class="tog-lbl">Contact</div>
                    <label class="tog">
                      <input type="checkbox" id="scontact_<?= $s['id'] ?>" <?= $s['show_in_contact']?'checked':'' ?>>
                      <span class="tog-sl"></span>
                    </label>
                  </div>
                  <div class="tog-wrap">
                    <div class="tog-lbl">Active</div>
                    <label class="tog">
                      <input type="checkbox" id="sactive_<?= $s['id'] ?>" <?= $s['is_active']?'checked':'' ?>>
                      <span class="tog-sl"></span>
                    </label>
                  </div>
                </div>
                <button class="btn btn-primary btn-sm" onclick="saveSocial(<?= $s['id'] ?>)">Save</button>
              </div>
              <?php endforeach; ?>
              <?php if(empty($socials)): ?>
              <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
                No social links configured. Add rows via database seed.
              </div>
              <?php endif; ?>
            </div>

          </div>

          <!-- ████ SEO ███████████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='seo'?'on':'' ?>" id="panel-seo">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">🔍</div>
                <div>
                  <div class="s-card-title">Default SEO Settings</div>
                  <div class="s-card-desc">Fallback title and description for pages without custom SEO</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="seoForm">
                  <?= csrfField() ?>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Default Page Title <span class="req">*</span></label>
                    <input type="text" name="seo_default_title" class="form-control" id="seoTitle"
                           value="<?= e(c('seo_default_title',$cfg)) ?>" maxlength="70">
                    <div id="seoTitleCount" class="char-cnt">0 / 70</div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Default Meta Description <span class="req">*</span></label>
                    <textarea name="seo_default_description" class="form-control" rows="3" id="seoDesc" maxlength="160"><?= e(c('seo_default_description',$cfg)) ?></textarea>
                    <div id="seoDescCount" class="char-cnt">0 / 160</div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Default OG / Social Share Image URL</label>
                    <input type="url" name="seo_og_image" class="form-control"
                           value="<?= e(c('seo_og_image',$cfg)) ?>" placeholder="https://yoursite.com/og-image.jpg">
                    <div class="form-hint">Displayed when sharing links on social media (1200×630px recommended)</div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Default Meta Keywords <span style="color:var(--text-dim);font-weight:400;text-transform:none;">(comma separated)</span></label>
                    <input type="text" name="seo_keywords" class="form-control"
                           value="<?= e(c('seo_keywords',$cfg)) ?>" placeholder="leetcode, dsa, web development, coding">
                  </div>
                  <!-- SERP preview -->
                  <div class="serp-preview" id="serpPreview">
                    <div class="serp-url">https://codebytushu.com</div>
                    <div class="serp-title" id="serpTitle"><?= e(c('seo_default_title',$cfg,'CodeByTushu')) ?></div>
                    <div class="serp-desc" id="serpDesc"><?= e(c('seo_default_description',$cfg,'')) ?></div>
                  </div>
                  <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                    <button type="submit" class="btn btn-primary">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                      Save SEO Settings
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- ████ FOOTER ████████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='footer'?'on':'' ?>" id="panel-footer">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">📄</div>
                <div>
                  <div class="s-card-title">Footer Content</div>
                  <div class="s-card-desc">Text, copyright, and year displayed in the site footer</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="footerForm">
                  <?= csrfField() ?>
                  <div class="form-row-2" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Copyright Year</label>
                      <input type="number" name="footer_year" class="form-control"
                             value="<?= e(c('footer_year',$cfg, date('Y'))) ?>"
                             min="2000" max="2099" placeholder="<?= date('Y') ?>">
                      <div class="form-hint">e.g. <?= date('Y') ?> — shown as © <?= date('Y') ?> CodeByTushu</div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Footer Tagline</label>
                      <input type="text" name="footer_tagline" class="form-control"
                             value="<?= e(c('footer_tagline',$cfg)) ?>"
                             placeholder="Made with ❤️ for developers">
                    </div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Footer About Text</label>
                    <textarea name="footer_about" class="form-control" rows="3"><?= e(c('footer_about',$cfg)) ?></textarea>
                    <div class="form-hint">Short description shown in footer "About" column</div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Privacy Policy URL</label>
                    <input type="url" name="privacy_policy_url" class="form-control"
                           value="<?= e(c('privacy_policy_url',$cfg)) ?>" placeholder="/privacy">
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Terms of Service URL</label>
                    <input type="url" name="terms_url" class="form-control"
                           value="<?= e(c('terms_url',$cfg)) ?>" placeholder="/terms">
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">Save Footer Settings</button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- ████ EMAIL ██████████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='email'?'on':'' ?>" id="panel-email">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">📧</div>
                <div>
                  <div class="s-card-title">SMTP Configuration</div>
                  <div class="s-card-desc">Read-only — edit values in your <code style="font-size:11px;background:var(--input-bg);padding:1px 5px;border-radius:3px;">.env</code> file</div>
                </div>
              </div>
              <div class="s-card-body">
                <div style="background:rgba(255,196,0,.05);border:1px solid rgba(255,196,0,.2);border-radius:var(--radius);padding:12px 16px;margin-bottom:18px;font-size:12px;color:var(--text-muted);display:flex;gap:10px;align-items:flex-start;">
                  <span style="font-size:16px;flex-shrink:0;">ℹ️</span>
                  <div>SMTP credentials are stored in your <strong>.env</strong> file for security.
                  Edit <code style="color:var(--accent);">SMTP_HOST</code>, <code style="color:var(--accent);">SMTP_USER</code>,
                  <code style="color:var(--accent);">SMTP_PASS</code> there directly.</div>
                </div>
                <div class="form-row-2" style="margin-bottom:16px;">
                  <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" class="form-control" value="<?= e($smtpHost) ?>" readonly style="opacity:.65;">
                  </div>
                  <div class="form-group">
                    <label class="form-label">SMTP Port</label>
                    <input type="text" class="form-control" value="<?= e($smtpPort) ?>" readonly style="opacity:.65;">
                  </div>
                </div>
                <div class="form-row-2" style="margin-bottom:16px;">
                  <div class="form-group">
                    <label class="form-label">From Email</label>
                    <input type="text" class="form-control" value="<?= e($smtpFrom) ?>" readonly style="opacity:.65;">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Notification To</label>
                    <input type="text" class="form-control" value="<?= e($smtpTo) ?>" readonly style="opacity:.65;">
                  </div>
                </div>
              </div>
            </div>

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">⚙️</div>
                <div>
                  <div class="s-card-title">Email Settings</div>
                  <div class="s-card-desc">Notification and auto-reply preferences</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="emailSettingsForm">
                  <?= csrfField() ?>
                  <div class="form-row-2" style="margin-bottom:16px;">
                    <div class="form-group">
                      <label class="form-label">Admin Notification Email</label>
                      <input type="email" name="admin_notification_email" class="form-control"
                             value="<?= e(c('admin_notification_email',$cfg,$smtpTo)) ?>"
                             placeholder="admin@yoursite.com">
                      <div class="form-hint">Receives contact form notifications</div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">From Name (sender label)</label>
                      <input type="text" name="email_from_name" class="form-control"
                             value="<?= e(c('email_from_name',$cfg,c('site_name',$cfg,'CodeByTushu'))) ?>"
                             placeholder="CodeByTushu">
                    </div>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Contact Form Auto-Reply Message</label>
                    <textarea name="email_autoreply_text" class="form-control" rows="3" placeholder="Thank you for reaching out! I'll get back to you within 24 hours."><?= e(c('email_autoreply_text',$cfg)) ?></textarea>
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">Save Email Settings</button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- ████ MAINTENANCE ████████████████████████████████ -->
          <div class="settings-panel <?= $activeTab==='maintenance'?'on':'' ?>" id="panel-maintenance">

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">🔧</div>
                <div>
                  <div class="s-card-title">Maintenance Mode</div>
                  <div class="s-card-desc">Temporarily take the public site offline</div>
                </div>
              </div>
              <div class="s-card-body">

                <!-- Status indicator -->
                <div class="maintenance-status <?= $maintenanceOn?'down':'live' ?>">
                  <div class="ms-dot <?= $maintenanceOn?'down':'live' ?>"></div>
                  <div class="ms-text">
                    <div class="ms-title" style="color:<?= $maintenanceOn?'#ef4444':'#22c55e' ?>;">
                      <?= $maintenanceOn ? '⚠️ Maintenance Mode is ACTIVE' : '✅ Site is LIVE' ?>
                    </div>
                    <div class="ms-sub">
                      <?= $maintenanceOn
                          ? 'Visitors see the maintenance page. Admin panel remains accessible.'
                          : 'All public pages are accessible to visitors.' ?>
                    </div>
                  </div>
                </div>

                <!-- Big toggle -->
                <div class="big-tog" onclick="document.getElementById('maintenanceCheck').click()">
                  <div class="big-tog-left">
                    <div class="big-tog-title">Enable Maintenance Mode</div>
                    <div class="big-tog-sub">When ON, visitors see a maintenance page instead of your site</div>
                  </div>
                  <div class="big-tog-sw">
                    <input type="checkbox" id="maintenanceCheck" <?= $maintenanceOn?'checked':'' ?>
                           onclick="event.stopPropagation()" onchange="toggleMaintenance(this.checked)">
                    <span class="big-tog-sl"></span>
                  </div>
                </div>

              </div>
            </div>

            <div class="s-card">
              <div class="s-card-hd">
                <div class="s-card-icon">📝</div>
                <div>
                  <div class="s-card-title">Maintenance Page Content</div>
                  <div class="s-card-desc">Customize what visitors see during maintenance</div>
                </div>
              </div>
              <div class="s-card-body">
                <form id="maintenanceForm">
                  <?= csrfField() ?>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Maintenance Headline</label>
                    <input type="text" name="maintenance_title" class="form-control"
                           value="<?= e(c('maintenance_title',$cfg,'We\'ll be back soon!')) ?>"
                           placeholder="We'll be back soon!">
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Maintenance Message</label>
                    <textarea name="maintenance_message" class="form-control" rows="3"><?= e(c('maintenance_message',$cfg,'We are currently performing scheduled maintenance. Thank you for your patience.')) ?></textarea>
                  </div>
                  <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Estimated Completion Time <span style="color:var(--text-dim);font-weight:400;text-transform:none;">(optional)</span></label>
                    <input type="text" name="maintenance_eta" class="form-control"
                           value="<?= e(c('maintenance_eta',$cfg)) ?>" placeholder="Back online in ~2 hours">
                  </div>
                  <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">Save Maintenance Content</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="s-card" style="border-color:rgba(239,68,68,.3);">
              <div class="s-card-hd" style="border-bottom-color:rgba(239,68,68,.15);">
                <div class="s-card-icon" style="background:rgba(239,68,68,.1);">⚡</div>
                <div>
                  <div class="s-card-title" style="color:#ef4444;">Cache & Performance</div>
                  <div class="s-card-desc">Clear server caches and rebuild static data</div>
                </div>
              </div>
              <div class="s-card-body" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-ghost btn-sm" onclick="clearCache('config')">🔄 Clear Config Cache</button>
                <button class="btn btn-ghost btn-sm" onclick="clearCache('templates')">🔄 Clear Template Cache</button>
                <button class="btn btn-danger btn-sm" onclick="clearCache('all')">⚡ Clear All Caches</button>
              </div>
            </div>

          </div>

        </div><!-- /panels -->
      </div><!-- /layout -->

    </main>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ══ TAB NAVIGATION (URL-based) ══════════════════════════ */
document.querySelectorAll('.snav-item').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const tab = link.dataset.tab;
    document.querySelectorAll('.snav-item').forEach(l => l.classList.remove('active'));
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('on'));
    link.classList.add('active');
    document.getElementById('panel-' + tab)?.classList.add('on');
    history.pushState({}, '', '?tab=' + tab);
  });
});

/* ══ FORMS ════════════════════════════════════════════════ */
ajaxForm('generalForm',     () => Toast.success('Saved ✓', 'Website identity updated.'));
ajaxForm('contactForm',     () => Toast.success('Saved ✓', 'Contact details updated.'));
ajaxForm('colorsForm',      () => Toast.success('Saved ✓', 'Brand colors updated.'));
ajaxForm('seoForm',         () => Toast.success('Saved ✓', 'SEO settings updated.'));
ajaxForm('footerForm',      () => Toast.success('Saved ✓', 'Footer settings updated.'));
ajaxForm('emailSettingsForm',()=> Toast.success('Saved ✓', 'Email settings updated.'));
ajaxForm('maintenanceForm', () => Toast.success('Saved ✓', 'Maintenance content saved.'));

/* ══ BRANDING UPLOADS ═══════════════════════════════════ */
function brandUpload(formId, fieldName, previewImgId, boxId) {
  const form = document.getElementById(formId);
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.set('csrf_token', CSRF);
    const r = await fetch('<?= SITE_URL ?>/api/admin/settings.php', { method:'POST', body:fd });
    const res = await r.json();
    if (res.success) {
      Toast.success('Updated!', fieldName + ' updated successfully.');
      if (res.data?.path) {
        const img = document.getElementById(previewImgId);
        const box = document.getElementById(boxId);
        if (img) { img.src = res.data.path; }
        else {
          box.innerHTML = `<img src="${res.data.path}" id="${previewImgId}" style="max-width:100%;max-height:100%;object-fit:contain;" alt="">`;
        }
        box.classList.add('has-img');
      }
    } else Toast.error('Error', res.error);
  });
}
brandUpload('logoForm',    'Logo',    'logoPreviewImg',    'logoBox');
brandUpload('faviconForm', 'Favicon', 'faviconPreviewImg', 'faviconBox');

// Logo file → URL preview
document.getElementById('logoInput')?.addEventListener('change', function() {
  const f = this.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => {
    const box = document.getElementById('logoBox');
    box.innerHTML = `<img src="${e.target.result}" id="logoPreviewImg" style="max-width:100%;max-height:100%;object-fit:contain;" alt="">`;
    box.classList.add('has-img');
  };
  r.readAsDataURL(f);
});
document.getElementById('faviconInput')?.addEventListener('change', function() {
  const f = this.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => {
    const box = document.getElementById('faviconBox');
    box.innerHTML = `<img src="${e.target.result}" id="faviconPreviewImg" style="max-width:32px;max-height:32px;" alt="">`;
    box.classList.add('has-img');
  };
  r.readAsDataURL(f);
});

async function removeBrand(key) {
  Modal.confirm('Remove this image?', async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/settings.php', { action:'remove_asset', key });
    if (r.success) { Toast.success('Removed'); setTimeout(() => location.reload(), 800); }
    else Toast.error('Error', r.error);
  }, { title:'Remove Image', confirmText:'Remove', type:'danger' });
}

/* ══ COLOR PICKERS ══════════════════════════════════════ */
function bindColorPicker(pickerId, textId) {
  const picker = document.getElementById(pickerId);
  const text   = document.getElementById(textId);
  if (!picker || !text) return;
  picker.addEventListener('input', () => { text.value = picker.value; });
  text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
}
bindColorPicker('colorPrimary',   'colorPrimaryText');
bindColorPicker('colorSecondary', 'colorSecondaryText');
bindColorPicker('colorBg',        'colorBgText');

/* ══ SOCIAL LINKS ═══════════════════════════════════════ */
async function saveSocial(id) {
  const url     = document.getElementById(`surl_${id}`)?.value;
  const footer  = document.getElementById(`sfooter_${id}`)?.checked  ? 1 : 0;
  const contact = document.getElementById(`scontact_${id}`)?.checked ? 1 : 0;
  const active  = document.getElementById(`sactive_${id}`)?.checked  ? 1 : 0;
  const r = await apiPost('<?= SITE_URL ?>/api/admin/settings.php', {
    action: 'update_social', id, url,
    show_in_footer: footer, show_in_contact: contact, is_active: active
  });
  if (r.success) Toast.success('Saved ✓', 'Social link updated.');
  else Toast.error('Error', r.error);
}

/* ══ MAINTENANCE TOGGLE ══════════════════════════════ */
async function toggleMaintenance(val) {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/settings.php', {
    action: 'update_config', maintenance_mode: val ? '1' : '0'
  });
  if (r.success) {
    Toast.success(val ? '⚠️ Maintenance ON' : '✅ Site is Live', '');
    setTimeout(() => location.reload(), 1000);
  } else Toast.error('Error', r.error);
}

/* ══ CACHE CLEAR ══════════════════════════════════════ */
async function clearCache(type) {
  const r = await apiPost('<?= SITE_URL ?>/api/admin/settings.php', { action:'clear_cache', cache_type: type });
  if (r.success) Toast.success('Cache cleared', r.message || 'Done.');
  else Toast.error('Error', r.error);
}

/* ══ SEO LIVE PREVIEW ════════════════════════════════ */
function charCount(inputId, countId, max) {
  const el  = document.getElementById(inputId);
  const cnt = document.getElementById(countId);
  if (!el || !cnt) return;
  function update() {
    const n = el.value.length;
    cnt.textContent = `${n} / ${max}`;
    cnt.className = 'char-cnt' + (n > max ? ' over' : n > max * .9 ? ' warn' : '');
  }
  el.addEventListener('input', update); update();
}
charCount('seoTitle', 'seoTitleCount', 70);
charCount('seoDesc',  'seoDescCount',  160);

document.getElementById('seoTitle')?.addEventListener('input', function() {
  document.getElementById('serpTitle').textContent = this.value || 'Page Title';
});
document.getElementById('seoDesc')?.addEventListener('input', function() {
  document.getElementById('serpDesc').textContent = this.value || 'Page description...';
});
// Init counts
document.getElementById('seoTitle')?.dispatchEvent(new Event('input'));
document.getElementById('seoDesc')?.dispatchEvent(new Event('input'));
</script>
</body>
</html>
