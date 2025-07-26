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
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
    $loan_plan = isset($_POST['loan_plan']) ? trim($_POST['loan_plan']) : '';
    $loan_type = isset($_POST['loan_type']) ? trim($_POST['loan_type']) : '';
    $plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
    
    // Validation
    $errors = [];
    
    // Validate loan amount
    if (empty($amount)) {
        $errors[] = 'Loan amount is required';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid loan amount (must be greater than 0)';
    }
    
    // Validate plan_id and get plan details from database
    if ($plan_id <= 0) {
        $errors[] = 'Please select a valid loan plan';
    } else {
        // Get plan details from database
        $plan_query = "SELECT * FROM loan_plans WHERE plan_id = ? AND is_active = 1";
        $stmt_plan = $conn->prepare($plan_query);
        $stmt_plan->bind_param("i", $plan_id);
        $stmt_plan->execute();
        $plan_result = $stmt_plan->get_result();
        
        if ($plan_result->num_rows === 0) {
            $errors[] = 'Selected loan plan is not available or inactive';
        } else {
            $plan_data = $plan_result->fetch_assoc();
            
            // Validate amount against plan limits
            if ($amount < $plan_data['min_amount']) {
                $errors[] = 'Loan amount is below the minimum required (Rs.' . number_format($plan_data['min_amount']) . ')';
            } elseif ($amount > $plan_data['max_amount']) {
                $errors[] = 'Loan amount exceeds the maximum allowed (Rs.' . number_format($plan_data['max_amount']) . ')';
            }
            
            // Validate loan plan and type match
            if ($loan_plan !== $plan_data['loan_plan']) {
                $errors[] = 'Invalid loan plan selected';
            }
            if ($loan_type !== $plan_data['loan_type']) {
                $errors[] = 'Invalid loan type selected';
            }
        }
        $stmt_plan->close();
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit();
    }
    
    // Get plan details again for calculations
    $plan_query = "SELECT * FROM loan_plans WHERE plan_id = ?";
    $stmt_plan = $conn->prepare($plan_query);
    $stmt_plan->bind_param("i", $plan_id);
    $stmt_plan->execute();
    $plan_result = $stmt_plan->get_result();
    $plan_data = $plan_result->fetch_assoc();
    $stmt_plan->close();
    
    // Calculate interest based on plan from database
    $interest_rate = (float)str_replace('%', '', $plan_data['interest_rate']);
    $interest_amount = ($amount * $interest_rate) / 100;
    $total_loan_amount = $amount + $interest_amount;
    
    // Calculate monthly installment
    $months = $plan_data['duration_months'];
    $monthly_installment = $total_loan_amount / $months;
    
    // Check if user already has pending loans
    $check_pending = "SELECT COUNT(*) as pending_count FROM loan WHERE user_id = ? AND status = 'pending'";
    $stmt_check = $conn->prepare($check_pending);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $pending_count = $result_check->fetch_assoc()['pending_count'];
    $stmt_check->close();
    
    if ($pending_count > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending loan application. Please wait for approval.']);
        exit();
    }
    
    // Convert amounts to strings for varchar fields
    $total_loan_amount_str = number_format($total_loan_amount, 2);
    $remaining_loan_str = number_format($total_loan_amount, 2);
    
    // Insert loan application into database
    try {
        $stmt = $conn->prepare("INSERT INTO loan (user_id, loan_amount, loan_plan, loan_type, status, remaining_loan) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("issss", $user_id, $total_loan_amount_str, $loan_plan, $loan_type, $remaining_loan_str);
        
        if ($stmt->execute()) {
            $loan_id = $conn->insert_id;
            
            // Prepare success response with loan details
            $response = [
                'success' => true,
                'message' => 'Loan application submitted successfully!',
                'loan_details' => [
                    'loan_id' => $loan_id,
                    'amount_requested' => $amount,
                    'total_amount' => $total_loan_amount,
                    'interest_amount' => $interest_amount,
                    'interest_rate' => $plan_data['interest_rate'],
                    'loan_plan' => $loan_plan,
                    'loan_type' => $loan_type,
                    'monthly_installment' => round($monthly_installment, 2),
                    'duration_months' => $months,
                    'penalty_rate' => $plan_data['penalty_rate'],
                    'status' => 'pending'
                ]
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit loan application. Please try again.']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 