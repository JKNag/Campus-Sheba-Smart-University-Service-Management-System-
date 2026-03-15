<?php
// register.php - COMPLETELY FIXED VERSION
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    // Map admin to department_admin
    if ($role === 'admin') {
        $role = 'department_admin';
    }
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, username, role) VALUES (?, ?, ?, ?, ?)");
            $username = explode('@', $email)[0];
            
            if ($stmt->execute([$full_name, $email, $password, $username, $role])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Campus Sheba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-wrapper {
            width: 100%;
            max-width: 550px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            padding: 15px;
        }

        .logo-circle img {
            width: 100%;
            height: auto;
        }

        .register-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .register-form {
            padding: 30px;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #c62828;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #28a745;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Role Selection - 3 cards */
        .role-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .role-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .role-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .role-card.student i { color: #4299e1; }
        .role-card.staff i { color: #9f7aea; }
        .role-card.admin i { color: #f6ad55; }

        .role-card i {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }

        .role-card span {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .admin-note {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #ffc107;
        }

        .admin-note i {
            color: #856404;
        }

        .register-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-link p {
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
        }

        .back-home a:hover {
            opacity: 1;
            background: rgba(255,255,255,0.3);
        }

        .role-radio {
            display: none;
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <div class="register-header">
                <div class="logo-circle">
                    <img src="images/logo.jpeg" alt="Campus Sheba">
                </div>
                <h1>Create Account</h1>
                <p>Join Campus Sheba today</p>
            </div>
            
            <div class="register-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Create a password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>I am a</label>
                        
                        <input type="radio" name="role" id="role-student" value="student" class="role-radio" checked>
                        <input type="radio" name="role" id="role-staff" value="staff" class="role-radio">
                        <input type="radio" name="role" id="role-admin" value="admin" class="role-radio">
                        
                        <div class="role-container">
                            <div class="role-card student selected" onclick="document.getElementById('role-student').checked=true; document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('selected')); this.classList.add('selected');">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </div>
                            <div class="role-card staff" onclick="document.getElementById('role-staff').checked=true; document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('selected')); this.classList.add('selected');">
                                <i class="fas fa-user-tie"></i>
                                <span>Staff</span>
                            </div>
                            <div class="role-card admin" onclick="document.getElementById('role-admin').checked=true; document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('selected')); this.classList.add('selected');">
                                <i class="fas fa-user-cog"></i>
                                <span>Admin</span>
                            </div>
                        </div>
                        
                        <div class="admin-note">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Admin registration will be saved as Department Admin
                        </div>
                    </div>
                    
                    <button type="submit" class="register-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
        
        <div class="back-home">
            <a href="index.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>