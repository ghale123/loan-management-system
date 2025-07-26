<?php
include_once('header.php');
?>

<!-- dashboard -->
<div class="dashboard md:ml-80 pt-20 p-6">
    <?php
    // Get user's loan statistics
    $user_stats_query = "SELECT 
        COUNT(*) as total_loans,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_loans,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_loans,
        SUM(CASE WHEN status = 'approved' THEN CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2)) ELSE 0 END) as total_borrowed,
        AVG(CASE WHEN status = 'approved' THEN CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2)) ELSE NULL END) as avg_loan_amount
        FROM loan WHERE user_id = '$user_id'";
    $user_stats_result = mysqli_query($conn, $user_stats_query);
    $user_stats = mysqli_fetch_assoc($user_stats_result);

    // Get payment statistics
    $payment_stats_query = "SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_paid_amount,
        AVG(amount) as avg_payment,
        MAX(date) as last_payment_date
        FROM payment p 
        JOIN loan l ON p.loan_id = l.loan_id 
        WHERE l.user_id = '$user_id' AND l.status = 'approved'";
    $payment_stats_result = mysqli_query($conn, $payment_stats_query);
    $payment_stats = mysqli_fetch_assoc($payment_stats_result);

    // Get recent loans
    $recent_loans_query = "SELECT * FROM loan WHERE user_id = '$user_id' ORDER BY start_date DESC LIMIT 5";
    $recent_loans_result = mysqli_query($conn, $recent_loans_query);

    // Get recent payments
    $recent_payments_query = "SELECT p.*, l.loan_type, l.loan_plan 
        FROM payment p 
        JOIN loan l ON p.loan_id = l.loan_id 
        WHERE l.user_id = '$user_id' AND l.status = 'approved'
        ORDER BY p.date DESC LIMIT 5";
    $recent_payments_result = mysqli_query($conn, $recent_payments_query);

    // Get monthly payment data for chart
    $monthly_payments_query = "SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        SUM(amount) as total_amount,
        COUNT(*) as payment_count
        FROM payment p 
        JOIN loan l ON p.loan_id = l.loan_id 
        WHERE l.user_id = '$user_id' AND l.status = 'approved'
        AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month";
    $monthly_payments_result = mysqli_query($conn, $monthly_payments_query);

    // Calculate remaining amount
    $total_paid = $payment_stats['total_paid_amount'] ? $payment_stats['total_paid_amount'] : 0;
    $remaining_amount = $user_stats['total_borrowed'] - $total_paid;
    $payment_progress = $user_stats['total_borrowed'] > 0 ? ($total_paid / $user_stats['total_borrowed']) * 100 : 0;
    ?>

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h1>
        <p class="text-gray-600">Welcome back! Here's your loan overview and analytics.</p>
    </div>

    <!-- Main Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Loans -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 font-medium text-sm uppercase tracking-wide">Total Loans</p>
                    <span class="text-3xl font-bold"><?php echo $user_stats['total_loans']; ?></span>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="text-xs bg-blue-400 px-2 py-1 rounded-full"><?php echo $user_stats['approved_loans']; ?> Approved</span>
                        <span class="text-xs bg-yellow-400 px-2 py-1 rounded-full"><?php echo $user_stats['pending_loans']; ?> Pending</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Borrowed -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 font-medium text-sm uppercase tracking-wide">Total Borrowed</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($user_stats['total_borrowed']); ?></span>
                    <div class="mt-2 text-sm text-green-100">
                        Avg: Rs.<?php echo number_format($user_stats['avg_loan_amount']); ?>
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Paid -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 font-medium text-sm uppercase tracking-wide">Total Paid</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($total_paid); ?></span>
                    <div class="mt-2 text-sm text-purple-100">
                        <?php echo $payment_stats['total_payments']; ?> payments
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-credit-card text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Remaining Amount -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 font-medium text-sm uppercase tracking-wide">Remaining</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($remaining_amount); ?></span>
                    <div class="mt-2 text-sm text-orange-100">
                        <?php echo round($payment_progress, 1); ?>% paid
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress and Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Payment Progress -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">Payment Progress</h3>
                <span class="text-sm text-gray-500">Overall Progress</span>
            </div>
            
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Payment Progress</span>
                    <span class="text-sm font-medium text-gray-700"><?php echo round($payment_progress, 1); ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" 
                         style="width: <?php echo $payment_progress; ?>%"></div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">Rs.<?php echo number_format($total_paid); ?></div>
                    <div class="text-sm text-gray-600">Total Paid</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">Rs.<?php echo number_format($remaining_amount); ?></div>
                    <div class="text-sm text-gray-600">Remaining</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $payment_stats['total_payments']; ?></div>
                    <div class="text-sm text-gray-600">Payments Made</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Quick Actions</h3>
            <div class="space-y-4">
                <a href="loan_apply.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fa-solid fa-plus text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">Apply for Loan</div>
                        <div class="text-sm text-gray-600">New loan application</div>
                    </div>
                </a>
                
                <a href="payments.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fa-solid fa-credit-card text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">Make Payment</div>
                        <div class="text-sm text-gray-600">Pay loan installments</div>
                    </div>
                </a>
                
                <a href="payment-details.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors duration-200">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fa-solid fa-history text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">Payment History</div>
                        <div class="text-sm text-gray-600">View all payments</div>
                    </div>
                </a>
                
                <a href="my-loan.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors duration-200">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fa-solid fa-file-invoice text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">My Loans</div>
                        <div class="text-sm text-gray-600">View loan details</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Payment Trends Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Payment Trends (Last 6 Months)</h3>
            <div class="h-64">
                <canvas id="paymentChart" width="400" height="150"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">Recent Activity</h3>
            <div class="space-y-4">
                <?php 
                $activity_count = 0;
                while ($payment = mysqli_fetch_assoc($recent_payments_result)): 
                    if ($activity_count >= 5) break;
                    $activity_count++;
                ?>
                <div class="flex items-center p-3 bg-green-50 rounded-lg">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fa-solid fa-credit-card text-white text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-800">Payment Made</div>
                        <div class="text-sm text-gray-600">Rs.<?php echo number_format($payment['amount']); ?> - <?php echo $payment['loan_type']; ?></div>
                    </div>
                    <div class="text-xs text-gray-500"><?php echo date('M d', strtotime($payment['date'])); ?></div>
                </div>
                <?php endwhile; ?>
                
                <?php 
                while ($loan = mysqli_fetch_assoc($recent_loans_result)): 
                    if ($activity_count >= 5) break;
                    $activity_count++;
                    $status_color = $loan['status'] == 'approved' ? 'green' : ($loan['status'] == 'pending' ? 'yellow' : 'red');
                    $status_icon = $loan['status'] == 'approved' ? 'fa-check' : ($loan['status'] == 'pending' ? 'fa-clock' : 'fa-times');
                ?>
                <div class="flex items-center p-3 bg-<?php echo $status_color; ?>-50 rounded-lg">
                    <div class="w-8 h-8 bg-<?php echo $status_color; ?>-500 rounded-full flex items-center justify-center mr-3">
                        <i class="fa-solid <?php echo $status_icon; ?> text-white text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-800">Loan <?php echo ucfirst($loan['status']); ?></div>
                        <div class="text-sm text-gray-600">Rs.<?php echo number_format((float)str_replace(',', '', $loan['loan_amount'])); ?> - <?php echo $loan['loan_type']; ?></div>
                    </div>
                    <div class="text-xs text-gray-500"><?php echo date('M d', strtotime($loan['start_date'])); ?></div>
                </div>
                <?php endwhile; ?>
                
                <?php if ($activity_count == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                    <p>No recent activity</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Loan Summary Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Loan Summary</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    mysqli_data_seek($recent_loans_result, 0);
                    while ($loan = mysqli_fetch_assoc($recent_loans_result)): 
                        // Get paid amount for this specific loan
                        $loan_paid_query = "SELECT SUM(amount) as paid_amount FROM payment WHERE loan_id = " . $loan['loan_id'];
                        $loan_paid_result = mysqli_query($conn, $loan_paid_query);
                        $loan_paid = mysqli_fetch_assoc($loan_paid_result);
                        $paid_amount = $loan_paid['paid_amount'] ? $loan_paid['paid_amount'] : 0;
                        
                        $loan_amount_float = (float)str_replace(',', '', $loan['loan_amount']);
                        $loan_progress = $loan_amount_float > 0 ? ($paid_amount / $loan_amount_float) * 100 : 0;
                        $status_colors = [
                            'approved' => 'bg-green-100 text-green-800',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'rejected' => 'bg-red-100 text-red-800'
                        ];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo $loan['loan_type']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $loan['loan_plan']; ?></div>
                                <div class="text-xs text-gray-400">Applied: <?php echo date('M d, Y', strtotime($loan['start_date'])); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">Rs.<?php echo number_format((float)str_replace(',', '', $loan['loan_amount'])); ?></div>
                            <div class="text-sm text-gray-500">Paid: Rs.<?php echo number_format($paid_amount); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$loan['status']]; ?>">
                                <?php echo ucfirst($loan['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $loan_progress; ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo round($loan_progress, 1); ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($loan['status'] == 'approved'): ?>
                            <a href="payments.php" class="text-blue-600 hover:text-blue-900 mr-3">Make Payment</a>
                            <?php endif; ?>
                            <a href="my-loan.php" class="text-gray-600 hover:text-gray-900">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Payment Trends Chart
const ctx = document.getElementById('paymentChart').getContext('2d');
const paymentData = <?php 
    $chart_data = [];
    mysqli_data_seek($monthly_payments_result, 0);
    while ($row = mysqli_fetch_assoc($monthly_payments_result)) {
        $chart_data[] = [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'amount' => $row['total_amount'],
            'count' => $row['payment_count']
        ];
    }
    echo json_encode($chart_data);
?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: paymentData.map(item => item.month),
        datasets: [{
            label: 'Payment Amount (Rs.)',
            data: paymentData.map(item => item.amount),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs.' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>