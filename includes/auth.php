<?php
// includes/auth.php
require_once __DIR__ . '/../config/database.php';

function loginUser($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $password == $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectToDashboard() {
    $role = getUserRole();
    
    switch($role) {
        case 'super_admin':
        case 'department_admin':
            header("Location: pages/admin-dashboard.php");
            break;
        case 'staff':
            header("Location: pages/staff-dashboard.php");
            break;
        case 'student':
            header("Location: pages/student-dashboard.php");
            break;
        default:
            header("Location: index.html");
            break;
    }
    exit();
}

function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    setcookie('remember_email', '', time() - 3600, '/');
    session_destroy();
    
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Location: login.php");
    exit();
}
?>