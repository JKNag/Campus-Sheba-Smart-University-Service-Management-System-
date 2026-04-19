<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireRole(['staff','department_admin','super_admin']);

$userId = getUserId();
$role   = getUserRole();
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$req    = getRequestById($id);

if (!$req) { header("Location: all-requests.php"); exit(); }
if ($role === 'staff' && $req['assigned_to'] != $userId) { header("Location: staff-dashboard.php"); exit(); }

// Quick GET-based change from buttons
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status'])) {
    $s = $_GET['status'];
    if (in_array($s, ['in_progress','resolved','closed','rejected','pending_info','assigned'])) {
        updateRequestStatus($id, $s, "Status updated to $s.", $userId);
        header("Location: request-detail.php?id=$id"); exit();
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus  = $_POST['status']     ?? '';
    $comment    = trim($_POST['comment'] ?? '');
    $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $valid = ['submitted','pending_approval','assigned','in_progress','pending_info','resolved','closed','rejected'];
    if (!in_array($newStatus, $valid)) {
        $error = 'Invalid status.';
    } else {
        updateRequestStatus($id, $newStatus, $comment, $userId, $assignedTo);
        header("Location: request-detail.php?id=$id"); exit();
    }
}

$staffList = getStaffList();
$activePage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Update Status - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
body{display:block;padding:30px 20px;background:#f0f2f5}
.wrap{max-width:560px;margin:0 auto}
.back{display:inline-flex;align-items:center;gap:6px;color:#2a5298;text-decoration:none;font-size:14px;margin-bottom:18px}
.card-hdr{background:linear-gradient(135deg,#1e3c72,#2a5298);padding:22px 26px;color:#fff;border-radius:14px 14px 0 0}
.card-hdr h2{font-size:18px;margin-bottom:4px}
.card-hdr p{font-size:13px;opacity:.85}
.req-badge{display:inline-block;background:rgba(255,255,255,.2);padding:3px 12px;border-radius:20px;font-size:12px;font-family:monospace;margin-top:6px}
.card-body{background:#fff;padding:26px;border-radius:0 0 14px 14px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.info-box{background:#f8faff;border:1px solid #e0e8ff;border-radius:9px;padding:13px 15px;margin-bottom:18px;font-size:13px}
.info-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #eef}
.info-row:last-child{border-bottom:none}
.info-row label{color:#888}
.info-row span{color:#333;font-weight:500}
.btn-row{display:flex;gap:12px;margin-top:8px;flex-wrap:wrap}
.btn-cancel{padding:12px 20px;background:#f0f2f5;color:#555;border:none;border-radius:9px;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:'Poppins',sans-serif}
</style>
</head>
<body>
<div class="wrap">
    <a href="request-detail.php?id=<?= $id ?>" class="back"><i class="fas fa-arrow-left"></i> Back to Request</a>
    <div class="card-hdr">
        <h2><i class="fas fa-edit" style="margin-right:8px"></i> Update Request Status</h2>
        <p>Change status and optionally add a note</p>
        <div class="req-badge"><?= htmlspecialchars($req['request_id']) ?></div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <div class="info-row"><label>Title</label><span><?= htmlspecialchars(mb_substr($req['title'],0,50)) ?></span></div>
            <div class="info-row"><label>Requester</label><span><?= htmlspecialchars($req['requester_name']) ?></span></div>
            <div class="info-row"><label>Current Status</label><span><?= ucfirst(str_replace('_',' ',$req['status'])) ?></span></div>
            <div class="info-row"><label>Priority</label><span><?= ucfirst($req['priority']) ?></span></div>
        </div>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label class="form-group">New Status</label>
                <select name="status" class="form-control" required>
                    <?php foreach (['submitted','pending_approval','assigned','in_progress','pending_info','resolved','closed','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $req['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (in_array($role, ['department_admin','super_admin'])): ?>
            <div class="form-group">
                <label>Assign To Staff</label>
                <select name="assigned_to" class="form-control">
                    <option value="">— Keep current / unassigned —</option>
                    <?php foreach ($staffList as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $req['assigned_to']==$s['id']?'selected':'' ?>>
                        <?= htmlspecialchars($s['full_name']) ?> — <?= htmlspecialchars($s['department_name']??'N/A') ?> (<?= $s['active_requests'] ?> active)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Comment / Note <small style="color:#aaa">(optional)</small></label>
                <textarea name="comment" class="form-control" placeholder="Add a note about this status change…"></textarea>
            </div>

            <div class="btn-row">
                <a href="request-detail.php?id=<?= $id ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Status</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
