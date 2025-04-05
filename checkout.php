<?php
include '../config/database.php';
header('Content-Type: application/json');

// 1. Validate Request Method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    die(json_encode([
        "status" => "error",
        "message" => "❌ Only POST requests are allowed"
    ]));
}

// 2. Input Validation
if (!isset($_POST['professor_id']) || empty($_POST['professor_id'])) {
    http_response_code(400); // Bad Request
    die(json_encode([
        "status" => "error",
        "message" => "❌ Professor ID is required"
    ]));
}

$professor_id = (int)$_POST['professor_id'];
if ($professor_id <= 0) {
    http_response_code(400);
    die(json_encode([
        "status" => "error",
        "message" => "❌ Invalid Professor ID"
    ]));
}

try {
    // 3. Database Operations
    $conn->begin_transaction();

    // Check if professor exists
    $stmt = $conn->prepare("SELECT id FROM professors WHERE id = ?");
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Professor not found!");
    }

    // Get latest check-in
    $query = "SELECT id, check_in FROM attendance 
              WHERE professor_id = ? 
              AND check_out IS NULL
              ORDER BY check_in DESC 
              LIMIT 1 FOR UPDATE";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No active check-in found!");
    }

    $row = $result->fetch_assoc();
    $check_in = new DateTime($row['check_in']);
    $check_out = new DateTime();

    // Calculate duration
    $interval = $check_in->diff($check_out);
    $work_duration = $interval->format('%H:%I:%S');

    // Update record
    $update = "UPDATE attendance SET 
               check_out = ?,
               work_duration = ?,
               status = 'Present'
               WHERE id = ?";
    
    $stmt = $conn->prepare($update);
    $check_out_str = $check_out->format('Y-m-d H:i:s');
    $stmt->bind_param("ssi", $check_out_str, $work_duration, $row['id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update attendance record");
    }

    // Log action - FIXED: Correct parameter binding
    $action = "Professor timed out";
    $log_stmt = $conn->prepare("INSERT INTO logs 
                               (action, user, timestamp)
                               VALUES (?, 
                               (SELECT name FROM professors WHERE id = ?), 
                               NOW())");
    $log_stmt->bind_param("si", $action, $professor_id);
    if (!$log_stmt->execute()) {
        throw new Exception("Failed to log action");
    }

    $conn->commit();
    
    // 4. Success Response
    echo json_encode([
        "status" => "success",
        "message" => "✅ Time Out recorded successfully!",
        "check_out" => $check_out_str,
        "work_duration" => $work_duration,
        "professor_id" => $professor_id
    ]);

} catch (Exception $e) {
    // 5. Error Handling
    if ($conn) {
        $conn->rollback();
    }
    error_log("Checkout Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "❌ " . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>