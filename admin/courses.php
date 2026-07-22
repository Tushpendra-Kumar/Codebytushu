<?php
/**
 * CodeByTushu — Admin Courses Management v2
 * Premium list: summary cards, sortable table, publish/featured toggles.
 */
declare(strict_types=1);

$adminSection = 'Courses';
$adminTitle   = 'Courses Management — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Courses'],
];
require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

/* ══ Params ════════════════════════════════════════════ */
$perPage  = 15;
$page     = max(1,(int)get('page','1'));
$search   = trim(get('search',''));
$status   = get('status','');
$level    = get('level','');
$catId    = (int)get('cat','0');
$free     = get('free','');
$sort     = get('sort','created_at');
$dir      = strtoupper(get('dir','DESC'))==='ASC'?'ASC':'DESC';
$validSort=['title','enrollment_count','rating','duration_hours','total_lessons','published_at','created_at'];
if(!in_array($sort,$validSort,true)) $sort='created_at';

/* ══ WHERE ═════════════════════════════════════════════ */
$where=['1=1'];
$params=[];
if($search)      { $where[]='(c.title LIKE ? OR c.short_description LIKE ?)'; $w="%$search%"; array_push($params,$w,$w); }
if($status==='published')  { $where[]='c.is_published=1'; }
if($status==='draft')      { $where[]='c.is_published=0'; }
if($level)       { $where[]='c.level=?'; $params[]=$level; }
if($catId)       { $where[]='c.category_id=?'; $params[]=$catId; }
if($free==='1')  { $where[]='c.is_free=1'; }
if($free==='0')  { $where[]='c.is_free=0'; }
$wc=implode(' AND ',$where);

/* ══ Stats ═════════════════════════════════════════════ */
try {
    $sc=[
        'total'      =>(int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
        'published'  =>(int)$pdo->query('SELECT COUNT(*) FROM courses WHERE is_published=1')->fetchColumn(),
        'draft'      =>(int)$pdo->query('SELECT COUNT(*) FROM courses WHERE is_published=0')->fetchColumn(),
        'free'       =>(int)$pdo->query('SELECT COUNT(*) FROM courses WHERE is_free=1')->fetchColumn(),
        'featured'   =>(int)$pdo->query('SELECT COUNT(*) FROM courses WHERE is_featured=1')->fetchColumn(),
        'enrollments'=>(int)$pdo->query('SELECT COALESCE(SUM(enrollment_count),0) FROM courses')->fetchColumn(),
    ];
} catch(\Throwable){ $sc=['total'=>0,'published'=>0,'draft'=>0,'free'=>0,'featured'=>0,'enrollments'=>0]; }

/* ══ Paginated rows ════════════════════════════════════ */
$cs=$pdo->prepare("SELECT COUNT(*) FROM courses c WHERE $wc");
$cs->execute($params); $total=(int)$cs->fetchColumn();
$pager=paginate($total,$perPage,$page);

$stmt=$pdo->prepare(
    "SELECT c.id,c.title,c.slug,c.thumbnail_path,c.level,c.language,
            c.price,c.discount_price,c.is_free,c.is_published,c.is_featured,
            c.enrollment_count,c.rating,c.duration_hours,c.total_lessons,
            c.published_at,c.created_at,
            cat.name AS category_name,
            u.full_name AS instructor_name,
            (SELECT COUNT(*) FROM course_chapters ch WHERE ch.course_id=c.id) AS chapter_count
       FROM courses c
       LEFT JOIN categories cat ON cat.id=c.category_id
       LEFT JOIN users u ON u.id=c.instructor_id
      WHERE $wc ORDER BY c.$sort $dir
      LIMIT $perPage OFFSET {$pager['offset']}"
);
$stmt->execute($params);
$courses=$stmt->fetchAll();

$allCats=$pdo->query("SELECT id,name FROM categories WHERE type='course' OR type='blog' ORDER BY name")->fetchAll();

function csortUrl(string $col,string $cur,string $d,array $x=[]): string {
    return '?'.http_build_query(array_merge($x,['sort'=>$col,'dir'=>$col===$cur&&$d==='DESC'?'ASC':'DESC']));
}
$ux=array_filter(compact('search','status','level','catId','free'),fn($v)=>$v!==''&&$v!==0);
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<style>
.crs-summary{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:22px;}
.csm{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;
     text-decoration:none;display:block;cursor:pointer;transition:all var(--transition);position:relative;overflow:hidden;}
.csm:hover,.csm.af{border-color:var(--accent);}
.csm.af{background:var(--accent-glow);}
.csm::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--accent);transform:scaleX(0);transition:.2s;}
.csm:hover::after,.csm.af::after{transform:scaleX(1);}
.csv{font-size:20px;font-weight:800;color:var(--text);line-height:1.1;}
.csl{font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;}
.ct{width:56px;height:38px;object-fit:cover;border-radius:5px;border:1px solid var(--border);display:block;}
.ct-ph{width:56px;height:38px;background:var(--border);border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.pp-wrap{display:flex;align-items:center;gap:6px;cursor:pointer;}
.pp-wrap input{display:none;}
.pp{width:32px;height:18px;border-radius:9px;background:var(--border);position:relative;transition:.2s;flex-shrink:0;}
.pp::after{content:'';position:absolute;width:12px;height:12px;background:#fff;border-radius:50%;top:3px;left:3px;transition:.2s;}
.pp-wrap input:checked~.pp{background:var(--accent);}
.pp-wrap input:checked~.pp::after{left:17px;}
.sl{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:3px;}
.sl:hover{color:var(--accent);}
.sa{font-size:9px;color:var(--text-dim);} .sa.on{color:var(--accent);}
.star{color:#f59e0b;font-size:11px;}
@media(max-width:1100px){.crs-summary{grid-template-columns:repeat(3,1fr);}}
@media(max-width:600px){.crs-summary{grid-template-columns:repeat(2,1fr);}}
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
          <h1 class="page-title">Courses</h1>
          <p class="page-subtitle"><?= number_format($sc['published']) ?> published · <?= number_format($sc['draft']) ?> drafts · <?= number_format($sc['enrollments']) ?> total enrollments</p>
        </div>
        <div class="page-header-actions">
          <button onclick="openImportModal()" class="btn" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import from PDF
          </button>
          <a href="<?= SITE_URL ?>/admin/course-edit.php" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Course
          </a>
        </div>
      </div>

      <?= flashHtml() ?>

      <!-- Summary Cards -->
      <div class="crs-summary">
        <a href="<?= SITE_URL ?>/admin/courses.php" class="csm <?= !$search&&!$status&&!$level&&!$catId&&$free===''?'af':'' ?>">
          <div class="csv"><?= number_format($sc['total']) ?></div>
          <div class="csl">🎓 All Courses</div>
        </a>
        <a href="?status=published" class="csm <?= $status==='published'?'af':'' ?>">
          <div class="csv" style="color:#22c55e;"><?= number_format($sc['published']) ?></div>
          <div class="csl">✓ Published</div>
        </a>
        <a href="?status=draft" class="csm <?= $status==='draft'?'af':'' ?>">
          <div class="csv" style="color:#f59e0b;"><?= number_format($sc['draft']) ?></div>
          <div class="csl">✎ Drafts</div>
        </a>
        <a href="?free=1" class="csm <?= $free==='1'?'af':'' ?>">
          <div class="csv" style="color:#22c55e;"><?= number_format($sc['free']) ?></div>
          <div class="csl">🆓 Free</div>
        </a>
        <a href="?free=0" class="csm <?= $free==='0'?'af':'' ?>">
          <div class="csv" style="color:var(--accent);"><?= number_format($sc['total']-$sc['free']) ?></div>
          <div class="csl">💰 Paid</div>
        </a>
        <a href="?sort=enrollment_count&dir=DESC" class="csm">
          <div class="csv"><?= $sc['enrollments']>=1000?round($sc['enrollments']/1000,1).'k':$sc['enrollments'] ?></div>
          <div class="csl">👥 Enrollments</div>
        </a>
      </div>

      <!-- Toolbar -->
      <div class="card" style="padding:0;border-radius:var(--radius-lg) var(--radius-lg) 0 0;border-bottom:none;">
        <form id="filterForm" method="GET" action="<?= SITE_URL ?>/admin/courses.php"
              style="display:flex;align-items:center;gap:10px;padding:12px 18px;flex-wrap:wrap;">
          <div class="search-input-wrap" style="flex:1;min-width:180px;">
            <span class="si"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
            <input type="text" name="search" id="cSearch" placeholder="Search courses…" value="<?= e($search) ?>" autocomplete="off">
          </div>
          <select name="status" class="form-control" style="width:120px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
            <option value="draft"     <?= $status==='draft'    ?'selected':'' ?>>Draft</option>
          </select>
          <select name="level" class="form-control" style="width:130px;" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <option value="beginner"     <?= $level==='beginner'    ?'selected':'' ?>>Beginner</option>
            <option value="intermediate" <?= $level==='intermediate'?'selected':'' ?>>Intermediate</option>
            <option value="advanced"     <?= $level==='advanced'    ?'selected':'' ?>>Advanced</option>
            <option value="all"          <?= $level==='all'         ?'selected':'' ?>>All Levels</option>
          </select>
          <select name="free" class="form-control" style="width:100px;" onchange="this.form.submit()">
            <option value="">All Pricing</option>
            <option value="1" <?= $free==='1'?'selected':'' ?>>Free</option>
            <option value="0" <?= $free==='0'?'selected':'' ?>>Paid</option>
          </select>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
          <input type="hidden" name="dir"  value="<?= e($dir) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
          <?php if($search||$status||$level||$catId||$free!==''): ?>
            <a href="<?= SITE_URL ?>/admin/courses.php" class="btn btn-ghost btn-sm">✕ Clear</a>
          <?php endif; ?>
          <span style="margin-left:auto;font-size:12px;color:var(--text-muted);"><?= number_format($total) ?> result<?= $total!==1?'s':'' ?></span>
        </form>
      </div>

      <!-- Table -->
      <div class="card" style="border-radius:0 0 var(--radius-lg) var(--radius-lg);border-top:none;padding:0;">
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:40px;"><input type="checkbox" id="selAll" style="cursor:pointer;accent-color:var(--accent);"></th>
                <th style="width:66px;">Cover</th>
                <?php
                $hdr=[['k'=>'title','l'=>'Course'],['k'=>'','l'=>'Category'],
                      ['k'=>'','l'=>'Level'],['k'=>'','l'=>'Price'],
                      ['k'=>'enrollment_count','l'=>'Enrolled'],['k'=>'rating','l'=>'Rating'],
                      ['k'=>'duration_hours','l'=>'Duration'],['k'=>'total_lessons','l'=>'Lessons'],
                      ['k'=>'is_featured','l'=>'Featured'],['k'=>'is_published','l'=>'Status']];
                foreach($hdr as $h): $ia=$sort===$h['k']&&$h['k'];
                ?>
                <th><?php if($h['k']): ?>
                  <a class="sl" href="<?= csortUrl($h['k'],$sort,$dir,array_filter($ux)) ?>"><?= $h['l'] ?>
                    <span class="sa <?= $ia?'on':'' ?>"><?= $ia?($dir==='DESC'?'▼':'▲'):'⇅' ?></span></a>
                <?php else: echo $h['l']; endif; ?></th>
                <?php endforeach; ?>
                <th style="text-align:right;width:110px;">Actions</th>
              </tr>
            </thead>
            <tbody id="crsBody">
              <?php foreach($courses as $c): ?>
              <?php
                $lvlColor=match($c['level']){'beginner'=>'#22c55e','intermediate'=>'#f59e0b','advanced'=>'#ef4444',default=>'var(--text-muted)'};
              ?>
              <tr data-id="<?= $c['id'] ?>">
                <td><input type="checkbox" class="cb-row" value="<?= $c['id'] ?>" style="cursor:pointer;accent-color:var(--accent);"></td>
                <td>
                  <?php if($c['thumbnail_path']): ?>
                    <img src="<?= e($c['thumbnail_path']) ?>" class="ct" alt="">
                  <?php else: ?>
                    <div class="ct-ph">🎓</div>
                  <?php endif; ?>
                </td>
                <td style="max-width:250px;">
                  <div style="font-weight:600;font-size:13px;"><?= e(truncate($c['title'],44)) ?>
                    <?php if($c['is_featured']): ?><span style="color:var(--accent);font-size:10px;">⭐</span><?php endif; ?>
                  </div>
                  <div style="font-size:10px;color:var(--text-dim);font-family:monospace;">/courses/<?= e($c['slug']) ?></div>
                  <?php if($c['instructor_name']): ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">👤 <?= e($c['instructor_name']) ?></div>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-muted" style="font-size:10px;"><?= e($c['category_name']??'—') ?></span></td>
                <td><span style="font-size:11px;color:<?= $lvlColor ?>;font-weight:600;"><?= ucfirst($c['level']??'all') ?></span></td>
                <td>
                  <?php if($c['is_free']): ?>
                    <span class="badge badge-success" style="font-size:10px;">Free</span>
                  <?php else: ?>
                    <div style="font-size:12px;font-weight:700;color:var(--accent);">₹<?= number_format((float)$c['price']) ?></div>
                    <?php if($c['discount_price']): ?>
                      <div style="font-size:10px;color:#22c55e;">↓ ₹<?= number_format((float)$c['discount_price']) ?></div>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= number_format($c['enrollment_count']) ?></td>
                <td style="font-size:11px;">
                  <?php if($c['rating']): ?>
                    <span class="star">★</span> <strong><?= number_format((float)$c['rating'],1) ?></strong>
                  <?php else: ?>
                    <span style="color:var(--text-dim);">—</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?= $c['duration_hours']?$c['duration_hours'].'h':'—' ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= $c['total_lessons']?number_format($c['total_lessons']):0 ?></td>
                <td>
                  <label class="pp-wrap" title="Toggle featured">
                    <input type="checkbox" <?= $c['is_featured']?'checked':'' ?> onchange="toggleFeat(<?= $c['id'] ?>,this.checked,this)">
                    <div class="pp"></div>
                  </label>
                </td>
                <td>
                  <label class="pp-wrap" title="Toggle publish">
                    <input type="checkbox" <?= $c['is_published']?'checked':'' ?> onchange="togglePub(<?= $c['id'] ?>,this.checked,this)">
                    <div class="pp"></div>
                    <span style="font-size:11px;font-weight:600;color:<?= $c['is_published']?'var(--success)':'var(--text-muted)' ?>;" id="pub-lbl-<?= $c['id'] ?>">
                      <?= $c['is_published']?'Live':'Draft' ?>
                    </span>
                  </label>
                </td>
                <td>
                  <div style="display:flex;gap:4px;justify-content:flex-end;">
                    <a href="<?= SITE_URL ?>/admin/course-edit.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </a>
                    <a href="<?= SITE_URL ?>/admin/course-edit.php?id=<?= $c['id'] ?>&tab=chapters" class="btn btn-ghost btn-sm btn-icon" title="Manage Chapters">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </a>
                    <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="delCourse(<?= $c['id'] ?>,'<?= e(addslashes($c['title'])) ?>')">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($courses)): ?>
              <tr><td colspan="12">
                <div class="empty-state">
                  <div class="empty-icon">🎓</div>
                  <p class="empty-title">No courses found</p>
                  <p class="empty-desc"><a href="<?= SITE_URL ?>/admin/course-edit.php" style="color:var(--accent);">Create your first course →</a></p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php
        $queryParams = array_filter(compact('search', 'status', 'level', 'catId', 'free', 'sort', 'dir'),
            fn($v) => $v !== '' && $v !== 0);
        require __DIR__ . '/includes/pagination.php';
        ?>
      </div>

    </main>
  </div>
</div>
<script src="<?= SITE_URL ?>/admin/assets/admin.js?v=2"></script>
<script>
document.getElementById('selAll').addEventListener('change',e=>
  document.querySelectorAll('.cb-row').forEach(cb=>cb.checked=e.target.checked));

async function togglePub(id,pub,cb){
  const lbl=document.getElementById(`pub-lbl-${id}`);
  const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'toggle_publish',id,value:pub?1:0});
  if(r.success){Toast.success('Updated',pub?'Course published.':'Set to draft.');
    if(lbl){lbl.textContent=pub?'Live':'Draft';lbl.style.color=pub?'var(--success)':'var(--text-muted)';}}
  else{Toast.error('Error',r.error);cb.checked=!pub;}
}
async function toggleFeat(id,val,cb){
  const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'toggle_featured',id,value:val?1:0});
  if(r.success) Toast.success('Updated',val?'⭐ Marked featured.':'Removed from featured.');
  else{Toast.error('Error',r.error);cb.checked=!val;}
}
async function delCourse(id,title){
  Modal.confirm(`Delete "<strong>${escHtml(title)}</strong>"?<br><small style="color:var(--text-muted)">All chapters and lessons will be removed.</small>`,async()=>{
    const r=await apiPost('<?= SITE_URL ?>/api/admin/courses.php',{action:'delete',id});
    if(r.success){document.querySelector(`[data-id="${id}"]`)?.remove();Toast.success('Deleted','Course removed.');}
    else Toast.error('Error',r.error);
  },{title:'Delete Course',confirmText:'Delete',type:'danger'});
}
let st;
document.getElementById('cSearch').addEventListener('input',function(){
  clearTimeout(st);st=setTimeout(()=>document.getElementById('filterForm').submit(),500);
});
</script>
<!-- ════ PDF IMPORT MODAL ════════════════════════════════════ -->
<div id="pdf-import-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9000;align-items:center;justify-content:center;">
  <div style="background:#1a1a2e;border:1px solid #333;border-radius:16px;padding:32px;max-width:560px;width:95%;max-height:85vh;overflow-y:auto;position:relative;">
    <button onclick="closeImportModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;color:#aaa;font-size:1.4rem;cursor:pointer;line-height:1;">&#x2715;</button>

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
      <div style="width:40px;height:40px;background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      </div>
      <div>
        <h2 style="margin:0;color:#fff;font-size:1.2rem;">Import Course from PDF</h2>
        <p style="margin:0;color:#aaa;font-size:0.85rem;">Auto-generate complete course from a PDF file</p>
      </div>
    </div>

    <!-- Config row -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
      <div>
        <label style="color:#ccc;font-size:12px;display:block;margin-bottom:4px;">Price (₹ INR)</label>
        <input id="pi-price-inr" type="number" value="120" min="0" style="width:100%;padding:8px;background:#0d0d1a;border:1px solid #333;border-radius:6px;color:#fff;font-size:13px;">
      </div>
      <div>
        <label style="color:#ccc;font-size:12px;display:block;margin-bottom:4px;">Price ($USD)</label>
        <input id="pi-price-usd" type="number" value="2" min="0" style="width:100%;padding:8px;background:#0d0d1a;border:1px solid #333;border-radius:6px;color:#fff;font-size:13px;">
      </div>
      <div>
        <label style="color:#ccc;font-size:12px;display:block;margin-bottom:4px;">Status</label>
        <select id="pi-status" style="width:100%;padding:8px;background:#0d0d1a;border:1px solid #333;border-radius:6px;color:#fff;font-size:13px;">
          <option value="draft" selected>Draft</option>
          <option value="published">Published</option>
        </select>
      </div>
    </div>

    <!-- PDF list -->
    <div style="border:1px solid #2a2a3e;border-radius:10px;overflow:hidden;">
      <div style="background:#0d0d1a;padding:12px 16px;border-bottom:1px solid #2a2a3e;display:flex;justify-content:space-between;align-items:center;">
        <span style="color:#ccc;font-size:13px;font-weight:600;">📁 Detected PDFs in <code style="color:#7c3aed;">private/courses/</code></span>
        <button onclick="scanPdfs()" id="scan-btn" style="background:#7c3aed;color:#fff;border:none;padding:5px 12px;border-radius:6px;cursor:pointer;font-size:12px;">&#x21bb; Scan</button>
      </div>
      <div id="pi-pdf-list" style="padding:12px;min-height:80px;">
        <p style="color:#666;text-align:center;padding:20px 0;">Click Scan to detect available PDFs...</p>
      </div>
    </div>

    <!-- Progress -->
    <div id="pi-progress" style="display:none;margin-top:16px;">
      <div style="background:#0d0d1a;border-radius:8px;height:6px;overflow:hidden;">
        <div id="pi-progress-bar" style="background:linear-gradient(90deg,#7c3aed,#4f46e5);height:100%;width:0%;transition:width 0.4s;"></div>
      </div>
      <p id="pi-progress-text" style="color:#aaa;font-size:12px;margin-top:6px;text-align:center;">Importing...</p>
    </div>

    <!-- Result -->
    <div id="pi-result" style="display:none;margin-top:16px;padding:14px;border-radius:8px;"></div>
  </div>
</div>

<script>
async function openImportModal() {
  document.getElementById('pdf-import-overlay').style.display = 'flex';
  await scanPdfs();
}
function closeImportModal() {
  document.getElementById('pdf-import-overlay').style.display = 'none';
  document.getElementById('pi-result').style.display = 'none';
  document.getElementById('pi-progress').style.display = 'none';
}
async function scanPdfs() {
  const btn  = document.getElementById('scan-btn');
  const list = document.getElementById('pi-pdf-list');
  btn.textContent = 'Scanning...';
  btn.disabled    = true;
  list.innerHTML  = '<p style="color:#666;text-align:center;padding:20px 0;">Scanning...</p>';
  try {
    const r = await fetch('/api/admin/import_pdf.php', {
      method:'POST',
      body: new URLSearchParams({action:'scan'})
    });
    const d = await r.json();
    if (!d.success) { list.innerHTML = `<p style="color:#f44;padding:10px;">${d.error}</p>`; return; }
    if (d.pdfs.length === 0) {
      list.innerHTML = '<p style="color:#888;text-align:center;padding:20px;">No unimported PDFs found in private/courses/</p>';
      return;
    }
    list.innerHTML = d.pdfs.map(p => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid #2a2a3e;border-radius:8px;margin-bottom:8px;background:#0d0d1a;">
        <div>
          <div style="color:#fff;font-size:13px;font-weight:600;">📄 ${escHtml(p.filename)}</div>
          <div style="color:#888;font-size:11px;margin-top:2px;">${p.size_kb} KB</div>
        </div>
        <button onclick="importPdf('${escHtml(p.filename).replace(/'/g,"\\'")}')"
          style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">
          ⚡ Import
        </button>
      </div>`).join('');
  } catch(e) {
    list.innerHTML = '<p style="color:#f44;padding:10px;">Network error. Please try again.</p>';
  } finally {
    btn.textContent = '↻ Scan';
    btn.disabled    = false;
  }
}
async function importPdf(filename) {
  const priceInr = document.getElementById('pi-price-inr').value;
  const priceUsd = document.getElementById('pi-price-usd').value;
  const status   = document.getElementById('pi-status').value;
  const prog     = document.getElementById('pi-progress');
  const bar      = document.getElementById('pi-progress-bar');
  const txt      = document.getElementById('pi-progress-text');
  const res      = document.getElementById('pi-result');

  prog.style.display = 'block';
  res.style.display  = 'none';
  bar.style.width    = '20%';
  txt.textContent    = 'Reading PDF and generating course data...';

  // Animate progress bar
  let pct = 20;
  const iv = setInterval(() => {
    pct = Math.min(85, pct + Math.random() * 12);
    bar.style.width = pct + '%';
    if (pct > 40) txt.textContent = 'Analysing curriculum structure...';
    if (pct > 65) txt.textContent = 'Saving course to database...';
  }, 500);

  try {
    const r = await fetch('/api/admin/import_pdf.php', {
      method:'POST',
      body: new URLSearchParams({action:'import', filename, price_inr:priceInr, price_usd:priceUsd, status})
    });
    const d = await r.json();
    clearInterval(iv);
    bar.style.width = '100%';
    txt.textContent = 'Done!';
    setTimeout(() => prog.style.display = 'none', 800);

    if (d.success) {
      res.style.display    = 'block';
      res.style.background = 'rgba(34,197,94,.12)';
      res.style.border     = '1px solid #22c55e';
      res.innerHTML = `
        <div style="color:#22c55e;font-size:1.4rem;margin-bottom:6px;">&#10003; Import Successful!</div>
        <div style="color:#fff;font-weight:600;margin-bottom:10px;">${escHtml(d.title)}</div>
        <div style="display:flex;gap:10px;">
          <a href="/admin/course-edit.php?id=${d.course_id}" style="background:#22c55e;color:#000;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:700;font-size:13px;">✎ Edit Course</a>
          <button onclick="closeImportModal();location.reload()" style="background:#333;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;">Close & Refresh</button>
        </div>`;
      await scanPdfs(); // refresh list
    } else {
      res.style.display    = 'block';
      res.style.background = 'rgba(244,67,54,.12)';
      res.style.border     = '1px solid #f44336';
      res.innerHTML = `<div style="color:#f44336;">&#x2715; Error: ${escHtml(d.error)}</div>`;
    }
  } catch(e) {
    clearInterval(iv);
    res.style.display    = 'block';
    res.style.background = 'rgba(244,67,54,.12)';
    res.style.border     = '1px solid #f44336';
    res.innerHTML = '<div style="color:#f44336;">Network error. Please try again.</div>';
  }
}
function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
</body>
</html>
