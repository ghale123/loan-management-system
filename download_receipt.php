<?php
include("connection.php");

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<script>alert('Access denied. Please login as admin.')</script>";
    echo "<script>window.location.href='admin_login.php';</script>";
    exit();
}

// Check if payment_id is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    echo "<script>alert('Payment ID is required.')</script>";
    echo "<script>window.location.href='admin_payments.php';</script>";
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
    echo "<script>alert('Payment not found.')</script>";
    echo "<script>window.location.href='admin_payments.php';</script>";
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

// Get payment position
$payment_position_query = "SELECT COUNT(*) as position 
                           FROM payment 
                           WHERE loan_id = '" . $payment_data['loan_id'] . "' 
                           AND date <= '" . $payment_data['date'] . "'";
$payment_position_result = mysqli_query($conn, $payment_position_query);
$payment_position_data = mysqli_fetch_assoc($payment_position_result);

// Generate HTML receipt
$receipt_html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1c5f5f 0%, #59b76e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .receipt-number {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #1c5f5f;
        }
        .receipt-number h2 {
            margin: 0 0 10px 0;
            color: #1c5f5f;
            font-size: 20px;
        }
        .receipt-number p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .amount-section {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px solid #59b76e;
        }
        .amount-section h3 {
            margin: 0 0 10px 0;
            color: #1c5f5f;
            font-size: 18px;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #59b76e;
            margin: 10px 0;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .detail-section h4 {
            margin: 0 0 15px 0;
            color: #1c5f5f;
            font-size: 16px;
            border-bottom: 2px solid #1c5f5f;
            padding-bottom: 5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .loan-progress {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .loan-progress h4 {
            margin: 0 0 15px 0;
            color: #1c5f5f;
            font-size: 16px;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            background: linear-gradient(90deg, #59b76e 0%, #1c5f5f 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .stamp {
            position: absolute;
            top: 50%;
            right: 50px;
            transform: translateY(-50%);
            width: 80px;
            height: 80px;
            border: 3px solid #59b76e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #59b76e;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
        }
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h1>PAYMENT RECEIPT</h1>
            <p>Loan Management System</p>
        </div>
        
        <div class="content">
            <div class="receipt-number">
                <h2>Receipt Details</h2>
                <p><strong>Receipt No:</strong> ' . $payment_data['payment_id'] . '</p>
                <p><strong>Bill No:</strong> ' . $payment_data['bill_no'] . '</p>
                <p><strong>Date:</strong> ' . date('F d, Y', strtotime($payment_data['date'])) . '</p>
                <p><strong>Time:</strong> ' . date('h:i A', strtotime($payment_data['date'])) . '</p>
            </div>
            
            <div class="amount-section">
                <h3>Payment Amount</h3>
                <div class="amount">Rs.' . number_format($payment_data['amount'], 2) . '</div>
                <p style="margin: 0; color: #666; font-size: 14px;">Payment Position: ' . $payment_position_data['position'] . ' of ' . $loan_payments_data['payment_count'] . ' payments</p>
            </div>
            
            <div class="details-grid">
                <div class="detail-section">
                    <h4>Payer Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">' . $payment_data['user_name'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">' . $payment_data['email'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">PAN:</span>
                        <span class="detail-value">' . $payment_data['pan_no'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Profession:</span>
                        <span class="detail-value">' . $payment_data['profession'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">' . $payment_data['address'] . '</span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Loan Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Loan ID:</span>
                        <span class="detail-value">' . $payment_data['loan_id'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Loan Type:</span>
                        <span class="detail-value">' . $payment_data['loan_type'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Loan Plan:</span>
                        <span class="detail-value">' . $payment_data['loan_plan'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Loan Amount:</span>
                        <span class="detail-value">Rs.' . number_format($loan_amount, 2) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Loan Status:</span>
                        <span class="detail-value">' . ucfirst($payment_data['loan_status']) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="loan-progress">
                <h4>Loan Progress</h4>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ' . $payment_percentage . '%"></div>
                </div>
                <div class="progress-text">
                    <span>Total Paid: Rs.' . number_format($total_paid, 2) . '</span>
                    <span>Remaining: Rs.' . number_format($remaining_amount, 2) . '</span>
                </div>
                <div class="progress-text">
                    <span>Progress: ' . round($payment_percentage, 1) . '%</span>
                    <span>Payments: ' . $loan_payments_data['payment_count'] . '</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>This is a computer generated receipt and does not require a signature.</strong></p>
            <p>For any queries, please contact our support team.</p>
            <p>Generated on: ' . date('F d, Y \a\t h:i A') . '</p>
        </div>
        
        <div class="stamp">
            PAID<br>✓
        </div>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: text/html');
header('Content-Disposition: inline; filename="receipt_' . $payment_data['payment_id'] . '.html"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output the receipt HTML
echo $receipt_html;
?> 