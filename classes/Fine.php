<?php
// ============================================================
// classes/Fine.php — Fine Management Class
// ============================================================

class Fine
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** All fines grouped by student (admin view) */
    public function getAllGrouped(string $statusFilter = 'all', string $search = ''): array
    {
        $where  = ['1=1'];
        $params = [];
        $types  = '';

        if ($statusFilter !== 'all') {
            $where[]  = 'f.paid_status = ?';
            $params[] = $statusFilter;
            $types   .= 's';
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(u.full_name LIKE ? OR u.student_number LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        $sql = "SELECT f.id, f.amount, f.paid_status, f.paid_date, f.created_at,
                       br.borrow_date, br.due_date, br.return_date,
                       DATEDIFF(br.return_date, br.due_date) AS days_overdue,
                       b.title AS book_title, b.id AS book_id,
                       u.id AS student_db_id, u.full_name AS student_name,
                       u.student_number, u.course, u.year_level, u.email
                FROM fines f
                JOIN borrow_records br ON br.id = f.borrow_id
                JOIN books b  ON b.id  = br.book_id
                JOIN users u  ON u.id  = f.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY u.full_name ASC, f.created_at DESC";

        if (empty($params)) {
            $result = $this->conn->query($sql);
            $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Group by student_number
        $grouped = [];
        foreach ($rows as $row) {
            $sno = $row['student_number'];
            if (!isset($grouped[$sno])) {
                $grouped[$sno] = [
                    'student_db_id'  => $row['student_db_id'],
                    'student_name'   => $row['student_name'],
                    'student_number' => $sno,
                    'course'         => $row['course'],
                    'year_level'     => $row['year_level'],
                    'email'          => $row['email'],
                    'fines'          => [],
                ];
            }
            $grouped[$sno]['fines'][] = $row;
        }
        return $grouped;
    }

    /** Fines for one student */
    public function getByStudent(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT f.*, b.title AS book_title, br.borrow_date, br.due_date, br.return_date
             FROM fines f
             JOIN borrow_records br ON br.id = f.borrow_id
             JOIN books b ON b.id = br.book_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Mark a fine as paid */
    public function markPaid(int $fineId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE fines SET paid_status = 'paid', paid_date = CURDATE() WHERE id = ?"
        );
        $stmt->bind_param('i', $fineId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Mark all unpaid fines for a student as paid */
    public function markAllPaid(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE fines SET paid_status = 'paid', paid_date = CURDATE()
             WHERE user_id = ? AND paid_status = 'unpaid'"
        );
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Student submits a payment request — flips status to 'payment_requested'.
     * Admin sees it in their fines page and can Approve/Reject.
     * If $fineId is provided, only that fine. Otherwise all unpaid fines for the user.
     */
    public function requestPayment(int $userId, ?int $fineId = null, string $method = 'counter'): bool|string
    {
        $valid_methods = ['counter','gcash','maya','bank'];
        if (!in_array($method, $valid_methods)) $method = 'counter';

        if ($fineId !== null) {
            // Verify the fine belongs to this user and is unpaid
            $chk = $this->conn->prepare(
                "SELECT id FROM fines WHERE id = ? AND user_id = ? AND paid_status = 'unpaid' LIMIT 1"
            );
            $chk->bind_param('ii', $fineId, $userId);
            $chk->execute();
            $chk->store_result();
            $exists = $chk->num_rows > 0;
            $chk->close();
            if (!$exists) return 'Fine not found or already submitted.';

            $stmt = $this->conn->prepare(
                "UPDATE fines
                 SET paid_status = 'payment_requested',
                     payment_method = ?,
                     payment_submitted_at = NOW()
                 WHERE id = ? AND user_id = ? AND paid_status = 'unpaid'"
            );
            $stmt->bind_param('sii', $method, $fineId, $userId);
        } else {
            // All unpaid fines for the user
            $stmt = $this->conn->prepare(
                "UPDATE fines
                 SET paid_status = 'payment_requested',
                     payment_method = ?,
                     payment_submitted_at = NOW()
                 WHERE user_id = ? AND paid_status = 'unpaid'"
            );
            $stmt->bind_param('si', $method, $userId);
        }

        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    /** Summary stats */
    public function getSummary(): array
    {
        $r = $this->conn->query(
            "SELECT
               COUNT(*) AS total_fines,
               SUM(CASE WHEN paid_status='unpaid' THEN amount ELSE 0 END) AS total_unpaid,
               SUM(CASE WHEN paid_status='paid'   THEN amount ELSE 0 END) AS total_paid,
               COUNT(DISTINCT CASE WHEN paid_status='unpaid' THEN user_id END) AS students_with_fines
             FROM fines"
        );
        return $r ? $r->fetch_assoc() : [];
    }
}