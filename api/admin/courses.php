<?php
/**
 * CodeByTushu — Courses Admin API v2
 * Actions: create, update, delete, toggle_publish, toggle_featured,
 *          chapter_save, chapter_delete, chapter_reorder,
 *          lesson_save, lesson_delete, lesson_reorder
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';
Auth::boot();
if(!Auth::isAdmin()) jsonError('Forbidden.',403);
requireCsrf($_POST['csrf_token']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));

$pdo    = db();
$action = post('action');
$root   = rtrim(realpath(__DIR__.'/../../'),DIRECTORY_SEPARATOR);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   GET LESSON (for edit modal — no CSRF needed, GET only)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'get_lesson' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $lid = (int)($_GET['lesson_id'] ?? 0);
    if (!$lid) jsonError('lesson_id required.', 400);
    $s = $pdo->prepare('SELECT * FROM course_lessons WHERE id=? LIMIT 1');
    $s->execute([$lid]); $l = $s->fetch();
    if (!$l) jsonError('Lesson not found.', 404);
    jsonSuccess($l);
}

// (CSRF already verified above for all POST requests)

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TOGGLE PUBLISH
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='toggle_publish'){
    $id=(int)post('id'); $val=(int)post('value')?1:0;
    if(!$id) jsonError('ID required.',400);
    $pa=$val?date('Y-m-d H:i:s'):null;
    $pdo->prepare('UPDATE courses SET is_published=?,published_at=?,updated_at=NOW() WHERE id=?')->execute([$val,$pa,$id]);
    jsonSuccess(null,$val?'Course published.':'Set to draft.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TOGGLE FEATURED
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='toggle_featured'){
    $id=(int)post('id'); $val=(int)post('value')?1:0;
    if(!$id) jsonError('ID required.',400);
    $pdo->prepare('UPDATE courses SET is_featured=?,updated_at=NOW() WHERE id=?')->execute([$val,$id]);
    jsonSuccess(null,$val?'Marked featured.':'Removed from featured.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   DELETE COURSE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='delete'){
    $id=(int)post('id'); if(!$id) jsonError('ID required.',400);
    $row=$pdo->prepare('SELECT thumbnail_path FROM courses WHERE id=? LIMIT 1');
    $row->execute([$id]); $c=$row->fetch();
    if($c&&$c['thumbnail_path']) @unlink($root.$c['thumbnail_path']);
    // Cascade deletes chapters+lessons via FK
    $pdo->prepare('DELETE FROM courses WHERE id=?')->execute([$id]);
    jsonSuccess(null,'Course deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CHAPTER SAVE (create or update)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='chapter_save'){
    $cid=(int)post('chapter_id');
    $courseId=(int)post('course_id'); if(!$courseId) jsonError('course_id required.',400);
    $title=sanitize(post('title')); if(!$title) jsonError('Chapter title required.',422);
    $desc=post('description')?:null;
    $sort=(int)post('sort_order');
    if($cid){
        $pdo->prepare('UPDATE course_chapters SET title=?,description=?,sort_order=? WHERE id=? AND course_id=?')
            ->execute([$title,$desc,$sort,$cid,$courseId]);
        jsonSuccess(['chapter_id'=>$cid],'Chapter updated.');
    } else {
        // Get next sort order using a proper fetch (not bool from execute)
        $s=$pdo->prepare('SELECT COALESCE(MAX(sort_order)+1,0) FROM course_chapters WHERE course_id=?');
        $s->execute([$courseId]); $ns=(int)$s->fetchColumn();
        $pdo->prepare('INSERT INTO course_chapters (course_id,title,description,sort_order) VALUES (?,?,?,?)')
            ->execute([$courseId,$title,$desc,$ns]);
        jsonSuccess(['chapter_id'=>(int)$pdo->lastInsertId()],'Chapter created.');
    }
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CHAPTER DELETE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='chapter_delete'){
    $id=(int)post('chapter_id'); if(!$id) jsonError('chapter_id required.',400);
    // Delete lesson files first
    $ls=$pdo->prepare('SELECT video_path,pdf_path FROM course_lessons WHERE chapter_id=?');
    $ls->execute([$id]);
    foreach($ls->fetchAll() as $l){
        if($l['video_path']) @unlink($root.$l['video_path']);
        if($l['pdf_path'])   @unlink($root.$l['pdf_path']);
    }
    $pdo->prepare('DELETE FROM course_chapters WHERE id=?')->execute([$id]);
    jsonSuccess(null,'Chapter deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CHAPTER REORDER
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='chapter_reorder'){
    $items=$_POST['items']??[];
    $st=$pdo->prepare('UPDATE course_chapters SET sort_order=? WHERE id=?');
    foreach($items as $i=>$cid){ $st->execute([$i,(int)$cid]); }
    jsonSuccess(null,'Reordered.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   LESSON SAVE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='lesson_save'){
    $lid=(int)post('lesson_id');
    $chapterId=(int)post('chapter_id'); if(!$chapterId) jsonError('chapter_id required.',400);
    $courseId =(int)post('course_id');  if(!$courseId)  jsonError('course_id required.',400);
    $title=sanitize(post('title'));     if(!$title) jsonError('Lesson title required.',422);
    $type=post('content_type');
    if(!in_array($type,['video','pdf','text','quiz','zip'],true)) $type='video';
    $dur=(int)post('duration_seconds')?:null;
    $isPrev=(int)post('is_preview')?1:0;
    $isAct =(int)post('is_active')===0?0:1;
    $desc=post('description')?:null;
    $textContent=$_POST['text_content']??null;
    $sort=(int)post('sort_order');

    // Current paths for replacement
    $videoPath=null; $pdfPath=null;
    if($lid){
        $ex=$pdo->prepare('SELECT video_path,pdf_path FROM course_lessons WHERE id=? LIMIT 1');
        $ex->execute([$lid]); $exr=$ex->fetch();
        $videoPath=$exr['video_path']??null; $pdfPath=$exr['pdf_path']??null;
    }

    // Video upload — server-side MIME validation
    if(!empty($_FILES['video_file']['tmp_name'])){
        $vf=$_FILES['video_file'];
        $vMime=mime_content_type($vf['tmp_name'])?:'application/octet-stream';
        $vAllowed=['video/mp4','video/webm','video/quicktime','video/x-msvideo','video/avi'];
        if(!in_array($vMime,$vAllowed,true)) jsonError('Video must be MP4, WebM, or MOV.',422);
        if($vf['size']>500*1024*1024) jsonError('Video must be under 500MB.',422);
        $vDir=$root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'courses'.DIRECTORY_SEPARATOR.'videos';
        if(!is_dir($vDir)){ mkdir($vDir,0755,true); file_put_contents($vDir.DIRECTORY_SEPARATOR.'.htaccess',"Options -Indexes\nphp_flag engine off\n"); }
        $vExt=pathinfo($vf['name'],PATHINFO_EXTENSION)?:'mp4';
        $vName='lesson-'.$courseId.'-'.$chapterId.'-'.bin2hex(random_bytes(6)).'.'.$vExt;
        if(move_uploaded_file($vf['tmp_name'],$vDir.DIRECTORY_SEPARATOR.$vName)){
            if($videoPath) @unlink($root.$videoPath);
            $videoPath='/uploads/courses/videos/'.$vName;
        }
    }

    // PDF upload — server-side MIME validation
    if(!empty($_FILES['pdf_file']['tmp_name'])){
        $pf=$_FILES['pdf_file'];
        $pMime=mime_content_type($pf['tmp_name'])?:'application/octet-stream';
        if($pMime!=='application/pdf') jsonError('File must be a real PDF document.',422);
        if($pf['size']>50*1024*1024) jsonError('PDF must be under 50MB.',422);
        $pDir=$root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'courses'.DIRECTORY_SEPARATOR.'pdfs';
        if(!is_dir($pDir)){ mkdir($pDir,0755,true); file_put_contents($pDir.DIRECTORY_SEPARATOR.'.htaccess',"Options -Indexes\nphp_flag engine off\n"); }
        $pName='lesson-'.$courseId.'-'.$chapterId.'-'.bin2hex(random_bytes(6)).'.pdf';
        if(move_uploaded_file($pf['tmp_name'],$pDir.DIRECTORY_SEPARATOR.$pName)){
            if($pdfPath) @unlink($root.$pdfPath);
            $pdfPath='/uploads/courses/pdfs/'.$pName;
        }
    }

    $data=['chapter_id'=>$chapterId,'course_id'=>$courseId,'title'=>$title,
           'description'=>$desc,'content_type'=>$type,'text_content'=>$textContent,
           'duration_seconds'=>$dur,'sort_order'=>$sort,'is_preview'=>$isPrev,'is_active'=>$isAct];
    if($videoPath!==null) $data['video_path']=$videoPath;
    if($pdfPath  !==null) $data['pdf_path']  =$pdfPath;

    if($lid){
        $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
        $v=array_values($data); $v[]=$lid;
        $pdo->prepare("UPDATE course_lessons SET $sets,updated_at=NOW() WHERE id=?")->execute($v);
        // Recalculate course stats
        updateCourseStats($pdo,$courseId);
        jsonSuccess(['lesson_id'=>$lid],'Lesson updated.');
    } else {
        // Auto sort
        $ss=$pdo->prepare('SELECT COALESCE(MAX(sort_order)+1,0) FROM course_lessons WHERE chapter_id=?');
        $ss->execute([$chapterId]); $data['sort_order']=(int)$ss->fetchColumn();
        $cols=implode(',',array_keys($data)); $ph=implode(',',array_fill(0,count($data),'?'));
        $pdo->prepare("INSERT INTO course_lessons ($cols,created_at) VALUES ($ph,NOW())")->execute(array_values($data));
        $nlid=(int)$pdo->lastInsertId();
        updateCourseStats($pdo,$courseId);
        jsonSuccess(['lesson_id'=>$nlid],'Lesson created.');
    }
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   LESSON DELETE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if($action==='lesson_delete'){
    $id=(int)post('lesson_id'); if(!$id) jsonError('lesson_id required.',400);
    $r=$pdo->prepare('SELECT course_id,video_path,pdf_path FROM course_lessons WHERE id=? LIMIT 1');
    $r->execute([$id]); $l=$r->fetch();
    if($l){
        if($l['video_path']) @unlink($root.$l['video_path']);
        if($l['pdf_path'])   @unlink($root.$l['pdf_path']);
        $pdo->prepare('DELETE FROM course_lessons WHERE id=?')->execute([$id]);
        updateCourseStats($pdo,(int)$l['course_id']);
    }
    jsonSuccess(null,'Lesson deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CREATE / UPDATE COURSE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if(!in_array($action,['create','update'],true)) jsonError('Unknown action.',400);

$id=(int)post('id'); $isNew=($action==='create');
$title=sanitize(post('title')); if(!$title) jsonError('Title required.',422,'title');
$slug=preg_replace('/[^a-z0-9-]/','',strtolower(post('slug'))); if(!$slug) jsonError('Slug required.',422,'slug');
$shortDesc=sanitize(post('short_description')); if(!$shortDesc) jsonError('Short description required.',422,'short_description');

$level=post('level');
if(!in_array($level,['beginner','intermediate','advanced','all'],true)) $level='all';
$isPublished=(int)($_POST['is_published']??0);

// Thumbnail upload — server-side MIME validation
$thumbPath=null;
if(!$isNew){
    $ex=$pdo->prepare('SELECT thumbnail_path FROM courses WHERE id=? LIMIT 1'); $ex->execute([$id]);
    $thumbPath=$ex->fetchColumn()?:null;
}
if(!empty($_FILES['thumbnail']['tmp_name'])){
    $f=$_FILES['thumbnail'];
    $fMime=mime_content_type($f['tmp_name'])?:'application/octet-stream';
    $ok=['image/jpeg','image/png','image/webp'];
    if(!in_array($fMime,$ok,true)) jsonError('Thumbnail must be a real JPG, PNG, or WebP image.',422);
    if($f['size']>3*1024*1024) jsonError('Thumbnail must be under 3MB.',422);
    $dir=$root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'courses'.DIRECTORY_SEPARATOR.'thumbs';
    if(!is_dir($dir)){ mkdir($dir,0755,true); file_put_contents($dir.DIRECTORY_SEPARATOR.'.htaccess',"Options -Indexes\nphp_flag engine off\n"); }
    $ext=match($fMime){'image/png'=>'png','image/webp'=>'webp',default=>'jpg'};
    $fn=$slug.'-'.bin2hex(random_bytes(6)).'.'.$ext;
    if(move_uploaded_file($f['tmp_name'],$dir.DIRECTORY_SEPARATOR.$fn)){
        if($thumbPath) @unlink($root.$thumbPath);
        $thumbPath='/uploads/courses/thumbs/'.$fn;
    }
}

// Preview video upload — server-side MIME validation
$previewVideo=post('preview_video_url')?:null;
if(!empty($_FILES['preview_video']['tmp_name'])){
    $pv=$_FILES['preview_video'];
    $pvMime=mime_content_type($pv['tmp_name'])?:'application/octet-stream';
    $vAllowed=['video/mp4','video/webm','video/quicktime'];
    if(in_array($pvMime,$vAllowed,true)&&$pv['size']<=200*1024*1024){
        $pvDir=$root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'courses'.DIRECTORY_SEPARATOR.'previews';
        if(!is_dir($pvDir)){ mkdir($pvDir,0755,true); file_put_contents($pvDir.DIRECTORY_SEPARATOR.'.htaccess',"Options -Indexes\nphp_flag engine off\n"); }
        $pvName=$slug.'-preview-'.bin2hex(random_bytes(6)).'.'.pathinfo($pv['name'],PATHINFO_EXTENSION);
        if(move_uploaded_file($pv['tmp_name'],$pvDir.DIRECTORY_SEPARATOR.$pvName))
            $previewVideo='/uploads/courses/previews/'.$pvName;
    }
}

$data=[
    'category_id'       =>(int)post('category_id')?:null,
    'instructor_id'     =>Auth::id(),
    'title'             =>$title,
    'slug'              =>$slug,
    'short_description' =>$shortDesc,
    'description'       =>$_POST['description']??null,
    'level'             =>$level,
    'language'          =>post('language')?:'Hindi',
    'requirements'      =>post('requirements')?:null,
    'what_you_learn'    =>post('what_you_learn')?:null,
    'price'             =>post('price')?(float)post('price'):0.00,
    'discount_price'    =>post('discount_price')?(float)post('discount_price'):null,
    'currency'          =>post('currency')?:'INR',
    'is_free'           =>isset($_POST['is_free'])?1:0,
    'is_featured'       =>isset($_POST['is_featured'])?1:0,
    'is_published'      =>$isPublished,
    'duration_hours'    =>post('duration_hours')?(float)post('duration_hours'):null,
    'meta_description'  =>post('meta_description')?:null,
    'download_file_path'=>post('download_file_path')?:null,
];
if($thumbPath!==null) $data['thumbnail_path']=$thumbPath;
if($previewVideo)     $data['preview_video']=$previewVideo;

if($isNew){
    $dup=$pdo->prepare('SELECT id FROM courses WHERE slug=? LIMIT 1'); $dup->execute([$slug]);
    if($dup->fetch()) jsonError('Slug already taken.',409,'slug');
    if($isPublished) $data['published_at']=date('Y-m-d H:i:s');
    $cols=implode(',',array_keys($data)); $ph=implode(',',array_fill(0,count($data),'?'));
    $pdo->prepare("INSERT INTO courses ($cols,created_at,updated_at) VALUES ($ph,NOW(),NOW())")->execute(array_values($data));
    jsonSuccess(['id'=>(int)$pdo->lastInsertId()],'Course created.');
} else {
    if(!$id) jsonError('ID required.',400);
    $dup=$pdo->prepare('SELECT id FROM courses WHERE slug=? AND id!=? LIMIT 1'); $dup->execute([$slug,$id]);
    if($dup->fetch()) jsonError('Slug already taken.',409,'slug');
    $prev=$pdo->prepare('SELECT published_at FROM courses WHERE id=? LIMIT 1');
    $prev->execute([$id]); $pr=$prev->fetch();
    if($isPublished&&empty($pr['published_at'])) $data['published_at']=date('Y-m-d H:i:s');
    $sets=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
    $v=array_values($data); $v[]=$id;
    $pdo->prepare("UPDATE courses SET $sets,updated_at=NOW() WHERE id=?")->execute($v);
    jsonSuccess(['id'=>$id],'Course updated.');
}

function updateCourseStats(PDO $pdo,int $courseId): void {
    $ls=$pdo->prepare('SELECT COUNT(*),COALESCE(SUM(duration_seconds),0) FROM course_lessons WHERE course_id=? AND is_active=1');
    $ls->execute([$courseId]); [$lc,$secs]=$ls->fetch(PDO::FETCH_NUM);
    $hrs=round($secs/3600,1);
    $pdo->prepare('UPDATE courses SET total_lessons=?,duration_hours=?,updated_at=NOW() WHERE id=?')->execute([(int)$lc,$hrs?:null,$courseId]);
}
