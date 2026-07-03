<?php
/**
 * CodeByTushu — Blog Post Editor v2
 * Full-featured: EasyMDE rich editor, tags, SEO preview, thumbnail drag-drop,
 * autosave, publish / draft / schedule, 5-tab layout, sidebar publish card.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';

$id   = (int)get('id', '0');
$mode = $id ? 'edit' : 'add';

$adminSection = 'Blogs';
$adminTitle   = ($mode === 'edit' ? 'Edit Post' : 'New Post') . ' — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard',        'url' => '/admin/'],
    ['label' => 'Blog Management',  'url' => '/admin/blogs.php'],
    ['label' => $mode === 'edit' ? 'Edit Post' : 'New Post'],
];

$pdo  = db();
$user = Auth::user();

/* ── Load post ─────────────────────────────────────────── */
$blog    = [];
$blogTags = [];

if ($mode === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM blog_articles WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $blog = $stmt->fetch();
    if (!$blog) { setFlash('error', 'Post not found.'); header('Location: /admin/blogs.php'); exit; }

    // Load tags if table exists
    try {
        $ts = $pdo->prepare('SELECT tag_id FROM blog_tag_map WHERE article_id = ?');
        $ts->execute([$id]);
        $blogTags = array_column($ts->fetchAll(), 'tag_id');
    } catch (\Throwable) { $blogTags = []; }
}

/* ── Reference data ─────────────────────────────────────── */
$categories = $pdo->query("SELECT id, name FROM categories WHERE type='blog' ORDER BY name")->fetchAll();

// Blog tags
$allBlogTags = [];
try {
    $allBlogTags = $pdo->query("SELECT id, name, color_hex FROM blog_tags WHERE is_active=1 ORDER BY name")->fetchAll();
} catch (\Throwable) { $allBlogTags = []; }

/* ── Helper ────────────────────────────────────────────── */
function bv(array $b, string $k, string $def=''): string {
    return htmlspecialchars((string)($b[$k] ?? $def), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}

/* ── Word count / read time estimate ───────────────────── */
$wordCount = $blog ? str_word_count(strip_tags($blog['content'] ?? '')) : 0;
$readEst   = max(1, (int)round($wordCount / 200));
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<!-- No external editor CDN needed — using built-in textarea editor -->
<style>
/* ── Layout ── */
.blog-layout { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
.blog-main   { min-width:0; }
.blog-sidebar { position:sticky; top:20px; }

/* ── Tabs ── */
.ed-tabs { display:flex; border-bottom:1px solid var(--border); overflow-x:auto; scrollbar-width:none; }
.ed-tabs::-webkit-scrollbar { display:none; }
.ed-tab  { padding:12px 18px; background:none; border:none; border-bottom:2px solid transparent;
           cursor:pointer; color:var(--text-muted); font-family:var(--font); font-size:13px;
           font-weight:500; margin-bottom:-1px; transition:all .2s; display:flex; align-items:center;
           gap:6px; white-space:nowrap; flex-shrink:0; }
.ed-tab:hover  { color:var(--text); background:var(--bg-hover); }
.ed-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.tab-pane { display:none; padding:22px; }
.tab-pane.active { display:block; }

/* ── Custom Markdown Editor ── */
.md-editor-wrap {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--input-bg);
}
.md-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  padding: 8px 10px;
  background: var(--bg-hover);
  border-bottom: 1px solid var(--border);
}
.md-toolbar button {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 5px 8px;
  border-radius: 4px;
  font-size: 13px;
  font-family: var(--font);
  font-weight: 600;
  line-height: 1;
  transition: all 0.15s;
}
.md-toolbar button:hover {
  background: var(--accent-glow);
  color: var(--accent);
}
.md-toolbar .md-sep {
  width: 1px;
  background: var(--border);
  margin: 2px 4px;
  align-self: stretch;
}
#blogContent {
  width: 100%;
  min-height: 400px;
  background: var(--input-bg) !important;
  color: var(--text) !important;
  border: none !important;
  outline: none !important;
  font-family: 'Fira Code', ui-monospace, monospace !important;
  font-size: 14px !important;
  line-height: 1.8 !important;
  padding: 16px !important;
  resize: vertical;
  display: block;
  box-sizing: border-box;
}
.md-status-bar {
  display: flex;
  justify-content: space-between;
  padding: 6px 12px;
  background: var(--bg-hover);
  border-top: 1px solid var(--border);
  font-size: 11px;
  color: var(--text-dim);
}

/* ── Thumbnail ── */
.thumb-zone { border:2px dashed var(--border); border-radius:var(--radius); aspect-ratio:16/9;
              display:flex; flex-direction:column; align-items:center; justify-content:center;
              cursor:pointer; transition:all .2s; background:var(--input-bg); position:relative;
              overflow:hidden; }
.thumb-zone:hover, .thumb-zone.drag-over { border-color:var(--accent); background:var(--accent-glow); }
.thumb-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; }
.thumb-zone img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; }
.thumb-zone img.show { display:block; }
.thumb-zone .thumb-overlay { position:absolute; inset:0; background:rgba(0,0,0,.6);
                              display:flex; align-items:center; justify-content:center;
                              opacity:0; transition:opacity .2s; }
.thumb-zone:hover .thumb-overlay { opacity:1; }
.thumb-placeholder { display:flex; flex-direction:column; align-items:center; gap:8px; pointer-events:none; }

/* ── Tag chips ── */
.tag-grid { display:flex; flex-wrap:wrap; gap:8px; }
.tag-chip-lbl { display:inline-flex; align-items:center; gap:6px; padding:5px 12px;
                background:var(--input-bg); border:1.5px solid var(--border);
                border-radius:99px; cursor:pointer; font-size:12px; font-weight:500; transition:all .2s; }
.tag-chip-lbl input { display:none; }
.tag-chip-lbl:has(input:checked) { border-color:var(--accent); background:var(--accent-glow); color:var(--accent); }
.tag-chip-lbl:hover { border-color:var(--border-mid); }

/* ── SEO preview ── */
.serp-box { background:var(--input-bg); border:1px solid var(--border); border-radius:var(--radius);
            padding:16px; margin-top:10px; }
.serp-url   { font-size:12px; color:#22c55e; font-family:monospace; margin-bottom:3px; }
.serp-title { font-size:17px; color:#93c5fd; font-weight:500; margin-bottom:5px; line-height:1.3; }
.serp-desc  { font-size:13px; color:var(--text-muted); line-height:1.5; }

/* ── Sidebar cards ── */
.sb { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
      padding:18px; margin-bottom:14px; }
.sb-hd { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
         color:var(--text-muted); margin-bottom:14px; display:flex; align-items:center; gap:6px; }
.pub-status-row { display:flex; align-items:center; justify-content:space-between;
                   padding:8px 0; border-bottom:1px solid var(--border); margin-bottom:12px; font-size:12px; color:var(--text-muted); }
.status-dot { width:8px; height:8px; border-radius:50%; }

/* ── Autosave ── */
.as-dot { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-dim); }
.as-dot.saving { color:var(--accent); } .as-dot.saved { color:#22c55e; } .as-dot.error { color:#ef4444; }
.as-pulse { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }
.as-dot.saving .as-pulse { animation:pr 1s infinite; }
@keyframes pr { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.4} }

/* ── Char counter ── */
.cc { font-size:10px; color:var(--text-dim); text-align:right; margin-top:3px; }
@media(max-width:1024px) { .blog-layout { grid-template-columns:1fr; } .blog-sidebar { position:static; } }
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
          <h1 class="page-title"><?= $mode === 'edit' ? 'Edit Blog Post' : 'New Blog Post' ?></h1>
          <?php if ($mode === 'edit'): ?>
          <p class="page-subtitle" style="display:flex;align-items:center;gap:10px;">
            <?= e(truncate($blog['title'], 60)) ?>
            &nbsp;·&nbsp;
            <span class="as-dot" id="asDot">
              <span class="as-pulse"></span>
              <span id="asText">Ready</span>
            </span>
          </p>
          <?php endif; ?>
        </div>
        <div class="page-header-actions">
          <a href="<?= SITE_URL ?>/admin/blogs.php" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back
          </a>
          <?php if ($mode === 'edit' && !empty($blog['is_published'])): ?>
          <a href="/blog/<?= e($blog['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            View Live
          </a>
          <?php endif; ?>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- ══ Form ══════════════════════════════════════════ -->
      <form id="blogForm" method="post" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action"       id="formAction" value="<?= $mode==='edit'?'update':'create' ?>">
        <input type="hidden" name="is_published" id="isPub"      value="<?= !empty($blog['is_published'])?'1':'0' ?>">
        <?php if ($mode==='edit'): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="blog-layout">

          <!-- ══ MAIN ════════════════════════════════════ -->
          <div class="blog-main">

            <!-- Title + Slug always visible -->
            <div class="card" style="padding:22px;margin-bottom:16px;">
              <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Post Title <span class="req">*</span></label>
                <input type="text" name="title" id="blogTitle" class="form-control"
                       value="<?= bv($blog,'title') ?>" placeholder="Your blog post title…" required
                       style="font-size:18px;font-weight:600;padding:14px 16px;" oninput="onTitleInput()">
              </div>
              <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                  <label class="form-label">URL Slug <span class="req">*</span></label>
                  <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;flex-shrink:0;">/blog/</span>
                    <input type="text" name="slug" id="blogSlug" class="form-control"
                           value="<?= bv($blog,'slug') ?>" placeholder="url-friendly-slug"
                           required pattern="[a-z0-9-]+" oninput="this.dataset.manual='1';updateSeo();">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Read Time (mins)</label>
                  <input type="number" name="read_time_mins" id="readTime" class="form-control"
                         min="1" max="120" value="<?= bv($blog,'read_time_mins') ?>"
                         placeholder="<?= $readEst ?>">
                  <div class="form-hint" id="wordCountHint" style="font-size:10px;color:var(--text-dim);margin-top:3px;">
                    <?= $wordCount ? "$wordCount words · ~{$readEst} min read" : 'Estimated from content' ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5-tab card -->
            <div class="card" style="padding:0;overflow:visible;margin-bottom:16px;">
              <div class="ed-tabs">
                <button type="button" class="ed-tab active" data-tab="content">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  Content
                </button>
                <button type="button" class="ed-tab" data-tab="excerpt">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/></svg>
                  Excerpt
                </button>
                <button type="button" class="ed-tab" data-tab="tags">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                  Tags
                  <?php if (!empty($blogTags)): ?><span style="background:var(--accent);color:#000;font-size:9px;font-weight:700;border-radius:99px;padding:1px 5px;"><?= count($blogTags) ?></span><?php endif; ?>
                </button>
                <button type="button" class="ed-tab" data-tab="seo">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  SEO
                </button>
                <button type="button" class="ed-tab" data-tab="advanced">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41M21 12h-2M5 12H3M12 19v2M12 5V3"/></svg>
                  Advanced
                </button>
              </div>

              <!-- ── Tab: Content ── -->
              <div class="tab-pane active" id="tab-content">
                <div class="form-group">
                  <label class="form-label" style="margin-bottom:10px;">Post Content <span class="req">*</span></label>
                  <div class="md-editor-wrap">
                    <div class="md-toolbar">
                      <button type="button" onclick="mdWrap('**','**')" title="Bold"><b>B</b></button>
                      <button type="button" onclick="mdWrap('*','*')" title="Italic"><i>I</i></button>
                      <button type="button" onclick="mdWrap('~~','~~')" title="Strikethrough"><s>S</s></button>
                      <div class="md-sep"></div>
                      <button type="button" onclick="mdLine('# ')" title="H1">H1</button>
                      <button type="button" onclick="mdLine('## ')" title="H2">H2</button>
                      <button type="button" onclick="mdLine('### ')" title="H3">H3</button>
                      <div class="md-sep"></div>
                      <button type="button" onclick="mdLine('- ')" title="Bullet List">• List</button>
                      <button type="button" onclick="mdLine('1. ')" title="Numbered List">1. List</button>
                      <button type="button" onclick="mdLine('> ')" title="Blockquote">&ldquo; Quote</button>
                      <div class="md-sep"></div>
                      <button type="button" onclick="mdInsertCode()" title="Code Block">&lt;/&gt; Code</button>
                      <button type="button" onclick="mdInsertLink()" title="Link">🔗 Link</button>
                      <button type="button" onclick="mdInsertImage()" title="Image">🖼 Image</button>
                      <div class="md-sep"></div>
                      <button type="button" onclick="mdLine('---')" title="Horizontal Rule">— HR</button>
                    </div>
                    <textarea name="content" id="blogContent" required placeholder="Write your blog post in Markdown..."><?= htmlspecialchars($blog['content'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE) ?></textarea>
                    <div class="md-status-bar">
                      <span id="wordCountHint2"><?= $wordCount ? "{$wordCount} words · ~{$readEst} min read" : '0 words' ?></span>
                      <span>Markdown supported</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ── Tab: Excerpt ── -->
              <div class="tab-pane" id="tab-excerpt">
                <div class="form-group">
                  <label class="form-label">Excerpt / Summary <span class="req">*</span></label>
                  <textarea name="excerpt" id="excerptArea" class="form-control" rows="5" required
                            placeholder="A brief summary shown in blog listing cards and search snippets…"
                            oninput="updateExcerptCount()"><?= bv($blog,'excerpt') ?></textarea>
                  <div style="display:flex;justify-content:space-between;margin-top:4px;">
                    <div class="form-hint">Shown in blog cards, social shares, and SEO snippets.</div>
                    <span class="cc" id="exCount"><?= strlen($blog['excerpt']??'') ?>/500</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Card Title <span style="font-size:11px;color:var(--text-dim);font-weight:400;text-transform:none;">(short version for card grids)</span></label>
                  <input type="text" name="card_title" class="form-control"
                         value="<?= bv($blog,'card_title') ?>" placeholder="Short card title (optional)" maxlength="100">
                </div>
                <div class="form-group">
                  <label class="form-label">Post Icon (emoji)</label>
                  <input type="text" name="icon_name" class="form-control" maxlength="10"
                         value="<?= bv($blog,'icon_name','📝') ?>" placeholder="📝">
                  <div class="form-hint">Displayed on blog listing cards.</div>
                </div>
              </div>

              <!-- ── Tab: Tags ── -->
              <div class="tab-pane" id="tab-tags">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
                  <div>
                    <div style="font-weight:600;color:var(--text);margin-bottom:3px;">Blog Tags</div>
                    <div style="font-size:12px;color:var(--text-muted);">Select topic tags for this post.</div>
                  </div>
                  <span id="tagCount" style="font-size:12px;color:var(--accent);font-weight:700;padding:4px 10px;background:var(--accent-glow);border-radius:99px;">
                    <?= count($blogTags) ?> selected
                  </span>
                </div>
                <input type="text" class="form-control" id="tagSearch" placeholder="Filter tags…" style="margin-bottom:14px;">
                <div class="tag-grid" id="tagGrid">
                  <?php if (!empty($allBlogTags)): ?>
                    <?php foreach ($allBlogTags as $t): ?>
                    <label class="tag-chip-lbl" data-tname="<?= strtolower(e($t['name'])) ?>">
                      <input type="checkbox" name="tags[]" value="<?= $t['id'] ?>"
                             <?= in_array($t['id'], $blogTags)?'checked':'' ?>
                             onchange="updateTagCount()">
                      <?php if ($t['color_hex']): ?>
                        <span style="width:7px;height:7px;border-radius:50%;background:<?= e($t['color_hex']) ?>;flex-shrink:0;"></span>
                      <?php endif; ?>
                      <?= e($t['name']) ?>
                    </label>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">
                      No blog tags yet.
                      <a href="<?= SITE_URL ?>/admin/categories.php" style="color:var(--accent);">Create tags →</a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- ── Tab: SEO ── -->
              <div class="tab-pane" id="tab-seo">
                <div class="form-group">
                  <label class="form-label">SEO / Meta Title</label>
                  <input type="text" name="seo_title" id="seoTitle" class="form-control" maxlength="70"
                         value="<?= bv($blog,'seo_title') ?>"
                         placeholder="Custom title for Google (leave blank to use post title)"
                         oninput="updateSeo()">
                  <div style="display:flex;justify-content:space-between;margin-top:3px;">
                    <div class="form-hint">Max 70 chars. Best: 50–60.</div>
                    <span class="cc" id="seoTc">0/70</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Meta Description</label>
                  <textarea name="meta_description" id="metaDesc" class="form-control" rows="3"
                            maxlength="160" placeholder="Search engine description…"
                            oninput="updateSeo()"><?= bv($blog,'meta_description') ?></textarea>
                  <div style="display:flex;justify-content:space-between;margin-top:3px;">
                    <div class="form-hint">Max 160 chars. Shown in Google search results.</div>
                    <span class="cc" id="metaTc">0/160</span>
                  </div>
                </div>
                <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                  <div class="form-group">
                    <label class="form-label">Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-control"
                           value="<?= bv($blog,'meta_keywords') ?>" placeholder="php, mysql, tutorial">
                    <div class="form-hint">Comma-separated.</div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">OG Image URL</label>
                    <input type="url" name="og_image_url" class="form-control"
                           value="<?= bv($blog,'og_image_url') ?>" placeholder="https://… (1200×630px)"
                           oninput="updateSeo()">
                  </div>
                </div>
                <!-- SERP preview -->
                <div style="margin-top:18px;">
                  <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                    Google Search Preview
                  </div>
                  <div class="serp-box">
                    <div class="serp-url" id="spUrl">codebytushu.com › blog › <?= bv($blog,'slug','your-post-slug') ?></div>
                    <div class="serp-title" id="spTitle"><?= bv($blog,'seo_title') ?: bv($blog,'title') ?: 'Post Title — CodeByTushu' ?></div>
                    <div class="serp-desc"  id="spDesc"><?= bv($blog,'meta_description') ?: 'No meta description yet.' ?></div>
                  </div>
                </div>
              </div>

              <!-- ── Tab: Advanced ── -->
              <div class="tab-pane" id="tab-advanced">
                <div class="form-group">
                  <label class="form-label">Cover Image Path</label>
                  <input type="text" name="cover_image_path" class="form-control"
                         value="<?= bv($blog,'cover_image_path') ?>" placeholder="/uploads/blogs/covers/…">
                  <div class="form-hint">Full-width banner image path (different from thumbnail card).</div>
                </div>
                <?php if ($mode === 'edit'): ?>
                <div style="background:var(--input-bg);border-radius:var(--radius);padding:14px;font-size:12px;color:var(--text-muted);">
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div><span style="color:var(--text-dim);">Post ID</span><br><strong style="color:var(--text);">#<?= $id ?></strong></div>
                    <div><span style="color:var(--text-dim);">Views</span><br><strong style="color:var(--text);"><?= number_format($blog['view_count']??0) ?></strong></div>
                    <div><span style="color:var(--text-dim);">Created</span><br><strong style="color:var(--text);"><?= e(formatDate($blog['created_at']??'','d M Y')) ?></strong></div>
                    <div><span style="color:var(--text-dim);">Last Updated</span><br><strong style="color:var(--text);"><?= e(formatDate($blog['updated_at']??'','d M Y')) ?></strong></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div><!-- /card tabs -->

          </div><!-- /blog-main -->

          <!-- ══ SIDEBAR ══════════════════════════════════ -->
          <div class="blog-sidebar">

            <!-- Publish card -->
            <div class="sb">
              <div class="sb-hd">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Publish
              </div>
              <div class="pub-status-row">
                <span>Status</span>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div class="status-dot" id="statusDot"
                       style="background:<?= !empty($blog['is_published'])?'#22c55e':'#f59e0b' ?>;"></div>
                  <span id="statusLbl" style="font-weight:600;font-size:12px;color:<?= !empty($blog['is_published'])?'#22c55e':'#f59e0b' ?>;">
                    <?= !empty($blog['is_published']) ? 'Published' : 'Draft' ?>
                  </span>
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;color:var(--text-muted);">
                  <input type="checkbox" name="is_featured" value="1" id="featCb"
                         <?= !empty($blog['is_featured'])?'checked':'' ?>
                         style="accent-color:var(--accent);">
                  ⭐ Featured Post
                </label>
              </div>
              <div style="display:flex;flex-direction:column;gap:8px;">
                <button type="button" class="btn btn-ghost btn-sm" id="draftBtn" onclick="submit('draft')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                  Save Draft
                </button>
                <button type="button" class="btn btn-primary" id="pubBtn" onclick="submit('publish')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <?= !empty($blog['is_published']) ? 'Update & Publish' : 'Publish Now' ?>
                </button>
                <?php if (!empty($blog['is_published'])): ?>
                <button type="button" class="btn btn-warning btn-sm" id="unpubBtn" onclick="unpublish()">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 16 12 21 17 16"/></svg>
                  Unpublish
                </button>
                <?php endif; ?>
              </div>
              <?php if (!empty($blog['published_at'])): ?>
              <div style="margin-top:12px;font-size:11px;color:var(--text-dim);">
                Published: <?= e(formatDate($blog['published_at'], 'd M Y, g:i A')) ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Thumbnail card -->
            <div class="sb">
              <div class="sb-hd">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Thumbnail
              </div>
              <div class="thumb-zone" id="thumbZone" onclick="document.getElementById('thumbInput').click()">
                <input type="file" name="thumbnail" id="thumbInput" accept="image/*" onchange="onThumb(this)">
                <img id="thumbImg" src="<?= bv($blog,'thumbnail_path') ?>" alt=""
                     class="<?= !empty($blog['thumbnail_path'])?'show':'' ?>">
                <div class="thumb-overlay">
                  <span style="color:#fff;font-size:12px;font-weight:600;">Change Image</span>
                </div>
                <div class="thumb-placeholder" id="thumbPh"
                     style="<?= !empty($blog['thumbnail_path'])?'display:none':'' ?>">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  <span style="font-size:11px;color:var(--text-muted);text-align:center;">Click or drag to upload<br><span style="color:var(--text-dim);font-size:10px;">JPG, PNG, WebP · max 5MB</span></span>
                </div>
              </div>
              <?php if (!empty($blog['thumbnail_path'])): ?>
              <button type="button" class="btn btn-ghost btn-sm" style="width:100%;margin-top:8px;"
                      onclick="clearThumb()">Remove Thumbnail</button>
              <?php endif; ?>
            </div>

            <!-- Category + Author -->
            <div class="sb">
              <div class="sb-hd">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Details
              </div>
              <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label" style="font-size:11px;">Category</label>
                <select name="category_id" class="form-control" style="font-size:12px;">
                  <option value="">— Uncategorized —</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($blog['category_id']??'')==$c['id']?'selected':'' ?>>
                      <?= e($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size:11px;">Author</label>
                <div style="font-size:12px;color:var(--text-muted);padding:8px 12px;background:var(--input-bg);border-radius:var(--radius-sm);border:1px solid var(--border);">
                  <?= e($user['full_name'] ?? $user['username'] ?? 'You') ?>
                </div>
                <input type="hidden" name="author_id" value="<?= Auth::id() ?>">
              </div>
            </div>

            <!-- Danger Zone (edit only) -->
            <?php if ($mode === 'edit'): ?>
            <div class="sb" style="border-color:rgba(239,68,68,.3);">
              <div class="sb-hd" style="color:#ef4444;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Danger Zone
              </div>
              <button type="button" class="btn btn-danger btn-sm" style="width:100%;"
                      onclick="doDelete(<?= $id ?>, '<?= bv($blog,'title') ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                Delete This Post
              </button>
            </div>
            <?php endif; ?>

          </div><!-- /sidebar -->
        </div><!-- /blog-layout -->
      </form><!-- /#blogForm -->

    </main>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
/* ══════════════════════════════════════════════════════════
   CUSTOM MARKDOWN EDITOR TOOLBAR HELPERS
   ════════════════════════════════════════════════════════ */
const blogContent = document.getElementById('blogContent');

function mdWrap(before, after) {
  const ta = blogContent;
  const s = ta.selectionStart, e = ta.selectionEnd;
  const sel = ta.value.substring(s, e) || 'text';
  const newText = before + sel + after;
  ta.setRangeText(newText, s, e, 'select');
  ta.focus();
  updateWordCount();
}

function mdLine(prefix) {
  const ta = blogContent;
  const s = ta.selectionStart;
  const lineStart = ta.value.lastIndexOf('\n', s - 1) + 1;
  ta.setRangeText(prefix, lineStart, lineStart, 'end');
  ta.focus();
  updateWordCount();
}

function mdInsertCode() {
  const ta = blogContent;
  const s = ta.selectionStart, e = ta.selectionEnd;
  const sel = ta.value.substring(s, e) || 'code here';
  const snippet = '```\n' + sel + '\n```';
  ta.setRangeText(snippet, s, e, 'end');
  ta.focus();
  updateWordCount();
}

function mdInsertLink() {
  const url = prompt('Enter URL:');
  if (!url) return;
  const ta = blogContent;
  const s = ta.selectionStart, e = ta.selectionEnd;
  const label = ta.value.substring(s, e) || 'link text';
  ta.setRangeText('[' + label + '](' + url + ')', s, e, 'end');
  ta.focus();
}

function mdInsertImage() {
  const url = prompt('Enter image URL:');
  if (!url) return;
  const alt = prompt('Alt text:') || 'image';
  const ta = blogContent;
  const s = ta.selectionStart;
  ta.setRangeText('![' + alt + '](' + url + ')', s, s, 'end');
  ta.focus();
}

function countWords(str) { return str.trim() ? str.trim().split(/\s+/).length : 0; }

function updateWordCount() {
  const wc = countWords(blogContent.value);
  const rt  = Math.max(1, Math.round(wc / 200));
  const hint = document.getElementById('wordCountHint');
  if (hint) hint.textContent = `${wc} words · ~${rt} min read`;
  const hint2 = document.getElementById('wordCountHint2');
  if (hint2) hint2.textContent = `${wc} words · ~${rt} min read`;
  const rtEl = document.getElementById('readTime');
  if (rtEl && !rtEl.dataset.manual && !rtEl.value) rtEl.placeholder = rt;
}

blogContent.addEventListener('input', updateWordCount);
blogContent.addEventListener('keydown', function(e) {
  // Tab key inserts spaces instead of losing focus
  if (e.key === 'Tab') {
    e.preventDefault();
    const s = this.selectionStart, end = this.selectionEnd;
    this.setRangeText('  ', s, end, 'end');
  }
});

/* ══════════════════════════════════════════════════════════
   TABS
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
   SLUG + SEO UPDATE
   ════════════════════════════════════════════════════════ */
function onTitleInput() {
  const sl = document.getElementById('blogSlug');
  if (!sl.dataset.manual) {
    sl.value = document.getElementById('blogTitle').value
      .toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
  }
  updateSeo();
}

function updateSeo() {
  const title = document.getElementById('seoTitle').value  || document.getElementById('blogTitle').value || 'Post Title';
  const slug  = document.getElementById('blogSlug').value  || 'your-slug';
  const desc  = document.getElementById('metaDesc').value  || 'No meta description yet.';
  document.getElementById('spTitle').textContent = title + ' — CodeByTushu';
  document.getElementById('spUrl').textContent   = `codebytushu.com › blog › ${slug}`;
  document.getElementById('spDesc').textContent  = desc;
  const stl = document.getElementById('seoTitle');
  const mdl = document.getElementById('metaDesc');
  document.getElementById('seoTc').textContent  = `${stl.value.length}/70`;
  document.getElementById('metaTc').textContent = `${mdl.value.length}/160`;
  document.getElementById('seoTc').style.color  = stl.value.length > 70  ? '#ef4444' : 'var(--text-dim)';
  document.getElementById('metaTc').style.color = mdl.value.length > 160 ? '#ef4444' : 'var(--text-dim)';
}
updateSeo();

function updateExcerptCount() {
  const n = document.getElementById('excerptArea').value.length;
  document.getElementById('exCount').textContent = `${n}/500`;
  document.getElementById('exCount').style.color = n > 500 ? '#ef4444' : 'var(--text-dim)';
}
updateExcerptCount();

/* ══════════════════════════════════════════════════════════
   TAGS
   ════════════════════════════════════════════════════════ */
function updateTagCount() {
  const n = document.querySelectorAll('#tagGrid input:checked').length;
  document.getElementById('tagCount').textContent = `${n} selected`;
}
document.getElementById('tagSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.tag-chip-lbl').forEach(c => {
    c.style.display = c.dataset.tname.includes(q) ? '' : 'none';
  });
});

/* ══════════════════════════════════════════════════════════
   THUMBNAIL
   ════════════════════════════════════════════════════════ */
function onThumb(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 5*1024*1024) { Toast.error('Too large', 'Thumbnail must be under 5MB.'); input.value=''; return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('thumbImg').src = e.target.result;
    document.getElementById('thumbImg').classList.add('show');
    document.getElementById('thumbPh').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function clearThumb() {
  document.getElementById('thumbImg').src = '';
  document.getElementById('thumbImg').classList.remove('show');
  document.getElementById('thumbPh').style.display = 'flex';
  document.getElementById('thumbInput').value = '';
}

const tz = document.getElementById('thumbZone');
tz.addEventListener('dragover', e => { e.preventDefault(); tz.classList.add('drag-over'); });
tz.addEventListener('dragleave', () => tz.classList.remove('drag-over'));
tz.addEventListener('drop', e => {
  e.preventDefault(); tz.classList.remove('drag-over');
  const f = e.dataTransfer.files[0];
  if (!f || !f.type.startsWith('image/')) return;
  const dt = new DataTransfer(); dt.items.add(f);
  const inp = document.getElementById('thumbInput');
  inp.files = dt.files;
  onThumb(inp);
});

/* ══════════════════════════════════════════════════════════
   FORM SUBMIT
   ════════════════════════════════════════════════════════ */
let asTimer; // global — safe in both add + edit mode

async function handleBlogSubmit(isPub) {
  const actBtn  = isPub ? document.getElementById('pubBtn') : document.getElementById('draftBtn');
  const origTxt = actBtn.innerHTML;
  actBtn.disabled = true;
  actBtn.innerHTML = `<span style="opacity:.6">${isPub ? 'Publishing…' : 'Saving…'}</span>`;

  clearInterval(asTimer);

  const fd = new FormData(document.getElementById('blogForm'));
  fd.set('is_published', isPub ? '1' : '0');
  fd.set('action', document.getElementById('formAction').value);

  try {
    const res  = await fetch('<?= SITE_URL ?>/api/admin/blogs.php', { method:'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      Toast.success(isPub ? '🚀 Published!' : '💾 Saved!', json.message || 'Post saved.');
      if (isPub) {
        document.getElementById('statusDot').style.background = '#22c55e';
        document.getElementById('statusLbl').style.color      = '#22c55e';
        document.getElementById('statusLbl').textContent      = 'Published';
      }
      if (json.data?.id && !<?= $id ?>) {
        setTimeout(() => { window.location.href = `/admin/blog-edit.php?id=${json.data.id}`; }, 900);
      } else startAutosave();
    } else {
      Toast.error('Error', json.error || 'Save failed.');
      startAutosave();
    }
  } catch (err) {
    console.error('Submit error:', err);
    Toast.error('Network Error', 'Could not reach the server.');
    startAutosave();
  } finally {
    actBtn.disabled = false;
    actBtn.innerHTML = origTxt;
  }
}

function submit(mode) {
  document.getElementById('isPub').value = mode === 'publish' ? '1' : '0';
  handleBlogSubmit(mode === 'publish');
}

document.getElementById('blogForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const isPub = document.getElementById('isPub').value === '1';
  handleBlogSubmit(isPub);
});

/* ══════════════════════════════════════════════════════════
   UNPUBLISH
   ════════════════════════════════════════════════════════ */
async function unpublish() {
  Modal.confirm('Remove this post from public view?', async () => {
    const r = await apiPost('<?= SITE_URL ?>/api/admin/blogs.php', { action:'toggle_publish', id: <?= $id ?>, value: 0 });
    if (r.success) {
      Toast.success('Unpublished', 'Post set to draft.');
      document.getElementById('statusDot').style.background = '#f59e0b';
      document.getElementById('statusLbl').style.color      = '#f59e0b';
      document.getElementById('statusLbl').textContent      = 'Draft';
      document.getElementById('isPub').value                = '0';
    } else Toast.error('Error', r.error);
  }, { title:'Unpublish Post', confirmText:'Unpublish', type:'warning' });
}

/* ══════════════════════════════════════════════════════════
   DELETE
   ════════════════════════════════════════════════════════ */
async function doDelete(id, title) {
  Modal.confirm(
    `Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">This cannot be undone.</small>`,
    async () => {
      const r = await apiPost('<?= SITE_URL ?>/api/admin/blogs.php', { action:'delete', id });
      if (r.success) { Toast.success('Deleted'); setTimeout(() => { window.location.href='/admin/blogs.php'; }, 900); }
      else Toast.error('Error', r.error);
    },
    { title:'Delete Blog Post', confirmText:'Delete', type:'danger' }
  );
}

/* ══════════════════════════════════════════════════════════
   AUTOSAVE (edit mode only — every 60s)
   ════════════════════════════════════════════════════════ */
<?php if ($mode === 'edit'): ?>
const asDot = document.getElementById('asDot');
const asTxt = document.getElementById('asText');

async function doAutosave() {
  if (document.hidden) return;
  asDot.className = 'as-dot saving'; asTxt.textContent = 'Saving…';
  const fd = new FormData(document.getElementById('blogForm'));
  fd.set('action', 'update');
  try {
    const r = await fetch('<?= SITE_URL ?>/api/admin/blogs.php', { method:'POST', body: fd });
    const j = await r.json();
    if (j.success) { asDot.className='as-dot saved'; asTxt.textContent='Saved '+new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}); }
    else           { asDot.className='as-dot error'; asTxt.textContent='Save failed'; }
  } catch { asDot.className='as-dot error'; asTxt.textContent='Offline?'; }
  setTimeout(() => { asDot.className='as-dot'; asTxt.textContent='Ready'; }, 4000);
}

function startAutosave() { clearInterval(asTimer); asTimer = setInterval(doAutosave, 60000); }
startAutosave();
<?php endif; ?>

/* ══════════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
   ════════════════════════════════════════════════════════ */
document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && e.key === 's') { e.preventDefault(); submit('draft'); }
  if ((e.ctrlKey||e.metaKey) && e.shiftKey && e.key === 'P') { e.preventDefault(); submit('publish'); }
});
</script>
</body>
</html>
