<?php
if (isset($_GET['notifications_read'])) {
    foreach ($_SESSION['student_notifications'] ?? [] as &$notification) {
        $notification['unread'] = false;
    }
    unset($notification);
}

$notifications = $_SESSION['student_notifications'] ?? [];
$unread_count = count(array_filter($notifications, fn($notification) => !empty($notification['unread'])));
$mark_read_url = htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?') . '?notifications_read=1');
?>
<div class="student-notifications">
  <input type="checkbox" class="notification-check" id="notificationToggle">
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
      <a href="<?= $mark_read_url ?>">Mark read</a>
    </div>
    <?php if (empty($notifications)): ?>
      <div class="notification-empty">No notifications yet.</div>
    <?php else: ?>
      <div class="notification-list">
        <?php foreach (array_reverse($notifications) as $notification): ?>
          <div class="notification-item<?= !empty($notification['unread']) ? ' unread' : '' ?>">
            <div class="notification-status">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
            </div>
            <div class="notification-copy">
              <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
              <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
              <div class="notification-time"><?= htmlspecialchars($notification['time']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
