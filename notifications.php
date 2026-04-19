<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
requireLogin();

$userId = getUserId();
if (isset($_GET['mark_all'])) { markAllNotificationsRead($userId); header("Location: notifications.php"); exit(); }
if (isset($_GET['mark']) && is_numeric($_GET['mark'])) { markNotificationRead((int)$_GET['mark'], $userId); header("Location: notifications.php"); exit(); }

$notifications = getNotifications($userId, 50);
$unread        = getUnreadCount($userId);
$activePage    = 'notifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notifications - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.notif-item{background:#fff;border-radius:12px;padding:16px 18px;margin-bottom:12px;
            border-left:4px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,.05);
            display:flex;gap:14px;align-items:flex-start;transition:.25s}
.notif-item:hover{box-shadow:0 4px 14px rgba(0,0,0,.1)}
.notif-item.unread{border-left-color:#2a5298;background:#f8f9ff}
.notif-item.type-success{border-left-color:#43a047}
.notif-item.type-warning{border-left-color:#ffa000}
.notif-item.type-error{border-left-color:#e53935}
.n-icon{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.ni-info{background:#e3f2fd;color:#1565c0}.ni-success{background:#e8f5e9;color:#2e7d32}
.ni-warning{background:#fff8e1;color:#f57f17}.ni-error{background:#ffebee;color:#c62828}
.n-title{font-size:14px;font-weight:600;color:#333;margin-bottom:4px}
.n-msg{font-size:13px;color:#666;line-height:1.5}
.n-ts{font-size:11px;color:#bbb;margin-top:6px}
.n-links{display:flex;gap:12px;margin-top:7px}
.n-links a{font-size:12px;color:#2a5298;text-decoration:none;font-weight:500}
.n-links a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2>
        <i class="fas fa-bell" style="color:#2a5298;margin-right:8px"></i> Notifications
        <?php if ($unread > 0): ?>
        <span style="background:#dc3545;color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;margin-left:8px"><?= $unread ?> new</span>
        <?php endif; ?>
    </h2>
    <div class="tb-right">
        <?php if ($unread > 0): ?>
        <a href="?mark_all=1" class="btn" style="background:#f0f2f5;color:#555;padding:7px 14px">
            <i class="fas fa-check-double"></i> Mark all read
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<?php if (empty($notifications)): ?>
<div class="empty-state"><i class="far fa-bell-slash"></i><p>No notifications yet.</p></div>
<?php else: ?>
<?php foreach ($notifications as $n):
    $ic = match($n['type']) { 'success'=>'ni-success','warning'=>'ni-warning','error'=>'ni-error',default=>'ni-info' };
    $in = match($n['type']) { 'success'=>'check-circle','warning'=>'exclamation-triangle','error'=>'times-circle',default=>'info-circle' };
?>
<div class="notif-item <?= !$n['is_read']?'unread':'' ?> type-<?= $n['type'] ?>">
    <div class="n-icon <?= $ic ?>"><i class="fas fa-<?= $in ?>"></i></div>
    <div style="flex:1">
        <div class="n-title"><?= htmlspecialchars($n['title'] ?? 'Notification') ?></div>
        <div class="n-msg"><?= htmlspecialchars($n['message']) ?></div>
        <div class="n-ts"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></div>
        <div class="n-links">
            <?php if ($n['request_id']): ?>
            <a href="request-detail.php?id=<?= $n['request_id'] ?>"><i class="fas fa-eye"></i> View Request</a>
            <?php endif; ?>
            <?php if (!$n['is_read']): ?>
            <a href="?mark=<?= $n['id'] ?>"><i class="fas fa-check"></i> Mark read</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
</div>
</body>
</html>
