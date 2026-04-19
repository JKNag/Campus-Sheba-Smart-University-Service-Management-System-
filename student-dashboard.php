<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
require_once __DIR__ . '/../includes/notifications.php';
requireRole(['student']);
header("Cache-Control: no-store, no-cache, must-revalidate");

$userId  = getUserId();
$stats   = getStudentStats($userId);
$recent  = getRequestsByUser($userId, null, 5);
$unread  = getUnreadCount($userId);
$notifs  = getNotifications($userId, 5);
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Campus Sheba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">

        <!-- Top bar -->
        <div class="top-bar">
            <h2>Welcome back, <?= htmlspecialchars(explode(' ', getUserName())[0]) ?>! 👋</h2>
            <div class="tb-right">
                <!-- Bell -->
                <div style="position:relative;cursor:pointer" onclick="toggleNotif()">
                    <i class="far fa-bell" style="font-size:20px;color:#666"></i>
                    <?php if($unread>0): ?><span style="position:absolute;top:-6px;right:-6px;background:#dc3545;color:#fff;font-size:10px;padding:2px 5px;border-radius:50%;min-width:17px;text-align:center"><?=$unread?></span><?php endif; ?>
                </div>
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px">
                    <?= strtoupper(substr(getUserName(),0,2)) ?>
                </div>
                <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <!-- Notification dropdown -->
            <div id="notifDrop" style="display:none;position:absolute;top:68px;right:20px;width:310px;
                 background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.15);z-index:200;overflow:hidden">
                <div style="padding:13px 18px;background:#1e3c72;color:#fff;font-size:14px;font-weight:600;
                            display:flex;justify-content:space-between;align-items:center">
                    Notifications
                    <a href="notifications.php?mark_all=1" style="color:rgba(255,255,255,.7);font-size:12px">Mark all read</a>
                </div>
                <?php if(empty($notifs)): ?>
                <div style="padding:20px;text-align:center;color:#aaa;font-size:13px">No notifications.</div>
                <?php else: foreach($notifs as $n): ?>
                <div style="padding:12px 18px;border-bottom:1px solid #f0f2f5;font-size:13px;<?= !$n['is_read']?'background:#f8f9ff':'' ?>">
                    <strong style="color:#333"><?= htmlspecialchars($n['title']) ?></strong>
                    <p style="color:#888;margin-top:3px;font-size:12px"><?= htmlspecialchars(substr($n['message'],0,70)) ?></p>
                </div>
                <?php endforeach; endif; ?>
                <div style="padding:10px 18px;text-align:center">
                    <a href="notifications.php" style="color:#2a5298;font-size:13px">View all →</a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info"><h3>Total Requests</h3><span class="num"><?= $stats['total'] ?></span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-spinner"></i></div>
                <div class="stat-info"><h3>In Progress</h3><span class="num"><?= $stats['active'] ?></span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info"><h3>Pending</h3><span class="num"><?= $stats['pending'] ?></span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><h3>Resolved</h3><span class="num"><?= $stats['resolved'] ?></span></div></div>
        </div>

        <!-- Recent requests -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list" style="color:#2a5298"></i> Recent Service Requests</h3>
                <a href="my-requests.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if(empty($recent)): ?>
            <div class="empty-state"><i class="fas fa-inbox"></i>
                <p>No requests yet. <a href="new-request.php">Submit your first request →</a></p></div>
            <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Request ID</th><th>Category</th><th>Title</th><th>Status</th><th>Priority</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($recent as $r): ?>
                <tr>
                    <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
                    <td><?= htmlspecialchars($r['category_name']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($r['title'],0,35)).(mb_strlen($r['title'])>35?'…':'') ?></td>
                    <td><span class="badge-status s-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                    <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
                    <td><?= date('d M Y',strtotime($r['created_at'])) ?></td>
                    <td><a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- FAB -->
<a href="new-request.php" style="position:fixed;bottom:28px;right:28px;width:56px;height:56px;
   background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;display:flex;align-items:center;
   justify-content:center;color:#fff;font-size:22px;box-shadow:0 5px 18px rgba(30,60,114,.4);
   text-decoration:none;transition:.3s" title="New Request"><i class="fas fa-plus"></i></a>
<script>
function toggleNotif(){
    var d=document.getElementById('notifDrop');
    d.style.display=d.style.display==='none'?'block':'none';
}
document.addEventListener('click',function(e){
    if(!e.target.closest('#notifDrop')&&!e.target.closest('[onclick="toggleNotif()"]'))
        document.getElementById('notifDrop').style.display='none';
});
</script>
</body></html>
