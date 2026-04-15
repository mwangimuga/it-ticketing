<?php
session_start();
require_once 'db_connect.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$allowedRoles)) {
        http_response_code(403);
        die("403 Forbidden: You do not have permission to access this page.");
    }
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'department' => $_SESSION['department'] ?? null
    ];
}
