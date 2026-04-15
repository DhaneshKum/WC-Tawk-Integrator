<?php
// ============================================================
// CONFIG FILE - Edit these settings before using
// ============================================================

// Database Configuration
define('DB_HOST', 'localhost:/home/maheshkumar/.config/Local/run/WTBfk--ad/mysql/mysqld.sock');
define('DB_NAME', 'local');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// WordPress / WooCommerce API Settings
define('WC_STORE_URL', 'https://uscarinfo.local'); // Your WordPress URL
define('WC_CONSUMER_KEY', 'ck_72f57ca8885bdcd066f8a1c8eaf9788a510f6ac1');
define('WC_CONSUMER_SECRET', 'cs_17839a55d1f9dabe665fd186f1d47d17fa4e44b2');

// Tawk.to API Settings
define('TAWKTO_API_KEY', 'your_tawkto_api_key');         // From Tawk.to > Admin > API
define('TAWKTO_PROPERTY_ID', 'your_property_id');        // Property ID from Tawk.to
define('TAWKTO_INBOX_ID', 'your_inbox_id');              // Inbox ID

// App Settings
define('APP_NAME', 'Admin Dashboard');
define('APP_URL', 'http://localhost:8000');
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Karachi');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
