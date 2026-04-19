<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['student']);
global $pdo;

$userId = getUserId();

$feedbacks = $pdo->prepare(
    "SELECT f.*,sr.title,sr.request_id,sr.resolved_at,sc.name AS category
     FROM feedback f JOIN service_requests sr ON f.request_id=sr.id
     JOIN service_categories sc ON sr.category_id=sc.id
     WHERE f.user_id=? ORDER BY f.created_at DESC"
);
$feedbacks->execute([$userId]); $feedbacks=$feedbacks->fetchAll();

$unrated = $pdo->prepare(
    "SELECT sr.*,sc.name AS category_name FROM service_requests sr
     JOIN service_categories sc ON sr.category_id=sc.id
     WHERE sr.user_id=? AND sr.status IN ('resolved','closed')
       AND sr.id NOT IN (SELECT request_id FROM feedback WHERE user_id=?)
     ORDER BY sr.resolved_at DESC"
);
$unrated->execute([$userId,$userId]); $unrated=$unrated->fetchAll();

$avg = $pdo->prepare("SELECT ROUND(AVG(rating),1) FROM feedback WHERE user_id=?");
$avg->execute([$userId]); $avg=$avg->fetchColumn();
$activePage='feedback';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Feedback - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.summary-bar{background:linear-gradient(135deg,#1e3c72,#2a5298);color:#fff;padding:20px 26px;
             border-radius:14px;margin-bottom:22px;display:flex;align-items:center;gap:24px}
.big-num{font-size:46px;font-weight:700;line-height:1}
.sum-stars{color:#ffc107;font-size:19px;margin-top:4px}
.sum-label{font-size:13px;opacity:.85;margin-top:3px}
.unrated-item{display:flex;justify-content:space-between;align-items:center;
              padding:13px;border:2px solid #ffc107;border-radius:10px;margin-bottom:10px;background:#fffbf0}
.btn-rate{padding:7px 16px;background:#ffc107;color:#333;border:none;border-radius:7px;
          font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.fb-card{border:1px solid #f0f2f5;border-radius:12px;padding:16px;margin-bottom:14px;transition:.25s}
.fb-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.fb-hdr{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px}
.stars-row{color:#ffc107;font-size:15px}
.fb-comment{font-size:13px;color:#666;margin-top:9px;font-style:italic;
            background:#f9f9fb;padding:10px 13px;border-radius:8px}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-star" style="color:#ffc107;margin-right:8px"></i> My Feedback</h2>
    <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<?php if ($avg): ?>
<div class="summary-bar">
    <div>
        <div class="big-num"><?= $avg ?></div>
        <div class="sum-stars">
            <?php for ($i=1;$i<=5;$i++) echo '<i class="fas fa-star" style="color:'.($i<=$avg?'#ffc107':'rgba(255,255,255,.3)').'"></i>'; ?>
        </div>
        <div class="sum-label">Average across <?= count($feedbacks) ?> review<?= count($feedbacks)!=1?'s':'' ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Pending ratings -->
<?php if (!empty($unrated)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-circle" style="color:#ffc107"></i>
            Pending Ratings (<?= count($unrated) ?>)
        </h3>
    </div>
    <?php foreach ($unrated as $r): ?>
    <div class="unrated-item">
        <div>
            <div style="font-weight:600;font-size:14px;color:#333"><?= htmlspecialchars($r['title']) ?></div>
            <div style="font-size:12px;color:#aaa;margin-top:2px">
                <?= htmlspecialchars($r['category_name']) ?> · Resolved <?= date('d M Y',strtotime($r['resolved_at'])) ?>
            </div>
        </div>
        <a href="request-detail.php?id=<?= $r['id'] ?>#feedback" class="btn-rate">
            <i class="fas fa-star"></i> Rate Now
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Feedback history -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-history" style="color:#2a5298"></i> Feedback History</h3></div>
    <?php if (empty($feedbacks)): ?>
    <div class="empty-state"><i class="far fa-star"></i><p>No feedback submitted yet.</p></div>
    <?php else: ?>
    <?php foreach ($feedbacks as $fb): ?>
    <div class="fb-card">
        <div class="fb-hdr">
            <div>
                <div style="font-size:14px;font-weight:600;color:#333"><?= htmlspecialchars($fb['title']) ?></div>
                <div style="font-size:12px;color:#aaa;margin-top:2px">
                    <?= htmlspecialchars($fb['category']) ?> · Rated <?= date('d M Y',strtotime($fb['created_at'])) ?>
                </div>
            </div>
            <div class="stars-row">
                <?php for ($i=1;$i<=5;$i++) echo '<i class="fas fa-star" style="color:'.($i<=$fb['rating']?'#ffc107':'#ddd').'"></i>'; ?>
                <span style="font-size:13px;color:#555;margin-left:5px"><?= $fb['rating'] ?>/5</span>
            </div>
        </div>
        <?php if ($fb['comment']): ?>
        <div class="fb-comment">"<?= htmlspecialchars($fb['comment']) ?>"</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
</div>
</body>
</html>
