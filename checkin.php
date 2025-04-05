<?php
header('Content-Type: application/json');
include '../config/database.php';

// Error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    die(json_encode(["status" => "error", "message" => "Method Not Allowed"]));
}

// Get and validate input
$professor_id = $_POST['professor_id'] ?? null;
$image_data = $_POST['image_data'] ?? null;
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$accuracy = $_POST['accuracy'] ?? null;

// Validate required fields
if (!$professor_id) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Professor ID is required"]));
}

// Verify professor exists
$professorQuery = $conn->prepare("SELECT name FROM professors WHERE id = ?");
$professorQuery->bind_param("i", $professor_id);
$professorQuery->execute();
$result = $professorQuery->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(["status" => "error", "message" => "Professor not found"]));
}

// Check for existing check-in today
$existingQuery = $conn->prepare("SELECT id FROM attendance 
                               WHERE professor_id = ? 
                               AND DATE(check_in) = CURDATE()");
$existingQuery->bind_param("i", $professor_id);
$existingQuery->execute();

if ($existingQuery->get_result()->num_rows > 0) {
    http_response_code(409);
    die(json_encode(["status" => "error", "message" => "Already checked in today"]));
}

// Process image if provided
$photo_path = null;
if ($image_data) {
    $upload_dir = '../uploads/checkins/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = 'checkin_' . $professor_id . '_' . time() . '.jpg';
    $photo_path = $upload_dir . $filename;
    
    // Save base64 image data
    if (!file_put_contents($photo_path, base64_decode($image_data))) {
        error_log("Failed to save image for professor $professor_id");
    }
}

// Record attendance with all available data
$sql = "INSERT INTO attendance 
        (professor_id, check_in, status, latitude, longitude, accuracy, photo_path) 
        VALUES (?, NOW(), 'Present', ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iddds", 
    $professor_id,
    $latitude,
    $longitude,
    $accuracy,
    $photo_path
);

if ($stmt->execute()) {
    // Log successful check-in
    error_log("Professor $professor_id checked in at " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        "status" => "success", 
        "message" => "Check-in recorded successfully",
        "checkin_time" => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    error_log("Database error: " . $stmt->error);
    echo json_encode([
        "status" => "error", 
        "message" => "Failed to record check-in"
    ]);
}

$conn->close();
?>