<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
require_once __DIR__ . '/../includes/notifications.php';
requireRole(['staff']);
header("Cache-Control: no-store, no-cache, must-revalidate");

$userId    = getUserId();
$stats     = getStaffStats($userId);
$inProg    = getAllRequests(['assigned_to'=>$userId,'status'=>'in_progress'], 5);
$assigned  = getAllRequests(['assigned_to'=>$userId,'status'=>'assigned'],    5);
$unread    = getUnreadCount($userId);
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Staff Dashboard - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2>Welcome, <?= htmlspecialchars(explode(' ', getUserName())[0]) ?>! 👋</h2>
    <div class="tb-right">
        <?php if ($unread > 0): ?>
        <a href="notifications.php" style="color:#dc3545;text-decoration:none;font-size:13px">
            <i class="fas fa-bell"></i> <?= $unread ?> new
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-info"><h3>Total Assigned</h3><span class="num"><?= $stats['total_assigned'] ?></span></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-spinner"></i></div>
        <div class="stat-info"><h3>In Progress</h3><span class="num"><?= $stats['in_progress'] ?></span></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-double"></i></div>
        <div class="stat-info"><h3>Completed</h3><span class="num"><?= $stats['completed'] ?></span></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-info"><h3>Avg Rating</h3><span class="num"><?= $stats['avg_rating'] ?></span></div></div>
</div>

<!-- In Progress -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-spinner" style="color:#f57f17"></i> In Progress</h3>
        <a href="staff-requests.php?status=in_progress" class="view-all">View All →</a>
    </div>
    <?php if (empty($inProg)): ?>
    <div class="empty-state"><i class="fas fa-check-circle" style="color:#43a047"></i><p>No tasks in progress right now.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Request ID</th><th>Title</th><th>Requester</th><th>Priority</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($inProg as $r): ?>
        <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
            <td><?= htmlspecialchars(mb_substr($r['title'],0,40)) ?></td>
            <td><?= htmlspecialchars($r['requester_name']) ?></td>
            <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
            <td>
                <a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a>
                <a href="update-status.php?id=<?= $r['id'] ?>&status=resolved"
                   onclick="return confirm('Mark as resolved?')"
                   class="btn-sm" style="background:#2e7d32;color:#fff;margin-left:4px">Resolve</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</div>

<!-- Newly Assigned -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-inbox" style="color:#3949ab"></i> Newly Assigned</h3>
        <a href="staff-requests.php?status=assigned" class="view-all">View All →</a>
    </div>
    <?php if (empty($assigned)): ?>
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No newly assigned requests.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Request ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($assigned as $r): ?>
        <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
            <td><?= htmlspecialchars(mb_substr($r['title'],0,40)) ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
            <td>
                <a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a>
                <a href="update-status.php?id=<?= $r['id'] ?>&status=in_progress"
                   onclick="return confirm('Start working on this?')"
                   class="btn-sm" style="background:#f57f17;color:#fff;margin-left:4px">Start</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</div>

</div>
</div>
</body>
</html>
