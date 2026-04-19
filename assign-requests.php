<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireRole(['department_admin','super_admin']);

$userId  = getUserId();
$success = '';
$error   = '';
$preId   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId   = (int)($_POST['request_id'] ?? 0);
    $staffId = (int)($_POST['staff_id']   ?? 0);
    $comment = trim($_POST['comment'] ?? 'Assigned by admin.');
    if (!$reqId || !$staffId) {
        $error = 'Please select both a request and a staff member.';
    } else {
        $req = getRequestById($reqId);
        if ($req) {
            updateRequestStatus($reqId, 'assigned', $comment, $userId, $staffId);
            $success = "Request {$req['request_id']} assigned successfully!";
            $preId   = 0;
        }
    }
}

$unassigned = getAllRequests(['status'=>'submitted'], 50);
$staffList  = getStaffList();
$preReq     = $preId ? getRequestById($preId) : null;
$activePage = 'assign-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Assign Requests - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.req-item{padding:14px;border-radius:10px;border:2px solid #eee;margin-bottom:10px;cursor:pointer;transition:.25s}
.req-item:hover{border-color:#667eea;background:#f8f9ff}
.req-item.selected{border-color:#1e3c72;background:rgba(30,60,114,.05)}
.req-scroll{max-height:500px;overflow-y:auto}
.staff-card{padding:11px;border-radius:10px;border:2px solid #eee;margin-bottom:8px;
            display:flex;align-items:center;gap:10px;cursor:pointer;transition:.25s}
.staff-card:hover{border-color:#667eea}
.s-av{width:36px;height:36px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;
      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0}
.s-tasks{margin-left:auto;background:#e8eaf6;color:#3949ab;padding:2px 8px;border-radius:20px;font-size:12px;font-weight:600}
.req-id{font-family:monospace;font-size:11px;color:#aaa}
.req-title{font-size:13px;font-weight:600;color:#333;margin:3px 0}
.req-meta{font-size:11px;color:#aaa;display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:900px){.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-user-check" style="color:#2a5298;margin-right:8px"></i> Assign Requests</h2>
    <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="two-col">
    <!-- Unassigned requests list -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-inbox" style="color:#3949ab"></i> Unassigned Requests</h3>
            <span style="background:#e8eaf6;color:#3949ab;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600">
                <?= count($unassigned) ?>
            </span>
        </div>
        <?php if (empty($unassigned)): ?>
        <div class="empty-state"><i class="fas fa-check-circle" style="color:#43a047"></i><p>All requests are assigned!</p></div>
        <?php else: ?>
        <div class="req-scroll">
            <?php foreach ($unassigned as $r): ?>
            <div class="req-item <?= ($preReq && $preReq['id']==$r['id']) ? 'selected':'' ?>"
                 onclick="selectReq(<?= $r['id'] ?>,this)">
                <div class="req-id"><?= htmlspecialchars($r['request_id']) ?></div>
                <div class="req-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="req-meta">
                    <span><?= htmlspecialchars($r['category_name']) ?></span>
                    <span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span>
                    <span><?= htmlspecialchars($r['requester_name']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Assignment form -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-plus" style="color:#2e7d32"></i> Assign to Staff</h3></div>
        <form method="POST">
            <div class="form-group">
                <label>Selected Request</label>
                <select name="request_id" id="reqSelect" class="form-control" required>
                    <option value="">— Click a request on the left —</option>
                    <?php foreach ($unassigned as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($preReq && $preReq['id']==$r['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($r['request_id']) ?> — <?= htmlspecialchars(mb_substr($r['title'],0,40)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Staff Member</label>
                <?php if (empty($staffList)): ?>
                <p style="color:#aaa;font-size:13px">No staff yet. <a href="manage-users.php">Add staff →</a></p>
                <?php else: ?>
                <select name="staff_id" id="staffSelect" class="form-control" required>
                    <option value="">— Select staff —</option>
                    <?php foreach ($staffList as $s): ?>
                    <option value="<?= $s['id'] ?>">
                        <?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['department_name']??'N/A') ?> — <?= $s['active_requests'] ?> active)
                    </option>
                    <?php endforeach; ?>
                </select>
                <!-- Staff quick-pick cards -->
                <div style="margin-top:12px">
                    <?php foreach (array_slice($staffList,0,5) as $s): ?>
                    <div class="staff-card" onclick="document.getElementById('staffSelect').value='<?= $s['id'] ?>'">
                        <div class="s-av"><?= strtoupper(substr($s['full_name'],0,2)) ?></div>
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($s['full_name']) ?></div>
                            <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($s['department_name']??'N/A') ?></div>
                        </div>
                        <span class="s-tasks"><?= $s['active_requests'] ?> active</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Note <small style="color:#aaa">(optional)</small></label>
                <textarea name="comment" class="form-control" placeholder="Assignment note…"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">
                <i class="fas fa-user-check"></i> Assign Request
            </button>
        </form>
    </div>
</div>

</div>
</div>
<script>
function selectReq(id, card) {
    document.querySelectorAll('.req-item').forEach(el => el.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('reqSelect').value = id;
}
</script>
</body>
</html>
