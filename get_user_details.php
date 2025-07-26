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

// Get user details with loan statistics
$user_query = "SELECT u.*, 
                      COUNT(l.loan_id) as total_loans,
                      SUM(CASE WHEN l.status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
                      SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END) as pending_loans,
                      SUM(CASE WHEN l.status = 'rejected' THEN 1 ELSE 0 END) as rejected_loans,
                      SUM(CAST(REPLACE(l.loan_amount, ',', '') AS DECIMAL(10,2))) as total_borrowed,
                      SUM(p.amount) as total_paid
               FROM user u 
               LEFT JOIN loan l ON u.user_id = l.user_id 
               LEFT JOIN payment p ON l.loan_id = p.loan_id
               WHERE u.user_id = '$user_id'
               GROUP BY u.user_id";

$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);

// Get recent loan history
$loans_query = "SELECT l.*, 
                       COALESCE(SUM(p.amount), 0) as total_paid
                FROM loan l 
                LEFT JOIN payment p ON l.loan_id = p.loan_id 
                WHERE l.user_id = '$user_id' 
                GROUP BY l.loan_id 
                ORDER BY l.start_date DESC 
                LIMIT 5";
$loans_result = mysqli_query($conn, $loans_query);

$recent_loans = [];
while ($loan = mysqli_fetch_assoc($loans_result)) {
    $recent_loans[] = $loan;
}

// Get recent payment history
$payments_query = "SELECT p.*, l.loan_type, l.loan_plan 
                   FROM payment p 
                   LEFT JOIN loan l ON p.loan_id = l.loan_id 
                   WHERE p.user_id = '$user_id' 
                   ORDER BY p.date DESC 
                   LIMIT 5";
$payments_result = mysqli_query($conn, $payments_query);

$recent_payments = [];
while ($payment = mysqli_fetch_assoc($payments_result)) {
    $recent_payments[] = $payment;
}

// Calculate additional statistics
$total_borrowed = $user_data['total_borrowed'] ?: 0;
$total_paid = $user_data['total_paid'] ?: 0;
$remaining_amount = $total_borrowed - $total_paid;
$payment_percentage = $total_borrowed > 0 ? ($total_paid / $total_borrowed) * 100 : 0;

// Prepare response data
$response = [
    'success' => true,
    'user' => [
        'user_id' => $user_data['user_id'],
        'user_name' => $user_data['user_name'],
        'email' => $user_data['email'],
        'address' => $user_data['address'],
        'gender' => $user_data['gender'],
        'profession' => $user_data['profession'],
        'pan_no' => $user_data['pan_no']
    ],
    'statistics' => [
        'total_loans' => $user_data['total_loans'],
        'approved_loans' => $user_data['approved_loans'],
        'pending_loans' => $user_data['pending_loans'],
        'rejected_loans' => $user_data['rejected_loans'],
        'total_borrowed' => number_format($total_borrowed, 2),
        'total_paid' => number_format($total_paid, 2),
        'remaining_amount' => number_format($remaining_amount, 2),
        'payment_percentage' => round($payment_percentage, 1)
    ],
    'recent_loans' => $recent_loans,
    'recent_payments' => $recent_payments
];

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
?> 