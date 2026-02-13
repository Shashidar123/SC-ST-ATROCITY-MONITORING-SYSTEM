<?php
file_put_contents(__DIR__.'/../debug_ajax.txt', 'IN AUTH.PHP: '.date('c').PHP_EOL, FILE_APPEND);
session_start();
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after session_start '.date('c').PHP_EOL, FILE_APPEND);
require_once __DIR__ . '/../includes/db.php';
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after require db '.date('c').PHP_EOL, FILE_APPEND);

function authenticate($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

function requireLogin() {
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: in requireLogin '.date('c').PHP_EOL, FILE_APPEND);
    if (!isset($_SESSION['user_id'])) {
        file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH REDIRECT/EXIT: '.date('c').PHP_EOL, FILE_APPEND);
        header('Location: login.php');
        exit();
    }
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: passed login check '.date('c').PHP_EOL, FILE_APPEND);
}
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after requireLogin def '.date('c').PHP_EOL, FILE_APPEND);

function requireRole($role) {
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: in requireRole '.date('c').PHP_EOL, FILE_APPEND);
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH REDIRECT/EXIT: '.date('c').PHP_EOL, FILE_APPEND);
        header('Location: ' . getRoleRedirect($_SESSION['role']));
        exit();
    }
}
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after requireRole def '.date('c').PHP_EOL, FILE_APPEND);

function getRoleRedirect($role) {
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: in getRoleRedirect '.date('c').PHP_EOL, FILE_APPEND);
    switch ($role) {
        case 'admin':
            return 'admin_dashboard.php';
        case 'police':
            return 'police.php';
        case 'c_section':
            return 'c_section_dashboard.php';
        case 'collector':
            return 'collector_dashboard.php';
        case 'social_welfare':
            return 'social_welfare_dashboard.php';
        case 'rdo':
            return 'sp_dashboard.php';
        case 'dcr':
            return 'dcr_dashboard.php';
        case 'io':
            return 'io_dashboard.php';
        default:
            return 'login.php';
    }
}
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after getRoleRedirect def '.date('c').PHP_EOL, FILE_APPEND);

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

function logout() {
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: in logout '.date('c').PHP_EOL, FILE_APPEND);
    session_destroy();
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH REDIRECT/EXIT: '.date('c').PHP_EOL, FILE_APPEND);
    header('Location: login.php');
    exit();
}
file_put_contents(__DIR__.'/../debug_ajax.txt', 'AUTH: after logout def '.date('c').PHP_EOL, FILE_APPEND);
?> 