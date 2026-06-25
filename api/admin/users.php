<?php
/**
 * CodeByTushu — Admin Users API v2
 * POST /api/admin/users.php
 *
 * Actions (single):
 *   create, update, delete, set_status, change_role
 *
 * Actions (bulk):
 *   bulk_delete, bulk_activate, bulk_ban
 *
 * Actions (read):
 *   get  — GET ?action=get&id=N
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden.', 403);

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: fetch single user ───────────────────────────────── */
if ($method === 'GET') {
    $action = sanitize($_GET['action'] ?? '');
    $id     = (int)($_GET['id'] ?? 0);

    if ($action === 'get' && $id > 0) {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, username, email, role, status,
                    email_verified, profile_image, login_count,
                    last_login, created_at, updated_at
               FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) jsonError('User not found.', 404);
        jsonSuccess($u);
    }

    jsonError('Invalid GET action.', 400);
}

/* ── POST: all mutation actions ───────────────────────────── */
requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$action = post('action');

/* ── BULK ACTIONS ─────────────────────────────────────────── */
if (in_array($action, ['bulk_delete', 'bulk_activate', 'bulk_ban'], true)) {
    $rawIds = $_POST['ids'] ?? '';
    $ids    = array_filter(
        array_map('intval', is_array($rawIds) ? $rawIds : explode(',', $rawIds)),
        fn($i) => $i > 0 && $i !== Auth::id()
    );
    if (empty($ids)) jsonError('No valid user IDs provided.');

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    switch ($action) {
        case 'bulk_delete':
            $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($ids);
            jsonSuccess(['count' => count($ids)], count($ids) . ' user(s) deleted.');

        case 'bulk_activate':
            $pdo->prepare("UPDATE users SET status='active', updated_at=NOW() WHERE id IN ($placeholders)")->execute($ids);
            jsonSuccess(['count' => count($ids)], count($ids) . ' user(s) activated.');

        case 'bulk_ban':
            $pdo->prepare("UPDATE users SET status='banned', updated_at=NOW() WHERE id IN ($placeholders)")->execute($ids);
            jsonSuccess(['count' => count($ids)], count($ids) . ' user(s) banned.');
    }
}

/* ── SINGLE ID ACTIONS ────────────────────────────────────── */
$id = (int)post('id');
if (!$id && $action !== 'create') jsonError('User ID is required.', 400);

$validRoles    = ['user', 'editor', 'admin', 'super_admin'];
$validStatuses = ['active', 'banned', 'pending'];

switch ($action) {

    /* ── CREATE ──────────────────────────────────────────── */
    case 'create':
        $fullName = sanitize(post('full_name'));
        $username = sanitize(post('username'));
        $email    = sanitizeEmail(post('email')) ?: null;
        $role     = post('role') ?: 'user';
        $status   = post('status') ?: 'active';
        $password = $_POST['password'] ?? '';

        if (!$fullName)           jsonError('Full name is required.', 422, 'full_name');
        if (!$username)           jsonError('Username is required.', 422, 'username');
        if (!$email)              jsonError('Valid email is required.', 422, 'email');
        if (strlen($password) < 8) jsonError('Password must be at least 8 characters.', 422, 'password');
        if (!in_array($role, $validRoles))       jsonError('Invalid role.', 422, 'role');
        if (!in_array($status, $validStatuses))  jsonError('Invalid status.', 422, 'status');
        if ($role === 'super_admin' && !Auth::isSuperAdmin()) jsonError('Insufficient privileges.', 403);

        // Uniqueness checks
        $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $dup->execute([$email]);
        if ($dup->fetch()) jsonError('Email already registered.', 409, 'email');

        $dupU = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $dupU->execute([$username]);
        if ($dupU->fetch()) jsonError('Username already taken.', 409, 'username');

        $hash = hashPassword($password);
        $pdo->prepare(
            'INSERT INTO users (full_name, username, email, password_hash, role, status, email_verified, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())'
        )->execute([$fullName, $username, $email, $hash, $role, $status]);

        $newId = (int)$pdo->lastInsertId();
        jsonSuccess(['id' => $newId], 'User created successfully.');

    /* ── UPDATE ──────────────────────────────────────────── */
    case 'update':
        $fullName = sanitize(post('full_name'));
        $email    = sanitizeEmail(post('email')) ?: null;
        $role     = post('role');
        $status   = post('status');
        $newPass  = $_POST['new_password'] ?? '';

        if (!$fullName) jsonError('Full name is required.', 422, 'full_name');
        if (!$email)    jsonError('Valid email is required.', 422, 'email');
        if (!in_array($role, $validRoles))       jsonError('Invalid role.', 422, 'role');
        if (!in_array($status, $validStatuses))  jsonError('Invalid status.', 422, 'status');
        if ($role === 'super_admin' && !Auth::isSuperAdmin()) jsonError('Insufficient privileges.', 403);

        // Self-demotion guard
        if ($id === Auth::id() && $status === 'banned') jsonError('You cannot ban yourself.', 403);

        // Email uniqueness
        $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) jsonError('Email already used by another account.', 409, 'email');

        if ($newPass) {
            if (strlen($newPass) < 8) jsonError('Password must be at least 8 characters.', 422, 'new_password');
            $hash = hashPassword($newPass);
            $pdo->prepare('UPDATE users SET full_name=?,email=?,role=?,status=?,password_hash=?,updated_at=NOW() WHERE id=?')
                ->execute([$fullName, $email, $role, $status, $hash, $id]);
        } else {
            $pdo->prepare('UPDATE users SET full_name=?,email=?,role=?,status=?,updated_at=NOW() WHERE id=?')
                ->execute([$fullName, $email, $role, $status, $id]);
        }
        jsonSuccess(null, 'User updated successfully.');

    /* ── DELETE ──────────────────────────────────────────── */
    case 'delete':
        if ($id === Auth::id()) jsonError('You cannot delete your own account.', 403);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        jsonSuccess(null, 'User deleted.');

    /* ── SET STATUS ──────────────────────────────────────── */
    case 'set_status':
        $status = post('status');
        if (!in_array($status, $validStatuses)) jsonError('Invalid status.');
        if ($id === Auth::id()) jsonError('Cannot change your own status.', 403);
        $pdo->prepare('UPDATE users SET status=?,updated_at=NOW() WHERE id=?')->execute([$status, $id]);
        jsonSuccess(null, 'User status updated.');

    /* ── CHANGE ROLE ─────────────────────────────────────── */
    case 'change_role':
        $role = post('role');
        if (!in_array($role, $validRoles)) jsonError('Invalid role.');
        if ($role === 'super_admin' && !Auth::isSuperAdmin()) jsonError('Insufficient privileges.', 403);
        if ($id === Auth::id()) jsonError('Cannot change your own role.', 403);
        $pdo->prepare('UPDATE users SET role=?,updated_at=NOW() WHERE id=?')->execute([$role, $id]);
        jsonSuccess(null, 'Role updated.');

    default:
        jsonError('Unknown action.', 400);
}
