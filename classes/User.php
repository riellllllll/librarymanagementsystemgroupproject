<?php
// ============================================================
// classes/User.php — User Authentication & Management Class
// ============================================================

class User
{
    private mysqli $conn;

    // ── Constructor ──────────────────────────────────────────
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // ════════════════════════════════════════════════════════
    // AUTHENTICATION
    // ════════════════════════════════════════════════════════

    /**
     * Student login using student_number + password
     * Returns student row array on success, false on failure
     */
    public function loginStudent(string $studentNumber, string $password): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.full_name, u.student_number, u.email, u.password,
                    (SELECT COUNT(*) FROM borrow_records
                     WHERE user_id = u.id AND status IN ('active','overdue','pending_return')) AS active_borrows,
                    (SELECT COUNT(*) FROM fines
                     WHERE user_id = u.id AND paid_status = 'unpaid') AS unpaid_fines
             FROM users u
             WHERE u.student_number = ? AND u.role = 'student'
             LIMIT 1"
        );

        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $user['has_fines'] = (int)$user['unpaid_fines'] > 0;
            return $user;
        }

        return false;
    }

    /**
     * Admin login using username + password
     * Returns admin row array on success, false on failure
     */
    public function loginAdmin(string $username, string $password): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT id, username, full_name, email, password
             FROM users
             WHERE username = ? AND role = 'admin'
             LIMIT 1"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    // ════════════════════════════════════════════════════════
    // REGISTRATION
    // ════════════════════════════════════════════════════════

    /**
     * Register a new student account
     * Returns true on success, false if student_number/email already exists
     */
    public function register(array $data): bool
    {
        // Check for duplicate student_number or email
        $check = $this->conn->prepare(
            "SELECT id FROM users
             WHERE student_number = ? OR email = ?
             LIMIT 1"
        );
        $check->bind_param('ss', $data['student_number'], $data['email']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            return false;
        }
        $check->close();

        $full_name = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name']
        );

        $stmt = $this->conn->prepare(
            "INSERT INTO users
             (role, student_number, first_name, last_name, middle_name, full_name,
              email, password, dob, gender, course, year_level, phone)
             VALUES ('student', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // 12 variables → 12 's' characters in the type string
        $stmt->bind_param(
            'ssssssssssss',
            $data['student_number'],
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'],
            $full_name,
            $data['email'],
            $data['password'],      // already hashed in login.php
            $data['dob'],
            $data['gender'],
            $data['course'],
            $data['year_level'],
            $data['phone']
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // ADMIN — STUDENT MANAGEMENT
    // ════════════════════════════════════════════════════════

    /**
     * Get all students with borrow/fine stats
     */
    public function getAllStudents(): array
    {
        $result = $this->conn->query(
            "SELECT u.id, u.student_number, u.full_name, u.first_name, u.last_name, u.middle_name,
                    u.email, u.course, u.year_level, u.status, u.created_at, u.qr_token,
                    (SELECT COUNT(*) FROM borrow_records
                     WHERE user_id = u.id AND status IN ('active','overdue','pending_return'))
                     AS active_borrows,
                    (SELECT COALESCE(SUM(amount),0) FROM fines
                     WHERE user_id = u.id AND paid_status = 'unpaid')
                     AS total_fines
             FROM users u
             WHERE u.role = 'student'
             ORDER BY u.full_name ASC"
        );

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Backfill qr_token for students created before this feature existed.
     * Safe to call on every page load — only touches rows where it's NULL.
     */
    public function backfillQrTokens(): void
    {
        $result = $this->conn->query(
            "SELECT id FROM users WHERE role = 'student' AND qr_token IS NULL"
        );
        if (!$result) return;

        while ($row = $result->fetch_assoc()) {
            $this->regenerateQrToken((int)$row['id']);
        }
    }

    /**
     * Get one student by ID
     */
    public function getStudentById(int $id): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE id = ? AND role = 'student' LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? false;
    }

    /**
     * Admin manually adds a student account
     * Returns the new student's ID on success, or false on duplicate/failure
     */
    public function addStudent(array $data): int|false
    {
        // Check for duplicate
        $check = $this->conn->prepare(
            "SELECT id FROM users WHERE student_number = ? OR email = ? LIMIT 1"
        );
        $check->bind_param('ss', $data['student_number'], $data['email']);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) { $check->close(); return false; }
        $check->close();

        $middle    = $data['middle_name'] ?? '';
        $full_name = trim(
            $data['first_name'] . ' ' .
            ($middle !== '' ? $middle . ' ' : '') .
            $data['last_name']
        );
        $hashed    = password_hash($data['password'], PASSWORD_DEFAULT);
        $qrToken   = $this->generateUniqueQrToken();

        $stmt = $this->conn->prepare(
            "INSERT INTO users
             (role, student_number, first_name, last_name, middle_name, full_name, email, password, course, year_level, qr_token)
             VALUES ('student', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssssssss',
            $data['student_number'],
            $data['first_name'],
            $data['last_name'],
            $middle,
            $full_name,
            $data['email'],
            $hashed,
            $data['course'],
            $data['year_level'],
            $qrToken
        );
        $ok = $stmt->execute();
        $newId = $ok ? $this->conn->insert_id : false;
        $stmt->close();
        return $newId;
    }

    /**
     * Generate a random token for QR-based identity, guaranteed unique.
     * The token (not the student number) is what gets encoded in the QR
     * code, so a lost/photographed ID can't be used to guess another
     * student's code.
     */
    private function generateUniqueQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
            $stmt  = $this->conn->prepare("SELECT id FROM users WHERE qr_token = ? LIMIT 1");
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
        } while ($exists);

        return $token;
    }

    /**
     * Look up a student by their QR token (for future attendance scanning)
     */
    public function getStudentByQrToken(string $token): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT id, student_number, full_name, email, course, year_level, status
             FROM users WHERE qr_token = ? AND role = 'student' LIMIT 1"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?? false;
    }

    /**
     * Regenerate a student's QR token (e.g. if their ID/QR was compromised)
     */
    public function regenerateQrToken(int $id): string|false
    {
        $token = $this->generateUniqueQrToken();
        $stmt  = $this->conn->prepare(
            "UPDATE users SET qr_token = ? WHERE id = ? AND role = 'student'"
        );
        $stmt->bind_param('si', $token, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $token : false;
    }

    /**
     * Admin edits a student account
     * Returns true, or error string
     */
    public function editStudent(int $id, array $data): bool|string
    {
        // Check duplicate student_number/email (excluding self)
        $check = $this->conn->prepare(
            "SELECT id FROM users WHERE (student_number = ? OR email = ?) AND id != ? LIMIT 1"
        );
        $check->bind_param('ssi', $data['student_number'], $data['email'], $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            return 'Another student already uses that student number or email.';
        }
        $check->close();

        $middle = $data['middle_name'] ?? '';
        $full_name = trim(
            $data['first_name'] . ' ' .
            ($middle !== '' ? $middle . ' ' : '') .
            $data['last_name']
        );

        $stmt = $this->conn->prepare(
            "UPDATE users SET
                student_number = ?, first_name = ?, last_name = ?, middle_name = ?, full_name = ?,
                email = ?, course = ?, year_level = ?, status = ?
             WHERE id = ? AND role = 'student'"
        );
        $stmt->bind_param(
            'sssssssssi',
            $data['student_number'],
            $data['first_name'],
            $data['last_name'],
            $middle,
            $full_name,
            $data['email'],
            $data['course'],
            $data['year_level'],
            $data['status'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Delete a student (only if no active borrows)
     */
    public function deleteStudent(int $id): bool|string
    {
        // Check for active borrows first
        $check = $this->conn->prepare(
            "SELECT COUNT(*) AS cnt FROM borrow_records
             WHERE user_id = ? AND status IN ('active','overdue','pending_return')"
        );
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        if ((int)$row['cnt'] > 0) {
            return 'Cannot delete: student has active or pending borrows.';
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ════════════════════════════════════════════════════════
    // PROFILE UPDATE
    // ════════════════════════════════════════════════════════

    /**
     * Update student/admin profile info
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE users SET email = ?, phone = ?, course = ?, year_level = ? WHERE id = ?"
        );
        $stmt->bind_param(
            'ssssi',
            $data['email'],
            $data['phone'],
            $data['course'],
            $data['year_level'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Change password (verifies old password first)
     */
    public function changePassword(int $id, string $oldPassword, string $newPassword): bool|string
    {
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($oldPassword, $row['password'])) {
            return 'Current password is incorrect.';
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd    = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param('si', $hashed, $id);
        $ok = $upd->execute();
        $upd->close();
        return $ok;
    }
}