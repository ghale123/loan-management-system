<?php
session_start();
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $loan_id = isset($_POST['loan_id']) ? trim($_POST['loan_id']) : '';
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
    $bill_no = isset($_POST['bill_no']) ? trim($_POST['bill_no']) : '';
    
    // Validation
    $errors = [];
    
    // Validate loan ID
    if (empty($loan_id)) {
        $errors[] = 'Loan ID is required';
    } elseif (!is_numeric($loan_id) || $loan_id <= 0) {
        $errors[] = 'Invalid loan ID';
    }
    
    // Validate payment amount
    if (empty($amount)) {
        $errors[] = 'Payment amount is required';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid payment amount (must be greater than 0)';
    }
    
    // Validate bill number
    if (empty($bill_no)) {
        $errors[] = 'Bill number is required';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit();
    }
    
    // Verify that the loan belongs to the current user and is approved
    $loan_query = "SELECT * FROM loan WHERE loan_id = ? AND user_id = ? AND status = 'approved'";
    $stmt_loan = $conn->prepare($loan_query);
    $stmt_loan->bind_param("ii", $loan_id, $user_id);
    $stmt_loan->execute();
    $loan_result = $stmt_loan->get_result();
    
    if ($loan_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid loan or loan not approved for payment']);
        exit();
    }
    
    $loan_data = $loan_result->fetch_assoc();
    $remaining_loan = (float) str_replace(',', '', $loan_data['remaining_loan']);
    $loan_amount = (float) str_replace(',', '', $loan_data['loan_amount']);
    $loan_plan = $loan_data['loan_plan'];
    $loan_type = $loan_data['loan_type'];
    $payment_amount = (float) $amount;
    
    // Check if payment amount is greater than remaining loan
    if ($payment_amount > $remaining_loan) {
        echo json_encode(['success' => false, 'message' => 'Payment amount cannot be greater than remaining loan amount (Rs. ' . number_format($remaining_loan, 2) . ')']);
        exit();
    }
    
    // Verify loan plan exists in database (optional validation)
    $plan_query = "SELECT * FROM loan_plans WHERE loan_type = ? AND loan_plan = ? AND is_active = 1";
    $stmt_plan = $conn->prepare($plan_query);
    $stmt_plan->bind_param("ss", $loan_type, $loan_plan);
    $stmt_plan->execute();
    $plan_result = $stmt_plan->get_result();
    
    if ($plan_result->num_rows === 0) {
        // Log warning but don't block payment - plan might have been deactivated
        error_log("Warning: Loan plan not found in active plans - Type: $loan_type, Plan: $loan_plan");
    }
    
    // Calculate new remaining loan amount
    $new_remaining_loan = $remaining_loan - $amount;
    $new_remaining_loan = max(0, $new_remaining_loan); // Ensure it doesn't go below 0
    
    // Check if this payment completes the loan
    $is_loan_completed = ($new_remaining_loan == 0);
    
    // Update loan remaining amount
    try {
        $update_loan = "UPDATE loan SET remaining_loan = ? WHERE loan_id = ?";
        $stmt_update = $conn->prepare($update_loan);
        $new_remaining_str = number_format($new_remaining_loan, 2);
        $stmt_update->bind_param("si", $new_remaining_str, $loan_id);
        
        if (!$stmt_update->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update loan amount. Please try again.']);
            exit();
        }
        
        // Insert payment record
        $payment_query = "INSERT INTO payment (user_id, loan_id, amount, bill_no, date) VALUES (?, ?, ?, ?, NOW())";
        $stmt_payment = $conn->prepare($payment_query);
        $stmt_payment->bind_param("iids", $user_id, $loan_id, $amount, $bill_no);
        
        if ($stmt_payment->execute()) {
            $payment_id = $conn->insert_id;
            
            // Prepare success response
            $response = [
                'success' => true,
                'message' => $is_loan_completed ? 
                    '🎉 Congratulations! Your loan has been fully paid off!' : 
                    '✅ Payment processed successfully!',
                'payment_details' => [
                    'payment_id' => $payment_id,
                    'bill_no' => $bill_no,
                    'loan_id' => $loan_id,
                    'amount_paid' => $amount,
                    'previous_remaining' => $remaining_loan,
                    'new_remaining' => $new_remaining_loan,
                    'is_completed' => $is_loan_completed,
                    'payment_date' => date('Y-m-d H:i:s')
                ]
            ];
            
            echo json_encode($response);
        } else {
            // Rollback loan update if payment insertion fails
            $rollback_query = "UPDATE loan SET remaining_loan = ? WHERE loan_id = ?";
            $stmt_rollback = $conn->prepare($rollback_query);
            $remaining_str = number_format($remaining_loan, 2);
            $stmt_rollback->bind_param("si", $remaining_str, $loan_id);
            $stmt_rollback->execute();
            
            echo json_encode(['success' => false, 'message' => 'Failed to record payment. Please try again.']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} 
// else {
//     // Invalid request method
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
// }
?>