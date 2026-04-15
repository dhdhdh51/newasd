<?php
/**
 * School ERP — Root entry point
 * Redirects authenticated users to their dashboard,
 * unauthenticated users to the login page.
 */
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    $role = get_user_role();
    $map  = [
        'admin'   => SITE_URL . '/admin/',
        'student' => SITE_URL . '/student/',
        'teacher' => SITE_URL . '/teacher/',
        'parent'  => SITE_URL . '/parent/',
    ];
    redirect($map[$role] ?? SITE_URL . '/auth/login.php');
}

redirect(SITE_URL . '/auth/login.php');
