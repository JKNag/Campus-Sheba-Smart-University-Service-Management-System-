<?php
require_once __DIR__ . '/config/database.php';
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    die("Invalid or expired token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'];
    $confPass = $_POST['confirm_password'];

    if (strlen($newPass) < 6) {
        $error = "Password too short.";
    } elseif ($newPass !== $confPass) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        // Update user
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$hashed, $resetRequest['email']]);
        // Delete token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")
            ->execute([$resetRequest['email']]);
        
        $success = "Password updated! <a href='login.php'>Login now</a>";
    }
}
?>
<form method="POST">
    <input type="password" name="new_password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Update Password</button>
</form>