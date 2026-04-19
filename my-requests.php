<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/requests.php';
requireRole(['student']);
$userId=getUserId(); $status=$_GET['status']??''; $page=max(1,(int)($_GET['page']??1));
$limit=15; $offset=($page-1)*$limit;
$requests=getRequestsByUser($userId,$status?:null,$limit,$offset);
$total=countRequests(['user_id'=>$userId]+($status?['status'=>$status]:[]));
$pages=ceil($total/$limit); $activePage='my-requests';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Requests - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css"></head>
<body><div class="dashboard-container"><?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
<div class="top-bar"><h2><i class="fas fa-list" style="color:#2a5298;margin-right:8px"></i>My Service Requests</h2>
<div class="tb-right"><a href="new-request.php" class="btn btn-primary"><i class="fas fa-plus"></i>New</a>
<a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i>Logout</a></div></div>
<div class="filter-bar">
<?php foreach(['']+['submitted','assigned','in_progress','resolved','closed','rejected'] as $s): ?>
<a href="my-requests.php<?=$s?"?status=$s":''?>" class="filter-tab <?=$status===$s?'active':''?>">
<?=$s?ucfirst(str_replace('_',' ',$s)):'All'?></a>
<?php endforeach; ?>
</div>
<div class="card">
<?php if(empty($requests)): ?><div class="empty-state"><i class="fas fa-inbox"></i>
<p>No requests found. <a href="new-request.php">Submit one now →</a></p></div>
<?php else: ?><div class="table-wrap"><table>
<thead><tr><th>Request ID</th><th>Category</th><th>Title</th><th>Status</th><th>Priority</th><th>Date</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($requests as $r): ?>
<tr><td style="font-family:monospace;font-size:12px"><?=htmlspecialchars($r['request_id'])?></td>
<td><?=htmlspecialchars($r['category_name'])?></td>
<td><?=htmlspecialchars(mb_substr($r['title'],0,40)).(mb_strlen($r['title'])>40?'…':'')?></td>
<td><span class="badge-status s-<?=$r['status']?>"><?=ucfirst(str_replace('_',' ',$r['status']))?></span></td>
<td><span class="p-<?=$r['priority']?>"><?=ucfirst($r['priority'])?></span></td>
<td><?=date('d M Y',strtotime($r['created_at']))?></td>
<td><a href="request-detail.php?id=<?=$r['id']?>" class="btn-sm" style="background:#1e3c72;color:#fff">View</a></td>
</tr>
<?php endforeach; ?></tbody></table></div>
<?php if($pages>1): ?><div class="pagination">
<?php for($i=1;$i<=$pages;$i++): ?>
<a href="?status=<?=$status?>&page=<?=$i?>" class="<?=$i===$page?'active':''?>"><?=$i?></a>
<?php endfor; ?></div><?php endif; ?>
<?php endif; ?></div></div></div></body></html>
