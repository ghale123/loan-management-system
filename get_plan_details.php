<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please login as admin.']);
    exit();
}

// Check if plan_id is provided
if (!isset($_GET['plan_id']) || empty($_GET['plan_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Plan ID is required.']);
    exit();
}

$plan_id = (int)$_GET['plan_id'];

// Get plan details
$plan_query = "SELECT * FROM loan_plans WHERE plan_id = $plan_id";
$plan_result = mysqli_query($conn, $plan_query);

if (mysqli_num_rows($plan_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan plan not found.']);
    exit();
}

$plan_data = mysqli_fetch_assoc($plan_result);

// Prepare response data
$response = [
    'success' => true,
    'plan' => [
        'plan_id' => $plan_data['plan_id'],
        'loan_type' => $plan_data['loan_type'],
        'loan_plan' => $plan_data['loan_plan'],
        'interest_rate' => $plan_data['interest_rate'],
        'penalty_rate' => $plan_data['penalty_rate'],
        'duration_months' => $plan_data['duration_months'],
        'min_amount' => $plan_data['min_amount'],
        'max_amount' => $plan_data['max_amount'],
        'description' => $plan_data['description'],
        'is_active' => $plan_data['is_active'],
        'created_at' => $plan_data['created_at'],
        'updated_at' => $plan_data['updated_at']
    ]
];

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?> 