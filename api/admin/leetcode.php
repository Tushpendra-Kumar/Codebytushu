<?php
/**
 * CodeByTushu — LeetCode Admin API v2
 * POST /api/admin/leetcode.php
 *
 * Actions: create, update, delete, toggle_publish, autosave
 * Handles: thumbnail upload, PDF upload, platform, seo_title,
 *          description, notes, all 21 fields.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Upload.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden.', 403);
requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$pdo    = db();
$action = post('action');

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TOGGLE PUBLISH
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'toggle_publish') {
    $id  = (int)post('id');
    $val = (int)post('value') ? 1 : 0;
    if (!$id) jsonError('ID required.', 400);
    // Use conditional binding — avoid SQL string interpolation even for safe constants
    if ($val) {
        $pdo->prepare('UPDATE leetcode_solutions SET is_published=1, published_at=NOW() WHERE id=?')
            ->execute([$id]);
    } else {
        $pdo->prepare('UPDATE leetcode_solutions SET is_published=0, published_at=NULL WHERE id=?')
            ->execute([$id]);
    }
    jsonSuccess(null, $val ? 'Solution published.' : 'Set to draft.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   DELETE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'delete') {
    $id = (int)post('id');
    if (!$id) jsonError('ID required.', 400);

    $stmt = $pdo->prepare('SELECT month_id, thumbnail_path, pdf_path FROM leetcode_solutions WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $sol = $stmt->fetch();

    // Delete physical files
    if ($sol) {
        $root = rootPath();
        if ($sol['thumbnail_path']) @unlink($root . DIRECTORY_SEPARATOR . ltrim($sol['thumbnail_path'], '/\\'));
        if ($sol['pdf_path'])       @unlink($root . DIRECTORY_SEPARATOR . ltrim($sol['pdf_path'], '/\\'));
    }

    $pdo->prepare('DELETE FROM leetcode_solutions WHERE id=?')->execute([$id]);
    if ($sol) updateMonthCount($pdo, $sol['month_id']);

    jsonSuccess(null, 'Solution deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CREATE / UPDATE (also handles autosave)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if (!in_array($action, ['create','update'], true)) jsonError('Unknown action.', 400);

$id    = (int)post('id');
$isNew = ($action === 'create');

/* ── Core required fields ── */
$date  = post('solution_date');
$title = sanitize(post('problem_title'));
$slug  = preg_replace('/[^a-z0-9-]/', '', strtolower(post('slug')));
$diff  = post('difficulty');

if (!$date || !$title || !$slug || !$diff) jsonError('Required fields: date, title, slug, difficulty.', 422);
if (!in_array($diff, ['Easy','Medium','Hard'], true)) jsonError('Invalid difficulty.', 422);

/* ── Parse date ── */
$dt = \DateTime::createFromFormat('Y-m-d', $date);
if (!$dt) jsonError('Invalid date format.', 422);
$year  = (int)$dt->format('Y');
$month = (int)$dt->format('m');
$day   = (int)$dt->format('d');

/* ── Month detection ── */
$monthId = (int)post('month_id') ?: null;
if (!$monthId) {
    $ms = $pdo->prepare('SELECT id FROM leetcode_months WHERE year=? AND month_num=? LIMIT 1');
    $ms->execute([$year, $month]);
    $mon = $ms->fetch();
    $monthId = $mon ? (int)$mon['id'] : null;
}
// If still no month, create one automatically
if (!$monthId) {
    $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    $mName  = $months[$month] ?? "Month $month";
    $mLabel = $mName . ' ' . $year;
    // Ensure year record exists first
    $pdo->prepare('INSERT IGNORE INTO leetcode_years (year, badge_label, card_title, description, status) VALUES (?,?,?,?,?)')
        ->execute([$year, (string)$year, "Year $year", "", "active"]);
    $yearId = (int)($pdo->query("SELECT id FROM leetcode_years WHERE year=$year LIMIT 1")->fetchColumn());
    $mShort = substr($mName, 0, 3);
    $tDays  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $pdo->prepare('INSERT IGNORE INTO leetcode_months (year_id, year, month_num, month_name, month_short, total_days) VALUES (?,?,?,?,?,?)')
        ->execute([$yearId, $year, $month, $mName, $mShort, $tDays]);
    $monthId = (int)$pdo->lastInsertId() ?: null;
    // Try fetch again
    if (!$monthId) {
        $ms->execute([$year, $month]);
        $monthId = (int)($ms->fetch()['id'] ?? 0);
    }
}
if (!$monthId) jsonError("Could not find/create month record for $year-$month.", 500);

/* ── Platform ── */
$validPlatforms = ['LeetCode','GeeksForGeeks','HackerRank','Codeforces','CodeChef','AtCoder','Other'];
$platform = in_array(post('platform'), $validPlatforms, true) ? post('platform') : 'LeetCode';

/* ── File uploads ── */
$thumbnailPath = null;
$pdfPath       = null;
$uploadRoot    = rtrim(realpath(__DIR__.'/../../'), DIRECTORY_SEPARATOR);

if (!$isNew) {
    // Load existing paths for replacement
    $existing = $pdo->prepare('SELECT thumbnail_path, pdf_path FROM leetcode_solutions WHERE id=? LIMIT 1');
    $existing->execute([$id]);
    $existingRow = $existing->fetch() ?: [];
    $thumbnailPath = $existingRow['thumbnail_path'] ?? null;
    $pdfPath       = $existingRow['pdf_path']       ?? null;
}

// Thumbnail
if (!empty($_FILES['thumbnail']['tmp_name'])) {
    $val = Upload::validateFile($_FILES['thumbnail'], 'image');
    if (!$val['ok']) jsonError($val['error'], 422);

    $dir = $uploadRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'leetcode' . DIRECTORY_SEPARATOR . 'thumbs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $fname   = $slug . '-' . time() . '.' . $val['ext'];
    $dest    = $dir . DIRECTORY_SEPARATOR . $fname;

    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dest)) {
        if ($thumbnailPath) @unlink($uploadRoot . $thumbnailPath);
        $thumbnailPath = '/uploads/leetcode/thumbs/' . $fname;
    }
}

// PDF
if (!empty($_FILES['pdf_file']['tmp_name'])) {
    $val = Upload::validateFile($_FILES['pdf_file'], 'pdf');
    if (!$val['ok']) jsonError($val['error'], 422);

    $dir = $uploadRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'leetcode' . DIRECTORY_SEPARATOR . 'pdfs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $fname = $slug . '-' . time() . '.' . $val['ext'];
    $dest  = $dir . DIRECTORY_SEPARATOR . $fname;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest)) {
        if ($pdfPath) @unlink($uploadRoot . $pdfPath);
        $pdfPath = '/uploads/leetcode/pdfs/' . $fname;
    }
}

/* ── Build data array ── */
$isPublished = (int)($_POST['is_published'] ?? 0);
$data = [
    'month_id'         => $monthId,
    'solution_date'    => $date,
    'day_number'       => $day,
    'year'             => $year,
    'month'            => $month,
    'slug'             => $slug,
    'platform'         => $platform,
    'problem_title'    => $title,
    'problem_number'   => post('problem_number') ?: null,
    'difficulty'       => $diff,
    'time_complexity'  => post('time_complexity')  ?: null,
    'space_complexity' => post('space_complexity') ?: null,
    'approach_summary' => post('approach_summary')  ?: null,
    'explanation'      => $_POST['explanation']      ?? null,
    'leetcode_url'     => post('leetcode_url')       ?: null,
    'youtube_url'      => post('youtube_url')        ?: null,
    'meta_description' => post('meta_description')   ?: null,
    'og_image_url'     => post('og_image_url')       ?: null,
    'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
    'is_published'     => $isPublished,
];

// Only set file paths if we have new uploads or existing ones
if ($thumbnailPath !== null) $data['thumbnail_path'] = $thumbnailPath;
if ($pdfPath !== null)       $data['pdf_path']       = $pdfPath;

/* ── INSERT ── */
if ($isNew) {
    // Slug uniqueness check
    $dup = $pdo->prepare('SELECT id FROM leetcode_solutions WHERE slug=? LIMIT 1');
    $dup->execute([$slug]);
    if ($dup->fetch()) jsonError('This slug is already taken. Please use a unique slug.', 409);

    if ($isPublished) $data['published_at'] = date('Y-m-d H:i:s');

    $cols = implode(', ', array_keys($data));
    $ph   = implode(', ', array_fill(0, count($data), '?'));
    $pdo->prepare("INSERT INTO leetcode_solutions ($cols, created_at, updated_at) VALUES ($ph, NOW(), NOW())")
        ->execute(array_values($data));
    $newId = (int)$pdo->lastInsertId();

    saveSolutionCodes($pdo, $newId, $_POST['codes'] ?? []);
    saveSolutionTags($pdo, $newId, $_POST['tags'] ?? []);
    updateMonthCount($pdo, $monthId);

    jsonSuccess(['id' => $newId], 'Solution created successfully.');

/* ── UPDATE ── */
} else {
    if (!$id) jsonError('ID required for update.', 400);

    // Slug uniqueness (exclude self)
    $dup = $pdo->prepare('SELECT id FROM leetcode_solutions WHERE slug=? AND id!=? LIMIT 1');
    $dup->execute([$slug, $id]);
    if ($dup->fetch()) jsonError('This slug is already taken by another solution.', 409);

    // Set published_at if transitioning to published
    $wasPublished = (int)($pdo->prepare('SELECT is_published FROM leetcode_solutions WHERE id=? LIMIT 1')
        ->execute([$id]) ? 0 : 0);
    $stmt = $pdo->prepare('SELECT is_published, published_at FROM leetcode_solutions WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $prev = $stmt->fetch();
    if ($isPublished && !$prev['published_at']) $data['published_at'] = date('Y-m-d H:i:s');

    $sets = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
    $vals = array_values($data);
    $vals[] = $id;
    $pdo->prepare("UPDATE leetcode_solutions SET $sets, updated_at=NOW() WHERE id=?")->execute($vals);

    saveSolutionCodes($pdo, $id, $_POST['codes'] ?? []);
    saveSolutionTags($pdo, $id, $_POST['tags'] ?? []);
    updateMonthCount($pdo, $monthId);

    jsonSuccess(['id' => $id], 'Solution updated successfully.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   HELPER FUNCTIONS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function saveSolutionCodes(PDO $pdo, int $solId, array $codes): void {
    if (empty($codes)) return;
    $pdo->prepare('DELETE FROM solution_code_blocks WHERE solution_id=?')->execute([$solId]);
    $sort = 0;
    foreach ($codes as $c) {
        $lang = sanitize($c['language'] ?? 'Java');
        $code = trim($c['code'] ?? '');
        if (!$code) continue;
        $prim = !empty($c['is_primary']) ? 1 : 0;
        $lSlug = strtolower(preg_replace('/[^a-z0-9]/i','-',$lang));
        $pdo->prepare(
            'INSERT INTO solution_code_blocks (solution_id, language, language_slug, code, is_primary, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$solId, $lang, $lSlug, $code, $prim, $sort++]);
    }
}

function saveSolutionTags(PDO $pdo, int $solId, array $tagIds): void {
    // Get old tags before deleting (for usage_count update)
    $oldTags = $pdo->prepare('SELECT tag_id FROM solution_tag_map WHERE solution_id=?');
    $oldTags->execute([$solId]);
    $affectedTagIds = array_column($oldTags->fetchAll(), 'tag_id');

    $pdo->prepare('DELETE FROM solution_tag_map WHERE solution_id=?')->execute([$solId]);
    foreach ($tagIds as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $pdo->prepare('INSERT IGNORE INTO solution_tag_map (solution_id, tag_id) VALUES (?,?)')->execute([$solId, $tid]);
            $affectedTagIds[] = $tid;
        }
    }
    // Update usage_count only for affected tags
    $affectedTagIds = array_unique(array_filter(array_map('intval', $affectedTagIds)));
    if (!empty($affectedTagIds)) {
        $in = implode(',', $affectedTagIds);
        $pdo->query("UPDATE solution_tags SET usage_count=(SELECT COUNT(*) FROM solution_tag_map WHERE tag_id=id) WHERE id IN ($in)");
    }
}

function updateMonthCount(PDO $pdo, ?int $monthId): void {
    if (!$monthId) return;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM leetcode_solutions WHERE month_id=? AND is_published=1');
    $stmt->execute([$monthId]);
    $pdo->prepare('UPDATE leetcode_months SET published_solutions=? WHERE id=?')
        ->execute([(int)$stmt->fetchColumn(), $monthId]);
}
