<?php
// ============================================================
// includes/student_notifications.php
// Notification bell + dropdown (DB-powered).
// Keeps the original CSS-toggle design and class names.
// Requires session_start() already called in parent page.
// ============================================================

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Notification.php';

// If somehow not logged in, render nothing
if (!isset($_SESSION['user_id'])) { return; }

$__notif_conn = (new Database())->getConnection();
$__notif      = new Notification($__notif_conn);
$__uid        = (int)$_SESSION['user_id'];

// Note: ?notifications_read=1 is handled inside student_auth.php
// (runs before any HTML so header() works without warnings)

// ── Load from DB ──
$notifications = $__notif->getByStudent($__uid);
$unread_count  = $__notif->unreadCount($__uid);
$mark_read_url = htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?') . '?notifications_read=1');

// Map DB notification type → SVG icon path
function _notif_icon(string $type): string {
    return match ($type) {
        'approved' => '<path d="M20 6 9 17l-5-5"/>',                       // check
        'rejected' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>', // x
        'returned' => '<path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/>',            // return
        'fine'     => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>', // alert
        default    => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',  // info
    };
}

// Friendly relative time
function _notif_time(string $datetime): string {
    $ts   = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}
?>
<div class="student-notifications">
  <input type="checkbox" class="notification-check" id="notificationToggle" autocomplete="off">
  <label class="topbar-icon-btn notification-btn" for="notificationToggle" aria-label="Notifications">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>
    <?php if ($unread_count > 0): ?>
      <span class="topbar-notif-dot"></span>
    <?php endif; ?>
  </label>

  <div class="notification-panel" aria-live="polite">
    <div class="notification-panel-head">
      <strong>Notifications</strong>
      <?php if ($unread_count > 0): ?>
        <a href="<?= $mark_read_url ?>">Mark read</a>
      <?php endif; ?>
    </div>
    <?php if (empty($notifications)): ?>
      <div class="notification-empty">No notifications yet.</div>
    <?php else: ?>
      <div class="notification-list">
        <?php foreach ($notifications as $notification): ?>
          <div class="notification-item<?= empty($notification['is_read']) ? ' unread' : '' ?>">
            <div class="notification-status">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?= _notif_icon($notification['type']) ?>
              </svg>
            </div>
            <div class="notification-copy">
              <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
              <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
              <div class="notification-time"><?= htmlspecialchars(_notif_time($notification['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>