<?php
// includes/notifications.php
require_once __DIR__ . '/../config/database.php';

function createNotification($userId, $type, $title, $message, $requestId = null, $link = null) {
    global $pdo;
    try {
        $pdo->prepare(
            "INSERT INTO notifications (user_id,type,title,message,request_id,link) VALUES (?,?,?,?,?,?)"
        )->execute([$userId, $type, $title, $message, $requestId, $link]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) { return false; }
}

function getUnreadCount($userId) {
    global $pdo;
    $s = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}

function getNotifications($userId, $limit = 20, $onlyUnread = false) {
    global $pdo;
    $sql = "SELECT * FROM notifications WHERE user_id=?";
    if ($onlyUnread) $sql .= " AND is_read=0";
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $s = $pdo->prepare($sql);
    $s->execute([$userId, $limit]);
    return $s->fetchAll();
}

function markNotificationRead($id, $userId) {
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $userId]);
}

function markAllNotificationsRead($userId) {
    global $pdo;
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
}

function notifyStatusChange($request, $newStatus, $comment, $changedById) {
    $titles = [
        'assigned'    => 'Your request has been assigned',
        'in_progress' => 'Work started on your request',
        'resolved'    => 'Your request has been resolved ✅',
        'closed'      => 'Your request has been closed',
        'rejected'    => 'Your request was rejected',
        'pending_info'=> 'More information needed',
    ];
    $title   = $titles[$newStatus] ?? 'Request status updated';
    $message = "Request #{$request['request_id']} → " . strtoupper($newStatus) . ($comment ? ". Note: $comment" : '');
    $link    = "pages/request-detail.php?id=" . $request['id'];

    createNotification($request['user_id'], 'info', $title, $message, $request['id'], $link);

    if (!empty($request['assigned_to']) && $request['assigned_to'] != $changedById) {
        createNotification($request['assigned_to'], 'info',
            'Request #' . $request['request_id'] . ' updated', $message, $request['id'], $link);
    }
}
?>
