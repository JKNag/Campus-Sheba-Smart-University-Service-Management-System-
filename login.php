<?php
<<<<<<< HEAD
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isset($_GET['logout'])) logout();
if (isLoggedIn()) redirectToDashboard();
=======
// login.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user wants to switch account
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    logout();
}

if (isLoggedIn()) {
    redirectToDashboard();
}
>>>>>>> d0e0a54232ff112c6b2de5a9005bff7a4384372e

$error = '';
$rememberedEmail = $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
<<<<<<< HEAD
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $user = loginUser($email, $password);
        if ($user) {
            if (isset($_POST['remember'])) setcookie('remember_email', $email, time()+86400*30, '/');
            redirectToDashboard();
        } else {
            $error = 'Invalid email or password.';
=======
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $user = loginUser($email, $password);
        
        if ($user) {
            if (isset($_POST['remember'])) {
                setcookie('remember_email', $email, time() + (86400 * 30), "/");
            }
            redirectToDashboard();
        } else {
            $error = 'Invalid email or password';
>>>>>>> d0e0a54232ff112c6b2de5a9005bff7a4384372e
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Sheba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
<<<<<<< HEAD
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);
             min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .wrapper{width:100%;max-width:450px}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden}
        .hdr{background:linear-gradient(135deg,#1e3c72,#2a5298);padding:40px 30px;text-align:center;color:#fff}
        .logo-circle{width:100px;height:100px;background:#fff;border-radius:50%;display:flex;
                     align-items:center;justify-content:center;margin:0 auto 20px;padding:14px}
        .logo-circle img{width:100%;height:auto}
        .hdr h1{font-size:28px;margin-bottom:6px}
        .hdr p{opacity:.9;font-size:14px}
        .body{padding:38px}
        .alert{padding:13px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;
               gap:10px;font-size:14px}
        .alert-error{background:#ffebee;color:#c62828;border-left:4px solid #c62828}
        .alert-info{background:#e8f5e9;color:#1b5e20;border-left:4px solid #2e7d32}
        .form-group{margin-bottom:22px}
        .form-group label{display:block;margin-bottom:8px;color:#333;font-weight:500;font-size:14px}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#aaa;font-size:17px}
        .input-wrap input{width:100%;padding:14px 14px 14px 44px;border:2px solid #e0e0e0;
                          border-radius:10px;font-size:15px;font-family:'Poppins',sans-serif;transition:.3s}
        .input-wrap input:focus{border-color:#667eea;outline:none}
        .form-options{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
        .remember{display:flex;align-items:center;gap:8px;font-size:14px;color:#666;cursor:pointer}
        .forgot a{color:#667eea;font-size:14px;text-decoration:none;font-weight:500}
        .btn-login{width:100%;padding:15px;background:linear-gradient(135deg,#667eea,#764ba2);
                   color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;
                   cursor:pointer;transition:.3s;display:flex;align-items:center;justify-content:center;gap:8px}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(102,126,234,.4)}
        .register-link{text-align:center;margin-top:22px;padding-top:22px;
                       border-top:1px solid #eee;font-size:14px;color:#666}
        .register-link a{color:#667eea;font-weight:600;text-decoration:none}
        .test-creds{background:#f8f9fa;padding:14px;border-radius:10px;margin-top:18px;font-size:12px;color:#555}
        .test-creds strong{display:block;margin-bottom:6px;color:#333}
        .back{text-align:center;margin-top:18px}
        .back a{color:#fff;font-size:14px;background:rgba(255,255,255,.2);padding:8px 22px;
                border-radius:50px;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .back a:hover{background:rgba(255,255,255,.3)}
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="hdr">
            <div class="logo-circle"><img src="images/logo.jpeg" alt="Campus Sheba"></div>
            <h1>Welcome Back!</h1>
            <p>Login to access your dashboard</p>
        </div>
        <div class="body">
            <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-info"><i class="fas fa-check-circle"></i> Account created! You can now log in.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error']==='unauthorized'): ?>
            <div class="alert alert-error"><i class="fas fa-ban"></i> Access denied.</div>
            <?php endif; ?>
            <?php if (isset($_GET['reset'])): ?>
            <div class="alert alert-info"><i class="fas fa-check-circle"></i> Password reset successfully! You can now log in with your new password.</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email"
                               value="<?= htmlspecialchars($rememberedEmail) ?>" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" <?= $rememberedEmail ? 'checked' : '' ?>>
                        Remember me
                    </label>
                    <div class="forgot"><a href="forgot-password.php">Forgot Password?</a></div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Sign up here</a>
            </div>
        </div>
    </div>
    <div class="back"><a href="index.html"><i class="fas fa-arrow-left"></i> Back to Home</a></div>
</div>
</body>
</html>
=======
        /* Add your login CSS here */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-wrapper { width: 100%; max-width: 450px; }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .logo-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            padding: 15px;
        }
        .logo-circle img { width: 100%; height: auto; }
        .login-header h1 { font-size: 28px; margin-bottom: 5px; }
        .login-header p { opacity: 0.9; font-size: 14px; }
        .login-form { padding: 40px; }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #c62828;
        }
        .form-group { margin-bottom: 25px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .input-group { position: relative; }
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
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
        }
        .input-group input:focus {
            border-color: #667eea;
            outline: none;
        }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .remember-me label {
            color: #666;
            font-size: 14px;
            cursor: pointer;
        }
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        .register-link p { color: #666; font-size: 14px; }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .test-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 25px;
            font-size: 12px;
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
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-circle">
                    <img src="images/logo.jpeg" alt="Campus Sheba">
                </div>
                <h1>Welcome Back!</h1>
                <p>Login to access your dashboard</p>
            </div>
            
            <div class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($rememberedEmail); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" name="remember" id="remember" <?php echo $rememberedEmail ? 'checked' : ''; ?>>
                            <label for="remember">Remember me</label>
                        </div>
                        <div class="forgot-password">
                            <a href="#">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
                </div>
                
                <div class="test-credentials">
                    <p><strong>Test Credentials:</strong></p>
                    <p>👨‍🎓 Student: student@campus.edu / student123</p>
                    <p>👨‍💼 Staff: staff@campus.edu / staff123</p>
                    <p>👑 Admin: admin@campus.edu / admin123</p>
                </div>
            </div>
        </div>
        
        <div class="back-home">
            <a href="index.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>
>>>>>>> d0e0a54232ff112c6b2de5a9005bff7a4384372e
