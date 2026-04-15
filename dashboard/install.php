<?php
/**
 * INSTALLER - Run once to setup the database
 * DELETE this file after installation!
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'dashboard_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host    = $_POST['db_host'] ?? '127.0.0.1';
    $dbname  = $_POST['db_name'] ?? 'dashboard_db';
    $dbuser  = $_POST['db_user'] ?? 'root';
    $dbpass  = $_POST['db_pass'] ?? '';
    $adminUser = $_POST['admin_username'] ?? 'admin';
    $adminEmail = $_POST['admin_email'] ?? '';
    $adminPass = $_POST['admin_password'] ?? '';

    try {
        // Parse host for LocalWP sockets or custom ports
        $cleanHost = str_replace('localhost:', '', $host);
        
        if (strpos($cleanHost, '/') === 0) {
            $pdoDsn = "mysql:unix_socket=$cleanHost;charset=utf8mb4";
        } elseif (strpos($cleanHost, ':') !== false) {
            list($ip, $port) = explode(':', $cleanHost);
            $pdoDsn = "mysql:host=$ip;port=$port;charset=utf8mb4";
        } else {
            $pdoDsn = "mysql:host=$cleanHost;charset=utf8mb4";
        }

        // Connect without DB first
        $pdo = new PDO($pdoDsn, $dbuser, $dbpass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) UNIQUE NOT NULL,
                `email` VARCHAR(100) UNIQUE NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('super_admin','admin','agent') DEFAULT 'agent',
                `status` ENUM('active','inactive') DEFAULT 'active',
                `avatar` VARCHAR(255) DEFAULT NULL,
                `last_login` DATETIME DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create chat_notes table (internal notes for chats)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `chat_notes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `chat_id` VARCHAR(100) NOT NULL,
                `user_id` INT NOT NULL,
                `note` TEXT NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_chat_id` (`chat_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create activity_log table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `activity_log` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT DEFAULT NULL,
                `action` VARCHAR(100) NOT NULL,
                `details` TEXT DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Insert admin user
        if ($adminUser && $adminEmail && $adminPass) {
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, full_name, password, role) VALUES (?, ?, 'Super Admin', ?, 'super_admin')");
            $stmt->execute([$adminUser, $adminEmail, $hash]);
            $success[] = "Admin user created: <strong>$adminUser</strong>";
        }

        // Update config file
        $configContent = file_get_contents(__DIR__ . '/includes/config.php');
        $configContent = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$host');", $configContent);
        $configContent = str_replace("define('DB_NAME', 'dashboard_db');", "define('DB_NAME', '$dbname');", $configContent);
        $configContent = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$dbuser');", $configContent);
        $configContent = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$dbpass');", $configContent);
        file_put_contents(__DIR__ . '/includes/config.php', $configContent);

        $success[] = "Database <strong>$dbname</strong> created/updated successfully!";
        $success[] = "Tables created: users, chat_notes, activity_log";
        $success[] = "<strong style='color:red'>⚠️ Please delete install.php after setup!</strong>";

    } catch (PDOException $e) {
        $errors[] = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .card-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px 15px 0 0 !important; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center py-4">
                    <h3 class="mb-0">🚀 Dashboard Installer</h3>
                    <small>One-time setup</small>
                </div>
                <div class="card-body p-4">
                    <?php foreach ($errors as $e): ?>
                        <div class="alert alert-danger"><?= $e ?></div>
                    <?php endforeach; ?>
                    <?php foreach ($success as $s): ?>
                        <div class="alert alert-success"><?= $s ?></div>
                    <?php endforeach; ?>

                    <?php if (empty($success)): ?>
                    <form method="POST">
                        <h6 class="text-muted mb-3">Database Settings</h6>
                        <div class="mb-3">
                            <label class="form-label">DB Host</label>
                            <input type="text" name="db_host" class="form-control" value="127.0.0.1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">DB Name</label>
                            <input type="text" name="db_name" class="form-control" value="dashboard_db" required>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">DB Username</label>
                                    <input type="text" name="db_user" class="form-control" value="root" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="mb-3">
                                    <label class="form-label">DB Password</label>
                                    <input type="password" name="db_pass" class="form-control">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3">Admin Account</h6>
                        <div class="mb-3">
                            <label class="form-label">Admin Username</label>
                            <input type="text" name="admin_username" class="form-control" value="admin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="admin_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Password</label>
                            <input type="password" name="admin_password" class="form-control" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">Install Dashboard</button>
                    </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-success btn-lg">Go to Login →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
