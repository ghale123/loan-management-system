<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please login as admin.']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

// Get form data
$plan_id = (int)$_POST['plan_id'];
$action = mysqli_real_escape_string($conn, $_POST['action']);

// Validation
if ($plan_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan ID.']);
    exit();
}

if (!in_array($action, ['activate', 'deactivate'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action.']);
    exit();
}

// Check if plan exists
$check_query = "SELECT * FROM loan_plans WHERE plan_id = $plan_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan plan not found.']);
    exit();
}

$plan_data = mysqli_fetch_assoc($check_result);
$new_status = ($action === 'activate') ? 1 : 0;

// Update plan status
$update_query = "UPDATE loan_plans SET is_active = $new_status, updated_at = NOW() WHERE plan_id = $plan_id";

if (mysqli_query($conn, $update_query)) {
    $status_text = $new_status ? 'activated' : 'deactivated';
    echo json_encode([
        'success' => true,
        'message' => "✅ Loan plan '{$plan_data['loan_plan']}' has been $status_text successfully!"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update plan status. Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?> 