<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Access denied. Please login as admin.')</script>";
    echo "<script>window.location.href='admin_login.php';</script>";
    exit();
}

// Validate user_id parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid user. User ID is required.')</script>";
    echo "<script>window.location.href='admin_users.php';</script>";
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if user exists and get user details
$check_query = "SELECT u.*, 
                       COUNT(l.loan_id) as total_loans,
                       SUM(CASE WHEN l.status = 'approved' THEN 1 ELSE 0 END) as active_loans,
                       SUM(CAST(REPLACE(l.loan_amount, ',', '') AS DECIMAL(10,2))) as total_borrowed,
                       SUM(p.amount) as total_paid
                FROM user u 
                LEFT JOIN loan l ON u.user_id = l.user_id 
                LEFT JOIN payment p ON l.loan_id = p.loan_id
                WHERE u.user_id = '$user_id'
                GROUP BY u.user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo "<script>alert('User not found.')</script>";
    echo "<script>window.location.href='admin_users.php';</script>";
    exit();
}

$user_data = mysqli_fetch_assoc($check_result);

// Check if user has active loans (approved loans with remaining balance)
$total_borrowed = $user_data['total_borrowed'] ?: 0;
$total_paid = $user_data['total_paid'] ?: 0;
$remaining_amount = $total_borrowed - $total_paid;
$active_loans = $user_data['active_loans'] ?: 0;

// Safety checks before deletion
if ($active_loans > 0 && $remaining_amount > 0) {
    echo "<script>
        alert('❌ Cannot delete user with active loans!\\n\\nUser: " . addslashes($user_data['user_name']) . "\\nActive Loans: " . $active_loans . "\\nRemaining Amount: Rs." . number_format($remaining_amount, 2) . "\\n\\nPlease ensure all loans are fully paid before deletion.');
    </script>";
    echo "<script>window.location.href='admin_users.php';</script>";
    exit();
}

// Check if user has pending loans
if ($user_data['total_loans'] > 0) {
    $pending_query = "SELECT COUNT(*) as pending_count FROM loan WHERE user_id = '$user_id' AND status = 'pending'";
    $pending_result = mysqli_query($conn, $pending_query);
    $pending_data = mysqli_fetch_assoc($pending_result);
    
    if ($pending_data['pending_count'] > 0) {
        echo "<script>
            alert('❌ Cannot delete user with pending loan requests!\\n\\nUser: " . addslashes($user_data['user_name']) . "\\nPending Requests: " . $pending_data['pending_count'] . "\\n\\nPlease approve or reject all pending requests first.');
        </script>";
        echo "<script>window.location.href='admin_users.php';</script>";
        exit();
    }
}

// Start transaction for safe deletion
mysqli_begin_transaction($conn);

try {
    // Delete related records in order (foreign key constraints)
    
    // 1. Delete payments first
    $delete_payments = "DELETE FROM payment WHERE user_id = '$user_id'";
    $payments_result = mysqli_query($conn, $delete_payments);
    
    if (!$payments_result) {
        throw new Exception("Failed to delete payments: " . mysqli_error($conn));
    }
    
    // 2. Delete loans
    $delete_loans = "DELETE FROM loan WHERE user_id = '$user_id'";
    $loans_result = mysqli_query($conn, $delete_loans);
    
    if (!$loans_result) {
        throw new Exception("Failed to delete loans: " . mysqli_error($conn));
    }
    
    // 3. Finally delete the user
    $delete_user = "DELETE FROM user WHERE user_id = '$user_id'";
    $user_result = mysqli_query($conn, $delete_user);
    
    if (!$user_result) {
        throw new Exception("Failed to delete user: " . mysqli_error($conn));
    }
    
    // If all operations successful, commit transaction
    mysqli_commit($conn);
    
    // Success message
    echo "<script>
        alert('✅ User deleted successfully!\\n\\nUser: " . addslashes($user_data['user_name']) . "\\nEmail: " . $user_data['email'] . "\\n\\nAll associated data has been removed:\\n• " . mysqli_affected_rows($conn) . " loans deleted\\n• All payments deleted\\n• User account deleted');
    </script>";
    echo "<script>window.location.href='admin_users.php';</script>";
    
} catch (Exception $e) {
    // If any operation fails, rollback transaction
    mysqli_rollback($conn);
    
    echo "<script>
        alert('❌ Failed to delete user. Please try again.\\n\\nError: " . addslashes($e->getMessage()) . "\\n\\nNo data has been modified.');
    </script>";
    echo "<script>window.location.href='admin_users.php';</script>";
}

mysqli_close($conn);
?>