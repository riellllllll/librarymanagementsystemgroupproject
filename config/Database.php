<?php
// ============================================================
// config/Database.php — MySQLi OOP Connection Class
// ============================================================

class Database
{
    // ── Connection settings ──────────────────────────────────
    private string $host     = 'localhost';
    private string $dbname   = 'library_db';
    private string $username = 'root';
    private string $password = '';          // Change if your MySQL has a password
    private string $charset  = 'utf8mb4';

    private ?mysqli $conn = null;

    // ── Constructor: auto-connect ────────────────────────────
    public function __construct()
    {
        $this->connect();
    }

    // ── Establish connection ─────────────────────────────────
    private function connect(): void
    {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->dbname
        );

        if ($this->conn->connect_error) {
            // In production, log this instead of displaying
            error_log('DB Connection Error: ' . $this->conn->connect_error);
            $this->conn = null;
            return;
        }

        $this->conn->set_charset($this->charset);
    }

    // ── Return the active connection ─────────────────────────
    public function getConnection(): ?mysqli
    {
        return $this->conn;
    }

    // ── Close connection ─────────────────────────────────────
    public function close(): void
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    // ── Prevent cloning ──────────────────────────────────────
    private function __clone() {}
}