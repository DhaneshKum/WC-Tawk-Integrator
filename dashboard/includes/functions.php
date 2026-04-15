<?php
// ============================================================
// HELPER FUNCTIONS
// ============================================================

// Format currency
function formatCurrency($amount, $currency = 'PKR') {
    return $currency . ' ' . number_format(floatval($amount), 2);
}

// Format date
function formatDate($date, $format = 'd M Y, h:i A') {
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'pending'    => 'badge-warning',
        'processing' => 'badge-primary',
        'on-hold'    => 'badge-secondary',
        'completed'  => 'badge-success',
        'cancelled'  => 'badge-danger',
        'refunded'   => 'badge-info',
        'failed'     => 'badge-danger',
        'open'       => 'badge-success',
        'missed'     => 'badge-warning',
        'closed'     => 'badge-secondary',
        'active'     => 'badge-success',
        'inactive'   => 'badge-danger',
        'admin'      => 'badge-purple',
        'agent'      => 'badge-primary',
        'super_admin'=> 'badge-dark',
    ];

    $class = $badges[strtolower($status)] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst(str_replace('-', ' ', $status)) . '</span>';
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60)     return $time . 's ago';
    if ($time < 3600)   return floor($time / 60) . 'm ago';
    if ($time < 86400)  return floor($time / 3600) . 'h ago';
    if ($time < 604800) return floor($time / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

// Get avatar initials
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type']    = $type;
    }
    header('Location: ' . $url);
    exit;
}

// Get flash message
function getFlash() {
    if (!empty($_SESSION['flash_message'])) {
        $msg  = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $msg, 'type' => $type];
    }
    return null;
}

// JSON Response
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Pagination helper
function getPagination($currentPage, $totalItems, $perPage, $url) {
    $totalPages = ceil($totalItems / $perPage);
    return [
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'per_page'    => $perPage,
        'has_prev'    => $currentPage > 1,
        'has_next'    => $currentPage < $totalPages,
        'prev_url'    => $url . '?page=' . ($currentPage - 1),
        'next_url'    => $url . '?page=' . ($currentPage + 1),
    ];
}
