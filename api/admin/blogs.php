<?php
/**
 * CodeByTushu — Blog Admin API v2
 * POST /api/admin/blogs.php
 *
 * Actions: create, update, delete, toggle_publish, toggle_featured
 * Handles: thumbnail upload/replacement, tags (blog_tag_map), seo_title,
 *          card_title, cover_image_path, author_id.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden.', 403);
requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$pdo    = db();
$action = post('action');

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TOGGLE PUBLISH
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'toggle_publish') {
    $id  = (int)post('id');
    $val = (int)post('value') ? 1 : 0;
    if (!$id) jsonError('ID required.', 400);
    $ts = $val ? date('Y-m-d H:i:s') : null;
    $pdo->prepare('UPDATE blog_articles SET is_published=?, published_at=?, updated_at=NOW() WHERE id=?')
        ->execute([$val, $ts, $id]);
    jsonSuccess(null, $val ? 'Post published.' : 'Set to draft.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TOGGLE FEATURED
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'toggle_featured') {
    $id  = (int)post('id');
    $val = (int)post('value') ? 1 : 0;
    if (!$id) jsonError('ID required.', 400);
    $pdo->prepare('UPDATE blog_articles SET is_featured=?, updated_at=NOW() WHERE id=?')->execute([$val, $id]);
    jsonSuccess(null, $val ? 'Marked featured.' : 'Removed from featured.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   DELETE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'delete') {
    $id = (int)post('id');
    if (!$id) jsonError('ID required.', 400);
    $stmt = $pdo->prepare('SELECT thumbnail_path, cover_image_path FROM blog_articles WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    // Clean up physical files
    $root = rtrim(realpath(__DIR__.'/../../'), DIRECTORY_SEPARATOR);
    if ($row) {
        if ($row['thumbnail_path'])   @unlink($root . $row['thumbnail_path']);
        if ($row['cover_image_path']) @unlink($root . $row['cover_image_path']);
    }
    $pdo->prepare('DELETE FROM blog_articles WHERE id=?')->execute([$id]);
    jsonSuccess(null, 'Blog post deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CREATE / UPDATE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if (!in_array($action, ['create','update'], true)) jsonError('Unknown action.', 400);

$id    = (int)post('id');
$isNew = ($action === 'create');

/* ── Required fields ── */
$title   = sanitize(post('title'));
$slug    = preg_replace('/[^a-z0-9-]/', '', strtolower(post('slug')));
$excerpt = sanitize(post('excerpt'));
$content = $_POST['content'] ?? '';

if (!$title)   jsonError('Title is required.', 422, 'title');
if (!$slug)    jsonError('Slug is required.', 422, 'slug');
if (!$content) jsonError('Content is required. Please write something in the Content tab.', 422, 'content');
// excerpt is optional - auto-generate from content if missing
if (!$excerpt) {
    $excerpt = mb_substr(strip_tags($content), 0, 300);
    if (mb_strlen(strip_tags($content)) > 300) $excerpt .= '...';
}

$catId    = (int)post('category_id') ?: null;
$authorId = (int)post('author_id') ?: Auth::id();

/* ── Slug uniqueness ── */
if ($isNew) {
    $dup = $pdo->prepare('SELECT id FROM blog_articles WHERE slug=? LIMIT 1');
    $dup->execute([$slug]);
    if ($dup->fetch()) jsonError('This slug is already taken. Choose a unique slug.', 409, 'slug');
} else {
    if (!$id) jsonError('ID required for update.', 400);
    $dup = $pdo->prepare('SELECT id FROM blog_articles WHERE slug=? AND id!=? LIMIT 1');
    $dup->execute([$slug, $id]);
    if ($dup->fetch()) jsonError('Slug already taken by another post.', 409, 'slug');
}

/* ── Thumbnail upload ── */
$thumbPath = null;
if (!$isNew) {
    $ex = $pdo->prepare('SELECT thumbnail_path FROM blog_articles WHERE id=? LIMIT 1');
    $ex->execute([$id]);
    $thumbPath = $ex->fetchColumn() ?: null;
}

if (!empty($_FILES['thumbnail']['tmp_name'])) {
    $file    = $_FILES['thumbnail'];
    // SEC: Use server-side MIME detection — never trust browser-supplied file['type']
    $realMime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
    $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($realMime, $allowed, true)) {
        jsonError('Thumbnail must be a real JPG, PNG, WebP, or GIF image. Got: ' . $realMime, 422);
    }
    if ($file['size'] > 5 * 1024 * 1024) jsonError('Thumbnail must be under 5MB.', 422);

    $root = rootPath();
    $dir  = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'blogs' . DIRECTORY_SEPARATOR . 'thumbs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Prevent PHP execution inside upload dirs
        file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess',
            "Options -Indexes\nphp_flag engine off\n<FilesMatch \"\\.php\">\nDeny from all\n</FilesMatch>\n");
    }

    $ext   = match($realMime) { 'image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',default=>'jpg' };
    $fname = $slug . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $dir . DIRECTORY_SEPARATOR . $fname;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        if ($thumbPath) @unlink($root . $thumbPath);
        $thumbPath = '/uploads/blogs/thumbs/' . $fname;
    } else {
        jsonError('Thumbnail upload failed — check directory permissions.', 500);
    }
}

/* ── Auto read-time estimate ── */
$readTime = post('read_time_mins') ?: null;
if (!$readTime) {
    $wc = str_word_count(strip_tags($content));
    $readTime = max(1, (int)round($wc / 200));
}

/* ── Is published ── */
$isPublished = (int)($_POST['is_published'] ?? 0);

/* ── Build payload ── */
$data = [
    'category_id'       => $catId,
    'author_id'         => $authorId,
    'slug'              => $slug,
    'title'             => $title,
    'card_title'        => post('card_title')        ?: null,
    'excerpt'           => $excerpt,
    'content'           => $content,
    'cover_image_path'  => post('cover_image_path')  ?: null,
    'icon_name'         => post('icon_name')         ?: null,
    'meta_description'  => post('meta_description')  ?: null,
    'meta_keywords'     => post('meta_keywords')     ?: null,
    'og_image_url'      => post('og_image_url')      ?: null,
    'seo_title'         => post('seo_title')         ?: null,
    'read_time_mins'    => $readTime,
    'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
    'is_published'      => $isPublished,
];
if ($thumbPath !== null) $data['thumbnail_path'] = $thumbPath;

if ($isNew) {
    if ($isPublished) $data['published_at'] = date('Y-m-d H:i:s');

    $cols = implode(', ', array_keys($data));
    $ph   = implode(', ', array_fill(0, count($data), '?'));
    $pdo->prepare("INSERT INTO blog_articles ($cols, created_at, updated_at) VALUES ($ph, NOW(), NOW())")
        ->execute(array_values($data));
    $newId = (int)$pdo->lastInsertId();

    saveBlogTags($pdo, $newId, $_POST['tags'] ?? []);
    jsonSuccess(['id' => $newId], 'Blog post created.');

} else {
    // Check if transitioning to published for first time
    $prevStmt = $pdo->prepare('SELECT is_published, published_at FROM blog_articles WHERE id=? LIMIT 1');
    $prevStmt->execute([$id]);
    $prev = $prevStmt->fetch();
    if ($isPublished && empty($prev['published_at'])) $data['published_at'] = date('Y-m-d H:i:s');

    $sets = implode(', ', array_map(fn($k)=>"$k=?", array_keys($data)));
    $vals = array_values($data);
    $vals[] = $id;
    $pdo->prepare("UPDATE blog_articles SET $sets, updated_at=NOW() WHERE id=?")->execute($vals);

    saveBlogTags($pdo, $id, $_POST['tags'] ?? []);
    jsonSuccess(['id' => $id], 'Blog post updated.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   HELPERS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function saveBlogTags(PDO $pdo, int $articleId, array $tagIds): void {
    // blog_tag_map may not exist yet — wrap in try/catch
    try {
        $pdo->prepare('DELETE FROM blog_tag_map WHERE article_id=?')->execute([$articleId]);
        foreach ($tagIds as $tid) {
            $tid = (int)$tid;
            if ($tid > 0) {
                $pdo->prepare('INSERT IGNORE INTO blog_tag_map (article_id, tag_id) VALUES (?,?)')->execute([$articleId, $tid]);
            }
        }
    } catch (\PDOException) {
        // Table doesn't exist yet — silently skip until migration is run
    }
}
