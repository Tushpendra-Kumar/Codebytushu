<?php
/**
 * CodeByTushu — LeetCode Problem Editor v2
 * Full-featured editor: all 21 fields, 6 tabs, autosave, draft/publish,
 * multi-language code editors, file uploads, SEO preview.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';

$id   = (int)get('id', '0');
$mode = $id ? 'edit' : 'add';

$adminSection = 'LeetCode';
$adminTitle   = ($mode === 'edit' ? 'Edit Solution' : 'New Solution') . ' — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard',    'url' => '/admin/'],
    ['label' => 'LeetCode CMS', 'url' => '/admin/leetcode.php'],
    ['label' => $mode === 'edit' ? 'Edit Solution' : 'New Solution'],
];

$pdo  = db();

/* ── Load existing solution ─────────────────────────────── */
$sol     = [];
$codes   = [];
$solTags = [];

if ($mode === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM leetcode_solutions WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $sol = $stmt->fetch();
    if (!$sol) {
        redirectWithMessage('/admin/leetcode.php', 'error', 'Solution not found.');
        exit;
    }
    $stmt2 = $pdo->prepare('SELECT * FROM solution_code_blocks WHERE solution_id = ? ORDER BY sort_order');
    $stmt2->execute([$id]);
    $codes = $stmt2->fetchAll();

    $stmt3 = $pdo->prepare('SELECT tag_id FROM solution_tag_map WHERE solution_id = ?');
    $stmt3->execute([$id]);
    $solTags = array_column($stmt3->fetchAll(), 'tag_id');
}

/* ── Reference data ─────────────────────────────────────── */
$allTags  = $pdo->query('SELECT id, name, color_hex FROM solution_tags WHERE is_active=1 ORDER BY name')->fetchAll();
$months   = $pdo->query('SELECT * FROM leetcode_months ORDER BY year ASC, month_num ASC')->fetchAll();

$languages = ['Java','Python','C++','JavaScript','TypeScript','Go','Rust','C#','Kotlin','Swift','C'];
$platforms = ['LeetCode','GeeksForGeeks','HackerRank','Codeforces','CodeChef','AtCoder','Other'];

/* ── Default code blocks ────────────────────────────────── */
if (empty($codes)) {
    $codes = [['id'=>0,'language'=>'Java','code'=>'','is_primary'=>1,'sort_order'=>0]];
}

/* ── v helper ─────────────────────────────────────────────── */
function v(array $sol, string $key, string $default=''): string {
    return htmlspecialchars((string)($sol[$key] ?? $default), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── Editor layout ── */
.editor-layout { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
.editor-main   { min-width:0; }
.editor-sidebar { position:sticky; top:20px; }

/* ── Tab system ── */
.ed-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); padding:0;
           overflow-x:auto; scrollbar-width:none; }
.ed-tabs::-webkit-scrollbar { display:none; }
.ed-tab { padding:13px 18px; background:none; border:none; border-bottom:2px solid transparent;
          cursor:pointer; color:var(--text-muted); font-family:var(--font); font-size:13px;
          font-weight:500; margin-bottom:-1px; transition:all .2s; display:flex; align-items:center;
          gap:7px; white-space:nowrap; flex-shrink:0; }
.ed-tab:hover { color:var(--text); background:var(--bg-hover); }
.ed-tab.active { color:var(--accent); border-bottom-color:var(--accent); background:var(--accent-glow); }
.ed-tab .tab-badge { background:var(--accent); color:#000; font-size:9px; font-weight:700;
                     border-radius:99px; padding:1px 5px; min-width:16px; text-align:center; }
.tab-pane { display:none; padding:24px; }
.tab-pane.active { display:block; }

/* ── Code editor ── */
.code-ed-wrap { background:#0d0d18; border:1px solid var(--border); border-radius:var(--radius);
                overflow:hidden; margin-top:6px; }
.code-ed-bar  { display:flex; align-items:center; justify-content:space-between;
                padding:8px 14px; border-bottom:1px solid var(--border);
                background:#0d0d18; gap:10px; }
.lang-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.code-ed-wrap textarea { width:100%; background:transparent; border:none; outline:none;
                          color:#e0e8ff; font-family:'Fira Code',ui-monospace,monospace;
                          font-size:13px; line-height:1.7; padding:16px;
                          min-height:220px; resize:vertical; display:block; tab-size:4; }

/* ── Code block item ── */
.code-block { border:1px solid var(--border); border-radius:var(--radius);
              margin-bottom:12px; overflow:hidden; background:var(--card-bg); }
.code-block-hd { display:flex; align-items:center; justify-content:space-between;
                  padding:10px 14px; background:var(--bg-hover); border-bottom:1px solid var(--border);
                  gap:10px; }

/* ── File upload zones ── */
.upload-zone { border:2px dashed var(--border); border-radius:var(--radius);
               padding:24px 16px; text-align:center; cursor:pointer; transition:all .2s;
               background:var(--input-bg); }
.upload-zone:hover, .upload-zone.drag-over { border-color:var(--accent); background:var(--accent-glow); }
.upload-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; }
.upload-preview { margin-top:12px; display:none; }
.upload-preview.show { display:block; }

/* ── Tag chips ── */
.tag-grid { display:flex; flex-wrap:wrap; gap:8px; }
.tag-chip-label { display:inline-flex; align-items:center; gap:7px; padding:6px 14px;
                  background:var(--input-bg); border:1.5px solid var(--border);
                  border-radius:99px; cursor:pointer; font-size:12px; font-weight:500;
                  transition:all .2s; user-select:none; }
.tag-chip-label input { display:none; }
.tag-chip-label:has(input:checked) { border-color:var(--accent); background:var(--accent-glow); color:var(--accent); }
.tag-chip-label:hover { border-color:var(--border-mid); }

/* ── SEO preview ── */
.seo-preview { background:var(--input-bg); border:1px solid var(--border); border-radius:var(--radius);
               padding:16px; margin-top:12px; }
.seo-url  { font-size:12px; color:#22c55e; margin-bottom:4px; font-family:monospace; }
.seo-title-prev { font-size:16px; color:#93c5fd; margin-bottom:4px; font-weight:500; }
.seo-desc-prev  { font-size:13px; color:var(--text-muted); line-height:1.5; }

/* ── Sidebar cards ── */
.sb-card { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
           padding:18px; margin-bottom:14px; }
.sb-card-title { font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;
                 letter-spacing:.5px; margin-bottom:14px; display:flex; align-items:center; gap:6px; }

/* ── Autosave indicator ── */
.autosave-dot { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-dim); }
.autosave-dot.saving { color:var(--accent); }
.autosave-dot.saved  { color:#22c55e; }
.autosave-dot.error  { color:#ef4444; }
.autosave-pulse { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }
.autosave-dot.saving .autosave-pulse { animation:pulse-ring 1s infinite; }
@keyframes pulse-ring { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.4} }

/* ── Publish button strip ── */
.pub-strip { display:flex; gap:8px; flex-direction:column; }
.pub-status { display:flex; align-items:center; justify-content:space-between;
              font-size:12px; color:var(--text-muted); padding:8px 0; border-bottom:1px solid var(--border); margin-bottom:8px; }
.pub-status .dot { width:8px; height:8px; border-radius:50%; }

/* ── Form group helpers ── */
.form-hint { font-size:11px; color:var(--text-dim); margin-top:4px; }
.form-row-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.char-counter { font-size:10px; color:var(--text-dim); text-align:right; margin-top:3px; }

/* ── Lang colors ── */
.lang-java       { background:#f89820; }
.lang-python     { background:#3776ab; }
.lang-cpp        { background:#00599c; }
.lang-javascript { background:#f7df1e; }
.lang-typescript { background:#3178c6; }
.lang-go         { background:#00acd7; }
.lang-rust       { background:#b7410e; }
.lang-csharp     { background:#239120; }
.lang-kotlin     { background:#7f52ff; }
.lang-swift      { background:#f05138; }
.lang-c          { background:#555555; }
.lang-default    { background:var(--accent); }

@media(max-width:1024px) { .editor-layout { grid-template-columns:1fr; } .editor-sidebar { position:static; } }
@media(max-width:600px)  { .form-row-3 { grid-template-columns:1fr; } }
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
          <h1 class="page-title">
            <?= $mode === 'edit' ? 'Edit Solution' : 'New LeetCode Solution' ?>
          </h1>
          <?php if ($mode === 'edit'): ?>
            <p class="page-subtitle">
              <?= $sol['problem_number'] ? '#'.e($sol['problem_number']).' · ' : '' ?><?= e($sol['problem_title']) ?>
              &nbsp;·&nbsp;
              <span class="autosave-dot" id="autosaveStatus">
                <span class="autosave-pulse"></span>
                <span id="autosaveText">Ready</span>
              </span>
            </p>
          <?php endif; ?>
        </div>
        <div class="page-header-actions">
          <a href="<?= SITE_URL ?>/admin/leetcode.php" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to list
          </a>
          <?php if ($mode === 'edit' && !empty($sol['is_published'])): ?>
            <a href="<?= SITE_URL ?>/leetcode/problem/<?= e($sol['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              View Live
            </a>
          <?php endif; ?>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- ══ Main Editor Form ════════════════════════════════ -->
      <form id="editorForm" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" id="formAction" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
        <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="editor-layout">

          <!-- ══ MAIN PANEL ════════════════════════════════ -->
          <div class="editor-main">
            <div class="card" style="padding:0;overflow:visible;">

              <!-- ── Tabs ─────────────────────────────────── -->
              <div class="ed-tabs">
                <button type="button" class="ed-tab active" data-tab="details">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  Details
                </button>
                <button type="button" class="ed-tab" data-tab="code">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                  Code
                  <span class="tab-badge" id="codeBadge"><?= count($codes) ?></span>
                </button>
                <button type="button" class="ed-tab" data-tab="description">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  Description
                </button>
                <button type="button" class="ed-tab" data-tab="tags">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                  Tags
                  <?php if (!empty($solTags)): ?><span class="tab-badge"><?= count($solTags) ?></span><?php endif; ?>
                </button>
                <button type="button" class="ed-tab" data-tab="media">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  Media
                </button>
                <button type="button" class="ed-tab" data-tab="seo">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  SEO
                </button>
              </div>

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 1 — DETAILS
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane active" id="tab-details">

                <div class="form-row-3">
                  <div class="form-group">
                    <label class="form-label">Problem Number</label>
                    <input type="number" name="problem_number" class="form-control" min="1" max="9999"
                           value="<?= v($sol,'problem_number') ?>" placeholder="e.g. 1" id="probNum">
                  </div>
                  <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Problem Title <span class="req">*</span></label>
                    <input type="text" name="problem_title" class="form-control" id="probTitle"
                           value="<?= v($sol,'problem_title') ?>" placeholder="Two Sum" required>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label">URL Slug <span class="req">*</span></label>
                    <input type="text" name="slug" id="solSlug" class="form-control"
                           value="<?= v($sol,'slug') ?>" placeholder="two-sum" required
                           pattern="[a-z0-9-]+" autocomplete="off">
                    <div class="form-hint">Auto-generated · only lowercase, numbers, hyphens</div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Solution Date <span class="req">*</span></label>
                    <input type="date" name="solution_date" class="form-control"
                           value="<?= v($sol,'solution_date',date('Y-m-d')) ?>" required>
                  </div>
                </div>

                <div class="form-row-3">
                  <div class="form-group">
                    <label class="form-label">Difficulty <span class="req">*</span></label>
                    <select name="difficulty" class="form-control" required id="diffSelect">
                      <option value="">Select…</option>
                      <?php foreach (['Easy','Medium','Hard'] as $d): ?>
                        <option value="<?= $d ?>" <?= ($sol['difficulty']??'')===$d?'selected':'' ?>><?= $d ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Platform</label>
                    <select name="platform" class="form-control">
                      <option value="LeetCode" <?= ($sol['platform']??'LeetCode')==='LeetCode'?'selected':'' ?>>LeetCode</option>
                      <?php foreach (array_slice($platforms,1) as $p): ?>
                        <option value="<?= $p ?>" <?= ($sol['platform']??'')===$p?'selected':'' ?>><?= $p ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Month (auto-detect)</label>
                    <select name="month_id" class="form-control">
                      <option value="">— Auto from date —</option>
                      <?php foreach ($months as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($sol['month_id']??'')==$m['id']?'selected':'' ?>>
                          <?= e($m['month_name']) ?> <?= $m['year'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label">Time Complexity</label>
                    <input type="text" name="time_complexity" class="form-control"
                           value="<?= v($sol,'time_complexity') ?>" placeholder="O(n)">
                    <div class="form-hint">e.g. O(n), O(n log n), O(1)</div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Space Complexity</label>
                    <input type="text" name="space_complexity" class="form-control"
                           value="<?= v($sol,'space_complexity') ?>" placeholder="O(1)">
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label">LeetCode Problem URL</label>
                    <input type="url" name="leetcode_url" class="form-control"
                           value="<?= v($sol,'leetcode_url') ?>"
                           placeholder="https://leetcode.com/problems/two-sum/">
                  </div>
                  <div class="form-group">
                    <label class="form-label">YouTube Video URL</label>
                    <input type="url" name="youtube_url" class="form-control" id="ytUrl"
                           value="<?= v($sol,'youtube_url') ?>"
                           placeholder="https://youtu.be/…">
                  </div>
                </div>

                <!-- YouTube preview -->
                <div id="ytPreview" style="display:none;margin-top:-8px;margin-bottom:16px;">
                  <div style="background:var(--input-bg);border-radius:var(--radius);padding:10px;display:flex;align-items:center;gap:10px;">
                    <span style="font-size:20px;">▶️</span>
                    <div>
                      <div style="font-size:12px;font-weight:600;color:var(--text);">YouTube video linked</div>
                      <a id="ytLink" href="#" target="_blank" style="font-size:11px;color:var(--accent);word-break:break-all;"></a>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Notes</label>
                  <textarea name="notes" class="form-control" rows="3"
                            placeholder="Internal notes, edge cases, alternative approaches…"><?= v($sol,'notes') ?></textarea>
                </div>

              </div><!-- /tab-details -->

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 2 — CODE BLOCKS
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane" id="tab-code">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
                  <div>
                    <div style="font-weight:600;color:var(--text);margin-bottom:3px;">Solution Code Blocks</div>
                    <div style="font-size:12px;color:var(--text-muted);">Add code in multiple programming languages. Mark one as primary.</div>
                  </div>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="addCodeBlock()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Language
                  </button>
                </div>

                <div id="codeBlockList">
                  <?php foreach ($codes as $ci => $c): ?>
                  <?php
                    $langSlug = strtolower(preg_replace('/[^a-z0-9]/i','-',$c['language']??'java'));
                    $dotClass = "lang-$langSlug";
                  ?>
                  <div class="code-block" id="codeBlock<?= $ci ?>">
                    <div class="code-block-hd">
                      <div style="display:flex;align-items:center;gap:10px;">
                        <div class="lang-dot <?= $dotClass ?>" style="width:10px;height:10px;border-radius:50%;flex-shrink:0;"></div>
                        <select name="codes[<?= $ci ?>][language]" class="form-control"
                                style="width:150px;" onchange="updateLangDot(this,<?= $ci ?>)">
                          <?php foreach ($languages as $lang): ?>
                            <option value="<?= $lang ?>" <?= ($c['language']===$lang)?'selected':'' ?>><?= $lang ?></option>
                          <?php endforeach; ?>
                        </select>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:var(--text-muted);">
                          <input type="checkbox" name="codes[<?= $ci ?>][is_primary]" value="1"
                                 <?= !empty($c['is_primary'])?'checked':'' ?>
                                 style="accent-color:var(--accent);">
                          Primary
                        </label>
                      </div>
                      <div style="display:flex;gap:6px;align-items:center;">
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Copy code"
                                onclick="copyCode('codeArea<?= $ci ?>')">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <?php if ($c['id']): ?>
                          <input type="hidden" name="codes[<?= $ci ?>][id]" value="<?= $c['id'] ?>">
                        <?php endif; ?>
                        <?php if ($ci > 0): ?>
                          <button type="button" class="btn btn-danger btn-sm btn-icon"
                                  onclick="removeCodeBlock('codeBlock<?= $ci ?>')">✕</button>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="code-ed-wrap" style="border:none;border-radius:0;background:#0d0d18;">
                      <textarea id="codeArea<?= $ci ?>" name="codes[<?= $ci ?>][code]"
                                placeholder="// <?= e($c['language']) ?> solution here…"
                                rows="16" spellcheck="false"><?= htmlspecialchars($c['code']??'', ENT_QUOTES|ENT_SUBSTITUTE) ?></textarea>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div><!-- /tab-code -->

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 3 — DESCRIPTION
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane" id="tab-description">
                <div class="form-group">
                  <label class="form-label">Problem Description</label>
                  <textarea name="description" class="form-control" rows="6"
                            placeholder="Copy the problem statement here…"><?= v($sol,'description') ?></textarea>
                  <div class="form-hint">Full problem statement from LeetCode / platform.</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Approach Summary</label>
                  <textarea name="approach_summary" class="form-control" rows="4"
                            placeholder="Brief one-paragraph summary of the approach…"><?= v($sol,'approach_summary') ?></textarea>
                </div>
                <div class="form-group">
                  <label class="form-label">Full Explanation</label>
                  <textarea name="explanation" class="form-control" rows="10"
                            placeholder="Step-by-step explanation of the solution…"
                            id="explanationArea"><?= v($sol,'explanation') ?></textarea>
                  <div class="form-hint">Supports Markdown. Line breaks are preserved.</div>
                </div>
              </div><!-- /tab-description -->

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 4 — TAGS
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane" id="tab-tags">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                  <div>
                    <div style="font-weight:600;color:var(--text);margin-bottom:3px;">Problem Tags</div>
                    <div style="font-size:12px;color:var(--text-muted);">Select all topic tags that apply.</div>
                  </div>
                  <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="clearAllTags()">Clear All</button>
                    <span id="tagCountBadge" style="font-size:12px;color:var(--accent);font-weight:700;padding:4px 10px;background:var(--accent-glow);border-radius:99px;">
                      <?= count($solTags) ?> selected
                    </span>
                  </div>
                </div>

                <!-- Quick filter -->
                <input type="text" class="form-control" id="tagSearch"
                       placeholder="Filter tags…" style="margin-bottom:14px;">

                <div class="tag-grid" id="tagGrid">
                  <?php foreach ($allTags as $t): ?>
                  <label class="tag-chip-label" data-tag-name="<?= strtolower(e($t['name'])) ?>">
                    <input type="checkbox" name="tags[]" value="<?= $t['id'] ?>"
                           <?= in_array($t['id'], $solTags) ? 'checked' : '' ?>
                           onchange="updateTagCount()">
                    <?php if ($t['color_hex']): ?>
                      <span style="width:7px;height:7px;border-radius:50%;background:<?= e($t['color_hex']) ?>;flex-shrink:0;"></span>
                    <?php endif; ?>
                    <?= e($t['name']) ?>
                  </label>
                  <?php endforeach; ?>
                  <?php if (empty($allTags)): ?>
                    <p style="color:var(--text-muted);font-size:13px;">No tags found. <a href="<?= SITE_URL ?>/admin/categories.php" style="color:var(--accent);">Add tags →</a></p>
                  <?php endif; ?>
                </div>
              </div><!-- /tab-tags -->

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 5 — MEDIA
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane" id="tab-media">
                <div class="form-row-2">

                  <!-- Thumbnail Upload -->
                  <div class="form-group">
                    <label class="form-label">Thumbnail Image</label>
                    <div class="upload-zone" id="thumbZone" onclick="document.getElementById('thumbInput').click()">
                      <input type="file" id="thumbInput" name="thumbnail" accept="image/*"
                             onchange="previewThumb(this)" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" style="margin:0 auto 10px;display:block;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                      <div style="font-size:13px;font-weight:600;color:var(--text-muted);">Click or drag image here</div>
                      <div style="font-size:11px;color:var(--text-dim);margin-top:4px;">JPG, PNG, WebP · Max 2MB · Recommended 800×450px</div>
                    </div>
                    <?php if (!empty($sol['thumbnail_path'])): ?>
                    <div class="upload-preview show" id="thumbPreview">
                      <img src="<?= e($sol['thumbnail_path']) ?>" id="thumbPreviewImg"
                           style="max-width:100%;border-radius:var(--radius);border:1px solid var(--border);" alt="">
                      <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">Current: <?= basename($sol['thumbnail_path']) ?></div>
                      <input type="hidden" name="keep_thumbnail" value="1">
                    </div>
                    <?php else: ?>
                    <div class="upload-preview" id="thumbPreview">
                      <img id="thumbPreviewImg" src="" style="max-width:100%;border-radius:var(--radius);border:1px solid var(--border);" alt="">
                    </div>
                    <?php endif; ?>
                  </div>

                  <!-- PDF Upload -->
                  <div class="form-group">
                    <label class="form-label">Solution PDF</label>
                    <div class="upload-zone" id="pdfZone" onclick="document.getElementById('pdfInput').click()">
                      <input type="file" id="pdfInput" name="pdf_file" accept="application/pdf"
                             onchange="previewPdf(this)" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" style="margin:0 auto 10px;display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                      <div style="font-size:13px;font-weight:600;color:var(--text-muted);">Click or drag PDF here</div>
                      <div style="font-size:11px;color:var(--text-dim);margin-top:4px;">PDF only · Max 10MB</div>
                    </div>
                    <?php if (!empty($sol['pdf_path'])): ?>
                    <div class="upload-preview show" id="pdfPreview">
                      <div style="display:flex;align-items:center;gap:10px;background:var(--input-bg);border-radius:var(--radius);padding:10px 14px;border:1px solid var(--border);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <div>
                          <div style="font-size:12px;font-weight:600;color:var(--text);"><?= basename($sol['pdf_path']) ?></div>
                          <a href="<?= e($sol['pdf_path']) ?>" target="_blank" style="font-size:11px;color:var(--accent);">View PDF →</a>
                        </div>
                      </div>
                      <input type="hidden" name="keep_pdf" value="1">
                    </div>
                    <?php else: ?>
                    <div class="upload-preview" id="pdfPreview"></div>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Drag-drop handlers -->
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
                  <div style="font-size:12px;color:var(--text-muted);">
                    <strong style="color:var(--text);">Upload notes:</strong>
                    Thumbnails are stored at <code>/uploads/leetcode/thumbs/</code>.
                    PDFs at <code>/uploads/leetcode/pdfs/</code>.
                    Old files are replaced automatically on update.
                  </div>
                </div>
              </div><!-- /tab-media -->

              <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                   TAB 6 — SEO
              ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
              <div class="tab-pane" id="tab-seo">
                <div class="form-group">
                  <label class="form-label">SEO Title</label>
                  <input type="text" name="seo_title" id="seoTitle" class="form-control" maxlength="70"
                         value="<?= v($sol,'seo_title') ?>"
                         placeholder="Custom title for search engines (leave blank to use problem title)"
                         oninput="updateSeoPreview()">
                  <div style="display:flex;justify-content:space-between;margin-top:3px;">
                    <div class="form-hint">Max 70 characters recommended.</div>
                    <span class="char-counter" id="seoTitleCount">0/70</span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Meta Description <span class="req" title="Important for SEO">*</span></label>
                  <textarea name="meta_description" id="metaDesc" class="form-control" rows="3"
                            maxlength="160" oninput="updateSeoPreview()"
                            placeholder="Concise description for search engines…"><?= v($sol,'meta_description') ?></textarea>
                  <div style="display:flex;justify-content:space-between;margin-top:3px;">
                    <div class="form-hint">Max 160 characters. Appears in Google search results.</div>
                    <span class="char-counter" id="metaCount">0/160</span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">OG Image URL</label>
                  <input type="url" name="og_image_url" id="ogImage" class="form-control"
                         value="<?= v($sol,'og_image_url') ?>"
                         placeholder="https://codebytushu.com/path/to/image.jpg"
                         oninput="updateSeoPreview()">
                  <div class="form-hint">Social media preview image (1200×630px recommended). Leave blank to use thumbnail.</div>
                </div>

                <!-- SERP Preview -->
                <div style="margin-top:20px;">
                  <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Google Search Preview</div>
                  <div class="seo-preview">
                    <div class="seo-url" id="prevUrl">codebytushu.com › Leetcode › solution › <?= e($sol['slug']??'two-sum') ?></div>
                    <div class="seo-title-prev" id="prevTitle"><?= v($sol,'seo_title') ?: v($sol,'problem_title') ?: 'Problem Title — CodeByTushu' ?></div>
                    <div class="seo-desc-prev" id="prevDesc"><?= v($sol,'meta_description') ?: 'No meta description yet.' ?></div>
                  </div>
                </div>
              </div><!-- /tab-seo -->

            </div><!-- /card -->
          </div><!-- /editor-main -->

          <!-- ══ SIDEBAR ══════════════════════════════════════ -->
          <div class="editor-sidebar">

            <!-- Publish card -->
            <div class="sb-card">
              <div class="sb-card-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Publish
              </div>

              <div class="pub-status">
                <span>Status</span>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div class="dot" id="statusDot" style="background:<?= !empty($sol['is_published'])?'#22c55e':'#f59e0b' ?>;"></div>
                  <span id="statusLabel" style="font-weight:600;color:<?= !empty($sol['is_published'])?'#22c55e':'#f59e0b' ?>;">
                    <?= !empty($sol['is_published']) ? 'Published' : 'Draft' ?>
                  </span>
                </div>
              </div>

              <!-- Featured toggle -->
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <label style="font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px;cursor:pointer;">
                  <input type="checkbox" name="is_featured" value="1" id="featToggle"
                         <?= !empty($sol['is_featured'])?'checked':'' ?>
                         style="accent-color:var(--accent);">
                  ⭐ Featured
                </label>
              </div>

              <!-- is_published hidden — driven by buttons -->
              <input type="hidden" name="is_published" id="isPublishedInput" value="<?= !empty($sol['is_published'])?'1':'0' ?>">

              <div class="pub-strip">
                <button type="button" class="btn btn-ghost btn-sm" id="saveDraftBtn" onclick="submitForm('draft')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                  Save Draft
                </button>
                <button type="button" class="btn btn-primary" id="publishBtn" onclick="submitForm('publish')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <?= !empty($sol['is_published']) ? 'Update & Publish' : 'Publish Now' ?>
                </button>
                <?php if (!empty($sol['is_published'])): ?>
                <button type="button" class="btn btn-warning btn-sm" onclick="unpublish()">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 16 12 21 17 16"/><line x1="12" y1="21" x2="12" y2="9"/></svg>
                  Unpublish
                </button>
                <?php endif; ?>
              </div>

              <?php if (!empty($sol['published_at'])): ?>
              <div style="margin-top:12px;font-size:11px;color:var(--text-dim);">
                Published: <?= e(formatDate($sol['published_at'],'d M Y')) ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Quick Stats (edit mode) -->
            <?php if ($mode === 'edit'): ?>
            <div class="sb-card">
              <div class="sb-card-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Stats
              </div>
              <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                  <span style="color:var(--text-muted);">Views</span>
                  <span style="font-weight:700;color:var(--text);"><?= number_format($sol['view_count']??0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                  <span style="color:var(--text-muted);">Code blocks</span>
                  <span style="font-weight:700;color:var(--text);"><?= count($codes) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                  <span style="color:var(--text-muted);">Tags</span>
                  <span style="font-weight:700;color:var(--text);"><?= count($solTags) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                  <span style="color:var(--text-muted);">Created</span>
                  <span style="font-weight:600;color:var(--text);font-size:11px;"><?= e(formatDate($sol['created_at']??'','d M Y')) ?></span>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Difficulty preview -->
            <div class="sb-card">
              <div class="sb-card-title">Difficulty Preview</div>
              <div style="text-align:center;">
                <span class="badge" id="diffPreview" style="font-size:14px;padding:8px 20px;">
                  <?= !empty($sol['difficulty']) ? e($sol['difficulty']) : '—' ?>
                </span>
              </div>
            </div>

            <!-- Danger zone (edit mode) -->
            <?php if ($mode === 'edit'): ?>
            <div class="sb-card" style="border-color:rgba(239,68,68,.3);">
              <div class="sb-card-title" style="color:#ef4444;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Danger Zone
              </div>
              <button type="button" class="btn btn-danger btn-sm" style="width:100%;"
                      onclick="deleteSolution(<?= $id ?>, '<?= v($sol,'problem_title') ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                Delete Solution
              </button>
            </div>
            <?php endif; ?>

          </div><!-- /editor-sidebar -->
        </div><!-- /editor-layout -->
      </form>

    </main><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.admin-layout -->

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ══════════════════════════════════════════════════════════
   TAB SYSTEM
   ════════════════════════════════════════════════════════ */
document.querySelectorAll('.ed-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.ed-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab)?.classList.add('active');
  });
});

/* ══════════════════════════════════════════════════════════
   AUTO-SLUG FROM TITLE
   ════════════════════════════════════════════════════════ */
const probTitle = document.getElementById('probTitle');
const solSlug   = document.getElementById('solSlug');

probTitle.addEventListener('input', () => {
  if (!solSlug.dataset.manual) {
    solSlug.value = probTitle.value
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }
  updateSeoPreview();
});
solSlug.addEventListener('input', () => { solSlug.dataset.manual = '1'; updateSeoPreview(); });

/* ══════════════════════════════════════════════════════════
   DIFFICULTY PREVIEW BADGE
   ════════════════════════════════════════════════════════ */
const diffSelect  = document.getElementById('diffSelect');
const diffPreview = document.getElementById('diffPreview');
const diffColors  = { Easy:'#22c55e', Medium:'#f59e0b', Hard:'#ef4444' };
const diffBgs     = { Easy:'rgba(34,197,94,.12)', Medium:'rgba(245,158,11,.12)', Hard:'rgba(239,68,68,.12)' };
function updateDiff() {
  const v = diffSelect.value;
  diffPreview.textContent = v || '—';
  diffPreview.style.color = diffColors[v] || 'var(--text-muted)';
  diffPreview.style.background = diffBgs[v] || 'var(--input-bg)';
}
diffSelect.addEventListener('change', updateDiff);
updateDiff();

/* ══════════════════════════════════════════════════════════
   YOUTUBE PREVIEW
   ════════════════════════════════════════════════════════ */
const ytInput   = document.getElementById('ytUrl');
const ytPreview = document.getElementById('ytPreview');
const ytLink    = document.getElementById('ytLink');
function updateYt() {
  const val = ytInput.value.trim();
  if (val && (val.includes('youtube.com') || val.includes('youtu.be'))) {
    ytPreview.style.display = 'block';
    ytLink.href = val; ytLink.textContent = val;
  } else { ytPreview.style.display = 'none'; }
}
ytInput.addEventListener('input', updateYt); updateYt();

/* ══════════════════════════════════════════════════════════
   SEO PREVIEW UPDATE
   ════════════════════════════════════════════════════════ */
const seoTitle  = document.getElementById('seoTitle');
const metaDesc  = document.getElementById('metaDesc');
const seoTitleCount = document.getElementById('seoTitleCount');
const metaCount = document.getElementById('metaCount');
const prevTitle = document.getElementById('prevTitle');
const prevDesc  = document.getElementById('prevDesc');
const prevUrl   = document.getElementById('prevUrl');

function updateSeoPreview() {
  const t = seoTitle.value || probTitle.value || 'Problem Title';
  const d = metaDesc.value || 'No meta description yet.';
  const s = solSlug.value || 'slug';
  prevTitle.textContent = t + ' — CodeByTushu';
  prevDesc.textContent  = d;
  prevUrl.textContent   = `codebytushu.com › Leetcode › solution › ${s}`;
  seoTitleCount.textContent = `${seoTitle.value.length}/70`;
  seoTitleCount.style.color = seoTitle.value.length > 70 ? '#ef4444' : 'var(--text-dim)';
  metaCount.textContent = `${metaDesc.value.length}/160`;
  metaCount.style.color = metaDesc.value.length > 160 ? '#ef4444' : 'var(--text-dim)';
}
seoTitle.addEventListener('input', updateSeoPreview);
metaDesc.addEventListener('input', updateSeoPreview);
updateSeoPreview();

/* ══════════════════════════════════════════════════════════
   CODE BLOCKS — DYNAMIC ADD/REMOVE
   ════════════════════════════════════════════════════════ */
let blockCount = <?= count($codes) ?>;
const langs = <?= json_encode($languages) ?>;
const langColors = {
  Java:'#f89820', Python:'#3776ab', 'C++':'#00599c', JavaScript:'#f7df1e',
  TypeScript:'#3178c6', Go:'#00acd7', Rust:'#b7410e', 'C#':'#239120',
  Kotlin:'#7f52ff', Swift:'#f05138', C:'#555555'
};

function addCodeBlock() {
  const idx = blockCount++;
  const html = `
    <div class="code-block" id="codeBlock${idx}">
      <div class="code-block-hd">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="lang-dot" id="dot${idx}" style="width:10px;height:10px;border-radius:50%;background:var(--accent);flex-shrink:0;"></div>
          <select name="codes[${idx}][language]" class="form-control" style="width:150px;"
                  onchange="updateLangDot(this,${idx})">
            ${langs.map(l => `<option value="${l}">${l}</option>`).join('')}
          </select>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:var(--text-muted);">
            <input type="checkbox" name="codes[${idx}][is_primary]" value="1" style="accent-color:var(--accent);">
            Primary
          </label>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
          <button type="button" class="btn btn-ghost btn-sm btn-icon" onclick="copyCode('codeArea${idx}')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          </button>
          <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="removeCodeBlock('codeBlock${idx}')">✕</button>
        </div>
      </div>
      <div style="background:#0d0d18;border-top:1px solid var(--border);">
        <textarea id="codeArea${idx}" name="codes[${idx}][code]" placeholder="// Your solution here…"
                  rows="14" style="width:100%;background:transparent;border:none;outline:none;
                  color:#e0e8ff;font-family:'Fira Code',monospace;font-size:13px;line-height:1.7;
                  padding:16px;resize:vertical;display:block;"></textarea>
      </div>
    </div>`;
  document.getElementById('codeBlockList').insertAdjacentHTML('beforeend', html);
  updateCodeBadge();
}

function removeCodeBlock(id) {
  document.getElementById(id)?.remove();
  updateCodeBadge();
}

function updateCodeBadge() {
  const n = document.querySelectorAll('.code-block').length;
  document.getElementById('codeBadge').textContent = n;
}

function updateLangDot(select, idx) {
  const dot = document.getElementById(`dot${idx}`);
  if (dot) dot.style.background = langColors[select.value] || 'var(--accent)';
}

function copyCode(areaId) {
  const area = document.getElementById(areaId);
  if (!area) return;
  navigator.clipboard.writeText(area.value).then(() => Toast.success('Copied!', 'Code copied to clipboard.'));
}

/* ══════════════════════════════════════════════════════════
   TAG SYSTEM
   ════════════════════════════════════════════════════════ */
function updateTagCount() {
  const n = document.querySelectorAll('#tagGrid input:checked').length;
  document.getElementById('tagCountBadge').textContent = `${n} selected`;
}
function clearAllTags() {
  document.querySelectorAll('#tagGrid input').forEach(cb => cb.checked = false);
  updateTagCount();
}

// Tag search filter
document.getElementById('tagSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.tag-chip-label').forEach(chip => {
    chip.style.display = chip.dataset.tagName.includes(q) ? '' : 'none';
  });
});

/* ══════════════════════════════════════════════════════════
   THUMBNAIL & PDF PREVIEWS
   ════════════════════════════════════════════════════════ */
function previewThumb(input) {
  if (!input.files[0]) return;
  const file = input.files[0];
  if (file.size > 2*1024*1024) { Toast.error('Too large', 'Thumbnail must be under 2MB.'); input.value=''; return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('thumbPreviewImg').src = e.target.result;
    document.getElementById('thumbPreview').classList.add('show');
  };
  reader.readAsDataURL(file);
}

function previewPdf(input) {
  if (!input.files[0]) return;
  const file = input.files[0];
  if (file.size > 10*1024*1024) { Toast.error('Too large', 'PDF must be under 10MB.'); input.value=''; return; }
  document.getElementById('pdfPreview').innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;background:var(--input-bg);border-radius:var(--radius);padding:10px 14px;border:1px solid var(--border);">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <div>
        <div style="font-size:12px;font-weight:600;color:var(--text);">${escHtml(file.name)}</div>
        <div style="font-size:11px;color:var(--text-muted);">${(file.size/1024/1024).toFixed(2)} MB</div>
      </div>
    </div>`;
  document.getElementById('pdfPreview').classList.add('show');
}

// Drag & drop for upload zones
['thumbZone','pdfZone'].forEach(zoneId => {
  const zone = document.getElementById(zoneId);
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
    const input = zone.querySelector('input[type="file"]');
    if (e.dataTransfer.files.length && input) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }
  });
});

/* ══════════════════════════════════════════════════════════
   FORM SUBMISSION (publish / draft)
   ════════════════════════════════════════════════════════ */
function submitForm(mode) {
  document.getElementById('isPublishedInput').value = mode === 'publish' ? '1' : '0';
  document.getElementById('editorForm').dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}));
}

document.getElementById('editorForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const saveDraftBtn = document.getElementById('saveDraftBtn');
  const publishBtn   = document.getElementById('publishBtn');
  const isPublish    = document.getElementById('isPublishedInput').value === '1';

  const activeBtn = isPublish ? publishBtn : saveDraftBtn;
  const origText  = activeBtn.innerHTML;
  activeBtn.disabled = true;
  activeBtn.innerHTML = `<span style="opacity:.6">${isPublish ? 'Publishing…' : 'Saving…'}</span>`;

  // Stop autosave during manual save
  clearInterval(autosaveTimer);

  const fd = new FormData(this);

  try {
    const res  = await fetch('<?= SITE_URL ?>/api/admin/leetcode.php', { method:'POST', body: fd });
    const json = await res.json();

    if (json.success) {
      Toast.success(isPublish ? '🚀 Published!' : '💾 Saved!', json.message || 'Solution saved.');
      // Update publish status UI
      if (isPublish) {
        document.getElementById('statusDot').style.background   = '#22c55e';
        document.getElementById('statusLabel').style.color      = '#22c55e';
        document.getElementById('statusLabel').textContent      = 'Published';
      }
      if (json.data?.id && !<?= $id ?>)  {
        setTimeout(() => { window.location.href = `/admin/leetcode-edit.php?id=${json.data.id}`; }, 900);
      } else {
        startAutosave();
      }
    } else {
      Toast.error('Error', json.error || 'Save failed.');
      startAutosave();
    }
  } catch (err) {
    Toast.error('Network Error', 'Could not reach the server. Check your connection.');
    startAutosave();
  } finally {
    activeBtn.disabled = false;
    activeBtn.innerHTML = origText;
  }
});

/* ══════════════════════════════════════════════════════════
   UNPUBLISH
   ════════════════════════════════════════════════════════ */
async function unpublish() {
  Modal.confirm('Remove this solution from public view? It will stay as a draft.', async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/leetcode.php', {
      action: 'toggle_publish', id: <?= $id ?>, value: 0
    });
    if (r.success) {
      Toast.success('Unpublished', 'Solution set to draft.');
      document.getElementById('statusDot').style.background = '#f59e0b';
      document.getElementById('statusLabel').style.color    = '#f59e0b';
      document.getElementById('statusLabel').textContent    = 'Draft';
      document.getElementById('isPublishedInput').value     = '0';
    } else Toast.error('Error', r.error);
  }, { title: 'Unpublish Solution', confirmText: 'Unpublish', type: 'warning' });
}

/* ══════════════════════════════════════════════════════════
   DELETE
   ════════════════════════════════════════════════════════ */
async function deleteSolution(id, title) {
  Modal.confirm(
    `Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">All code blocks, tags, and media will be removed.</small>`,
    async () => {
      const r = await apiPost('<?= SITE_URL ?>/api/admin/leetcode.php', { action: 'delete', id });
      if (r.success) {
        Toast.success('Deleted', 'Solution removed.');
        setTimeout(() => { window.location.href = '/admin/leetcode.php'; }, 900);
      } else Toast.error('Error', r.error);
    },
    { title: 'Delete Solution', confirmText: 'Delete', type: 'danger' }
  );
}

/* ══════════════════════════════════════════════════════════
   AUTOSAVE (edit mode only — every 45s)
   ════════════════════════════════════════════════════════ */
<?php if ($mode === 'edit'): ?>
let autosaveTimer;
const asStatus = document.getElementById('autosaveStatus');
const asText   = document.getElementById('autosaveText');

async function runAutosave() {
  if (document.hidden) return; // skip if tab hidden
  asStatus.className = 'autosave-dot saving';
  asText.textContent = 'Saving…';

  const fd = new FormData(document.getElementById('editorForm'));
  // Always save as draft in autosave (don't change publish status)
  fd.set('action', 'update');

  try {
    const res  = await fetch('<?= SITE_URL ?>/api/admin/leetcode.php', { method:'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      asStatus.className = 'autosave-dot saved';
      asText.textContent = 'Saved ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    } else {
      asStatus.className = 'autosave-dot error';
      asText.textContent = 'Save failed';
    }
  } catch {
    asStatus.className = 'autosave-dot error';
    asText.textContent = 'Offline?';
  }
  setTimeout(() => {
    if (asStatus.classList.contains('saved') || asStatus.classList.contains('error')) {
      asStatus.className = 'autosave-dot';
      asText.textContent = 'Ready';
    }
  }, 4000);
}

function startAutosave() {
  clearInterval(autosaveTimer);
  autosaveTimer = setInterval(runAutosave, 45000);
}
startAutosave();
<?php endif; ?>

/* ══════════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
   ════════════════════════════════════════════════════════ */
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    submitForm('draft');
  }
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
    e.preventDefault();
    submitForm('publish');
  }
});
</script>
</body>
</html>
