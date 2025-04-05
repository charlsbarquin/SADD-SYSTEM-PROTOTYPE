<?php
include '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Attendance ID required']);
    exit;
}

$attendanceId = $_GET['id'];
$query = "SELECT 
            a.*, 
            p.name, 
            p.designation,
            p.email,
            a.latitude,
            a.longitude,
            a.work_duration,
            CASE 
                WHEN TIME(a.check_in) > '22:00:00' THEN 'Late'
                WHEN a.check_out IS NULL THEN 'Active'
                ELSE 'Present'
            END as status
          FROM attendance a
          JOIN professors p ON a.professor_id = p.id
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $attendanceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(['error' => 'Record not found']);
}
?>