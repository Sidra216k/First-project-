<?php
// Traveler/api/mark_notifications_read.php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'traveler') {
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$uid = $data['user_id'] ?? $_SESSION['user_id'];

// تحديث الإشعارات غير المقروءة فقط
$stmt = $conn->prepare("UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=? AND role='traveler' AND is_read=0");
$stmt->bind_param("i", $uid);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode(['success' => true, 'marked' => (int)$affected]);
