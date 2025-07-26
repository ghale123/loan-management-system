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

// Validation
if ($plan_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan ID.']);
    exit();
}

// Check if plan exists and get plan details
$check_query = "SELECT * FROM loan_plans WHERE plan_id = $plan_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan plan not found.']);
    exit();
}

$plan_data = mysqli_fetch_assoc($check_result);

// Check if plan is being used by any loans
$usage_query = "SELECT COUNT(*) as usage_count FROM loan WHERE loan_type = '{$plan_data['loan_type']}' AND loan_plan = '{$plan_data['loan_plan']}'";
$usage_result = mysqli_query($conn, $usage_query);
$usage_data = mysqli_fetch_assoc($usage_result);

if ($usage_data['usage_count'] > 0) {
    http_response_code(400);
    echo json_encode([
        'error' => "Cannot delete this loan plan. It is currently being used by {$usage_data['usage_count']} active loan(s). Please deactivate the plan instead."
    ]);
    exit();
}

// Delete the plan
$delete_query = "DELETE FROM loan_plans WHERE plan_id = $plan_id";

if (mysqli_query($conn, $delete_query)) {
    echo json_encode([
        'success' => true,
        'message' => "✅ Loan plan '{$plan_data['loan_plan']}' has been deleted successfully!"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to delete loan plan. Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?> 