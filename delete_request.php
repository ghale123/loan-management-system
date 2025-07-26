<?php
include ("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Access denied. Please login as admin.')</script>";
    echo "<script>window.location.href='admin_login.php';</script>";
    exit();
}

// Validate loan_id parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid loan request. Loan ID is required.')</script>";
    echo "<script>window.location.href='admin_dashboard.php';</script>";
    exit();
}

$loan_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if loan exists and is pending
$check_query = "SELECT l.*, u.user_name FROM loan l 
                LEFT JOIN user u ON l.user_id = u.user_id 
                WHERE l.loan_id = '$loan_id' AND l.status = 'pending'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo "<script>alert('Loan request not found or already processed.')</script>";
    echo "<script>window.location.href='admin_dashboard.php';</script>";
    exit();
}

$loan_data = mysqli_fetch_assoc($check_result);

// Update loan status to rejected instead of deleting
$update_query = "UPDATE loan SET status='rejected' WHERE loan_id='$loan_id'";
$update_data = mysqli_query($conn, $update_query);

if ($update_data) {
    echo "<script>
        alert('❌ Loan request rejected successfully!\\n\\nBorrower: " . addslashes($loan_data['user_name']) . "\\nLoan Amount: Rs." . number_format((float)str_replace(',', '', $loan_data['loan_amount']), 2) . "\\nLoan Type: " . $loan_data['loan_type'] . "\\n\\nThe borrower will be notified of the rejection.');
    </script>";
    echo "<script>window.location.href='admin_dashboard.php';</script>";
} else {
    echo "<script>alert('❌ Failed to reject loan request. Please try again.\\n\\nError: " . addslashes(mysqli_error($conn)) . "')</script>";
    echo "<script>window.location.href='admin_dashboard.php';</script>";
}
?>