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
$loan_type = mysqli_real_escape_string($conn, $_POST['loan_type']);
$loan_plan = mysqli_real_escape_string($conn, $_POST['loan_plan']);
$interest_rate = mysqli_real_escape_string($conn, $_POST['interest_rate']);
$penalty_rate = mysqli_real_escape_string($conn, $_POST['penalty_rate']);
$duration_months = (int)$_POST['duration_months'];
$min_amount = (float)$_POST['min_amount'];
$max_amount = (float)$_POST['max_amount'];
$description = mysqli_real_escape_string($conn, $_POST['description']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validation
$errors = [];

if ($plan_id <= 0) {
    $errors[] = "Invalid plan ID.";
}

if (empty($loan_type)) {
    $errors[] = "Loan type is required.";
}

if (empty($loan_plan)) {
    $errors[] = "Plan name is required.";
}

if (empty($interest_rate)) {
    $errors[] = "Interest rate is required.";
} elseif (!is_numeric(str_replace('%', '', $interest_rate)) || (float)str_replace('%', '', $interest_rate) < 0 || (float)str_replace('%', '', $interest_rate) > 100) {
    $errors[] = "Interest rate must be between 0% and 100%.";
}

if (empty($penalty_rate)) {
    $errors[] = "Penalty rate is required.";
} elseif (!is_numeric(str_replace('%', '', $penalty_rate)) || (float)str_replace('%', '', $penalty_rate) < 0 || (float)str_replace('%', '', $penalty_rate) > 100) {
    $errors[] = "Penalty rate must be between 0% and 100%.";
}

if ($duration_months < 1 || $duration_months > 120) {
    $errors[] = "Duration must be between 1 and 120 months.";
}

if ($min_amount < 0) {
    $errors[] = "Minimum amount cannot be negative.";
}

if ($max_amount < 0) {
    $errors[] = "Maximum amount cannot be negative.";
}

if ($max_amount <= $min_amount) {
    $errors[] = "Maximum amount must be greater than minimum amount.";
}

if (empty($description)) {
    $errors[] = "Description is required.";
}

// Check if plan exists
$check_query = "SELECT * FROM loan_plans WHERE plan_id = $plan_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    $errors[] = "Loan plan not found.";
}

// Check if plan name already exists for different plan
$duplicate_query = "SELECT COUNT(*) as count FROM loan_plans WHERE loan_type = '$loan_type' AND loan_plan = '$loan_plan' AND plan_id != $plan_id";
$duplicate_result = mysqli_query($conn, $duplicate_query);
$duplicate_data = mysqli_fetch_assoc($duplicate_result);

if ($duplicate_data['count'] > 0) {
    $errors[] = "A plan with this name already exists for the selected loan type.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit();
}

// Update plan
$update_query = "UPDATE loan_plans SET 
                 loan_type = '$loan_type',
                 loan_plan = '$loan_plan',
                 interest_rate = '$interest_rate',
                 penalty_rate = '$penalty_rate',
                 duration_months = $duration_months,
                 min_amount = $min_amount,
                 max_amount = $max_amount,
                 description = '$description',
                 is_active = $is_active,
                 updated_at = NOW()
                 WHERE plan_id = $plan_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode([
        'success' => true,
        'message' => "✅ Loan plan '$loan_plan' updated successfully!"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update loan plan. Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?> 