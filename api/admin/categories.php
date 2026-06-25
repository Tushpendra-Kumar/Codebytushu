<?php
/**
 * Categories + Tags API
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';
Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden', 403);
requireCsrf();

$pdo    = db();
$action = post('action');

switch ($action) {

  case 'save_category':
    $catId = (int)post('cat_id');
    $name  = sanitize(post('name'));
    $type  = post('type');
    $icon  = post('icon_name') ?: null;
    $sort  = (int)post('sort_order');
    $desc  = sanitize(post('description') ?: '');
    $slug  = preg_replace('/[^a-z0-9-]/', '', strtolower($name));

    if (!$name) jsonError('Name is required.');
    $valid = ['blog','course','leetcode','portfolio'];
    if (!in_array($type, $valid)) jsonError('Invalid type.');

    if ($catId) {
        $pdo->prepare('UPDATE categories SET name=?,type=?,slug=?,icon_name=?,sort_order=?,description=?,updated_at=NOW() WHERE id=?')
            ->execute([$name,$type,$slug,$icon,$sort,$desc,$catId]);
        jsonSuccess(null,'Category updated.');
    } else {
        $pdo->prepare('INSERT INTO categories (name,type,slug,icon_name,sort_order,description) VALUES (?,?,?,?,?,?)')
            ->execute([$name,$type,$slug,$icon,$sort,$desc]);
        jsonSuccess(null,'Category added.');
    }

  case 'delete_category':
    $id=(int)post('id'); if(!$id) jsonError('ID required.');
    $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
    jsonSuccess(null,'Category deleted.');

  case 'toggle':
    $id=(int)post('id'); $val=post('value')?1:0;
    $pdo->prepare('UPDATE categories SET is_active=? WHERE id=?')->execute([$val,$id]);
    jsonSuccess();

  case 'save_tag':
    $name  = sanitize(post('tag_name'));
    $color = post('color_hex') ?: '#ffc400';
    $slug  = preg_replace('/[^a-z0-9-]/', '', strtolower($name));
    if (!$name) jsonError('Tag name is required.');
    $pdo->prepare('INSERT IGNORE INTO solution_tags (name,slug,color_hex) VALUES (?,?,?)')
        ->execute([$name,$slug,$color]);
    jsonSuccess(null,'Tag added.');

  case 'delete_tag':
    $id=(int)post('id'); if(!$id) jsonError('ID required.');
    $pdo->prepare('DELETE FROM solution_tags WHERE id=?')->execute([$id]);
    jsonSuccess(null,'Tag deleted.');

  default: jsonError('Unknown action.',400);
}
