<?php
include '../config/database.php';

// Fetch unread notifications first, then recent ones (limit 25)
$query = "SELECT id, message, created_at AS time, type, is_read 
          FROM notifications 
          ORDER BY is_read ASC, created_at DESC 
          LIMIT 25";
$result = $conn->query($query);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Mark notifications as read when fetched
$updateQuery = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
$conn->query($updateQuery);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($notifications);
?>
