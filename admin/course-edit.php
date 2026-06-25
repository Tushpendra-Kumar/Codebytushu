<?php
/**
 * CodeByTushu — Course Editor v2
 * 6-tab layout: Overview, Description, Media, Curriculum, Pricing, SEO
 * Full chapter + lesson CRUD with AJAX, drag-drop reorder, file uploads.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';

$id   = (int)get('id', '0');
$mode = $id ? 'edit' : 'add';
$tab  = get('tab', 'overview');

$adminSection = 'Courses';
$adminTitle   = ($mode==='edit'?'Edit Course':'New Course').' — CodeByTushu Admin';
$breadcrumbs  = [
    ['label'=>'Dashboard',  'url'=>'/admin/'],
    ['label'=>'Courses',    'url'=>'/admin/courses.php'],
    ['label'=>$mode==='edit'?'Edit Course':'New Course'],
];

$pdo  = db();
$user = Auth::user();

/* ── Load course ────────────────────────────────────────── */
$course   = [];
$chapters = [];

if ($mode === 'edit') {
    $s = $pdo->prepare('SELECT * FROM courses WHERE id=? LIMIT 1');
    $s->execute([$id]); $course = $s->fetch();
    if (!$course) { setFlash('error','Course not found.'); header('Location: /admin/courses.php'); exit; }

    // Load chapters with lesson counts
    $cs = $pdo->prepare(
        "SELECT ch.*, COUNT(cl.id) AS lesson_count,
                COALESCE(SUM(cl.duration_seconds),0) AS total_secs
           FROM course_chapters ch
           LEFT JOIN course_lessons cl ON cl.chapter_id=ch.id AND cl.is_active=1
          WHERE ch.course_id=?
          GROUP BY ch.id ORDER BY ch.sort_order ASC"
    );
    $cs->execute([$id]); $chapters = $cs->fetchAll();
}

$allCats = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

function cv(array $c, string $k, string $d=''): string {
    return htmlspecialchars((string)($c[$k]??$d), ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
function secs2dur(int $s): string {
    if(!$s) return '—';
    $h=intdiv($s,3600); $m=intdiv($s%3600,60);
    return $h?"${h}h ${m}m":"${m}m";
}
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
/* ── Two-column layout ── */
.ce-layout { display:grid; grid-template-columns:1fr 290px; gap:20px; align-items:start; }
.ce-main   { min-width:0; }
.ce-sb     { position:sticky; top:20px; }

/* ── Tabs ── */
.ce-tabs { display:flex; border-bottom:1px solid var(--border); overflow-x:auto; scrollbar-width:none; }
.ce-tabs::-webkit-scrollbar { display:none; }
.ce-tab  { padding:12px 16px; background:none; border:none; border-bottom:2px solid transparent;
           cursor:pointer; color:var(--text-muted); font-family:var(--font); font-size:13px;
           font-weight:500; margin-bottom:-1px; transition:all .2s; white-space:nowrap; flex-shrink:0;
           display:flex; align-items:center; gap:6px; }
.ce-tab:hover  { color:var(--text); background:var(--bg-hover); }
.ce-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.tab-pane { display:none; padding:22px; }
.tab-pane.active { display:block; }

/* ── Upload zones ── */
.upload-zone { border:2px dashed var(--border); border-radius:var(--radius); padding:20px;
               text-align:center; cursor:pointer; transition:all .2s; background:var(--input-bg);
               position:relative; }
.upload-zone:hover,.upload-zone.drag-over { border-color:var(--accent); background:var(--accent-glow); }
.upload-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.uz-lbl { font-size:13px; font-weight:600; color:var(--text-muted); margin-top:8px; }
.uz-hint { font-size:11px; color:var(--text-dim); margin-top:4px; }

/* ── Curriculum ── */
.chapter-item { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius);
                margin-bottom:10px; overflow:hidden; }
.chapter-hd  { display:flex; align-items:center; gap:10px; padding:12px 16px;
               background:var(--bg-hover); cursor:pointer; user-select:none; }
.chapter-hd:hover { background:var(--border); }
.chapter-drag { color:var(--text-dim); font-size:14px; cursor:grab; padding:2px 4px; flex-shrink:0; }
.chapter-title-text { font-weight:600; font-size:13px; flex:1; }
.chapter-meta { font-size:11px; color:var(--text-muted); display:flex; gap:10px; }
.chapter-body { padding:14px; border-top:1px solid var(--border); }
.ch-collapse { display:none; } .ch-collapse.open { display:block; }
.chapter-actions { display:flex; gap:6px; }

/* ── Lessons ── */
.lesson-item { display:flex; align-items:center; gap:8px; padding:10px 12px;
               background:var(--input-bg); border:1px solid var(--border); border-radius:6px;
               margin-bottom:6px; }
.lesson-drag { color:var(--text-dim); cursor:grab; font-size:13px; flex-shrink:0; }
.lesson-icon { font-size:14px; flex-shrink:0; }
.lesson-title-txt { flex:1; font-size:13px; font-weight:500; }
.lesson-meta { font-size:10px; color:var(--text-muted); display:flex; gap:6px; flex-shrink:0; }
.lesson-actions { display:flex; gap:4px; flex-shrink:0; }

/* ── Lesson modal ── */
.lesson-modal { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:1000;
                display:flex; align-items:center; justify-content:center; padding:16px; }
.lesson-modal.hidden { display:none; }
.lm-box { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-lg);
          width:100%; max-width:680px; max-height:92vh; overflow-y:auto; }
.lm-hd  { display:flex; align-items:center; justify-content:space-between;
           padding:18px 22px; border-bottom:1px solid var(--border); }
.lm-hd h3 { font-size:16px; font-weight:700; }
.lm-body { padding:22px; }
.lm-ft   { padding:14px 22px; border-top:1px solid var(--border); display:flex; gap:10px; justify-content:flex-end; }

/* ── Chapter modal ── */
.ch-modal { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:999;
             display:flex; align-items:center; justify-content:center; padding:16px; }
.ch-modal.hidden { display:none; }
.ch-box  { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-lg);
           width:100%; max-width:480px; }

/* ── File upload progress ── */
.up-progress { height:4px; background:var(--border); border-radius:2px; margin-top:8px; overflow:hidden; display:none; }
.up-bar { height:100%; background:var(--accent); width:0%; transition:width .3s; }

/* ── Sidebar ── */
.sb { background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:18px; margin-bottom:14px; }
.sb-hd { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:14px; display:flex; align-items:center; gap:6px; }
.pub-stat { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); margin-bottom:12px; font-size:12px; color:var(--text-muted); }
.stat-d { width:8px; height:8px; border-radius:50%; }

/* ── Thumb preview ── */
.thumb-zone { border:2px dashed var(--border); border-radius:var(--radius); aspect-ratio:16/9;
              display:flex; flex-direction:column; align-items:center; justify-content:center;
              cursor:pointer; background:var(--input-bg); position:relative; overflow:hidden; transition:.2s; }
.thumb-zone:hover { border-color:var(--accent); }
.thumb-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; }
.thumb-zone img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.thumb-zone .ov { position:absolute; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center;
                  justify-content:center; opacity:0; transition:.2s; font-size:12px; color:#fff; font-weight:600; }
.thumb-zone:hover .ov { opacity:1; }

/* ── Autosave ── */
.as-d { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--text-dim); }
.as-d.saving { color:var(--accent); } .as-d.saved { color:#22c55e; } .as-d.error { color:#ef4444; }
.as-p { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }
.as-d.saving .as-p { animation:pr 1s infinite; }
@keyframes pr{ 0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:.4} }

/* ── Content type toggle ── */
.ctype-tabs { display:flex; gap:6px; margin-bottom:14px; flex-wrap:wrap; }
.ctype-tab { padding:7px 14px; border-radius:var(--radius-sm); border:1.5px solid var(--border);
             cursor:pointer; font-size:12px; font-weight:500; color:var(--text-muted); transition:.2s; background:var(--input-bg); }
.ctype-tab.active,.ctype-tab:has(input:checked) { border-color:var(--accent); background:var(--accent-glow); color:var(--accent); }
.ctype-tab input { display:none; }
.ctype-panel { display:none; } .ctype-panel.active { display:block; }

@media(max-width:1024px){ .ce-layout{grid-template-columns:1fr;} .ce-sb{position:static;} }
</style>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>
    <main class="page-content">

      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title"><?= $mode==='edit'?'Edit Course':'New Course' ?></h1>
          <?php if($mode==='edit'): ?>
          <p class="page-subtitle" style="display:flex;align-items:center;gap:10px;">
            <?= e(truncate($course['title'],55)) ?>
            &nbsp;·&nbsp;
            <span class="as-d" id="asDot"><span class="as-p"></span><span id="asTxt">Ready</span></span>
          </p>
          <?php endif; ?>
        </div>
        <div class="page-header-actions">
          <a href="<?= SITE_URL ?>/admin/courses.php" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back
          </a>
          <?php if($mode==='edit' && !empty($course['is_published'])): ?>
          <a href="/courses/<?= e($course['slug']) ?>" target="_blank" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            View Live
          </a>
          <?php endif; ?>
        </div>
      </div>

      <?= flashHtml() ?>

      <form id="courseForm" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" id="formAction" value="<?= $mode==='edit'?'update':'create' ?>">
        <input type="hidden" name="is_published" id="isPub" value="<?= !empty($course['is_published'])?'1':'0' ?>">
        <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <div class="ce-layout">

          <!-- ══ MAIN ══════════════════════════════════════ -->
          <div class="ce-main">

            <!-- Title + Slug always visible -->
            <div class="card" style="padding:20px;margin-bottom:14px;">
              <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Course Title <span class="req">*</span></label>
                <input type="text" name="title" id="cTitle" class="form-control"
                       value="<?= cv($course,'title') ?>" placeholder="Course title…" required
                       style="font-size:17px;font-weight:600;padding:14px 16px;" oninput="onTitleInput()">
              </div>
              <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                  <label class="form-label">URL Slug <span class="req">*</span></label>
                  <div style="display:flex;align-items:center;gap:5px;">
                    <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;">/courses/</span>
                    <input type="text" name="slug" id="cSlug" class="form-control"
                           value="<?= cv($course,'slug') ?>" placeholder="course-slug"
                           required pattern="[a-z0-9-]+" oninput="this.dataset.manual='1'">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Short Description <span class="req">*</span></label>
                  <input type="text" name="short_description" class="form-control"
                         value="<?= cv($course,'short_description') ?>"
                         placeholder="One-line course description…" required maxlength="500">
                </div>
              </div>
            </div>

            <!-- 6-Tab card -->
            <div class="card" style="padding:0;overflow:visible;">
              <div class="ce-tabs">
                <button type="button" class="ce-tab active" data-tab="overview">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                  Overview
                </button>
                <button type="button" class="ce-tab" data-tab="description">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  Description
                </button>
                <button type="button" class="ce-tab" data-tab="media">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  Media
                </button>
                <button type="button" class="ce-tab" data-tab="curriculum" id="currTab">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                  Curriculum
                  <?php if(!empty($chapters)): ?>
                  <span style="background:var(--accent);color:#000;font-size:9px;font-weight:700;border-radius:99px;padding:1px 5px;"><?= count($chapters) ?></span>
                  <?php endif; ?>
                </button>
                <button type="button" class="ce-tab" data-tab="pricing">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  Pricing
                </button>
                <button type="button" class="ce-tab" data-tab="seo">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  SEO
                </button>
              </div>

              <!-- ── Tab: Overview ── -->
              <div class="tab-pane active" id="tab-overview">
                <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                      <option value="">— Uncategorized —</option>
                      <?php foreach($allCats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($course['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-control">
                      <?php foreach(['beginner','intermediate','advanced','all'] as $l): ?>
                        <option value="<?= $l ?>" <?= ($course['level']??'all')===$l?'selected':'' ?>><?= ucfirst($l) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Language</label>
                    <input type="text" name="language" class="form-control"
                           value="<?= cv($course,'language','Hindi') ?>" placeholder="Hindi">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Duration (hours)</label>
                    <input type="number" name="duration_hours" class="form-control" step="0.1" min="0"
                           value="<?= cv($course,'duration_hours') ?>" placeholder="e.g. 12.5"
                           id="durHours">
                    <div class="form-hint" id="durHint" style="font-size:10px;color:var(--text-dim);margin-top:3px;">
                      <?php if($mode==='edit' && $course['total_lessons']): ?>
                        <?= $course['total_lessons'] ?> lessons · auto-calculated from lesson durations
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">What You Will Learn</label>
                  <textarea name="what_you_learn" class="form-control" rows="4"
                            placeholder="List key outcomes, one per line…&#10;&#10;&#10;PHP fundamentals&#10;Database design with MySQL&#10;REST API development"><?= cv($course,'what_you_learn') ?></textarea>
                  <div class="form-hint">One outcome per line. Displayed as bullet points on course page.</div>
                </div>
                <div class="form-group">
                  <label class="form-label">Requirements / Prerequisites</label>
                  <textarea name="requirements" class="form-control" rows="3"
                            placeholder="Basic HTML knowledge&#10;Laptop with browser"><?= cv($course,'requirements') ?></textarea>
                </div>
              </div>

              <!-- ── Tab: Description ── -->
              <div class="tab-pane" id="tab-description">
                <div class="form-group">
                  <label class="form-label">Full Course Description</label>
                  <textarea name="description" class="form-control" rows="12"
                            placeholder="Detailed course description. Markdown is supported.&#10;&#10;## What this course covers&#10;&#10;This course takes you from zero to hero…"><?= htmlspecialchars($course['description']??'', ENT_QUOTES|ENT_SUBSTITUTE) ?></textarea>
                  <div class="form-hint">Markdown supported. Be detailed — this helps with SEO and student decisions.</div>
                </div>
              </div>

              <!-- ── Tab: Media ── -->
              <div class="tab-pane" id="tab-media">
                <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                  <!-- Thumbnail -->
                  <div class="form-group">
                    <label class="form-label">Course Thumbnail</label>
                    <div class="thumb-zone" onclick="document.getElementById('thumbInput').click()">
                      <input type="file" id="thumbInput" name="thumbnail" accept="image/*" onchange="onThumb(this)">
                      <img id="thumbImg" src="<?= cv($course,'thumbnail_path') ?>" alt="" style="<?= empty($course['thumbnail_path'])?'display:none':'' ?>">
                      <div class="ov">Change Image</div>
                      <div id="thumbPh" style="text-align:center;<?= !empty($course['thumbnail_path'])?'display:none':'' ?>">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div class="uz-lbl">Click to upload thumbnail</div>
                        <div class="uz-hint">JPG, PNG, WebP · Max 3MB · 16:9 recommended</div>
                      </div>
                    </div>
                    <?php if(!empty($course['thumbnail_path'])): ?>
                    <button type="button" class="btn btn-ghost btn-sm" style="width:100%;margin-top:8px;" onclick="clearThumb()">Remove Thumbnail</button>
                    <?php endif; ?>
                  </div>

                  <!-- Preview Video -->
                  <div class="form-group">
                    <label class="form-label">Preview Video</label>
                    <div class="form-group" style="margin-bottom:10px;">
                      <label class="form-label" style="font-size:11px;">YouTube / External URL</label>
                      <input type="url" name="preview_video_url" class="form-control" id="pvUrl"
                             value="<?= cv($course,'preview_video') ?>"
                             placeholder="https://youtu.be/… or paste URL" oninput="pvPreview()">
                    </div>
                    <div style="text-align:center;font-size:11px;color:var(--text-muted);margin-bottom:10px;">— or upload —</div>
                    <div class="upload-zone" onclick="document.getElementById('pvInput').click()">
                      <input type="file" id="pvInput" name="preview_video" accept="video/*" onchange="onPv(this)">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                      <div class="uz-lbl">Upload preview video</div>
                      <div class="uz-hint">MP4, WebM · Max 200MB</div>
                    </div>
                    <div id="pvPrev" style="margin-top:8px;display:none;"></div>
                    <div class="up-progress" id="pvProg"><div class="up-bar" id="pvBar"></div></div>
                  </div>
                </div>
              </div>

              <!-- ── Tab: Curriculum ── -->
              <div class="tab-pane" id="tab-curriculum">
                <?php if($mode !== 'edit'): ?>
                <div style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px;">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5" style="display:block;margin:0 auto 12px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  <strong style="color:var(--text);">Save the course first</strong>
                  <p style="margin:6px 0 0;">Create the course, then come back to add chapters and lessons.</p>
                </div>
                <?php else: ?>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                  <div>
                    <div style="font-weight:600;color:var(--text);margin-bottom:2px;">Course Curriculum</div>
                    <div style="font-size:12px;color:var(--text-muted);">
                      <?= count($chapters) ?> chapter<?= count($chapters)!==1?'s':'' ?> ·
                      <?= array_sum(array_column($chapters,'lesson_count')) ?> total lessons
                    </div>
                  </div>
                  <button type="button" class="btn btn-primary btn-sm" onclick="openChapterModal()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Chapter
                  </button>
                </div>

                <div id="chapterList">
                  <?php foreach($chapters as $ci => $ch): ?>
                  <div class="chapter-item" id="chapter-<?= $ch['id'] ?>" data-chid="<?= $ch['id'] ?>">
                    <div class="chapter-hd" onclick="toggleChapter(<?= $ch['id'] ?>)">
                      <span class="chapter-drag" title="Drag to reorder">⋮⋮</span>
                      <div style="flex:1;">
                        <div class="chapter-title-text"><?= e($ch['title']) ?></div>
                        <div class="chapter-meta">
                          <span><?= $ch['lesson_count'] ?> lesson<?= $ch['lesson_count']!==1?'s':'' ?></span>
                          <span><?= secs2dur((int)$ch['total_secs']) ?></span>
                          <?php if(!$ch['is_active']): ?><span style="color:#ef4444;">Inactive</span><?php endif; ?>
                        </div>
                      </div>
                      <div class="chapter-actions" onclick="event.stopPropagation()">
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Add Lesson"
                                onclick="openLessonModal(<?= $ch['id'] ?>)">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Edit Chapter"
                                onclick="openChapterModal(<?= $ch['id'] ?>,'<?= e(addslashes($ch['title'])) ?>','<?= e(addslashes($ch['description']??'')) ?>')">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-icon" title="Delete Chapter"
                                onclick="deleteChapter(<?= $ch['id'] ?>,'<?= e(addslashes($ch['title'])) ?>')">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="chapter-chevron" id="chev-<?= $ch['id'] ?>" style="transition:transform .2s;transform:rotate(-90deg);color:var(--text-muted);"><polyline points="6 9 12 15 18 9"/></svg>
                      </div>
                    </div>

                    <div class="ch-collapse" id="chbody-<?= $ch['id'] ?>">
                      <div class="chapter-body">
                        <!-- Lesson list for this chapter -->
                        <div id="lessonList-<?= $ch['id'] ?>" class="lesson-list">
                          <?php
                            $ls=$pdo->prepare('SELECT * FROM course_lessons WHERE chapter_id=? ORDER BY sort_order ASC');
                            $ls->execute([$ch['id']]);
                            $lessons=$ls->fetchAll();
                          ?>
                          <?php foreach($lessons as $l): ?>
                          <?php
                            $lIcon=match($l['content_type']){'video'=>'▶️','pdf'=>'📄','text'=>'📝','quiz'=>'📋','zip'=>'📦',default=>'📝'};
                          ?>
                          <div class="lesson-item" id="lesson-<?= $l['id'] ?>" data-lid="<?= $l['id'] ?>">
                            <span class="lesson-drag">⋮⋮</span>
                            <span class="lesson-icon"><?= $lIcon ?></span>
                            <div class="lesson-title-txt">
                              <?= e($l['title']) ?>
                              <?php if($l['is_preview']): ?><span style="font-size:9px;color:#22c55e;background:rgba(34,197,94,.1);padding:1px 5px;border-radius:3px;margin-left:4px;">FREE</span><?php endif; ?>
                              <?php if(!$l['is_active']): ?><span style="font-size:9px;color:#ef4444;">Inactive</span><?php endif; ?>
                            </div>
                            <div class="lesson-meta">
                              <?php if($l['duration_seconds']): ?><span><?= secs2dur((int)$l['duration_seconds']) ?></span><?php endif; ?>
                              <span style="text-transform:capitalize;"><?= $l['content_type'] ?></span>
                            </div>
                            <div class="lesson-actions">
                              <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Edit"
                                      onclick="editLesson(<?= $l['id'] ?>,<?= $ch['id'] ?>)">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                              </button>
                              <button type="button" class="btn btn-danger btn-sm btn-icon" title="Delete"
                                      onclick="deleteLesson(<?= $l['id'] ?>,'<?= e(addslashes($l['title'])) ?>')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                              </button>
                            </div>
                          </div>
                          <?php endforeach; ?>
                          <?php if(empty($lessons)): ?>
                          <div style="text-align:center;padding:14px;font-size:12px;color:var(--text-muted);">
                            No lessons yet.
                            <button type="button" class="btn btn-ghost btn-sm" onclick="openLessonModal(<?= $ch['id'] ?>)">Add first lesson →</button>
                          </div>
                          <?php endif; ?>
                        </div>

                        <button type="button" class="btn btn-ghost btn-sm" style="margin-top:8px;width:100%;"
                                onclick="openLessonModal(<?= $ch['id'] ?>)">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                          Add Lesson to "<?= e(truncate($ch['title'],30)) ?>"
                        </button>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>

                  <?php if(empty($chapters)): ?>
                  <div style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px;border:2px dashed var(--border);border-radius:var(--radius);">
                    <div style="font-size:28px;margin-bottom:10px;">📚</div>
                    <strong style="color:var(--text);display:block;margin-bottom:6px;">No chapters yet</strong>
                    Click <strong>Add Chapter</strong> above to start building your curriculum.
                  </div>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>

              <!-- ── Tab: Pricing ── -->
              <div class="tab-pane" id="tab-pricing">
                <div class="form-group" style="margin-bottom:18px;">
                  <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;font-weight:600;">
                    <input type="checkbox" name="is_free" id="isFree" value="1"
                           <?= !empty($course['is_free'])||$mode==='add'?'checked':'' ?>
                           style="accent-color:var(--accent);width:16px;height:16px;" onchange="togglePricing()">
                    This course is FREE
                  </label>
                  <div class="form-hint">Uncheck to set a paid price.</div>
                </div>
                <div id="pricingFields" style="<?= (!empty($course['is_free'])||$mode==='add')?'display:none':'' ?>">
                  <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                      <label class="form-label">Price</label>
                      <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:14px;color:var(--text-muted);">₹</span>
                        <input type="number" name="price" class="form-control" step="1" min="0"
                               value="<?= cv($course,'price','0') ?>" placeholder="499">
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Discount Price <span style="font-size:11px;color:var(--text-dim);font-weight:400;">(optional)</span></label>
                      <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:14px;color:var(--text-muted);">₹</span>
                        <input type="number" name="discount_price" class="form-control" step="1" min="0"
                               value="<?= cv($course,'discount_price') ?>" placeholder="299">
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Currency</label>
                      <select name="currency" class="form-control">
                        <option value="INR" <?= ($course['currency']??'INR')==='INR'?'selected':'' ?>>INR (₹)</option>
                        <option value="USD" <?= ($course['currency']??'')==='USD'?'selected':'' ?>>USD ($)</option>
                        <option value="EUR" <?= ($course['currency']??'')==='EUR'?'selected':'' ?>>EUR (€)</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ── Tab: SEO ── -->
              <div class="tab-pane" id="tab-seo">
                <div class="form-group">
                  <label class="form-label">Meta Description</label>
                  <textarea name="meta_description" id="metaDesc" class="form-control" rows="3"
                            maxlength="160" oninput="updateSeoCount()"
                            placeholder="Brief description for search engines…"><?= cv($course,'meta_description') ?></textarea>
                  <div style="display:flex;justify-content:space-between;margin-top:3px;">
                    <div class="form-hint">Max 160 chars.</div>
                    <span style="font-size:10px;color:var(--text-dim);" id="metaTc">0/160</span>
                  </div>
                </div>
                <!-- SERP preview -->
                <div style="margin-top:16px;">
                  <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Google Search Preview</div>
                  <div style="background:var(--input-bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px;">
                    <div style="font-size:12px;color:#22c55e;font-family:monospace;" id="spUrl">codebytushu.com › courses › <?= cv($course,'slug','course-slug') ?></div>
                    <div style="font-size:16px;color:#93c5fd;font-weight:500;margin:4px 0;" id="spTitle"><?= cv($course,'title') ?: 'Course Title — CodeByTushu' ?></div>
                    <div style="font-size:13px;color:var(--text-muted);" id="spDesc"><?= cv($course,'meta_description') ?: 'No meta description yet.' ?></div>
                  </div>
                </div>
              </div>

            </div><!-- /card-tabs -->
          </div><!-- /ce-main -->

          <!-- ══ SIDEBAR ════════════════════════════════════ -->
          <div class="ce-sb">

            <!-- Publish -->
            <div class="sb">
              <div class="sb-hd">Publish</div>
              <div class="pub-stat">
                <span>Status</span>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div class="stat-d" id="sDot" style="background:<?= !empty($course['is_published'])?'#22c55e':'#f59e0b' ?>;"></div>
                  <span id="sLbl" style="font-weight:600;font-size:12px;color:<?= !empty($course['is_published'])?'#22c55e':'#f59e0b' ?>;"><?= !empty($course['is_published'])?'Published':'Draft' ?></span>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;color:var(--text-muted);">
                  <input type="checkbox" name="is_featured" value="1" <?= !empty($course['is_featured'])?'checked':'' ?> style="accent-color:var(--accent);">
                  ⭐ Featured Course
                </label>
              </div>
              <div style="display:flex;flex-direction:column;gap:8px;">
                <button type="button" class="btn btn-ghost btn-sm" id="draftBtn" onclick="doSubmit('draft')">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                  Save Draft
                </button>
                <button type="button" class="btn btn-primary" id="pubBtn" onclick="doSubmit('publish')">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <?= !empty($course['is_published'])?'Update &amp; Publish':'Publish Now' ?>
                </button>
                <?php if(!empty($course['is_published'])): ?>
                <button type="button" class="btn btn-warning btn-sm" onclick="doUnpublish()">Unpublish</button>
                <?php endif; ?>
              </div>
            </div>

            <!-- Stats (edit only) -->
            <?php if($mode==='edit'): ?>
            <div class="sb">
              <div class="sb-hd">Stats</div>
              <div style="display:flex;flex-direction:column;gap:10px;">
                <?php
                  $rows=[
                    ['Enrollments', number_format($course['enrollment_count']??0)],
                    ['Total Lessons', number_format($course['total_lessons']??0)],
                    ['Chapters', count($chapters)],
                    ['Duration', ($course['duration_hours']?$course['duration_hours'].'h':'—')],
                    ['Rating', ($course['rating']?'★ '.number_format((float)$course['rating'],1):'—')],
                    ['Created', formatDate($course['created_at']??'','d M Y')],
                  ];
                  foreach($rows as [$k,$v]): ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;">
                  <span style="color:var(--text-muted);"><?= $k ?></span>
                  <strong style="color:var(--text);"><?= $v ?></strong>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Danger Zone -->
            <?php if($mode==='edit'): ?>
            <div class="sb" style="border-color:rgba(239,68,68,.3);">
              <div class="sb-hd" style="color:#ef4444;">Danger Zone</div>
              <button type="button" class="btn btn-danger btn-sm" style="width:100%;"
                      onclick="doDeleteCourse(<?= $id ?>,'<?= cv($course,'title') ?>')">Delete Course</button>
            </div>
            <?php endif; ?>

          </div><!-- /sidebar -->
        </div><!-- /ce-layout -->
      </form>

      <!-- ══ Chapter Modal ════════════════════════════════ -->
      <div class="ch-modal hidden" id="chModal">
        <div class="ch-box">
          <div class="lm-hd">
            <h3 id="chModalTitle">Add Chapter</h3>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" onclick="closeChModal()">✕</button>
          </div>
          <div class="lm-body">
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label">Chapter Title <span class="req">*</span></label>
              <input type="text" id="chTitle" class="form-control" placeholder="e.g. Introduction to PHP">
            </div>
            <div class="form-group">
              <label class="form-label">Chapter Description</label>
              <textarea id="chDesc" class="form-control" rows="3" placeholder="Optional brief description…"></textarea>
            </div>
            <input type="hidden" id="chId" value="0">
          </div>
          <div class="lm-ft">
            <button type="button" class="btn btn-ghost" onclick="closeChModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="chSaveBtn" onclick="saveChapter()">Save Chapter</button>
          </div>
        </div>
      </div>

      <!-- ══ Lesson Modal ═════════════════════════════════ -->
      <div class="lesson-modal hidden" id="lsnModal">
        <div class="lm-box">
          <div class="lm-hd">
            <h3 id="lsnModalTitle">Add Lesson</h3>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" onclick="closeLsnModal()">✕</button>
          </div>
          <div class="lm-body">
            <input type="hidden" id="lsnId" value="0">
            <input type="hidden" id="lsnChapterId" value="0">

            <div class="form-row-2" style="display:grid;grid-template-columns:1fr auto;gap:16px;margin-bottom:14px;">
              <div class="form-group">
                <label class="form-label">Lesson Title <span class="req">*</span></label>
                <input type="text" id="lsnTitle" class="form-control" placeholder="e.g. Variables in PHP">
              </div>
              <div class="form-group">
                <label class="form-label">Duration (seconds)</label>
                <input type="number" id="lsnDur" class="form-control" placeholder="600" min="0" style="width:120px;">
                <div class="form-hint" id="lsnDurHint" style="font-size:10px;white-space:nowrap;"></div>
              </div>
            </div>

            <!-- Content type selector -->
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label">Content Type</label>
              <div class="ctype-tabs" id="ctypeTabs">
                <?php foreach(['video'=>'▶️ Video','pdf'=>'📄 PDF','text'=>'📝 Text','quiz'=>'📋 Quiz','zip'=>'📦 Resource'] as $ct=>$cl): ?>
                <label class="ctype-tab <?= $ct==='video'?'active':'' ?>">
                  <input type="radio" name="content_type_modal" value="<?= $ct ?>" <?= $ct==='video'?'checked':'' ?> onchange="switchContentType('<?= $ct ?>')">
                  <?= $cl ?>
                </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" id="lsnType" value="video">
            </div>

            <!-- Video panel -->
            <div class="ctype-panel active" id="cpanel-video">
              <div class="upload-zone" onclick="document.getElementById('lsnVideoInput').click()">
                <input type="file" id="lsnVideoInput" accept="video/*">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                <div class="uz-lbl">Upload video file</div>
                <div class="uz-hint">MP4, WebM, MOV · Max 500MB</div>
              </div>
              <div class="up-progress" id="lsnVidProg"><div class="up-bar" id="lsnVidBar"></div></div>
              <div id="lsnVidFile" style="margin-top:8px;font-size:11px;color:var(--text-muted);"></div>
              <div style="margin-top:10px;font-size:11px;color:var(--text-dim);text-align:center;">— or use existing path —</div>
              <input type="text" id="lsnVideoPath" class="form-control" style="margin-top:8px;font-size:12px;" placeholder="/uploads/courses/videos/lesson.mp4">
            </div>

            <!-- PDF panel -->
            <div class="ctype-panel" id="cpanel-pdf">
              <div class="upload-zone" onclick="document.getElementById('lsnPdfInput').click()">
                <input type="file" id="lsnPdfInput" accept="application/pdf">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <div class="uz-lbl">Upload PDF file</div>
                <div class="uz-hint">PDF · Max 50MB</div>
              </div>
              <div class="up-progress" id="lsnPdfProg"><div class="up-bar" id="lsnPdfBar"></div></div>
              <div id="lsnPdfFile" style="margin-top:8px;font-size:11px;color:var(--text-muted);"></div>
              <input type="text" id="lsnPdfPath" class="form-control" style="margin-top:8px;font-size:12px;" placeholder="/uploads/courses/pdfs/lesson.pdf">
            </div>

            <!-- Text panel -->
            <div class="ctype-panel" id="cpanel-text">
              <textarea id="lsnText" class="form-control" rows="6" placeholder="Write lesson text content (Markdown supported)…"></textarea>
            </div>

            <!-- Quiz panel -->
            <div class="ctype-panel" id="cpanel-quiz">
              <div style="padding:14px;text-align:center;background:var(--input-bg);border-radius:var(--radius);color:var(--text-muted);font-size:13px;">
                Quiz module coming soon. For now use Text content type to add quiz questions.
              </div>
            </div>

            <!-- Zip panel -->
            <div class="ctype-panel" id="cpanel-zip">
              <div class="upload-zone" onclick="document.getElementById('lsnZipInput').click()">
                <input type="file" id="lsnZipInput" accept=".zip,.rar,.tar.gz">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div class="uz-lbl">Upload resource ZIP</div>
                <div class="uz-hint">ZIP, RAR · Max 100MB</div>
              </div>
              <div id="lsnZipFile" style="margin-top:8px;font-size:11px;color:var(--text-muted);"></div>
            </div>

            <div class="form-group" style="margin-top:14px;">
              <label class="form-label">Lesson Description</label>
              <textarea id="lsnDesc" class="form-control" rows="2" placeholder="Brief description of this lesson…"></textarea>
            </div>

            <div style="display:flex;gap:16px;margin-top:12px;flex-wrap:wrap;">
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;">
                <input type="checkbox" id="lsnPreview" style="accent-color:var(--accent);"> Free Preview (no enrollment needed)
              </label>
              <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;">
                <input type="checkbox" id="lsnActive" checked style="accent-color:var(--accent);"> Active
              </label>
            </div>
          </div>
          <div class="lm-ft">
            <button type="button" class="btn btn-ghost" onclick="closeLsnModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="lsnSaveBtn" onclick="saveLesson()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
              Save Lesson
            </button>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
const COURSE_ID = <?= $id ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ══ TABS ══ */
document.querySelectorAll('.ce-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.ce-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab)?.classList.add('active');
  });
});
// Open to curriculum tab if URL param
if ('<?= $tab ?>' === 'chapters') {
  document.querySelector('[data-tab="curriculum"]')?.click();
}

/* ══ SLUG ══ */
function onTitleInput() {
  const s = document.getElementById('cSlug');
  if (!s.dataset.manual) {
    s.value = document.getElementById('cTitle').value
      .toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
  }
  updateSeo();
}
function updateSeo() {
  const sl = document.getElementById('cSlug').value||'course-slug';
  const tt = document.getElementById('cTitle').value||'Course Title';
  document.getElementById('spUrl').textContent = `codebytushu.com › courses › ${sl}`;
  document.getElementById('spTitle').textContent = tt + ' — CodeByTushu';
}
function updateSeoCount() {
  const n = document.getElementById('metaDesc').value.length;
  document.getElementById('metaTc').textContent = `${n}/160`;
  document.getElementById('metaTc').style.color = n>160?'#ef4444':'var(--text-dim)';
  document.getElementById('spDesc').textContent = document.getElementById('metaDesc').value||'No meta description yet.';
}
updateSeoCount();

/* ══ PRICING ══ */
function togglePricing() {
  document.getElementById('pricingFields').style.display =
    document.getElementById('isFree').checked ? 'none' : 'block';
}

/* ══ THUMBNAIL ══ */
function onThumb(input) {
  const f = input.files[0]; if (!f) return;
  if (f.size > 3*1024*1024) { Toast.error('Too large','Max 3MB.'); input.value=''; return; }
  const r = new FileReader();
  r.onload = e => { document.getElementById('thumbImg').src=e.target.result; document.getElementById('thumbImg').style.display=''; document.getElementById('thumbPh').style.display='none'; };
  r.readAsDataURL(f);
}
function clearThumb() { document.getElementById('thumbImg').style.display='none'; document.getElementById('thumbPh').style.display=''; document.getElementById('thumbInput').value=''; }

/* ══ PREVIEW VIDEO ══ */
function pvPreview() {
  const v = document.getElementById('pvUrl').value.trim();
  const p = document.getElementById('pvPrev');
  p.style.display = v ? 'block' : 'none';
  if (v) p.innerHTML = `<div style="background:var(--input-bg);border-radius:var(--radius);padding:8px 12px;font-size:12px;display:flex;align-items:center;gap:8px;"><span style="font-size:16px;">▶️</span><a href="${escHtml(v)}" target="_blank" style="color:var(--accent);word-break:break-all;">${escHtml(v.substring(0,60))}${v.length>60?'…':''}</a></div>`;
}
function onPv(input) {
  const f = input.files[0]; if (!f) return;
  document.getElementById('pvPrev').innerHTML = `<div style="font-size:12px;color:var(--text-muted);padding:6px 0;">${escHtml(f.name)} (${(f.size/1024/1024).toFixed(1)}MB)</div>`;
  document.getElementById('pvPrev').style.display = 'block';
}

/* ══ FORM SUBMIT ══ */
function doSubmit(mode) {
  document.getElementById('isPub').value = mode==='publish'?'1':'0';
  document.getElementById('courseForm').dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}));
}

document.getElementById('courseForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const isPub = document.getElementById('isPub').value==='1';
  const btn   = isPub ? document.getElementById('pubBtn') : document.getElementById('draftBtn');
  const orig  = btn.innerHTML;
  btn.disabled = true; btn.innerHTML = `<span style="opacity:.6">${isPub?'Publishing…':'Saving…'}</span>`;
  clearInterval(asTimer);
  const fd = new FormData(this);
  try {
    const res  = await fetch('<?= SITE_URL ?>/api/admin/courses.php', {method:'POST', body:fd});
    const json = await res.json();
    if (json.success) {
      Toast.success(isPub?'🚀 Published!':'💾 Saved!', json.message||'Course saved.');
      if (isPub) { document.getElementById('sDot').style.background='#22c55e'; document.getElementById('sLbl').style.color='#22c55e'; document.getElementById('sLbl').textContent='Published'; }
      if (json.data?.id && !COURSE_ID) setTimeout(()=>{ window.location.href=`/admin/course-edit.php?id=${json.data.id}`; },900);
      else startAutosave();
    } else { Toast.error('Error', json.error||'Save failed.'); startAutosave(); }
  } catch { Toast.error('Network Error','Could not reach server.'); startAutosave(); }
  finally  { btn.disabled=false; btn.innerHTML=orig; }
});

/* ══ UNPUBLISH ══ */
async function doUnpublish() {
  Modal.confirm('Remove this course from public view?', async ()=>{
    const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'toggle_publish',id:COURSE_ID,value:0});
    if(r.success){ Toast.success('Unpublished','Set to draft.'); document.getElementById('sDot').style.background='#f59e0b'; document.getElementById('sLbl').style.color='#f59e0b'; document.getElementById('sLbl').textContent='Draft'; document.getElementById('isPub').value='0'; }
    else Toast.error('Error',r.error);
  },{title:'Unpublish Course',confirmText:'Unpublish',type:'warning'});
}

/* ══ DELETE COURSE ══ */
async function doDeleteCourse(id,title){
  Modal.confirm(`Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">All chapters and lessons will be permanently removed.</small>`,async()=>{
    const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'delete',id});
    if(r.success){Toast.success('Deleted');setTimeout(()=>{window.location.href='/admin/courses.php';},900);}
    else Toast.error('Error',r.error);
  },{title:'Delete Course',confirmText:'Delete',type:'danger'});
}

/* ══ CHAPTER MODAL ══ */
function openChapterModal(cid=0,title='',desc=''){
  document.getElementById('chId').value=cid;
  document.getElementById('chTitle').value=title;
  document.getElementById('chDesc').value=desc;
  document.getElementById('chModalTitle').textContent=cid?'Edit Chapter':'Add Chapter';
  document.getElementById('chModal').classList.remove('hidden');
  setTimeout(()=>document.getElementById('chTitle').focus(),50);
}
function closeChModal(){ document.getElementById('chModal').classList.add('hidden'); }
async function saveChapter(){
  const title=document.getElementById('chTitle').value.trim();
  if(!title){Toast.error('Required','Chapter title is required.');return;}
  const btn=document.getElementById('chSaveBtn');
  btn.disabled=true; btn.textContent='Saving…';
  const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{
    action:'chapter_save',chapter_id:document.getElementById('chId').value,
    course_id:COURSE_ID,title,description:document.getElementById('chDesc').value
  });
  btn.disabled=false; btn.textContent='Save Chapter';
  if(r.success){
    Toast.success('Saved','Chapter saved.');
    closeChModal();
    setTimeout(()=>location.reload(),600);
  } else Toast.error('Error',r.error);
}
async function deleteChapter(cid,title){
  Modal.confirm(`Delete chapter "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">All lessons in this chapter will be deleted.</small>`,async()=>{
    const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'chapter_delete',chapter_id:cid});
    if(r.success){document.getElementById(`chapter-${cid}`)?.remove();Toast.success('Deleted','Chapter removed.');}
    else Toast.error('Error',r.error);
  },{title:'Delete Chapter',confirmText:'Delete',type:'danger'});
}
function toggleChapter(cid){
  const body=document.getElementById(`chbody-${cid}`);
  const chev=document.getElementById(`chev-${cid}`);
  body.classList.toggle('open');
  if(chev) chev.style.transform=body.classList.contains('open')?'rotate(0deg)':'rotate(-90deg)';
}

/* ══ CONTENT TYPE ══ */
function switchContentType(type){
  document.getElementById('lsnType').value=type;
  document.querySelectorAll('.ctype-panel').forEach(p=>p.classList.remove('active'));
  document.getElementById(`cpanel-${type}`)?.classList.add('active');
  document.querySelectorAll('.ctype-tab').forEach(t=>{
    t.classList.toggle('active',t.querySelector('input')?.value===type);
  });
}

/* ══ LESSON MODAL ══ */
function openLessonModal(chapterId){
  document.getElementById('lsnId').value=0;
  document.getElementById('lsnChapterId').value=chapterId;
  document.getElementById('lsnTitle').value='';
  document.getElementById('lsnDur').value='';
  document.getElementById('lsnDurHint').textContent='';
  document.getElementById('lsnDesc').value='';
  document.getElementById('lsnText').value='';
  document.getElementById('lsnVideoPath').value='';
  document.getElementById('lsnPdfPath').value='';
  document.getElementById('lsnVidFile').textContent='';
  document.getElementById('lsnPdfFile').textContent='';
  document.getElementById('lsnPreview').checked=false;
  document.getElementById('lsnActive').checked=true;
  document.getElementById('lsnModalTitle').textContent='Add Lesson';
  switchContentType('video');
  document.querySelectorAll('[name="content_type_modal"]').forEach(r=>r.checked=r.value==='video');
  document.getElementById('lsnModal').classList.remove('hidden');
  setTimeout(()=>document.getElementById('lsnTitle').focus(),50);
}

async function editLesson(lid, chapterId){
  document.getElementById('lsnChapterId').value=chapterId;
  document.getElementById('lsnId').value=lid;
  document.getElementById('lsnModalTitle').textContent='Edit Lesson';
  // Fetch lesson data
  const r=await apiGet(`/api/admin/courses.php?action=get_lesson&lesson_id=${lid}`);
  if(!r.success){Toast.error('Error','Could not load lesson.');return;}
  const l=r.data;
  document.getElementById('lsnTitle').value=l.title||'';
  document.getElementById('lsnDur').value=l.duration_seconds||'';
  document.getElementById('lsnDesc').value=l.description||'';
  document.getElementById('lsnText').value=l.text_content||'';
  document.getElementById('lsnVideoPath').value=l.video_path||'';
  document.getElementById('lsnPdfPath').value=l.pdf_path||'';
  document.getElementById('lsnPreview').checked=!!l.is_preview;
  document.getElementById('lsnActive').checked=!!l.is_active;
  switchContentType(l.content_type||'video');
  document.querySelectorAll('[name="content_type_modal"]').forEach(r2=>r2.checked=r2.value===l.content_type);
  updateDurHint();
  document.getElementById('lsnModal').classList.remove('hidden');
}

function closeLsnModal(){ document.getElementById('lsnModal').classList.add('hidden'); }

function updateDurHint(){
  const s=parseInt(document.getElementById('lsnDur').value)||0;
  if(s){const h=Math.floor(s/3600);const m=Math.floor((s%3600)/60);const sec=s%60;document.getElementById('lsnDurHint').textContent=h?`${h}h ${m}m ${sec}s`:`${m}m ${sec}s`;}
  else document.getElementById('lsnDurHint').textContent='';
}
document.getElementById('lsnDur').addEventListener('input',updateDurHint);

async function saveLesson(){
  const title=document.getElementById('lsnTitle').value.trim();
  if(!title){Toast.error('Required','Lesson title is required.');return;}
  const lid=document.getElementById('lsnId').value;
  const cid=document.getElementById('lsnChapterId').value;
  const type=document.getElementById('lsnType').value;
  const btn=document.getElementById('lsnSaveBtn');
  btn.disabled=true; btn.innerHTML='<span style="opacity:.6">Saving…</span>';

  const fd=new FormData();
  fd.append('csrf_token',CSRF_TOKEN);
  fd.append('action','lesson_save');
  fd.append('lesson_id',lid);
  fd.append('chapter_id',cid);
  fd.append('course_id',COURSE_ID);
  fd.append('title',title);
  fd.append('content_type',type);
  fd.append('description',document.getElementById('lsnDesc').value);
  fd.append('duration_seconds',document.getElementById('lsnDur').value||'0');
  fd.append('is_preview',document.getElementById('lsnPreview').checked?'1':'0');
  fd.append('is_active',document.getElementById('lsnActive').checked?'1':'0');
  if(type==='text') fd.append('text_content',document.getElementById('lsnText').value);
  if(type==='video'){
    const vf=document.getElementById('lsnVideoInput').files[0];
    if(vf) fd.append('video_file',vf);
    else   fd.append('video_path',document.getElementById('lsnVideoPath').value);
  }
  if(type==='pdf'){
    const pf=document.getElementById('lsnPdfInput').files[0];
    if(pf) fd.append('pdf_file',pf);
    else   fd.append('pdf_path',document.getElementById('lsnPdfPath').value);
  }

  try {
    const res=await fetch('<?= SITE_URL ?>/api/admin/courses.php',{method:'POST',body:fd});
    const json=await res.json();
    if(json.success){
      Toast.success('Saved!','Lesson saved.');
      closeLsnModal();
      setTimeout(()=>location.reload(),700);
    } else Toast.error('Error',json.error||'Save failed.');
  } catch { Toast.error('Network Error','Could not reach server.'); }
  finally { btn.disabled=false; btn.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg> Save Lesson'; }
}

async function deleteLesson(lid,title){
  Modal.confirm(`Delete lesson "<strong>${escHtml(title)}</strong>"?`,async()=>{
    const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'lesson_delete',lesson_id:lid});
    if(r.success){document.getElementById(`lesson-${lid}`)?.remove();Toast.success('Deleted','Lesson removed.');}
    else Toast.error('Error',r.error);
  },{title:'Delete Lesson',confirmText:'Delete',type:'danger'});
}

/* ══ CLOSE MODALS ON OVERLAY CLICK ══ */
document.getElementById('chModal').addEventListener('click',e=>{if(e.target===e.currentTarget) closeChModal();});
document.getElementById('lsnModal').addEventListener('click',e=>{if(e.target===e.currentTarget) closeLsnModal();});

/* ══ AUTOSAVE (edit mode) ══ */
<?php if($mode==='edit'): ?>
let asTimer;
const asDot=document.getElementById('asDot');
const asTxt=document.getElementById('asTxt');
async function runAs(){
  if(document.hidden) return;
  asDot.className='as-d saving'; asTxt.textContent='Saving…';
  const fd=new FormData(document.getElementById('courseForm'));
  fd.set('action','update');
  try{
    const r=await fetch('<?= SITE_URL ?>/api/admin/courses.php',{method:'POST',body:fd});
    const j=await r.json();
    if(j.success){asDot.className='as-d saved';asTxt.textContent='Saved '+new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});}
    else{asDot.className='as-d error';asTxt.textContent='Save failed';}
  }catch{asDot.className='as-d error';asTxt.textContent='Offline?';}
  setTimeout(()=>{asDot.className='as-d';asTxt.textContent='Ready';},4000);
}
function startAutosave(){clearInterval(asTimer);asTimer=setInterval(runAs,60000);}
startAutosave();
<?php else: ?>
function startAutosave(){}
<?php endif; ?>

/* ══ KEYBOARD SHORTCUTS ══ */
document.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key==='s'){e.preventDefault();doSubmit('draft');}
  if((e.ctrlKey||e.metaKey)&&e.shiftKey&&e.key==='P'){e.preventDefault();doSubmit('publish');}
  if(e.key==='Escape'){closeChModal();closeLsnModal();}
});
</script>
</body>
</html>
