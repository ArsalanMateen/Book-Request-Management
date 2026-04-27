<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Redirect to user login if not a logged-in regular user
function requireUser(): void {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
        app_redirect('user/login.php');
    }
}

// Redirect to admin login if not a logged-in admin
function requireAdmin(): void {
    if (empty($_SESSION['admin_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'])) {
        app_redirect('admin/login.php');
    }
}

// Redirect to superadmin login if not superadmin
function requireSuperAdmin(): void {
    if (empty($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
        app_redirect('superadmin/login.php');
    }
}

// Return true if user is logged in
function isUser(): bool {
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user';
}

// Return true if admin or superadmin is logged in
function isAdmin(): bool {
    return !empty($_SESSION['admin_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'superadmin']);
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function categoryLabel(string $category): string {
    static $labels = [
        'app_development' => 'App Development',
        'mobile_development' => 'Mobile Development',
        'ai' => 'Artificial Intelligence',
    ];

    return $labels[$category] ?? ucwords(str_replace('_', ' ', $category));
}

function statusBadge(string $status): string {
    $status = strtolower(trim($status));
    if ($status === 'pending') {
        return '<span class="badge badge-pending">Pending</span>';
    }
    if ($status === 'in_progress') {
        return '<span class="badge badge-progress">In Progress</span>';
    }
    return '<span class="badge badge-completed">Completed</span>';
}

function displayUsername(string $username): string {
    static $demoNames = [
        'demo_aya' => 'Aya Malik',
        'demo_liam' => 'Liam Walker',
        'demo_noah' => 'Noah Carter',
        'demo_zoe' => 'Zoe Hassan',
        'demo_mila' => 'Mila Khan',
        'demo_omar' => 'Omar Tariq',
        'demo_hana' => 'Hana Qureshi',
        'demo_ivan' => 'Ivan Petrov',
        'demo_luna' => 'Luna Reed',
        'demo_reza' => 'Reza Farid',
        'demo_nina' => 'Nina Shah',
        'demo_adam' => 'Adam Brooks',
    ];

    if (isset($demoNames[$username])) {
        return $demoNames[$username];
    }

    $clean = preg_replace('/^demo[_\.-]?/i', '', $username);
    $clean = str_replace(['_', '.', '-'], ' ', $clean);
    return ucwords(trim($clean));
}
