<?php
/**
 * School ERP - Notification System
 */

if (!defined('ROOT_PATH')) die('Direct access not allowed.');

/**
 * Create a DB notification
 */
function create_notification(
    int|null $user_id,
    string $role,
    string $title,
    string $message,
    string $type = 'info'
): void {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id,role,title,message,type) VALUES (?,?,?,?,?)"
    );
    $stmt->execute([$user_id, $role, $title, $message, $type]);
}

/**
 * Create notification for all users of a given role
 */
function notify_role(string $role, string $title, string $message, string $type = 'info'): void
{
    global $pdo;
    if ($role === 'all') {
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id,role,title,message,type) VALUES (NULL,'all',?,?,?)"
        );
        $stmt->execute([$title, $message, $type]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role=? AND status='active'");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll();
        $ins   = $pdo->prepare(
            "INSERT INTO notifications (user_id,role,title,message,type) VALUES (?,?,?,?,?)"
        );
        foreach ($users as $u) {
            $ins->execute([$u['id'], $role, $title, $message, $type]);
        }
    }
}

/**
 * Get unread count for logged-in user
 */
function get_unread_count(): int
{
    global $pdo;
    if (!is_logged_in()) return 0;
    $userId = (int)$_SESSION['user_id'];
    $role   = get_user_role();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications
         WHERE is_read=0 AND (user_id=? OR role=? OR role='all')"
    );
    $stmt->execute([$userId, $role]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get recent notifications for logged-in user
 */
function get_notifications(int $limit = 10): array
{
    global $pdo;
    if (!is_logged_in()) return [];
    $userId = (int)$_SESSION['user_id'];
    $role   = get_user_role();
    $stmt = $pdo->prepare(
        "SELECT * FROM notifications
         WHERE (user_id=? OR role=? OR role='all')
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$userId, $role, $limit]);
    return $stmt->fetchAll();
}

/**
 * Mark a notification as read
 */
function mark_notification_read(int $notif_id): void
{
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$notif_id]);
}

/**
 * Mark all notifications as read for current user
 */
function mark_all_read(): void
{
    global $pdo;
    if (!is_logged_in()) return;
    $userId = (int)$_SESSION['user_id'];
    $role   = get_user_role();
    $pdo->prepare(
        "UPDATE notifications SET is_read=1 WHERE (user_id=? OR role=? OR role='all') AND is_read=0"
    )->execute([$userId, $role]);
}

/**
 * Render notification bell dropdown
 */
function render_notification_bell(): string
{
    $count  = get_unread_count();
    $notifs = get_notifications(8);
    $badge  = $count > 0
        ? '<span class="badge bg-danger rounded-pill notif-badge">' . ($count > 99 ? '99+' : $count) . '</span>'
        : '';

    $items = '';
    if (empty($notifs)) {
        $items = '<li><div class="dropdown-item text-center text-muted py-3">No notifications</div></li>';
    } else {
        foreach ($notifs as $n) {
            $read_cls = $n['is_read'] ? '' : 'fw-bold';
            $icon_cls = match($n['type']) {
                'success' => 'text-success',
                'warning' => 'text-warning',
                'danger'  => 'text-danger',
                default   => 'text-primary',
            };
            $icon = match($n['type']) {
                'success' => 'check-circle',
                'warning' => 'exclamation-triangle',
                'danger'  => 'x-circle',
                default   => 'info-circle',
            };
            $time = date('d M, h:i A', strtotime($n['created_at']));
            $items .= '<li>
                <a class="dropdown-item py-2 px-3 ' . $read_cls . ' notif-item"
                   href="' . SITE_URL . '/notifications/read.php?id=' . $n['id'] . '"
                   data-id="' . $n['id'] . '">
                  <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-' . $icon . ' ' . $icon_cls . ' mt-1 flex-shrink-0"></i>
                    <div>
                      <div class="small">' . htmlspecialchars($n['title']) . '</div>
                      <div class="text-muted" style="font-size:0.75rem">' . $time . '</div>
                    </div>
                  </div>
                </a>
              </li>';
        }
    }

    $markAll = is_logged_in()
        ? '<li><a class="dropdown-item text-center text-primary small py-1" href="' . SITE_URL . '/notifications/mark-all.php">Mark all read</a></li><li><hr class="dropdown-divider my-0"></li>'
        : '';

    return <<<HTML
<li class="nav-item dropdown me-1">
  <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-bell-fill fs-5"></i>
    {$badge}
  </a>
  <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:300px;max-height:400px;overflow-y:auto">
    <li><div class="dropdown-header d-flex justify-content-between align-items-center">
      <span>Notifications</span>
      {$badge}
    </div></li>
    <li><hr class="dropdown-divider my-0"></li>
    {$markAll}
    {$items}
    <li><hr class="dropdown-divider my-0"></li>
    <li><a class="dropdown-item text-center small text-muted py-2" href="' . SITE_URL . '/notifications/">View All</a></li>
  </ul>
</li>
HTML;
}
