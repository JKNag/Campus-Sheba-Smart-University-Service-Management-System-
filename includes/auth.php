<?php
// includes/auth.php
//updated
require_once __DIR__ . '/../config/database.php';

function loginUser($email, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) return false;
        $valid = password_verify($password, $user['password']);
        if ($valid) {
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['logged_in']  = true;
            logActivity($user['id'], 'login', 'user', $user['id']);
            return $user;
        }
        return false;
    } catch (PDOException $e) { return false; }
}

function isLoggedIn()  { return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true; }
function getUserRole() { return $_SESSION['user_role'] ?? null; }
function getUserId()   { return $_SESSION['user_id']   ?? null; }
function getUserName() { return $_SESSION['user_name'] ?? null; }

function requireLogin() {
    if (!isLoggedIn()) { header("Location: " . getBaseUrl() . "login.php"); exit(); }
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array(getUserRole(), $roles)) {
        header("Location: " . getBaseUrl() . "login.php?error=unauthorized"); exit();
    }
}

function redirectToDashboard() {
    $b = getBaseUrl();
    switch (getUserRole()) {
        case 'super_admin': case 'department_admin':
            header("Location: {$b}pages/admin-dashboard.php"); break;
        case 'staff':
            header("Location: {$b}pages/staff-dashboard.php"); break;
        case 'student':
            header("Location: {$b}pages/student-dashboard.php"); break;
        default:
            header("Location: {$b}index.html");
    }
    exit();
}

function logout() {
    if (isLoggedIn()) logActivity(getUserId(), 'logout', 'user', getUserId());
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    setcookie('remember_email', '', time() - 3600, '/');
    session_destroy();
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Location: " . getBaseUrl() . "login.php");
    exit();
}

function logActivity($userId, $action, $entityType = null, $entityId = null, $old = null, $new = null) {
    global $pdo;
    try {
        $pdo->prepare(
            "INSERT INTO activity_logs (user_id,action,entity_type,entity_id,old_value,new_value,ip_address,user_agent)
             VALUES (?,?,?,?,?,?,?,?)"
        )->execute([
            $userId, $action, $entityType, $entityId,
            $old ? json_encode($old) : null,
            $new ? json_encode($new) : null,
            $_SERVER['REMOTE_ADDR']     ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (PDOException $e) {}
}

/**
 * Dynamically builds the base URL — works on Windows XAMPP, Linux, any folder name.
 * From root files  (login.php, etc.)  → http://localhost/campus_sheba/
 * From pages/      (any page/*.php)   → http://localhost/campus_sheba/
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts    = explode('/', trim($script, '/'));

    // If inside /pages/ subfolder, pop filename + 'pages'
    if (count($parts) >= 2 && $parts[count($parts) - 2] === 'pages') {
        array_pop($parts); array_pop($parts);
    } else {
        array_pop($parts); // just pop filename
    }

    $base = implode('/', $parts);
    return $protocol . '://' . $host . ($base ? '/' . $base . '/' : '/');
}

function sanitize($v) { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }
?>
