<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
require_once __DIR__ . '/../includes/notifications.php';
requireRole(['department_admin','super_admin']);
header("Cache-Control: no-store, no-cache, must-revalidate");

$userId = getUserId();
$stats  = getAdminStats();
$recent = getAllRequests([], 8);
$staff  = getStaffList();
$unread = getUnreadCount($userId);
$role   = getUserRole();

global $pdo;
$chart = $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM service_requests
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day"
)->fetchAll();
$chartLabels = array_column($chart,'day');
$chartValues = array_column($chart,'cnt');
$activePage  = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.kpi-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:18px;margin-bottom:22px}
.kpi{background:#fff;padding:18px 20px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);position:relative;overflow:hidden}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
.kpi.blue::before{background:linear-gradient(90deg,#1e3c72,#2a5298)}
.kpi.orange::before{background:linear-gradient(90deg,#f57f17,#ffa000)}
.kpi.purple::before{background:linear-gradient(90deg,#6a1b9a,#9c27b0)}
.kpi.green::before{background:linear-gradient(90deg,#2e7d32,#43a047)}
.kpi h4{font-size:11px;text-transform:uppercase;color:#aaa;margin-bottom:6px;font-weight:600}
.kpi .kn{font-size:26px;font-weight:700;color:#1e3c72}
.kpi .ks{font-size:11px;color:#aaa;margin-top:3px}
.two-col{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:22px}
.staff-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0f2f5}
.staff-row:last-child{border-bottom:none}
.s-av{width:36px;height:36px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;
      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0}
.s-tasks{margin-left:auto;font-size:12px;background:#e8eaf6;color:#3949ab;padding:2px 8px;border-radius:20px;font-weight:600}
@media(max-width:900px){.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <div>
        <h2 style="display:inline">Admin Overview</h2>
        <span style="background:rgba(30,60,114,.1);color:#1e3c72;padding:4px 12px;border-radius:20px;
               font-size:11px;font-weight:600;margin-left:10px">
            <?= $role==='super_admin'?'Super Admin':'Dept. Admin' ?>
        </span>
    </div>
    <div class="tb-right">
        <?php if ($unread > 0): ?>
        <a href="notifications.php" style="color:#dc3545;text-decoration:none;font-size:13px">
            <i class="fas fa-bell"></i> <?= $unread ?>
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- KPIs -->
<div class="kpi-bar">
    <div class="kpi blue"><h4>Total Requests</h4><div class="kn"><?= $stats['total'] ?></div></div>
    <div class="kpi orange"><h4>Pending</h4><div class="kn"><?= $stats['pending'] ?></div><div class="ks">Awaiting action</div></div>
    <div class="kpi purple"><h4>Active</h4><div class="kn"><?= $stats['active'] ?></div></div>
    <div class="kpi green"><h4>Resolved</h4><div class="kn"><?= $stats['resolved'] ?></div></div>
    <div class="kpi blue"><h4>Total Users</h4><div class="kn"><?= $stats['total_users'] ?></div></div>
    <div class="kpi orange"><h4>Staff</h4><div class="kn"><?= $stats['total_staff'] ?></div></div>
</div>

<div class="two-col">
    <!-- Chart -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar" style="color:#2a5298"></i> Requests — Last 7 Days</h3></div>
        <canvas id="chart" style="max-height:200px"></canvas>
    </div>
    <!-- Staff workload -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users" style="color:#2a5298"></i> Staff Workload</h3>
            <a href="manage-users.php" class="view-all">Manage →</a>
        </div>
        <?php if (empty($staff)): ?>
        <p style="color:#aaa;font-size:13px">No staff yet.</p>
        <?php else: ?>
        <?php foreach (array_slice($staff,0,6) as $s): ?>
        <div class="staff-row">
            <div class="s-av"><?= strtoupper(substr($s['full_name'],0,2)) ?></div>
            <div>
                <div style="font-size:13px;font-weight:600;color:#333"><?= htmlspecialchars($s['full_name']) ?></div>
                <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($s['department_name']??'N/A') ?></div>
            </div>
            <span class="s-tasks"><?= $s['active_requests'] ?> active</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Recent requests table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list" style="color:#2a5298"></i> Recent Requests</h3>
        <a href="all-requests.php" class="view-all">View All →</a>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Requester</th><th>Category</th><th>Status</th><th>Priority</th><th>Assigned To</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
            <td><?= htmlspecialchars($r['requester_name']) ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><span class="badge-status s-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
            <td><?= $r['assigned_staff_name'] ? htmlspecialchars($r['assigned_staff_name']) : '<span style="color:#ccc">Unassigned</span>' ?></td>
            <td><?= date('d M', strtotime($r['created_at'])) ?></td>
            <td style="white-space:nowrap">
                <a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a>
                <?php if (!$r['assigned_to']): ?>
                <a href="assign-requests.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#f57f17;color:#fff;margin-left:3px">Assign</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

</div>
</div>
<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Requests',
            data: <?= json_encode($chartValues) ?>,
            backgroundColor: 'rgba(42,82,152,0.7)',
            borderColor: '#1e3c72',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
</body>
</html>
