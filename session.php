<?php
// Start the session on every page that needs to access session data
session_start();

// Function to check if the user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

// Function to check if the user has a specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to check if the user has a superadmin role
function isSuperadmin() {
    return hasRole('Superadmin');
}

// Function to check if the user has an admin role
function isAdmin() {
    return hasRole('Admin');
}

// Function to check if the user has a viewer role
function isViewer() {
    return hasRole('Viewer');
}

// Function to restrict access to only superadmins
function requireSuperadmin() {
    if (!isSuperadmin()) {
        header("Location: no_access.php"); // Redirect to a no access page
        exit();
    }
}

// Function to restrict access to only admins
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: no_access.php"); // Redirect to a no access page
        exit();
    }
}

// Function to restrict access to only viewers
function requireViewer() {
    if (!isViewer()) {
        header("Location: no_access.php"); // Redirect to a no access page
        exit();
    }
}

// Redirect the user to the login page if not logged in
if (!isLoggedIn()) {
    header("Location: login.php"); // Redirect to login page
    exit(); // Ensure the script stops executing after redirect
}
// Function to log the user out
function logout() {
    session_unset(); // Remove all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to the login page
    exit();
}
?>
