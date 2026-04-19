<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireRole(['department_admin','super_admin','staff']);

$role   = getUserRole();
$userId = getUserId();
$status   = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$search   = trim($_GET['search'] ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page-1)*$limit;

$filters = [];
if ($status)   $filters['status']   = $status;
if ($priority) $filters['priority'] = $priority;
if ($search)   $filters['search']   = $search;
if ($role === 'staff') $filters['assigned_to'] = $userId;

$requests = getAllRequests($filters, $limit, $offset);
$total    = countRequests($filters);
$pages    = ceil($total/$limit);
$activePage = 'all-requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Requests - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-list-alt" style="color:#2a5298;margin-right:8px"></i>
        <?= $role==='staff' ? 'My Assigned Tasks' : 'All Service Requests' ?>
    </h2>
    <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <input type="text" name="search" class="form-control" style="width:200px;padding:8px 12px"
               placeholder="Search ID, title, name…" value="<?= htmlspecialchars($search) ?>">
        <select name="status" class="form-control" style="width:160px;padding:8px 12px">
            <option value="">All Statuses</option>
            <?php foreach (['submitted','pending_approval','assigned','in_progress','pending_info','resolved','closed','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control" style="width:140px;padding:8px 12px">
            <option value="">All Priorities</option>
            <?php foreach (['low','medium','high','urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:8px 18px"><i class="fas fa-search"></i> Filter</button>
        <a href="all-requests.php" class="btn" style="background:#f0f2f5;color:#555;padding:8px 14px">Reset</a>
    </form>
</div>

<div class="card">
    <div style="font-size:13px;color:#888;margin-bottom:10px">
        Showing <?= count($requests) ?> of <?= $total ?> requests
    </div>
    <?php if (empty($requests)): ?>
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No requests found.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Requester</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>Assigned To</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['request_id']) ?></td>
            <td><?= htmlspecialchars($r['requester_name']) ?></td>
            <td><?= htmlspecialchars(mb_substr($r['title'],0,35)).(mb_strlen($r['title'])>35?'…':'') ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><span class="badge-status s-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            <td><span class="p-<?= $r['priority'] ?>"><?= ucfirst($r['priority']) ?></span></td>
            <td><?= $r['assigned_staff_name'] ? htmlspecialchars($r['assigned_staff_name']) : '<span style="color:#ccc">—</span>' ?></td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td style="white-space:nowrap">
                <a href="request-detail.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a>
                <?php if (in_array($role,['department_admin','super_admin']) && !$r['assigned_to']): ?>
                <a href="assign-requests.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#f57f17;color:#fff;margin-left:3px">Assign</a>
                <?php endif; ?>
                <?php if (in_array($role,['department_admin','super_admin'])): ?>
                <a href="update-status.php?id=<?= $r['id'] ?>" class="btn-sm" style="background:#3949ab;color:#fff;margin-left:3px">Status</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php $qs = http_build_query(['status'=>$status,'priority'=>$priority,'search'=>$search]);
        for ($i=1;$i<=$pages;$i++): ?>
        <a href="?<?= $qs ?>&page=<?= $i ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?></div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</div>
</div>
</body>
</html>
