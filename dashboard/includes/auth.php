<?php
require_once __DIR__ . '/db.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Login user
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
            [$username, $username]
        );

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['avatar']    = $user['avatar'];
            $_SESSION['login_time'] = time();

            // Update last login
            $this->db->execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    // Logout
    public function logout() {
        session_destroy();
        return true;
    }

    // Check if logged in
    public static function check() {
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        // Session timeout check
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            session_destroy();
            return false;
        }
        return true;
    }

    // Require login (redirect if not)
    public static function requireLogin() {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    // Require admin role
    public static function requireAdmin() {
        self::requireLogin();
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
            header('Location: ' . APP_URL . '/index.php?error=unauthorized');
            exit;
        }
    }

    // Get current user
    public static function user() {
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'username'  => $_SESSION['username']  ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role']      ?? null,
            'email'     => $_SESSION['email']     ?? null,
            'avatar'    => $_SESSION['avatar']    ?? null,
        ];
    }

    // Create new user
    public function createUser($data) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$data['username'], $data['email']]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Username or Email already exists'];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $id = $this->db->insert(
            "INSERT INTO users (username, email, full_name, password, role, status, created_at) 
             VALUES (?, ?, ?, ?, ?, 'active', NOW())",
            [
                $data['username'],
                $data['email'],
                $data['full_name'],
                $hashedPassword,
                $data['role'] ?? 'agent'
            ]
        );

        return ['success' => true, 'id' => $id];
    }

    // Update user
    public function updateUser($id, $data) {
        $fields = [];
        $params = [];

        if (!empty($data['full_name'])) {
            $fields[] = 'full_name = ?';
            $params[] = $data['full_name'];
        }
        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (!empty($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (!empty($data['status'])) {
            $fields[] = 'status = ?';
            $params[] = $data['status'];
        }
        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No data to update'];
        }

        $params[] = $id;
        $this->db->execute(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );

        return ['success' => true];
    }

    // Delete user
    public function deleteUser($id) {
        // Cannot delete yourself
        if ($id == ($_SESSION['user_id'] ?? 0)) {
            return ['success' => false, 'message' => 'Cannot delete your own account'];
        }
        $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
        return ['success' => true];
    }

    // Get all users
    public function getAllUsers() {
        return $this->db->fetchAll("SELECT id, username, email, full_name, role, status, last_login, created_at FROM users ORDER BY created_at DESC");
    }
}
