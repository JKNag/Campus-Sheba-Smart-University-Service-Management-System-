<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['department_admin','super_admin']);
global $pdo;

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email']    ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role']     ?? 'student';
        if (!in_array($role,['student','staff','department_admin','super_admin'])) $role='student';
        if (!$name||!$email||!$pass)          $error='All fields required.';
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Invalid email.';
        else {
            $c=$pdo->prepare("SELECT id FROM users WHERE email=?"); $c->execute([$email]);
            if ($c->fetch()) $error='Email already exists.';
            else {
                $u=explode('@',$email)[0].'_'.rand(100,999);
                $pdo->prepare("INSERT INTO users (username,full_name,email,password,role) VALUES (?,?,?,?,?)")
                    ->execute([$u,$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
                $success="User \"$name\" created.";
            }
        }
    }
    if ($action==='toggle_active') {
        $uid=(int)($_POST['user_id']??0);
        if($uid&&$uid!==getUserId()){$pdo->prepare("UPDATE users SET is_active=NOT is_active WHERE id=?")->execute([$uid]);$success='Status updated.';}
    }
    if ($action==='change_role') {
        $uid=(int)($_POST['user_id']??0); $nr=$_POST['new_role']??'';
        if($uid&&in_array($nr,['student','staff','department_admin','super_admin'])){$pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$nr,$uid]);$success='Role updated.';}
    }
    if ($action==='reset_password') {
        $uid=(int)($_POST['user_id']??0); $np=$_POST['new_password']??'';
        if($uid&&strlen($np)>=6){$pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($np,PASSWORD_DEFAULT),$uid]);$success='Password reset.';}
        else $error='Password must be at least 6 chars.';
    }
}
if (isset($_GET['delete'])&&is_numeric($_GET['delete'])) {
    $uid=(int)$_GET['delete'];
    if($uid!==getUserId()){$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);$success='User deleted.';}
}

$search  = trim($_GET['search'] ?? '');
$rfilter = $_GET['role'] ?? '';
$sql     = "SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE 1=1";
$p       = [];
if ($search) { $sql.=" AND (u.full_name LIKE ? OR u.email LIKE ?)"; $s="%$search%"; $p[]=$s; $p[]=$s; }
if ($rfilter){ $sql.=" AND u.role=?"; $p[]=$rfilter; }
$sql .= " ORDER BY u.created_at DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($p);
$users=$stmt->fetchAll();
$activePage='manage-users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Users - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.two-col{display:grid;grid-template-columns:2fr 1fr;gap:22px;align-items:start}
.role-pill{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.rp-student{background:#e3f2fd;color:#1565c0}
.rp-staff{background:#f3e5f5;color:#6a1b9a}
.rp-department_admin,.rp-super_admin{background:#fff3e0;color:#e65100}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px}
.dot-on{background:#43a047}.dot-off{background:#bbb}
.av{width:34px;height:34px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;
    display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0}
/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{background:#fff;border-radius:14px;padding:26px;width:100%;max-width:400px;box-shadow:0 20px 50px rgba(0,0,0,.2)}
.modal h3{font-size:16px;color:#1e3c72;margin-bottom:16px;display:flex;justify-content:space-between}
.modal h3 span{cursor:pointer;color:#aaa;font-size:18px;font-weight:400}
@media(max-width:900px){.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-users" style="color:#2a5298;margin-right:8px"></i> Manage Users</h2>
    <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="two-col">
    <!-- Users list -->
    <div>
        <div class="filter-bar">
            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <input type="text" name="search" class="form-control" style="width:180px;padding:8px 12px"
                       placeholder="Search name/email…" value="<?= htmlspecialchars($search) ?>">
                <select name="role" class="form-control" style="width:150px;padding:8px 12px">
                    <option value="">All Roles</option>
                    <option value="student"          <?= $rfilter==='student'?'selected':'' ?>>Student</option>
                    <option value="staff"            <?= $rfilter==='staff'?'selected':'' ?>>Staff</option>
                    <option value="department_admin" <?= $rfilter==='department_admin'?'selected':'' ?>>Dept Admin</option>
                    <option value="super_admin"      <?= $rfilter==='super_admin'?'selected':'' ?>>Super Admin</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        <div class="card">
            <div class="table-wrap"><table>
                <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:9px">
                            <div class="av"><?= strtoupper(substr($u['full_name'],0,2)) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($u['full_name']) ?></div>
                                <?php if ($u['dept_name']): ?><div style="font-size:11px;color:#aaa"><?= htmlspecialchars($u['dept_name']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="role-pill rp-<?= $u['role'] ?>"><?= ucfirst(str_replace('_',' ',$u['role'])) ?></span></td>
                    <td><span class="dot <?= $u['is_active']?'dot-on':'dot-off' ?>"></span><?= $u['is_active']?'Active':'Inactive' ?></td>
                    <td style="font-size:12px"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
                    <td style="white-space:nowrap">
                        <button class="btn-sm" style="background:#e8eaf6;color:#3949ab"
                                onclick="openModal(<?= htmlspecialchars(json_encode($u)) ?>)">Edit</button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-sm" style="background:#fff3e0;color:#e65100;margin-left:3px"
                                    <?= $u['id']===getUserId()?'disabled':'' ?>>
                                <?= $u['is_active']?'Disable':'Enable' ?>
                            </button>
                        </form>
                        <?php if ($u['id']!==getUserId()): ?>
                        <a href="?delete=<?= $u['id'] ?>&<?= http_build_query(['search'=>$search,'role'=>$rfilter]) ?>"
                           class="btn-sm" style="background:#ffebee;color:#c62828;margin-left:3px"
                           onclick="return confirm('Delete this user?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>

    <!-- Create user form -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-plus" style="color:#2e7d32"></i> Create New User</h3></div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label>Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Full name" required></div>
            <div class="form-group"><label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email address" required></div>
            <div class="form-group"><label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required></div>
            <div class="form-group"><label>Role</label>
                <select name="role" class="form-control">
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="department_admin">Dept. Admin</option>
                    <?php if (getUserRole()==='super_admin'): ?>
                    <option value="super_admin">Super Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-plus"></i> Create User</button>
        </form>
    </div>
</div>

</div>
</div>

<!-- Edit Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal">
        <h3>Edit User <span onclick="document.getElementById('editModal').classList.remove('open')">✕</span></h3>
        <form method="POST" style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #eee">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" id="mId1">
            <div class="form-group"><label>Change Role</label>
                <select name="new_role" id="mRole" class="form-control">
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="department_admin">Dept. Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding:8px 18px">Update Role</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="mId2">
            <div class="form-group"><label>Reset Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="New password (min. 6)" required></div>
            <button type="submit" class="btn" style="background:#fff3e0;color:#e65100;padding:8px 18px">Reset Password</button>
        </form>
    </div>
</div>
<script>
function openModal(u){
    document.getElementById('mId1').value=u.id;
    document.getElementById('mId2').value=u.id;
    document.getElementById('mRole').value=u.role;
    document.getElementById('editModal').classList.add('open');
}
document.getElementById('editModal').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
</script>
</body>
</html>
