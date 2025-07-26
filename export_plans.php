<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Access denied. Please login as admin.')</script>";
    echo "<script>window.location.href='admin_login.php';</script>";
    exit();
}

// Get all loan plans
$plans_query = "SELECT * FROM loan_plans ORDER BY loan_type, loan_plan";
$plans_result = mysqli_query($conn, $plans_query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="loan_plans_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Plan ID',
    'Loan Type',
    'Plan Name',
    'Interest Rate',
    'Penalty Rate',
    'Duration (Months)',
    'Minimum Amount',
    'Maximum Amount',
    'Description',
    'Status',
    'Created Date',
    'Last Updated'
]);

// Add data rows
while ($plan = mysqli_fetch_assoc($plans_result)) {
    $status = $plan['is_active'] ? 'Active' : 'Inactive';
    
    fputcsv($output, [
        $plan['plan_id'],
        $plan['loan_type'],
        $plan['loan_plan'],
        $plan['interest_rate'],
        $plan['penalty_rate'],
        $plan['duration_months'],
        number_format($plan['min_amount'], 2),
        number_format($plan['max_amount'], 2),
        $plan['description'],
        $status,
        $plan['created_at'],
        $plan['updated_at'] ?: 'Never'
    ]);
}

fclose($output);
mysqli_close($conn);
?> 