<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirectToDashboard();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email']    ?? '');
    $pass    = $_POST['password']  ?? '';
    $confirm = $_POST['confirm']   ?? '';
    $role    = $_POST['role']      ?? 'student';

    if ($role === 'admin') $role = 'department_admin';
    if (!in_array($role, ['student','staff','department_admin'])) $role = 'student';

    // Designation only required for staff
    $designation = ($role === 'staff') ? trim($_POST['designation'] ?? '') : '';
    if ($role === 'staff' && !$designation) {
        $error = 'Please select your designation.';
    } elseif (!$name || !$email || !$pass) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email already registered. Please log in.';
        } else {
            $uname = explode('@',$email)[0].'_'.rand(100,999);
            $pdo->prepare(
                "INSERT INTO users (username,full_name,email,password,role,designation) VALUES (?,?,?,?,?,?)"
            )->execute([$uname, $name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $designation]);
            header("Location: login.php?registered=1"); exit();
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
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);
             min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .wrapper{width:100%;max-width:540px}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden}
        .hdr{background:linear-gradient(135deg,#1e3c72,#2a5298);padding:30px;text-align:center;color:#fff}
        .logo-circle{width:80px;height:80px;background:#fff;border-radius:50%;display:flex;
                     align-items:center;justify-content:center;margin:0 auto 14px;padding:12px}
        .logo-circle img{width:100%;height:auto}
        .hdr h1{font-size:24px;margin-bottom:4px}
        .hdr p{opacity:.9;font-size:13px}
        .body{padding:32px}
        .alert-error{background:#ffebee;color:#c62828;border-left:4px solid #c62828;
                     padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:13px;
                     display:flex;align-items:center;gap:8px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;margin-bottom:7px;color:#333;font-weight:500;font-size:13px}
        .input-wrap{position:relative}
        .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aaa;font-size:16px}
        .input-wrap input{width:100%;padding:12px 12px 12px 42px;border:2px solid #e0e0e0;
                          border-radius:8px;font-size:14px;font-family:'Poppins',sans-serif;transition:.3s}
        .input-wrap input:focus{border-color:#667eea;outline:none}
        .role-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:8px}
        .role-card{border:2px solid #e0e0e0;border-radius:12px;padding:14px 6px;text-align:center;
                   cursor:pointer;transition:.3s}
        .role-card:hover{border-color:#667eea;transform:translateY(-2px)}
        .role-card.sel{border-color:#667eea;background:rgba(102,126,234,.06)}
        .role-card i{font-size:26px;display:block;margin-bottom:7px}
        .role-card.rc-student i{color:#4299e1}
        .role-card.rc-staff   i{color:#9f7aea}
        .role-card span{font-size:13px;font-weight:600;color:#333}

        /* Designation field — hidden by default, shown for staff */
        .staff-field{display:none;margin-top:16px;padding:16px;
                     background:#f5f3ff;border-radius:10px;border:2px solid #e0d9ff}
        .staff-field.show{display:block}
        .staff-field label{display:block;margin-bottom:8px;color:#333;font-weight:500;font-size:13px}
        .staff-field select{width:100%;padding:12px 14px;border:2px solid #e0e0e0;
                            border-radius:8px;font-size:14px;font-family:'Poppins',sans-serif;
                            background:#fff;color:#333;transition:.3s}
        .staff-field select:focus{border-color:#9f7aea;outline:none}
        .staff-field .field-hint{font-size:12px;color:#9f7aea;margin-top:7px;
                                  display:flex;align-items:center;gap:5px}

        .note{background:#fff3cd;color:#856404;padding:10px 12px;border-radius:8px;
              font-size:12px;margin-top:10px;display:flex;align-items:center;gap:8px;border-left:3px solid #ffc107}
        input[type="radio"].rr{display:none}
        .btn-register{width:100%;padding:13px;background:linear-gradient(135deg,#667eea,#764ba2);
                      color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;
                      cursor:pointer;transition:.3s;display:flex;align-items:center;justify-content:center;
                      gap:8px;margin-top:6px;font-family:'Poppins',sans-serif}
        .btn-register:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,.35)}
        .login-link{text-align:center;margin-top:18px;padding-top:18px;border-top:1px solid #eee;
                    font-size:13px;color:#666}
        .login-link a{color:#667eea;font-weight:600;text-decoration:none}
        .back{text-align:center;margin-top:18px}
        .back a{color:#fff;font-size:13px;background:rgba(255,255,255,.2);padding:8px 20px;
                border-radius:50px;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .back a:hover{background:rgba(255,255,255,.3)}
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="hdr">
            <div class="logo-circle"><img src="images/logo.jpeg" alt="Campus Sheba"></div>
            <h1>Create Account</h1>
            <p>Join Campus Sheba today</p>
        </div>
        <div class="body">
            <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="Your full name" required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap"><i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Your email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap"><i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Min. 6 characters" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrap"><i class="fas fa-lock"></i>
                        <input type="password" name="confirm" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>I am a</label>
                    <input type="radio" name="role" id="r1" value="student" class="rr"
                           <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : '' ?>>
                    <input type="radio" name="role" id="r2" value="staff"   class="rr"
                           <?= (($_POST['role'] ?? '') === 'staff') ? 'checked' : '' ?>>
                    <div class="role-grid">
                        <div class="role-card rc-student <?= (($_POST['role'] ?? 'student') === 'student') ? 'sel' : '' ?>"
                             onclick="pick('r1',this)">
                            <i class="fas fa-user-graduate"></i><span>Student</span>
                        </div>
                        <div class="role-card rc-staff <?= (($_POST['role'] ?? '') === 'staff') ? 'sel' : '' ?>"
                             onclick="pick('r2',this)">
                            <i class="fas fa-user-tie"></i><span>Staff</span>
                        </div>
                    </div>

                    <!-- Designation — only visible when Staff is selected -->
                    <div class="staff-field" id="staffField">
                        <label>
                            <i class="fas fa-id-badge" style="color:#9f7aea;margin-right:6px"></i>
                            Designation <span style="color:#dc3545">*</span>
                        </label>
                        <select name="designation" id="designationSelect">
                            <option value="">— Select your designation —</option>
                            <optgroup label="IT & Technical">
                                <option value="IT Support Officer"   <?= (($_POST['designation']??'')==='IT Support Officer')?'selected':''?>>IT Support Officer</option>
                                <option value="Network Administrator" <?= (($_POST['designation']??'')==='Network Administrator')?'selected':''?>>Network Administrator</option>
                                <option value="System Administrator"  <?= (($_POST['designation']??'')==='System Administrator')?'selected':''?>>System Administrator</option>
                                <option value="Lab Technician"        <?= (($_POST['designation']??'')==='Lab Technician')?'selected':''?>>Lab Technician</option>
                                <option value="Software Engineer"     <?= (($_POST['designation']??'')==='Software Engineer')?'selected':''?>>Software Engineer</option>
                            </optgroup>
                            <optgroup label="Administration">
                                <option value="Administrative Officer" <?= (($_POST['designation']??'')==='Administrative Officer')?'selected':''?>>Administrative Officer</option>
                                <option value="Office Assistant"       <?= (($_POST['designation']??'')==='Office Assistant')?'selected':''?>>Office Assistant</option>
                                <option value="Registrar Staff"        <?= (($_POST['designation']??'')==='Registrar Staff')?'selected':''?>>Registrar Staff</option>
                                <option value="Accounts Officer"       <?= (($_POST['designation']??'')==='Accounts Officer')?'selected':''?>>Accounts Officer</option>
                            </optgroup>
                            <optgroup label="Facilities & Maintenance">
                                <option value="Maintenance Officer" <?= (($_POST['designation']??'')==='Maintenance Officer')?'selected':''?>>Maintenance Officer</option>
                                <option value="Electrician"         <?= (($_POST['designation']??'')==='Electrician')?'selected':''?>>Electrician</option>
                                <option value="Plumber"             <?= (($_POST['designation']??'')==='Plumber')?'selected':''?>>Plumber</option>
                                <option value="Housekeeping Staff"  <?= (($_POST['designation']??'')==='Housekeeping Staff')?'selected':''?>>Housekeeping Staff</option>
                                <option value="Security Guard"      <?= (($_POST['designation']??'')==='Security Guard')?'selected':''?>>Security Guard</option>
                            </optgroup>
                            <optgroup label="Transport">
                                <option value="Transport Officer" <?= (($_POST['designation']??'')==='Transport Officer')?'selected':''?>>Transport Officer</option>
                                <option value="Driver"           <?= (($_POST['designation']??'')==='Driver')?'selected':''?>>Driver</option>
                            </optgroup>
                            <optgroup label="Library & Others">
                                <option value="Librarian"        <?= (($_POST['designation']??'')==='Librarian')?'selected':''?>>Librarian</option>
                                <option value="Library Assistant" <?= (($_POST['designation']??'')==='Library Assistant')?'selected':''?>>Library Assistant</option>
                                <option value="Lab Assistant"    <?= (($_POST['designation']??'')==='Lab Assistant')?'selected':''?>>Lab Assistant</option>
                                <option value="Other"            <?= (($_POST['designation']??'')==='Other')?'selected':''?>>Other</option>
                            </optgroup>
                        </select>
                        <div class="field-hint">
                            <i class="fas fa-info-circle"></i>
                            Your designation will appear on your profile page
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
        </div>
    </div>
    <div class="back"><a href="index.html"><i class="fas fa-arrow-left"></i> Back to Home</a></div>
</div>
<script>
function pick(id, card) {
    document.getElementById(id).checked = true;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('sel'));
    card.classList.add('sel');

    const isStaff = (id === 'r2');
    const field   = document.getElementById('staffField');
    const select  = document.getElementById('designationSelect');
    if (isStaff) {
        field.classList.add('show');
        select.required = true;
    } else {
        field.classList.remove('show');
        select.required = false;
        select.value    = '';
    }
}

// On page load — restore state after form error reloads
window.addEventListener('DOMContentLoaded', function () {
    const staffRadio = document.getElementById('r2');
    if (staffRadio && staffRadio.checked) {
        document.getElementById('staffField').classList.add('show');
        document.getElementById('designationSelect').required = true;
    }
});
</script>
</body>
</html>
