<?php
require_once 'auth.php';
requireLogin();

$role = $_SESSION['role'] ?? null;

switch ($role) {
    case 'Admin':
        header("Location: admin_panel.php");
        break;
    case 'IT_Head':
        header("Location: head_dashboard.php");
        break;
    case 'IT_Agent':
        header("Location: agent_queue.php");
        break;
    case 'End_User':
    default:
        header("Location: end_user_dashboard.php");
        break;
}
exit;
