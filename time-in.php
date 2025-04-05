<?php
include '../config/database.php';

$professor_id = $_POST['professor_id'] ?? null;
if (!$professor_id) {
    die(json_encode(["success" => false, "message" => "No professor ID provided."]));
}

$check_in_time = date("Y-m-d H:i:s");

// Fetch professor name
$professorQuery = $conn->prepare("SELECT name FROM professors WHERE id = ?");
$professorQuery->bind_param("i", $professor_id);
$professorQuery->execute();
$result = $professorQuery->get_result();
$professor = $result->fetch_assoc();

if (!$professor) {
    die(json_encode(["success" => false, "message" => "Professor not found."]));
}

$professor_name = $professor['name'];

// Insert check-in record into attendance
$query = "INSERT INTO attendance (professor_id, check_in) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $professor_id, $check_in_time);

if (!$stmt->execute()) {
    die(json_encode(["success" => false, "message" => "Failed to insert attendance: " . $stmt->error]));
}

// 🔔 Insert notification
$notif_message = "$professor_name has checked in.";
$notif_type = "check-in";
$is_read = 0;

$query_notif = "INSERT INTO notifications (message, type, created_at, is_read) VALUES (?, ?, NOW(), ?)";
$stmt_notif = $conn->prepare($query_notif);
$stmt_notif->bind_param("ssi", $notif_message, $notif_type, $is_read);

if (!$stmt_notif->execute()) {
    die(json_encode(["success" => false, "message" => "Failed to insert notification: " . $stmt_notif->error]));
}

// ✅ Success
echo json_encode(["success" => true, "message" => "Check-in recorded successfully"]);
?>
