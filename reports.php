<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['department_admin','super_admin']);
global $pdo;

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$kpi = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('resolved','closed')) AS resolved,
            SUM(status IN ('submitted','pending_approval','assigned','in_progress')) AS open,
            SUM(status='rejected') AS rejected,
            ROUND(AVG(TIMESTAMPDIFF(HOUR,submitted_at,resolved_at)),1) AS avg_hrs,
            SUM(priority='urgent') AS urgent
     FROM service_requests WHERE DATE(created_at) BETWEEN ? AND ?"
);
$kpi->execute([$from,$to]); $kpi=$kpi->fetch();

$daily=$pdo->prepare("SELECT DATE(created_at) AS day,COUNT(*) AS cnt FROM service_requests WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day");
$daily->execute([$from,$to]); $daily=$daily->fetchAll();

$byStatus=$pdo->query("SELECT status,COUNT(*) AS cnt FROM service_requests GROUP BY status ORDER BY cnt DESC")->fetchAll();
$byCat=$pdo->query("SELECT sc.name,COUNT(sr.id) AS cnt FROM service_categories sc LEFT JOIN service_requests sr ON sc.id=sr.category_id GROUP BY sc.id,sc.name ORDER BY cnt DESC LIMIT 8")->fetchAll();

$staff=$pdo->query(
    "SELECT u.full_name, COUNT(sr.id) AS assigned,
            SUM(sr.status IN ('resolved','closed')) AS completed,
            ROUND(AVG(f.rating),1) AS avg_rating,
            ROUND(AVG(TIMESTAMPDIFF(HOUR,sr.assigned_at,sr.resolved_at)),1) AS avg_hrs
     FROM users u
     LEFT JOIN service_requests sr ON u.id=sr.assigned_to
     LEFT JOIN feedback f ON sr.id=f.request_id
     WHERE u.role='staff' GROUP BY u.id,u.full_name ORDER BY completed DESC"
)->fetchAll();

$activePage='reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reports - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:16px;margin-bottom:22px}
.kpi{background:#fff;padding:16px 18px;border-radius:13px;box-shadow:0 2px 8px rgba(0,0,0,.05);position:relative;overflow:hidden}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:4px}
.kpi.blue::before{background:linear-gradient(90deg,#1e3c72,#2a5298)}
.kpi.green::before{background:linear-gradient(90deg,#2e7d32,#43a047)}
.kpi.orange::before{background:linear-gradient(90deg,#f57f17,#ffa000)}
.kpi.red::before{background:linear-gradient(90deg,#c62828,#e53935)}
.kpi.purple::before{background:linear-gradient(90deg,#6a1b9a,#9c27b0)}
.kpi h4{font-size:11px;text-transform:uppercase;color:#aaa;margin-bottom:5px;font-weight:600}
.kpi .kn{font-size:24px;font-weight:700;color:#1e3c72}
.kpi .ks{font-size:11px;color:#aaa;margin-top:2px}
.chart-row{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:22px}
.stars{color:#ffc107;font-size:12px}
.pbar-wrap{display:flex;align-items:center;gap:8px}
.pbar{flex:1;background:#f0f2f5;border-radius:20px;height:6px;overflow:hidden}
.pbar-fill{height:100%;background:linear-gradient(90deg,#1e3c72,#2a5298);border-radius:20px}
@media(max-width:900px){.chart-row{grid-template-columns:1fr}}
@media print{.sidebar,.top-bar,.filter-bar{display:none!important}.main-content{margin-left:0!important}}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="top-bar">
    <h2><i class="fas fa-chart-bar" style="color:#2a5298;margin-right:8px"></i> Reports & Analytics</h2>
    <div class="tb-right">
        <button onclick="window.print()" class="btn" style="background:#f0f2f5;color:#333;padding:7px 14px">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- Date filter -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <label style="font-size:13px;color:#666">From</label>
        <input type="date" name="from" class="form-control" style="width:150px;padding:8px 12px" value="<?= $from ?>">
        <label style="font-size:13px;color:#666">To</label>
        <input type="date" name="to"   class="form-control" style="width:150px;padding:8px 12px" value="<?= $to ?>">
        <button type="submit" class="btn btn-primary" style="padding:8px 18px"><i class="fas fa-filter"></i> Apply</button>
    </form>
    <span style="font-size:12px;color:#aaa;margin-left:auto"><?= date('d M Y',strtotime($from)) ?> — <?= date('d M Y',strtotime($to)) ?></span>
</div>

<!-- KPIs -->
<div class="kpi-grid">
    <div class="kpi blue"><h4>Total</h4><div class="kn"><?= $kpi['total'] ?></div><div class="ks">In period</div></div>
    <div class="kpi green"><h4>Resolved</h4><div class="kn"><?= $kpi['resolved'] ?></div>
        <div class="ks"><?= $kpi['total']>0?round($kpi['resolved']/$kpi['total']*100):0 ?>% rate</div></div>
    <div class="kpi orange"><h4>Open</h4><div class="kn"><?= $kpi['open'] ?></div></div>
    <div class="kpi red"><h4>Rejected</h4><div class="kn"><?= $kpi['rejected'] ?></div></div>
    <div class="kpi purple"><h4>Avg. Resolution</h4><div class="kn"><?= $kpi['avg_hrs']??'—' ?></div><div class="ks">hours</div></div>
    <div class="kpi red"><h4>Urgent</h4><div class="kn"><?= $kpi['urgent'] ?></div></div>
</div>

<!-- Charts -->
<div class="chart-row">
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-line" style="color:#2a5298"></i> Daily Volume</h3></div>
        <canvas id="lineChart" style="max-height:200px"></canvas></div>
    <div class="card"><div class="card-header"><h3><i class="fas fa-chart-pie" style="color:#2a5298"></i> By Status</h3></div>
        <canvas id="donutChart" style="max-height:200px"></canvas></div>
</div>

<div class="card" style="margin-bottom:22px">
    <div class="card-header"><h3><i class="fas fa-chart-bar" style="color:#2a5298"></i> By Category</h3></div>
    <canvas id="barChart" style="max-height:180px"></canvas>
</div>

<!-- Staff performance -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-user-tie" style="color:#2a5298"></i> Staff Performance</h3></div>
    <?php if (empty($staff)): ?>
    <div class="empty-state"><i class="fas fa-user-slash"></i><p>No staff data yet.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Staff Member</th><th>Assigned</th><th>Completed</th><th>Rate</th><th>Avg Rating</th><th>Avg Resolution</th></tr></thead>
        <tbody>
        <?php foreach ($staff as $sp):
            $rate = $sp['assigned']>0 ? round($sp['completed']/$sp['assigned']*100) : 0;
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:9px">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700">
                        <?= strtoupper(substr($sp['full_name'],0,2)) ?>
                    </div>
                    <?= htmlspecialchars($sp['full_name']) ?>
                </div>
            </td>
            <td><?= $sp['assigned'] ?></td>
            <td><?= $sp['completed'] ?></td>
            <td>
                <div class="pbar-wrap">
                    <div class="pbar"><div class="pbar-fill" style="width:<?= $rate ?>%"></div></div>
                    <span style="font-size:12px;color:#555"><?= $rate ?>%</span>
                </div>
            </td>
            <td>
                <?php if ($sp['avg_rating']): ?>
                <span class="stars"><?php for($i=1;$i<=5;$i++) echo '<i class="fas fa-star" style="color:'.($i<=$sp['avg_rating']?'#ffc107':'#ddd').'"></i>'; ?></span>
                <span style="font-size:12px;margin-left:3px"><?= $sp['avg_rating'] ?></span>
                <?php else: ?><span style="color:#ccc">—</span><?php endif; ?>
            </td>
            <td><?= $sp['avg_hrs'] ? $sp['avg_hrs'].' hrs' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

</div>
</div>
<script>
// Line chart
new Chart(document.getElementById('lineChart'),{type:'line',data:{
    labels:<?= json_encode(array_column($daily,'day')) ?>,
    datasets:[{label:'Requests',data:<?= json_encode(array_column($daily,'cnt')) ?>,
        borderColor:'#2a5298',backgroundColor:'rgba(42,82,152,.08)',borderWidth:2,fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#1e3c72'}]
},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});

// Donut
const sc={submitted:'#1565c0',pending_approval:'#6a1b9a',assigned:'#3949ab',in_progress:'#f57f17',
          pending_info:'#e65100',resolved:'#2e7d32',closed:'#424242',rejected:'#c62828'};
new Chart(document.getElementById('donutChart'),{type:'doughnut',data:{
    labels:<?= json_encode(array_map(fn($s)=>ucfirst(str_replace('_',' ',$s['status'])),$byStatus)) ?>,
    datasets:[{data:<?= json_encode(array_column($byStatus,'cnt')) ?>,
        backgroundColor:<?= json_encode(array_map(fn($s)=>$sc[$s['status']]??'#999',$byStatus)) ?>,borderWidth:2,borderColor:'#fff'}]
},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}},cutout:'60%'}});

// Bar
new Chart(document.getElementById('barChart'),{type:'bar',data:{
    labels:<?= json_encode(array_column($byCat,'name')) ?>,
    datasets:[{label:'Requests',data:<?= json_encode(array_column($byCat,'cnt')) ?>,
        backgroundColor:'rgba(42,82,152,.75)',borderColor:'#1e3c72',borderWidth:2,borderRadius:5}]
},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
</script>
</body>
</html>
