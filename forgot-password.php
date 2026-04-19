<?php
// forgot-password.php — Password reset via email + full name verification
// No email server needed — works with existing users table only
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirectToDashboard();

// Allow user to restart the flow
if (isset($_GET['restart'])) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['reset_email']);
    header("Location: forgot-password.php"); exit();
}

$step  = 1;
$error = '';

// ── STEP 1: Verify email + full name ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === '1') {
    $email    = trim($_POST['email']     ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    if (!$email || !$fullName) {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && strtolower(trim($user['full_name'])) === strtolower($fullName)) {
            $_SESSION['reset_user_id']  = $user['id'];
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_email']    = $email;
            $step = 2;
        } else {
            $error = 'No account found with that email and full name combination.';
        }
    }
}

// ── STEP 2: Save new password ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === '2') {
    if (empty($_SESSION['reset_verified']) || empty($_SESSION['reset_user_id'])) {
        $error = 'Session expired. Please start again.';
        $step  = 1;
    } else {
        $newPass  = $_POST['new_password']     ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step  = 2;
        } elseif ($newPass !== $confPass) {
            $error = 'Passwords do not match.';
            $step  = 2;
        } else {
            $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $_SESSION['reset_user_id']]);

            unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['reset_email']);
            header("Location: login.php?reset=1");
            exit();
        }
    }
}

// Restore step 2 if session still valid (e.g. page refresh)
if ($step === 1 && !empty($_SESSION['reset_verified'])) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Campus Sheba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;
             background:linear-gradient(135deg,#667eea,#764ba2);
             min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .wrapper{width:100%;max-width:450px}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden}

        /* Header */
        .hdr{background:linear-gradient(135deg,#1e3c72,#2a5298);padding:36px 30px;
             text-align:center;color:#fff}
        .icon-circle{width:80px;height:80px;background:rgba(255,255,255,.15);border-radius:50%;
                     display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
                     border:3px solid rgba(255,255,255,.3)}
        .icon-circle i{font-size:34px;color:#fff}
        .hdr h1{font-size:22px;margin-bottom:5px;font-weight:700}
        .hdr p{opacity:.85;font-size:13px}

        /* Steps */
        .steps{display:flex;align-items:center;justify-content:center;padding:18px 30px 0}
        .step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;
                  justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:.3s}
        .step-dot.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
                         box-shadow:0 3px 10px rgba(102,126,234,.4)}
        .step-dot.done{background:#43a047;color:#fff}
        .step-dot.inactive{background:#f0f2f5;color:#bbb}
        .step-line{flex:1;height:3px;background:#f0f2f5;margin:0 6px;border-radius:2px}
        .step-line.done{background:linear-gradient(90deg,#667eea,#764ba2)}
        .step-label{display:flex;justify-content:space-between;padding:6px 22px 0;
                    font-size:11px;color:#aaa;font-weight:500}

        /* Body */
        .body{padding:28px 32px 36px}
        .alert{padding:12px 15px;border-radius:9px;margin-bottom:18px;
               display:flex;align-items:center;gap:9px;font-size:13px}
        .alert-error{background:#ffebee;color:#c62828;border-left:4px solid #c62828}
        .alert-info{background:#e3f2fd;color:#1565c0;border-left:4px solid #1976d2}

        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:7px;color:#333;font-weight:500;font-size:13px}
        .input-wrap{position:relative}
        .input-wrap .ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);
                         color:#aaa;font-size:16px}
        .input-wrap input{width:100%;padding:13px 13px 13px 42px;border:2px solid #e0e0e0;
                          border-radius:10px;font-size:14px;font-family:'Poppins',sans-serif;transition:.3s}
        .input-wrap input:focus{border-color:#667eea;outline:none;
                                box-shadow:0 0 0 3px rgba(102,126,234,.1)}
        .hint{font-size:12px;color:#888;margin-top:5px;display:flex;align-items:center;gap:5px}

        /* Password strength */
        .strength-bar{height:4px;border-radius:2px;background:#f0f2f5;margin-top:6px;overflow:hidden}
        .strength-fill{height:100%;width:0;border-radius:2px;transition:.4s}
        .strength-label{font-size:11px;margin-top:4px;font-weight:600}

        /* Eye toggle */
        .eye-btn{position:absolute;right:13px;top:50%;transform:translateY(-50%);
                 background:none;border:none;cursor:pointer;color:#aaa;font-size:15px;padding:0}
        .eye-btn:hover{color:#667eea}

        .btn-submit{width:100%;padding:14px;
                    background:linear-gradient(135deg,#667eea,#764ba2);
                    color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;
                    cursor:pointer;transition:.3s;display:flex;align-items:center;
                    justify-content:center;gap:8px;font-family:'Poppins',sans-serif;margin-top:4px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,.4)}

        hr{border:none;border-top:1px solid #eee;margin:20px 0}
        .back-login{text-align:center;font-size:13px;color:#666}
        .back-login a{color:#667eea;font-weight:600;text-decoration:none}
        .back-login a:hover{text-decoration:underline}

        .verified-badge{background:#e8f5e9;border:1px solid #a5d6a7;border-radius:10px;
                        padding:11px 14px;margin-bottom:18px;display:flex;align-items:center;
                        gap:9px;font-size:13px;color:#2e7d32;font-weight:500}

        .back{text-align:center;margin-top:18px}
        .back a{color:#fff;font-size:13px;background:rgba(255,255,255,.2);padding:8px 22px;
                border-radius:50px;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .back a:hover{background:rgba(255,255,255,.3)}
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        <!-- Header -->
        <div class="hdr">
            <div class="icon-circle">
                <i class="fas <?= $step === 2 ? 'fa-key' : 'fa-lock-open' ?>"></i>
            </div>
            <h1><?= $step === 2 ? 'Set New Password' : 'Forgot Password?' ?></h1>
            <p><?= $step === 2 ? 'Choose a strong new password below' : 'Verify your identity to reset your password' ?></p>
        </div>

        <!-- Step Indicator -->
        <div class="steps">
            <div class="step-dot <?= $step > 1 ? 'done' : 'active' ?>">
                <?= $step > 1 ? '<i class="fas fa-check" style="font-size:12px"></i>' : '1' ?>
            </div>
            <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
            <div class="step-dot <?= $step >= 2 ? 'active' : 'inactive' ?>">2</div>
        </div>
        <div class="step-label">
            <span style="color:<?= $step >= 1 ? '#667eea' : '#bbb' ?>">Verify Identity</span>
            <span style="color:<?= $step >= 2 ? '#667eea' : '#bbb' ?>">New Password</span>
        </div>

        <div class="body">

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- ── STEP 1: Identity Verification ── -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Enter your registered email and full name to verify your identity.
            </div>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                    <label>Registered Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope ico"></i>
                        <input type="email" name="email" placeholder="your@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Full Name
                        <small style="color:#aaa;font-weight:400">(exactly as registered)</small>
                    </label>
                    <div class="input-wrap">
                        <i class="fas fa-user ico"></i>
                        <input type="text" name="full_name" placeholder="Your full name"
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                               required>
                    </div>
                    <div class="hint">
                        <i class="fas fa-info-circle" style="color:#667eea"></i>
                        Case-insensitive — e.g. "john doe" works for "John Doe"
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-arrow-right"></i> Verify &amp; Continue
                </button>
            </form>

            <?php else: ?>
            <!-- ── STEP 2: New Password ── -->
            <div class="verified-badge">
                <i class="fas fa-check-circle"></i>
                Identity verified for
                <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>
            </div>
            <form method="POST" autocomplete="off" id="resetForm">
                <input type="hidden" name="step" value="2">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock ico"></i>
                        <input type="password" name="new_password" id="newPass"
                               placeholder="Min. 6 characters"
                               oninput="checkStrength(this.value)" required>
                        <button type="button" class="eye-btn"
                                onclick="toggleEye('newPass','eye1')">
                            <i class="fas fa-eye" id="eye1"></i>
                        </button>
                    </div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-label" id="strengthLabel" style="color:#aaa"></div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock ico"></i>
                        <input type="password" name="confirm_password" id="confPass"
                               placeholder="Repeat your new password"
                               oninput="checkMatch()" required>
                        <button type="button" class="eye-btn"
                                onclick="toggleEye('confPass','eye2')">
                            <i class="fas fa-eye" id="eye2"></i>
                        </button>
                    </div>
                    <div id="matchMsg" style="font-size:12px;margin-top:4px;font-weight:600"></div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
            <hr>
            <p style="text-align:center;font-size:12px;color:#aaa">
                <a href="?restart=1" style="color:#667eea;text-decoration:none">
                    <i class="fas fa-redo" style="font-size:11px"></i>
                    Start over with a different account
                </a>
            </p>
            <?php endif; ?>

            <hr>
            <div class="back-login">
                Remembered your password?
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <div class="back">
        <a href="index.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>
</div>

<script>
function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)           score++;
    if (val.length >= 10)          score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        {pct:'0%',   color:'#f0f2f5', text:'',            tc:'#aaa'    },
        {pct:'25%',  color:'#ef5350', text:'Weak',         tc:'#ef5350' },
        {pct:'50%',  color:'#ff9800', text:'Fair',         tc:'#ff9800' },
        {pct:'75%',  color:'#ffc107', text:'Good',         tc:'#f57f17' },
        {pct:'90%',  color:'#66bb6a', text:'Strong',       tc:'#43a047' },
        {pct:'100%', color:'#2e7d32', text:'Very Strong',  tc:'#2e7d32' },
    ];
    const l = levels[Math.min(score, 5)];
    fill.style.width      = l.pct;
    fill.style.background = l.color;
    label.textContent     = l.text;
    label.style.color     = l.tc;
    checkMatch();
}

function checkMatch() {
    const np  = document.getElementById('newPass').value;
    const cp  = document.getElementById('confPass').value;
    const msg = document.getElementById('matchMsg');
    if (!cp) { msg.textContent = ''; return; }
    if (np === cp) {
        msg.textContent = '✅ Passwords match';
        msg.style.color = '#2e7d32';
    } else {
        msg.textContent = '❌ Passwords do not match';
        msg.style.color = '#c62828';
    }
}

function toggleEye(inputId, eyeId) {
    const inp = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    if (inp.type === 'password') {
        inp.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Block submit if passwords don't match
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const np = document.getElementById('newPass').value;
    const cp = document.getElementById('confPass').value;
    if (np !== cp) {
        e.preventDefault();
        const msg = document.getElementById('matchMsg');
        msg.textContent = '❌ Passwords do not match';
        msg.style.color = '#c62828';
    }
});
</script>
</body>
</html>
