<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
global $pdo;

$userId  = getUserId();
$success = '';
$error = '';

$u = $pdo->prepare("SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.id=?");
$u->execute([$userId]);
$user = $u->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name']    ?? '');
        $phone = trim($_POST['phone']        ?? '');
        $desig = trim($_POST['designation']  ?? '');
        if (!$name) {
            $error = 'Name cannot be empty.';
        } else {
            $pdo->prepare("UPDATE users SET full_name=?,phone=?,designation=?,updated_at=NOW() WHERE id=?")
                ->execute([$name, $phone, $desig, $userId]);
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated.';
            $u->execute([$userId]);
            $user = $u->fetch();
        }
    }
    if ($action === 'change_password') {
        $cur  = $_POST['current_password']  ?? '';
        $new  = $_POST['new_password']      ?? '';
        $conf = $_POST['confirm_password']  ?? '';
        if (!$cur || !$new || !$conf) $error = 'All fields required.';
        elseif ($new !== $conf)   $error = 'Passwords do not match.';
        elseif (strlen($new) < 6)   $error = 'Password must be at least 6 characters.';
        else {
            $valid = password_verify($cur, $user['password']) || $cur === $user['password'];
            if (!$valid) $error = 'Current password is incorrect.';
            else {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
                $success = 'Password changed.';
            }
        }
    }
}

$role = getUserRole();
$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My Profile - Campus Sheba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <style>
        .two-col {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 22px;
            align-items: start
        }

        .profile-head {
            text-align: center;
            padding: 10px 0 20px
        }

        .av-lg {
            width: 88px;
            height: 88px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 30px;
            font-weight: 700;
            margin: 0 auto 12px
        }

        .pname {
            font-size: 19px;
            font-weight: 700;
            color: #1e3c72
        }

        .pemail {
            font-size: 13px;
            color: #888;
            margin-top: 3px
        }

        .role-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 7px
        }

        .rp-student {
            background: #e3f2fd;
            color: #1565c0
        }

        .rp-staff {
            background: #f3e5f5;
            color: #6a1b9a
        }

        .rp-department_admin,
        .rp-super_admin {
            background: #fff3e0;
            color: #e65100
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 9px 0;
            border-bottom: 1px solid #f0f2f5
        }

        .info-row:last-child {
            border-bottom: none
        }

        .info-row label {
            color: #888
        }

        .info-row span {
            color: #333;
            font-weight: 500
        }

        @media(max-width:800px) {
            .two-col {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="main-content">

            <div class="top-bar">
                <h2><i class="fas fa-user-circle" style="color:#2a5298;margin-right:8px"></i> My Profile</h2>
                <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="two-col">
                <!-- Left: summary -->
                <div>
                    <div class="card">
                        <div class="profile-head">
                            <div class="av-lg"><?= strtoupper(substr($user['full_name'], 0, 2)) ?></div>
                            <div class="pname"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div class="pemail"><?= htmlspecialchars($user['email']) ?></div>
                            <span class="role-pill rp-<?= $user['role'] ?>"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span>
                        </div>
                        <div class="info-row"><label>Username</label><span><?= htmlspecialchars($user['username']) ?></span></div>
                        <?php if ($user['phone']): ?>
                            <div class="info-row"><label>Phone</label><span><?= htmlspecialchars($user['phone']) ?></span></div>
                        <?php endif; ?>
                        <?php if ($user['designation']): ?>
                            <div class="info-row"><label>Designation</label><span><?= htmlspecialchars($user['designation']) ?></span></div>
                        <?php endif; ?>
                        <?php if ($user['dept_name']): ?>
                            <div class="info-row"><label>Department</label><span><?= htmlspecialchars($user['dept_name']) ?></span></div>
                        <?php endif; ?>
                        <div class="info-row"><label>Member Since</label><span><?= date('d M Y', strtotime($user['created_at'])) ?></span></div>
                        <?php if ($user['last_login']): ?>
                            <div class="info-row"><label>Last Login</label><span><?= date('d M Y, h:i A', strtotime($user['last_login'])) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: forms -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit" style="color:#2a5298"></i> Edit Profile</h3>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="form-group"><label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="form-group"><label>Email <small style="color:#aaa">(cannot change)</small></label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                            <div class="form-group"><label>Phone</label>
                                <input type="tel" name="phone" class="form-control" placeholder="+880 1X XX XX XX XX"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <?php if (in_array($role, ['staff', 'department_admin', 'super_admin'])): ?>
                                <div class="form-group"><label>Designation</label>
                                    <input type="text" name="designation" class="form-control" placeholder="e.g. IT Support Officer"
                                        value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock" style="color:#c62828"></i> Change Password</h3>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group"><label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Your current password" required>
                            </div>
                            <div class="form-group"><label>New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                            </div>
                            <div class="form-group"><label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>