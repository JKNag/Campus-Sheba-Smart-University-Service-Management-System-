<?php
// includes/requests.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notifications.php';

function createRequest($userId, $catId, $title, $desc, $priority='medium', $loc='', $bld='', $room='') {
    global $pdo;

    // catId is null for "Others / General Inquiry"
    $catIdVal = ($catId && $catId > 0) ? (int)$catId : null;

    $stmt = $pdo->prepare(
        "INSERT INTO service_requests
         (request_id, user_id, category_id, title, description, priority, location, building, room_number, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute(['TEMP', $userId, $catIdVal, $title, $desc, $priority, $loc, $bld, $room, 'submitted']);

    $id    = $pdo->lastInsertId();
    $reqId = 'REQ-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);

    // Update to real request_id
    $pdo->prepare("UPDATE service_requests SET request_id = ? WHERE id = ?")->execute([$reqId, $id]);

    // Set deadline from category estimated_hours (default 48h for Others)
    $hrs = 48;
    if ($catIdVal) {
        $cat = $pdo->prepare("SELECT estimated_hours FROM service_categories WHERE id = ?");
        $cat->execute([$catIdVal]);
        $hrs = $cat->fetchColumn() ?: 48;
    }
    $pdo->prepare("UPDATE service_requests SET deadline = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE id = ?")
        ->execute([$hrs, $id]);

    // Log history + notify
    addRequestHistory($id, 'submitted', 'Request submitted by student.', $userId);
    createNotification(
        $userId, 'success',
        'Request Submitted',
        "Your request \"$title\" ($reqId) was submitted successfully.",
        $id
    );

    return $id;
}

function updateRequestStatus($requestId, $newStatus, $comment, $changedById, $assignedTo = null) {
    global $pdo;
    $request = getRequestById($requestId);
    if (!$request) return false;

    $set = "status = ?, updated_at = NOW()";
    $p   = [$newStatus];

    if ($newStatus === 'in_progress' && !$request['started_at']) { $set .= ", started_at = NOW()"; }
    if ($newStatus === 'resolved')                               { $set .= ", resolved_at = NOW()"; }
    if ($newStatus === 'closed')                                 { $set .= ", closed_at = NOW()"; }
    if ($assignedTo !== null) {
        $set .= ", assigned_to = ?, assigned_by = ?, assigned_at = NOW()";
        $p[] = $assignedTo;
        $p[] = $changedById;
    }
    $p[] = $requestId;

    $pdo->prepare("UPDATE service_requests SET $set WHERE id = ?")->execute($p);
    addRequestHistory($requestId, $newStatus, $comment, $changedById);
    notifyStatusChange($request, $newStatus, $comment, $changedById);
    return true;
}

function getRequestById($id) {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT sr.*, COALESCE(sc.name, 'Others / General Inquiry') AS category_name,
                u.full_name  AS requester_name,
                st.full_name AS assigned_staff_name
         FROM service_requests sr
         LEFT JOIN service_categories sc ON sr.category_id = sc.id
         JOIN  users u  ON sr.user_id    = u.id
         LEFT JOIN users st ON sr.assigned_to = st.id
         WHERE sr.id = ?"
    );
    $s->execute([$id]);
    return $s->fetch();
}

function getRequestsByUser($userId, $status = null, $limit = 50, $offset = 0) {
    global $pdo;
    $sql = "SELECT sr.*, COALESCE(sc.name, 'Others / General Inquiry') AS category_name
            FROM service_requests sr
            LEFT JOIN service_categories sc ON sr.category_id = sc.id
            WHERE sr.user_id = ?";
    $p = [$userId];
    if ($status) { $sql .= " AND sr.status = ?"; $p[] = $status; }
    $sql .= " ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
    $p[] = $limit;
    $p[] = $offset;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchAll();
}

function getAllRequests($filters = [], $limit = 50, $offset = 0) {
    global $pdo;
    $sql = "SELECT sr.*, COALESCE(sc.name, 'Others / General Inquiry') AS category_name,
                   u.full_name  AS requester_name,
                   st.full_name AS assigned_staff_name
            FROM service_requests sr
            LEFT JOIN service_categories sc ON sr.category_id = sc.id
            JOIN  users u  ON sr.user_id    = u.id
            LEFT JOIN users st ON sr.assigned_to = st.id
            WHERE 1=1";
    $p = [];

    if (!empty($filters['status']))      { $sql .= " AND sr.status = ?";      $p[] = $filters['status']; }
    if (!empty($filters['priority']))    { $sql .= " AND sr.priority = ?";    $p[] = $filters['priority']; }
    if (!empty($filters['category']))    { $sql .= " AND sr.category_id = ?"; $p[] = $filters['category']; }
    if (!empty($filters['assigned_to'])) { $sql .= " AND sr.assigned_to = ?"; $p[] = $filters['assigned_to']; }
    if (!empty($filters['search'])) {
        $sql .= " AND (sr.title LIKE ? OR sr.request_id LIKE ? OR u.full_name LIKE ?)";
        $s2   = '%' . $filters['search'] . '%';
        $p[]  = $s2; $p[] = $s2; $p[] = $s2;
    }

    $sql .= " ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
    $p[] = $limit;
    $p[] = $offset;

    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchAll();
}

function countRequests($filters = []) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE 1=1";
    $p = [];
    if (!empty($filters['status']))      { $sql .= " AND sr.status = ?";      $p[] = $filters['status']; }
    if (!empty($filters['user_id']))     { $sql .= " AND sr.user_id = ?";     $p[] = $filters['user_id']; }
    if (!empty($filters['assigned_to'])) { $sql .= " AND sr.assigned_to = ?"; $p[] = $filters['assigned_to']; }
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return (int)$s->fetchColumn();
}

function addRequestHistory($reqId, $status, $comment, $userId) {
    global $pdo;
    $pdo->prepare(
        "INSERT INTO request_history (request_id, status, comment, changed_by) VALUES (?, ?, ?, ?)"
    )->execute([$reqId, $status, $comment, $userId]);
}

function getRequestHistory($reqId) {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT rh.*, u.full_name AS changed_by_name
         FROM request_history rh
         JOIN users u ON rh.changed_by = u.id
         WHERE rh.request_id = ?
         ORDER BY rh.created_at ASC"
    );
    $s->execute([$reqId]);
    return $s->fetchAll();
}

function addComment($reqId, $userId, $comment, $staffOnly = false) {
    global $pdo;
    $pdo->prepare(
        "INSERT INTO request_comments (request_id, user_id, comment, is_staff_only) VALUES (?, ?, ?, ?)"
    )->execute([$reqId, $userId, $comment, $staffOnly ? 1 : 0]);
}

function getComments($reqId, $includeStaffOnly = false) {
    global $pdo;
    $sql = "SELECT rc.*, u.full_name, u.role
            FROM request_comments rc
            JOIN users u ON rc.user_id = u.id
            WHERE rc.request_id = ?";
    if (!$includeStaffOnly) $sql .= " AND rc.is_staff_only = 0";
    $sql .= " ORDER BY rc.created_at ASC";
    $s = $pdo->prepare($sql);
    $s->execute([$reqId]);
    return $s->fetchAll();
}

function saveAttachment($reqId, $file, $uploadedBy) {
    global $pdo;
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed))    return ['error' => 'File type not allowed.'];
    if ($file['size'] > 10*1024*1024) return ['error' => 'File exceeds 10 MB.'];

    $dir = __DIR__ . '/../uploads/attachments/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $fname = uniqid('att_') . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) return ['error' => 'Upload failed.'];

    $pdo->prepare(
        "INSERT INTO request_attachments
         (request_id, file_name, file_path, file_size, file_type, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([$reqId, $file['name'], 'uploads/attachments/' . $fname, $file['size'], $file['type'], $uploadedBy]);

    $pdo->prepare("UPDATE service_requests SET has_attachments = 1 WHERE id = ?")->execute([$reqId]);
    return ['success' => true];
}

function getStudentStats($userId) {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT
            COUNT(*)                                          AS total,
            SUM(status IN ('assigned','in_progress'))        AS active,
            SUM(status IN ('resolved','closed'))             AS resolved,
            SUM(status = 'submitted')                        AS pending
         FROM service_requests WHERE user_id = ?"
    );
    $s->execute([$userId]);
    $r = $s->fetch();
    $avg = $pdo->prepare("SELECT ROUND(AVG(rating), 1) FROM feedback WHERE user_id = ?");
    $avg->execute([$userId]);
    $r['avg_rating'] = $avg->fetchColumn() ?: '—';
    return $r;
}

function getStaffStats($staffId) {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT
            COUNT(*)                                   AS total_assigned,
            SUM(status = 'in_progress')                AS in_progress,
            SUM(status IN ('resolved','closed'))       AS completed
         FROM service_requests WHERE assigned_to = ?"
    );
    $s->execute([$staffId]);
    $r = $s->fetch();
    $avg = $pdo->prepare(
        "SELECT ROUND(AVG(f.rating), 1)
         FROM feedback f
         JOIN service_requests sr ON f.request_id = sr.id
         WHERE sr.assigned_to = ?"
    );
    $avg->execute([$staffId]);
    $r['avg_rating'] = $avg->fetchColumn() ?: '—';
    return $r;
}

function getAdminStats() {
    global $pdo;
    $r = $pdo->query(
        "SELECT
            COUNT(*)                                              AS total,
            SUM(status IN ('submitted','pending_approval'))      AS pending,
            SUM(status IN ('assigned','in_progress'))            AS active,
            SUM(status IN ('resolved','closed'))                 AS resolved
         FROM service_requests"
    )->fetch();
    $r['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $r['total_staff'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
    $avg = $pdo->query("SELECT ROUND(AVG(rating), 1) FROM feedback");
    $r['avg_rating'] = $avg->fetchColumn() ?: '—';
    return $r;
}

function getServiceCategories() {
    global $pdo;
    return $pdo->query(
        "SELECT sc.*, d.name AS department_name
         FROM service_categories sc
         LEFT JOIN departments d ON sc.department_id = d.id
         WHERE sc.is_active = 1
         ORDER BY sc.name"
    )->fetchAll();
}

function getStaffList() {
    global $pdo;
    return $pdo->query(
        "SELECT u.id, u.full_name, u.email, d.name AS department_name,
                COUNT(sr.id) AS active_requests
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         LEFT JOIN service_requests sr
               ON u.id = sr.assigned_to
               AND sr.status IN ('assigned','in_progress')
         WHERE u.role = 'staff' AND u.is_active = 1
         GROUP BY u.id, u.full_name, u.email, d.name
         ORDER BY u.full_name"
    )->fetchAll();
}

function getAttachments($reqId) {
    global $pdo;
    $s = $pdo->prepare(
        "SELECT ra.*, u.full_name AS uploaded_by_name
         FROM request_attachments ra
         LEFT JOIN users u ON ra.uploaded_by = u.id
         WHERE ra.request_id = ?
         ORDER BY ra.uploaded_at ASC"
    );
    $s->execute([$reqId]);
    return $s->fetchAll();
}

?>
