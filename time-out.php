<?php
include '../config/database.php';

$professor_id = $_POST['professor_id'];
$check_out_time = date("Y-m-d H:i:s");

// Fetch professor name
$professorQuery = $conn->prepare("SELECT name FROM professors WHERE id = ?");
$professorQuery->bind_param("i", $professor_id);
$professorQuery->execute();
$result = $professorQuery->get_result();
$professor = $result->fetch_assoc();
$professor_name = $professor['name'] ?? "Unknown";

// Update the check-out record
$query = "UPDATE attendance SET check_out = ? WHERE professor_id = ? AND check_out IS NULL ORDER BY check_in DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $check_out_time, $professor_id);
$stmt->execute();

// 🔔 Insert a Notification
$notif_message = "$professor_name has checked out.";
$notif_type = "check-out";
$is_read = 0;

$query_notif = "INSERT INTO notifications (message, type, created_at, is_read) VALUES (?, ?, NOW(), ?)";
$stmt_notif = $conn->prepare($query_notif);
$stmt_notif->bind_param("ssi", $notif_message, $notif_type, $is_read);
$stmt_notif->execute();

echo json_encode(["success" => true, "message" => "Check-out recorded successfully"]);
?>
