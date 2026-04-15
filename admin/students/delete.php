<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) { set_flash('error','Invalid ID.'); redirect(SITE_URL.'/admin/students/'); }

$stmt = $pdo->prepare("SELECT * FROM students WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) { set_flash('error','Student not found.'); redirect(SITE_URL.'/admin/students/'); }

// Delete photo
if ($s['photo']) delete_upload($s['photo']);

// Delete user account
if ($s['user_id']) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$s['user_id']]);
}

$pdo->prepare("DELETE FROM students WHERE id=?")->execute([$id]);

set_flash('success', "Student '{$s['name']}' deleted.");
redirect(SITE_URL . '/admin/students/');
