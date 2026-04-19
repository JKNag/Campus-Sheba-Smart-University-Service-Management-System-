<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireLogin();

$userId = getUserId();
$role   = getUserRole();
$id     = (int)($_GET['id'] ?? 0);
$req    = getRequestById($id);

if (!$req) { echo "<p style='padding:40px;font-family:sans-serif'>Request not found.</p>"; exit(); }
if ($role === 'student' && $req['user_id'] != $userId) { header("Location: my-requests.php"); exit(); }

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'comment') {
        $comment   = trim($_POST['comment'] ?? '');
        $staffOnly = isset($_POST['staff_only']) && in_array($role, ['staff','department_admin','super_admin']);
        if ($comment) addComment($id, $userId, $comment, $staffOnly);
        header("Location: request-detail.php?id=$id#comments"); exit();
    }
    if ($action === 'feedback' && $role === 'student') {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['fb_comment'] ?? '');
        if ($rating >= 1 && $rating <= 5) {
            global $pdo;
            $pdo->prepare(
                "INSERT INTO feedback (request_id,user_id,rating,comment) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)"
            )->execute([$id, $userId, $rating, $comment]);
        }
        header("Location: request-detail.php?id=$id"); exit();
    }
}

$history  = getRequestHistory($id);
$isStaff  = in_array($role, ['staff','department_admin','super_admin']);
$comments = getComments($id, $isStaff);

global $pdo;
$feedbackRow = null;
if (in_array($req['status'], ['resolved','closed']) && $role === 'student') {
    $fs = $pdo->prepare("SELECT * FROM feedback WHERE request_id=? AND user_id=?");
    $fs->execute([$id, $userId]);
    $feedbackRow = $fs->fetch();
}

// Fetch real attachments from DB
$attachments = getAttachments($id);

$backLink = match($role) {
    'student' => 'my-requests.php',
    'staff'   => 'staff-requests.php',
    default   => 'all-requests.php',
};
$dashLink = match($role) {
    'student' => 'student-dashboard.php',
    'staff'   => 'staff-dashboard.php',
    default   => 'admin-dashboard.php',
};
$pc = ['low'=>'#2e7d32','medium'=>'#f57f17','high'=>'#e65100','urgent'=>'#c62828'];
$activePage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($req['request_id']) ?> - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;max-width:1200px;margin:0 auto}
.req-title-bar{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px}
.req-id{font-family:monospace;font-size:12px;color:#888}
.req-title{font-size:20px;font-weight:700;color:#1e3c72;margin:6px 0}
.meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin:14px 0}
.meta-item label{font-size:11px;color:#888;text-transform:uppercase;display:block;margin-bottom:3px}
.meta-item span{font-size:13px;font-weight:600;color:#333}
.desc-box{background:#f9f9fb;border-radius:9px;padding:14px;color:#555;line-height:1.7;font-size:14px}
.tl-item{display:flex;gap:12px;margin-bottom:16px}
.tl-dot{width:12px;height:12px;border-radius:50%;background:#2a5298;flex-shrink:0;margin-top:4px;
        box-shadow:0 0 0 3px rgba(42,82,152,.15)}
.tl-body h4{font-size:13px;font-weight:600;color:#333}
.tl-body p{font-size:12px;color:#888;margin-top:2px}
.comment-item{padding:12px;background:#f9f9fb;border-radius:9px;margin-bottom:10px}
.comment-item.staff-only{background:#fff8e1;border-left:3px solid #ffc107}
.comment-item .author{font-size:13px;font-weight:600;color:#1e3c72}
.comment-item .ts{font-size:11px;color:#aaa;margin-left:8px}
.comment-item .text{font-size:13px;color:#555;margin-top:5px;line-height:1.6}
.info-row{display:flex;justify-content:space-between;font-size:13px;padding:9px 0;border-bottom:1px solid #f0f2f5}
.info-row:last-child{border-bottom:none}
.info-row label{color:#888}
.info-row span{color:#333;font-weight:500;text-align:right}
.stars-input{display:flex;gap:6px;font-size:28px;cursor:pointer;margin:8px 0}
.stars-input span{color:#ddd;transition:.2s}
.stars-input span.on{color:#ffc107}
.action-bar{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid #f0f2f5}
.new-badge{background:#e8f5e9;color:#2e7d32;padding:10px 16px;border-radius:9px;
           margin-bottom:18px;display:flex;align-items:center;gap:8px;font-size:13px}
@media(max-width:900px){.detail-grid{grid-template-columns:1fr}}
/* ── Attachments ── */
.attach-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.05);padding:22px;margin-bottom:22px}
.attach-list{display:flex;flex-direction:column;gap:10px;margin-top:6px}
.attach-item{display:flex;align-items:center;gap:13px;padding:12px 15px;
             background:#f8f9ff;border:1px solid #e0e8ff;border-radius:10px;transition:.2s}
.attach-item:hover{background:#eef1ff;border-color:#667eea;box-shadow:0 2px 8px rgba(102,126,234,.12)}
.attach-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;
             justify-content:center;font-size:19px;flex-shrink:0}
.ai-img{background:#fff3e0;color:#e65100}
.ai-pdf{background:#ffebee;color:#c62828}
.ai-doc{background:#e3f2fd;color:#1565c0}
.ai-other{background:#f3e5f5;color:#6a1b9a}
.attach-info{flex:1;min-width:0}
.attach-name{font-size:13px;font-weight:600;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.attach-meta{font-size:11px;color:#aaa;margin-top:3px}
.attach-dl{padding:7px 14px;background:linear-gradient(135deg,#1e3c72,#2a5298);color:#fff;
           border-radius:7px;text-decoration:none;font-size:12px;font-weight:600;white-space:nowrap;
           display:flex;align-items:center;gap:5px;transition:.2s;flex-shrink:0}
.attach-dl:hover{opacity:.85;transform:translateY(-1px)}
.attach-empty{text-align:center;padding:20px 10px;color:#ccc;font-size:13px}
.attach-empty i{font-size:30px;display:block;margin-bottom:8px}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<!-- Back bar -->
<div style="margin-bottom:16px;display:flex;gap:16px;flex-wrap:wrap">
    <a href="<?= $backLink ?>" style="color:#2a5298;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px">
        <i class="fas fa-arrow-left"></i> Back to Requests
    </a>
    <a href="<?= $dashLink ?>" style="color:#2a5298;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px">
        <i class="fas fa-home"></i> Dashboard
    </a>
</div>

<?php if (isset($_GET['new'])): ?>
<div class="new-badge"><i class="fas fa-check-circle"></i> Request submitted! We'll notify you when there are updates.</div>
<?php endif; ?>

<div class="detail-grid">
    <!-- LEFT -->
    <div>
        <!-- Main card -->
        <div class="card">
            <div class="req-title-bar">
                <div>
                    <div class="req-id"><?= htmlspecialchars($req['request_id']) ?></div>
                    <div class="req-title"><?= htmlspecialchars($req['title']) ?></div>
                </div>
                <span class="badge-status s-<?= $req['status'] ?>">
                    <?= ucfirst(str_replace('_',' ',$req['status'])) ?>
                </span>
            </div>
            <div class="meta-grid">
                <div class="meta-item"><label>Category</label><span><?= htmlspecialchars($req['category_name']) ?></span></div>
                <div class="meta-item"><label>Priority</label>
                    <span style="color:<?= $pc[$req['priority']] ?? '#333' ?>"><?= ucfirst($req['priority']) ?></span></div>
                <div class="meta-item"><label>Submitted</label><span><?= date('d M Y, h:i A', strtotime($req['submitted_at'])) ?></span></div>
                <?php if ($req['deadline']): ?>
                <div class="meta-item"><label>Deadline</label><span><?= date('d M Y', strtotime($req['deadline'])) ?></span></div>
                <?php endif; ?>
                <?php if ($req['building']): ?>
                <div class="meta-item"><label>Building</label><span><?= htmlspecialchars($req['building']) ?></span></div>
                <?php endif; ?>
                <?php if ($req['room_number']): ?>
                <div class="meta-item"><label>Room</label><span><?= htmlspecialchars($req['room_number']) ?></span></div>
                <?php endif; ?>
            </div>
            <div style="font-size:12px;color:#888;text-transform:uppercase;margin-bottom:6px;font-weight:500">Description</div>
            <div class="desc-box"><?= nl2br(htmlspecialchars($req['description'])) ?></div>

            <!-- Staff action bar -->
            <?php if ($isStaff): ?>
            <div class="action-bar">
                <a href="update-status.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Update Status</a>
                <?php if ($req['status'] === 'assigned'): ?>
                <a href="update-status.php?id=<?= $id ?>&status=in_progress"
                   onclick="return confirm('Start this request?')" class="btn btn-warning"><i class="fas fa-play"></i> Start Work</a>
                <?php endif; ?>
                <?php if ($req['status'] === 'in_progress'): ?>
                <a href="update-status.php?id=<?= $id ?>&status=resolved"
                   onclick="return confirm('Mark as resolved?')" class="btn btn-success"><i class="fas fa-check"></i> Mark Resolved</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comments -->
        <div class="card" id="comments">
            <div class="card-header">
                <h3><i class="fas fa-comments" style="color:#2a5298"></i> Comments</h3>
            </div>
            <?php if (empty($comments)): ?>
                <p style="color:#aaa;font-size:13px">No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <div class="comment-item <?= $c['is_staff_only'] ? 'staff-only' : '' ?>">
                    <span class="author"><?= htmlspecialchars($c['full_name']) ?></span>
                    <span class="ts"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
                    <?php if ($c['is_staff_only']): ?><span style="font-size:10px;color:#f57f17;margin-left:8px">🔒 Staff only</span><?php endif; ?>
                    <div class="text"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form method="POST" style="margin-top:14px">
                <input type="hidden" name="action" value="comment">
                <textarea name="comment" class="form-control" placeholder="Add a comment…" required></textarea>
                <?php if ($isStaff): ?>
                <label style="font-size:12px;color:#666;display:flex;align-items:center;gap:6px;margin:8px 0;cursor:pointer">
                    <input type="checkbox" name="staff_only"> Staff-only (hidden from student)
                </label>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="margin-top:8px">
                    <i class="fas fa-paper-plane"></i> Post Comment
                </button>
            </form>
        </div>

        <!-- Feedback (student only, resolved) -->
        <?php if ($role === 'student' && in_array($req['status'], ['resolved','closed'])): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-star" style="color:#ffc107"></i>
                    <?= $feedbackRow ? 'Your Feedback' : 'Rate This Service' ?>
                </h3>
            </div>
            <?php if ($feedbackRow): ?>
                <p style="font-size:14px;color:#555">You rated this <strong><?= $feedbackRow['rating'] ?>/5 ⭐</strong></p>
                <?php if ($feedbackRow['comment']): ?>
                <p style="font-size:13px;color:#888;margin-top:6px;font-style:italic">"<?= htmlspecialchars($feedbackRow['comment']) ?>"</p>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="feedback">
                    <input type="hidden" name="rating" id="rVal" value="0">
                    <div class="stars-input" id="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span data-v="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</span>
                        <?php endfor; ?>
                    </div>
                    <textarea name="fb_comment" class="form-control" placeholder="Your experience (optional)"></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT -->
    <div>
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-info-circle" style="color:#2a5298"></i> Info</h3></div>
            <div class="info-row"><label>Requester</label><span><?= htmlspecialchars($req['requester_name']) ?></span></div>
            <?php if ($req['assigned_staff_name']): ?>
            <div class="info-row"><label>Assigned To</label><span><?= htmlspecialchars($req['assigned_staff_name']) ?></span></div>
            <?php endif; ?>
            <?php if ($req['started_at']): ?>
            <div class="info-row"><label>Started</label><span><?= date('d M Y', strtotime($req['started_at'])) ?></span></div>
            <?php endif; ?>
            <?php if ($req['resolved_at']): ?>
            <div class="info-row"><label>Resolved</label><span><?= date('d M Y', strtotime($req['resolved_at'])) ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Attachments Card -->
        <div class="attach-card">
            <div class="card-header" style="margin-bottom:10px">
                <h3 style="font-size:16px;color:#1e3c72;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i class="fas fa-paperclip" style="color:#2a5298"></i> Attachments
                    <?php if(!empty($attachments)): ?>
                    <span style="background:#e8eaf6;color:#3949ab;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700">
                        <?= count($attachments) ?>
                    </span>
                    <?php endif; ?>
                </h3>
            </div>
            <?php if(empty($attachments)): ?>
            <div class="attach-empty">
                <i class="fas fa-paperclip"></i>
                No attachments uploaded
            </div>
            <?php else: ?>
            <div class="attach-list">
                <?php foreach($attachments as $att):
                    $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                    $iconClass = in_array($ext,['jpg','jpeg','png','gif','webp']) ? 'ai-img'
                               : ($ext==='pdf' ? 'ai-pdf'
                               : (in_array($ext,['doc','docx']) ? 'ai-doc' : 'ai-other'));
                    $iconFa    = in_array($ext,['jpg','jpeg','png','gif','webp']) ? 'fa-image'
                               : ($ext==='pdf' ? 'fa-file-pdf'
                               : (in_array($ext,['doc','docx']) ? 'fa-file-word' : 'fa-file'));
                    $sizeStr   = $att['file_size'] > 1048576
                               ? round($att['file_size']/1048576,1).' MB'
                               : round($att['file_size']/1024,1).' KB';
                    $dlPath    = '../' . ltrim($att['file_path'], '/');
                ?>
                <div class="attach-item">
                    <div class="attach-icon <?= $iconClass ?>">
                        <i class="fas <?= $iconFa ?>"></i>
                    </div>
                    <div class="attach-info">
                        <div class="attach-name" title="<?= htmlspecialchars($att['file_name']) ?>">
                            <?= htmlspecialchars($att['file_name']) ?>
                        </div>
                        <div class="attach-meta">
                            <?= $sizeStr ?>
                            &nbsp;·&nbsp; <?= date('d M Y, h:i A', strtotime($att['uploaded_at'])) ?>
                            <?php if(!empty($att['uploaded_by_name'])): ?>
                            &nbsp;·&nbsp; by <?= htmlspecialchars($att['uploaded_by_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($dlPath) ?>"
                       download="<?= htmlspecialchars($att['file_name']) ?>"
                       class="attach-dl" target="_blank">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-stream" style="color:#2a5298"></i> Timeline</h3></div>
            <?php if (empty($history)): ?>
                <p style="color:#aaa;font-size:13px">No history yet.</p>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                <div class="tl-item">
                    <div class="tl-dot"></div>
                    <div class="tl-body">
                        <h4><?= ucfirst(str_replace('_',' ',$h['status'])) ?></h4>
                        <?php if ($h['comment']): ?>
                        <p><?= htmlspecialchars($h['comment']) ?></p>
                        <?php endif; ?>
                        <p><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?> — <?= htmlspecialchars($h['changed_by_name']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script>
function setRating(v){
    document.getElementById('rVal').value=v;
    document.querySelectorAll('#stars span').forEach(s=>s.classList.toggle('on',+s.dataset.v<=v));
}
</script>
</body>
</html>
