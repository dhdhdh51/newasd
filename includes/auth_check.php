<?php
/**
 * School ERP - Authentication guard
 * Include at the top of protected pages: require_login('admin');
 */

if (!defined('ROOT_PATH')) die('Direct access not allowed.');

// Verify user exists in DB (prevent ghost sessions)
function verify_session_user(): bool
{
    global $pdo;
    if (!is_logged_in()) return false;
    $stmt = $pdo->prepare("SELECT id,status FROM users WHERE id=? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        session_unset();
        session_destroy();
        return false;
    }
    return true;
}

// Call on every protected page:
//   auth_guard('admin');
function auth_guard(string $role): void
{
    if (!is_logged_in() || !verify_session_user()) {
        redirect(SITE_URL . '/auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    if (get_user_role() !== $role) {
        http_response_code(403);
        $allowed_url = SITE_URL . '/' . get_user_role() . '/';
        redirect($allowed_url);
    }
    // Refresh session timer
    $_SESSION['last_activity'] = time();
}
