<?php
/**
 * CodeByTushu — Messages Admin API v2
 *
 * Actions:
 *   mark_read        — single
 *   mark_unread      — single
 *   mark_all_read    — all unread → read
 *   star             — single
 *   unstar           — single
 *   mark_spam        — single → spam
 *   unspam           — single → not spam
 *   delete           — single (hard delete)
 *   bulk_mark_read   — array of ids
 *   bulk_mark_unread — array of ids
 *   bulk_spam        — array of ids
 *   bulk_delete      — array of ids
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden', 403);
requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$pdo    = db();
$action = post('action');
$id     = (int)post('id');

/* ━━ Helper: validate single ID ━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function requireId(int $id): void {
    if (!$id) jsonError('ID required.', 400);
}

/* ━━ Helper: validate & sanitize bulk IDs ━━━━━━━━━━━━━━━━━━ */
function getBulkIds(): array {
    $raw = $_POST['ids'] ?? [];
    if (!is_array($raw) || empty($raw)) jsonError('No IDs provided.', 400);
    $ids = array_filter(array_map('intval', $raw));
    if (empty($ids)) jsonError('No valid IDs.', 400);
    if (count($ids) > 100) jsonError('Max 100 at a time.', 400);
    return $ids;
}

/* ━━ Helper: run bulk UPDATE ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function bulkUpdate(PDO $pdo, string $sql, array $ids): void {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare(str_replace('?IDS?', $ph, $sql))->execute($ids);
}

switch ($action) {

    /* ── Single: mark read ──────────────────────────────────── */
    case 'mark_read':
        requireId($id);
        $pdo->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([$id]);
        jsonSuccess(null, 'Marked as read.');

    /* ── Single: mark unread ────────────────────────────────── */
    case 'mark_unread':
        requireId($id);
        $pdo->prepare('UPDATE contact_messages SET is_read=0 WHERE id=?')->execute([$id]);
        jsonSuccess(null, 'Marked as unread.');

    /* ── All: mark all read ─────────────────────────────────── */
    case 'mark_all_read':
        $pdo->query('UPDATE contact_messages SET is_read=1 WHERE is_read=0 AND is_spam=0');
        jsonSuccess(null, 'All messages marked as read.');

    /* ── Single: star ───────────────────────────────────────── */
    case 'star':
        requireId($id);
        try {
            $pdo->prepare('UPDATE contact_messages SET is_starred=1 WHERE id=?')->execute([$id]);
        } catch (\PDOException $e) {
            // is_starred column may not exist yet — migration needed
            jsonError('is_starred column missing. Run migration 006.', 500);
        }
        jsonSuccess(null, 'Starred.');

    /* ── Single: unstar ─────────────────────────────────────── */
    case 'unstar':
        requireId($id);
        try {
            $pdo->prepare('UPDATE contact_messages SET is_starred=0 WHERE id=?')->execute([$id]);
        } catch (\PDOException $e) {
            jsonError('is_starred column missing. Run migration 006.', 500);
        }
        jsonSuccess(null, 'Unstarred.');

    /* ── Single: mark spam ──────────────────────────────────── */
    case 'mark_spam':
        requireId($id);
        $pdo->prepare('UPDATE contact_messages SET is_spam=1, is_read=1 WHERE id=?')->execute([$id]);
        jsonSuccess(null, 'Moved to spam.');

    /* ── Single: restore from spam ──────────────────────────── */
    case 'unspam':
        requireId($id);
        $pdo->prepare('UPDATE contact_messages SET is_spam=0 WHERE id=?')->execute([$id]);
        jsonSuccess(null, 'Restored from spam.');

    /* ── Single: delete ─────────────────────────────────────── */
    case 'delete':
        requireId($id);
        $pdo->prepare('DELETE FROM contact_messages WHERE id=?')->execute([$id]);
        jsonSuccess(null, 'Message deleted.');

    /* ── Bulk: mark read ────────────────────────────────────── */
    case 'bulk_mark_read':
        $ids = getBulkIds();
        bulkUpdate($pdo, 'UPDATE contact_messages SET is_read=1 WHERE id IN (?IDS?)', $ids);
        jsonSuccess(['count' => count($ids)], count($ids) . ' messages marked as read.');

    /* ── Bulk: mark unread ──────────────────────────────────── */
    case 'bulk_mark_unread':
        $ids = getBulkIds();
        bulkUpdate($pdo, 'UPDATE contact_messages SET is_read=0 WHERE id IN (?IDS?)', $ids);
        jsonSuccess(['count' => count($ids)], count($ids) . ' messages marked as unread.');

    /* ── Bulk: mark spam ────────────────────────────────────── */
    case 'bulk_spam':
        $ids = getBulkIds();
        bulkUpdate($pdo, 'UPDATE contact_messages SET is_spam=1, is_read=1 WHERE id IN (?IDS?)', $ids);
        jsonSuccess(['count' => count($ids)], count($ids) . ' messages moved to spam.');

    /* ── Bulk: delete ───────────────────────────────────────── */
    case 'bulk_delete':
        $ids = getBulkIds();
        bulkUpdate($pdo, 'DELETE FROM contact_messages WHERE id IN (?IDS?)', $ids);
        jsonSuccess(['count' => count($ids)], count($ids) . ' messages deleted.');

    default:
        jsonError('Unknown action: ' . $action, 400);
}
