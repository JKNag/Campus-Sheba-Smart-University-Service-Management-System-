<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireRole(['staff']);

$userId = getUserId();
$status = $_GET['status'] ?? '';
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20; $offset = ($page-1)*$limit;

$filters = ['assigned_to' => $userId];
if ($status) $filters['status'] = $status;

$requests = getAllRequests($filters, $limit, $offset);
$total    = countRequests($filters);
$pages    = ceil($total / $limit);
$activePage = 'staff-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Tasks - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-tasks" style="color:#2a5298;margin-right:8px"></i> My Assigned Tasks</h2>
    <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<div class="filter-bar">
    <a href="staff-requests.php" class="filter-tab <?= !$status?'active':'' ?>">All</a>
    <?php foreach (['assigned','in_progress','resolved','closed'] as $s): ?>
    <a href="?status=<?= $s ?>" class="filter-tab <?= $status===$s?'active':'' ?>">
        <?= ucfirst(str_replace('_',' ',$s)) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if (empty($requests)): ?>
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No tasks found.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Request ID</th><th>Title</th><th>Category</th><th>Requester</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
            <td><?= htmlspecialchars(mb_substr($r['title'],0,35)).(mb_strlen($r['title'])>35?'…':'') ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><?= htmlspecialchars($r['requester_name']) ?></td>
            <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
            <td><span class="badge-status s-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td style="white-space:nowrap">
                <a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a>
                <?php if ($r['status']==='assigned'): ?>
                <a href="update-status.php?id=<?= $r['id'] ?>&status=in_progress"
                   onclick="return confirm('Start?')" class="btn-sm" style="background:#f57f17;color:#fff;margin-left:3px">Start</a>
                <?php elseif ($r['status']==='in_progress'): ?>
                <a href="update-status.php?id=<?= $r['id'] ?>&status=resolved"
                   onclick="return confirm('Resolve?')" class="btn-sm" style="background:#2e7d32;color:#fff;margin-left:3px">Resolve</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?status=<?= $status ?>&page=<?= $i ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</div>
</div>
</body>
</html>
