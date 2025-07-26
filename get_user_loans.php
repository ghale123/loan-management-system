<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please login as admin.']);
    exit();
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required.']);
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

// Get user basic information
$user_query = "SELECT user_name, email FROM user WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);

// Get all loans for the user with payment information
$loans_query = "SELECT l.*, 
                       COALESCE(SUM(p.amount), 0) as total_paid,
                       COUNT(p.payment_id) as payment_count
                FROM loan l 
                LEFT JOIN payment p ON l.loan_id = p.loan_id 
                WHERE l.user_id = '$user_id' 
                GROUP BY l.loan_id 
                ORDER BY l.start_date DESC";
$loans_result = mysqli_query($conn, $loans_query);

$loans = [];
$total_loans = 0;
$total_borrowed = 0;
$total_paid = 0;
$approved_loans = 0;
$pending_loans = 0;
$rejected_loans = 0;

while ($loan = mysqli_fetch_assoc($loans_result)) {
    $loans[] = $loan;
    $total_loans++;
    
    $loan_amount = (float)str_replace(',', '', $loan['loan_amount']);
    $total_borrowed += $loan_amount;
    $total_paid += $loan['total_paid'];
    
    if ($loan['status'] == 'approved') $approved_loans++;
    elseif ($loan['status'] == 'pending') $pending_loans++;
    elseif ($loan['status'] == 'rejected') $rejected_loans++;
}

// Calculate overall statistics
$remaining_amount = $total_borrowed - $total_paid;
$payment_percentage = $total_borrowed > 0 ? ($total_paid / $total_borrowed) * 100 : 0;

// Prepare response data
$response = [
    'success' => true,
    'user' => [
        'user_name' => $user_data['user_name'],
        'email' => $user_data['email']
    ],
    'statistics' => [
        'total_loans' => $total_loans,
        'approved_loans' => $approved_loans,
        'pending_loans' => $pending_loans,
        'rejected_loans' => $rejected_loans,
        'total_borrowed' => number_format($total_borrowed, 2),
        'total_paid' => number_format($total_paid, 2),
        'remaining_amount' => number_format($remaining_amount, 2),
        'payment_percentage' => round($payment_percentage, 1)
    ],
    'loans' => $loans
];

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?> 