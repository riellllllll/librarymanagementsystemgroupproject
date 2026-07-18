<?php
// ============================================================
// classes/Notification.php — Student Notification Class
// ============================================================

class Notification
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** Create a notification for a student */
    public function create(int $userId, string $type, string $title, string $message): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isss', $userId, $type, $title, $message);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Get all notifications for a student (newest first) */
    public function getByStudent(int $userId, int $limit = 50): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Count unread notifications */
    public function unreadCount(int $userId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    /** Mark a single notification as read */
    public function markRead(int $notifId, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $notifId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Mark all notifications as read for a student */
    public function markAllRead(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0"
        );
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Delete a notification */
    public function delete(int $notifId, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM notifications WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $notifId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}