<?php
require __DIR__ . '/../config/database.php';

// Security check (CLI or localhost only)
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die("Access denied.");
}

// 1. Find professors who forgot to check out TODAY
$today = date('Y-m-d');
$sql = "SELECT id, professor_id 
        FROM attendance 
        WHERE checkin_date = ? 
        AND check_out IS NULL
        AND auto_timeout = 0";  // Only process unmarked records
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

// 2. Auto-timeout at 23:59:59
$count = 0;
while ($row = $result->fetch_assoc()) {
    $timeout_sql = "UPDATE attendance 
                   SET check_out = CONCAT(?, ' 23:59:59'),
                       auto_logout_time = NOW(),
                       auto_timeout = 1,
                       status = 'Present'
                   WHERE id = ?";
    $stmt = $conn->prepare($timeout_sql);
    $stmt->bind_param("si", $today, $row['id']);
    $stmt->execute();
    $count++;
}

// Log results
file_put_contents(__DIR__ . '/auto_timeout.log', 
    date('Y-m-d H:i:s') . " - Auto-timed out $count professors\n", 
    FILE_APPEND);

echo "Success: $count professors auto-timed out.";
?>