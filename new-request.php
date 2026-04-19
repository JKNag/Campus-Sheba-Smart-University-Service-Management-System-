<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/requests.php';
requireRole(['student']);
$activePage='new-request';$userId=getUserId();$cats=getServiceCategories();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $catRaw=$_POST['category_id']??'';
    $cid=(int)$catRaw;
    $isOthers=($catRaw==='0');
    $title=trim($_POST['title']??'');
    $desc=trim($_POST['description']??'');$pri=$_POST['priority']??'medium';
    $loc=trim($_POST['location']??'');$bld=trim($_POST['building']??'');$room=trim($_POST['room']??'');
    if((!$cid && !$isOthers)||!$title||!$desc){$error='Please fill all required fields.';}
    else{
        $catIdFinal=$isOthers ? null : $cid;
        $rid=createRequest($userId,$catIdFinal,$title,$desc,$pri,$loc,$bld,$room);
        if($rid){if(!empty($_FILES['attachment']['name']))saveAttachment($rid,$_FILES['attachment'],$userId);
            header("Location: request-detail.php?id=$rid&new=1");exit();}
        else{$error='Failed. Please try again.';}
    }
}
$activePage='new-request';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>New Request - Campus Sheba</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/dashboard.css">
<style>
.priority-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.pri-card{border:2px solid #e0e0e0;border-radius:10px;padding:12px 8px;text-align:center;
          cursor:pointer;transition:.25s;font-size:13px;font-weight:600}
.pri-card:hover,.pri-card.sel{border-color:#667eea;background:rgba(102,126,234,.06)}
.pri-low{color:#2e7d32}.pri-medium{color:#f57f17}.pri-high{color:#e65100}.pri-urgent{color:#c62828}
.file-zone{border:2px dashed #d0d0d0;border-radius:8px;padding:20px;text-align:center;
           cursor:pointer;color:#888;font-size:13px;transition:.3s}
.file-zone:hover{border-color:#667eea;color:#667eea}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.max-w{max-width:800px}
@media(max-width:600px){.form-row{grid-template-columns:1fr}.priority-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
<div class="top-bar">
  <h2><i class="fas fa-plus-circle" style="color:#2a5298;margin-right:8px"></i>New Service Request</h2>
  <div class="tb-right"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i>Logout</a></div>
</div>
<div class="card max-w">
<?php if($error):?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="POST" enctype="multipart/form-data">
  <div class="form-row">
    <div class="form-group">
      <label>Service Category <span style="color:#dc3545">*</span></label>
      <select name="category_id" class="form-control" required>
        <option value="">— Select Category —</option>
        <?php foreach($cats as $c):?>
        <option value="<?=$c['id']?>" <?=(($_POST['category_id']??'')==$c['id'])?'selected':''?>>
          <?=htmlspecialchars($c['name'])?> (<?=htmlspecialchars($c['department_name'])?>)
        </option>
        <?php endforeach;?>
        <option value="0" <?=(($catRaw??'')==='0')?'selected':''?>>
          📋 Others / General Inquiry
        </option>
      </select>
    </div>
    <div class="form-group">
      <label>Request Title <span style="color:#dc3545">*</span></label>
      <input type="text" name="title" class="form-control" placeholder="Brief summary of the issue"
             value="<?=htmlspecialchars($_POST['title']??'')?>" required>
    </div>
  </div>
  <div class="form-group">
    <label>Description <span style="color:#dc3545">*</span></label>
    <textarea name="description" class="form-control" placeholder="Describe the issue in detail…" required><?=htmlspecialchars($_POST['description']??'')?></textarea>
  </div>
  <div class="form-group">
    <label>Priority Level <span style="color:#dc3545">*</span></label>
    <input type="radio" name="priority" id="p-low"    value="low"    style="display:none">
    <input type="radio" name="priority" id="p-medium" value="medium" style="display:none" checked>
    <input type="radio" name="priority" id="p-high"   value="high"   style="display:none">
    <input type="radio" name="priority" id="p-urgent" value="urgent" style="display:none">
    <div class="priority-grid">
      <div class="pri-card pri-low"    onclick="setPri('p-low',this)">🟢 Low</div>
      <div class="pri-card pri-medium sel" onclick="setPri('p-medium',this)">🟡 Medium</div>
      <div class="pri-card pri-high"   onclick="setPri('p-high',this)">🟠 High</div>
      <div class="pri-card pri-urgent" onclick="setPri('p-urgent',this)">🔴 Urgent</div>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Building / Block</label>
      <input type="text" name="building" class="form-control" placeholder="e.g. Main Block A"
             value="<?=htmlspecialchars($_POST['building']??'')?>">
    </div>
    <div class="form-group">
      <label>Room / Location</label>
      <input type="text" name="room" class="form-control" placeholder="e.g. Room 301"
             value="<?=htmlspecialchars($_POST['room']??'')?>">
    </div>
  </div>
  <div class="form-group">
    <label>Attachment <small style="color:#888;font-weight:400">(jpg/png/pdf/doc — max 10 MB, optional)</small></label>
    <div class="file-zone" onclick="document.getElementById('attach').click()">
      <i class="fas fa-cloud-upload-alt" style="font-size:28px;display:block;margin-bottom:8px"></i>
      <span id="fLabel">Click to choose a file</span>
    </div>
    <input type="file" id="attach" name="attachment" style="display:none"
           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
           onchange="document.getElementById('fLabel').textContent=this.files[0]?.name||'Click to choose a file'">
  </div>
  <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap">
    <a href="student-dashboard.php" class="btn" style="background:#f0f2f5;color:#555"><i class="fas fa-arrow-left"></i>Back</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i>Submit Request</button>
  </div>
</form>
</div>
</div>
</div>
<script>
function setPri(id,card){
    document.getElementById(id).checked=true;
    document.querySelectorAll('.pri-card').forEach(c=>c.classList.remove('sel'));
    card.classList.add('sel');
}
</script>
</body></html>
