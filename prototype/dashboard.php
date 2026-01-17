<?php

require_once 'config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$username = getCurrentUsername();
$roleId = getCurrentRoleId();
$roleName = $_SESSION['role_name'] ?? 'User';
$userId = getCurrentUserId();

// Redirect based on role
if ($roleId == 1) {
    // Student - redirect to student dashboard
    redirect('student_dashboard.php');
} elseif ($roleId == 2) {
    // Professor - redirect to professor dashboard
    redirect('professor_dashboard.php');
} else {
    // Unknown role - show error
    echo "Unknown user role. Please contact administrator.";
    exit();
}
?>