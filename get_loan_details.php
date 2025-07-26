<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please login as admin.']);
    exit();
}

// Check if loan_id is provided
if (!isset($_GET['loan_id']) || empty($_GET['loan_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Loan ID is required.']);
    exit();
}

$loan_id = mysqli_real_escape_string($conn, $_GET['loan_id']);

// Get loan details with user information and payment data
$loan_query = "SELECT l.*, u.user_name, u.email, u.address, u.gender, u.profession, u.pan_no,
                      COALESCE(SUM(p.amount), 0) as total_paid,
                      COUNT(p.payment_id) as payment_count
               FROM loan l 
               LEFT JOIN user u ON l.user_id = u.user_id 
               LEFT JOIN payment p ON l.loan_id = p.loan_id 
               WHERE l.loan_id = '$loan_id'
               GROUP BY l.loan_id";
$loan_result = mysqli_query($conn, $loan_query);

if (mysqli_num_rows($loan_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan not found.']);
    exit();
}

$loan_data = mysqli_fetch_assoc($loan_result);

// Get all payments for this loan
$payments_query = "SELECT p.*, l.loan_type, l.loan_plan 
                   FROM payment p 
                   LEFT JOIN loan l ON p.loan_id = l.loan_id 
                   WHERE p.loan_id = '$loan_id' 
                   ORDER BY p.date DESC";
$payments_result = mysqli_query($conn, $payments_query);

$payments = [];
while ($payment = mysqli_fetch_assoc($payments_result)) {
    $payments[] = $payment;
}

// Calculate financial details
$loan_amount = (float)str_replace(',', '', $loan_data['loan_amount']);
$total_paid = (float)$loan_data['total_paid'];
$remaining_amount = $loan_amount - $total_paid;
$payment_percentage = $loan_amount > 0 ? ($total_paid / $loan_amount) * 100 : 0;

// Calculate loan duration
$start_date = new DateTime($loan_data['start_date']);
$current_date = new DateTime();
$duration = $start_date->diff($current_date);
$months_elapsed = ($duration->y * 12) + $duration->m;

// Prepare response data
$response = [
    'success' => true,
    'loan' => [
        'loan_id' => $loan_data['loan_id'],
        'loan_amount' => number_format($loan_amount, 2),
        'loan_type' => $loan_data['loan_type'],
        'loan_plan' => $loan_data['loan_plan'],
        'status' => $loan_data['status'],
        'start_date' => $loan_data['start_date'],
        'months_elapsed' => $months_elapsed,
        'total_paid' => number_format($total_paid, 2),
        'remaining_amount' => number_format($remaining_amount, 2),
        'payment_percentage' => round($payment_percentage, 1),
        'payment_count' => $loan_data['payment_count']
    ],
    'borrower' => [
        'user_id' => $loan_data['user_id'],
        'user_name' => $loan_data['user_name'],
        'email' => $loan_data['email'],
        'address' => $loan_data['address'],
        'gender' => $loan_data['gender'],
        'profession' => $loan_data['profession'],
        'pan_no' => $loan_data['pan_no']
    ],
    'payments' => $payments
];

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?> 