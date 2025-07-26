<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please login as admin.']);
    exit();
}

// Check if payment_id is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment ID is required.']);
    exit();
}

$payment_id = mysqli_real_escape_string($conn, $_GET['payment_id']);

// Get payment details with user and loan information
$payment_query = "SELECT p.*, u.user_name, u.email, u.address, u.gender, u.profession, u.pan_no,
                         l.loan_type, l.loan_plan, l.loan_amount, l.status as loan_status,
                         l.start_date as loan_start_date
                  FROM payment p 
                  LEFT JOIN user u ON p.user_id = u.user_id 
                  LEFT JOIN loan l ON p.loan_id = l.loan_id 
                  WHERE p.payment_id = '$payment_id'";
$payment_result = mysqli_query($conn, $payment_query);

if (mysqli_num_rows($payment_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found.']);
    exit();
}

$payment_data = mysqli_fetch_assoc($payment_result);

// Get all payments for this loan to calculate loan progress
$loan_payments_query = "SELECT 
                           COALESCE(SUM(amount), 0) as total_paid,
                           COUNT(payment_id) as payment_count
                        FROM payment 
                        WHERE loan_id = '" . $payment_data['loan_id'] . "'";
$loan_payments_result = mysqli_query($conn, $loan_payments_query);
$loan_payments_data = mysqli_fetch_assoc($loan_payments_result);

// Calculate loan progress
$loan_amount = (float)str_replace(',', '', $payment_data['loan_amount']);
$total_paid = (float)$loan_payments_data['total_paid'];
$remaining_amount = $loan_amount - $total_paid;
$payment_percentage = $loan_amount > 0 ? ($total_paid / $loan_amount) * 100 : 0;

// Calculate loan duration
$loan_start_date = new DateTime($payment_data['loan_start_date']);
$current_date = new DateTime();
$duration = $loan_start_date->diff($current_date);
$months_elapsed = ($duration->y * 12) + $duration->m;

// Get payment position (which payment number this is)
$payment_position_query = "SELECT COUNT(*) as position 
                           FROM payment 
                           WHERE loan_id = '" . $payment_data['loan_id'] . "' 
                           AND date <= '" . $payment_data['date'] . "'";
$payment_position_result = mysqli_query($conn, $payment_position_query);
$payment_position_data = mysqli_fetch_assoc($payment_position_result);

// Prepare response data
$response = [
    'success' => true,
    'payment' => [
        'payment_id' => $payment_data['payment_id'],
        'amount' => number_format($payment_data['amount'], 2),
        'date' => $payment_data['date'],
        'bill_no' => $payment_data['bill_no'],
        'payment_position' => $payment_position_data['position'],
        'payment_count' => $loan_payments_data['payment_count']
    ],
    'loan' => [
        'loan_id' => $payment_data['loan_id'],
        'loan_amount' => number_format($loan_amount, 2),
        'loan_type' => $payment_data['loan_type'],
        'loan_plan' => $payment_data['loan_plan'],
        'status' => $payment_data['loan_status'],
        'start_date' => $payment_data['loan_start_date'],
        'months_elapsed' => $months_elapsed,
        'total_paid' => number_format($total_paid, 2),
        'remaining_amount' => number_format($remaining_amount, 2),
        'payment_percentage' => round($payment_percentage, 1)
    ],
    'payer' => [
        'user_id' => $payment_data['user_id'],
        'user_name' => $payment_data['user_name'],
        'email' => $payment_data['email'],
        'address' => $payment_data['address'],
        'gender' => $payment_data['gender'],
        'profession' => $payment_data['profession'],
        'pan_no' => $payment_data['pan_no']
    ]
];

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?> 