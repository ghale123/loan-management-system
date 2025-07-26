<?php
include_once('admin-header.php');
?>

<!-- Admin Reports Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Analytics & Reports</h1>
        <p class="text-gray-600">Comprehensive insights and analytics for your loan management system</p>
    </div>

    <?php
    // Get comprehensive statistics
    $overall_stats = "SELECT 
                        (SELECT COUNT(*) FROM user) as total_users,
                        (SELECT COUNT(*) FROM loan) as total_loans,
                        (SELECT COUNT(*) FROM loan WHERE status = 'approved') as approved_loans,
                        (SELECT COUNT(*) FROM loan WHERE status = 'pending') as pending_loans,
                        (SELECT COUNT(*) FROM loan WHERE status = 'rejected') as rejected_loans,
                        (SELECT SUM(CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2))) FROM loan WHERE status = 'approved') as total_disbursed,
                        (SELECT SUM(amount) FROM payment) as total_received,
                        (SELECT COUNT(*) FROM payment) as total_payments";
    $overall_result = mysqli_query($conn, $overall_stats);
    $overall = mysqli_fetch_assoc($overall_result);

    // Get loan type distribution
    $loan_types = "SELECT loan_type, COUNT(*) as count, SUM(CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2))) as total_amount 
                   FROM loan 
                   WHERE status = 'approved' 
                   GROUP BY loan_type";
    $loan_types_result = mysqli_query($conn, $loan_types);

    // Get monthly loan applications
    $monthly_loans = "SELECT 
                        DATE_FORMAT(start_date, '%Y-%m') as month,
                        COUNT(*) as applications,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                      FROM loan 
                      GROUP BY DATE_FORMAT(start_date, '%Y-%m') 
                      ORDER BY month DESC 
                      LIMIT 12";
    $monthly_loans_result = mysqli_query($conn, $monthly_loans);

    // Get payment trends
    $payment_trends = "SELECT 
                        DATE_FORMAT(date, '%Y-%m') as month,
                        SUM(amount) as total_payments,
                        COUNT(*) as payment_count
                      FROM payment 
                      GROUP BY DATE_FORMAT(date, '%Y-%m') 
                      ORDER BY month DESC 
                      LIMIT 12";
    $payment_trends_result = mysqli_query($conn, $payment_trends);

    // Calculate recovery rate
    $recovery_rate = $overall['total_disbursed'] > 0 ? ($overall['total_received'] / $overall['total_disbursed']) * 100 : 0;
    ?>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-800 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo $overall['total_users']; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-800 mb-1">Total Loans</p>
                    <p class="text-3xl font-bold text-green-900"><?php echo $overall['total_loans']; ?></p>
                    <p class="text-xs text-green-700 mt-1">
                        <?php echo $overall['approved_loans']; ?> Approved • 
                        <?php echo $overall['pending_loans']; ?> Pending
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-landmark text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-800 mb-1">Amount Disbursed</p>
                    <p class="text-2xl font-bold text-purple-900">Rs.<?php echo number_format($overall['total_disbursed'], 2); ?></p>
                    <p class="text-xs text-purple-700 mt-1">
                        Received: Rs.<?php echo number_format($overall['total_received'], 2); ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-money-bills text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-800 mb-1">Recovery Rate</p>
                    <p class="text-3xl font-bold text-orange-900"><?php echo round($recovery_rate, 1); ?>%</p>
                    <p class="text-xs text-orange-700 mt-1">
                        <?php echo $overall['total_payments']; ?> Payments Made
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Loan Applications Trend -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Applications Trend</h3>
            <canvas id="loanTrendChart" width="400" height="200"></canvas>
        </div>

        <!-- Payment Trends -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Collection Trends</h3>
            <canvas id="paymentTrendChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Loan Type Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Type Distribution</h3>
            <canvas id="loanTypeChart" width="400" height="200"></canvas>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Status Overview</h3>
            <canvas id="loanStatusChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Top Performers -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Loan Recipients</h3>
            <div class="space-y-3">
                <?php
                $top_recipients = "SELECT u.user_name, SUM(CAST(REPLACE(l.loan_amount, ',', '') AS DECIMAL(10,2))) as total_borrowed, COUNT(l.loan_id) as loan_count
                                  FROM loan l 
                                  JOIN user u ON l.user_id = u.user_id 
                                  WHERE l.status = 'approved' 
                                  GROUP BY l.user_id 
                                  ORDER BY total_borrowed DESC 
                                  LIMIT 5";
                $top_result = mysqli_query($conn, $top_recipients);
                $rank = 1;
                while ($recipient = mysqli_fetch_assoc($top_result)):
                ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">
                            <?php echo $rank; ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo $recipient['user_name']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $recipient['loan_count']; ?> loans</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">Rs.<?php echo number_format($recipient['total_borrowed'], 2); ?></p>
                    </div>
                </div>
                <?php 
                $rank++;
                endwhile; 
                ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
            <div class="space-y-3">
                <?php
                $recent_activity = "SELECT 'loan' as type, l.loan_id, u.user_name, l.loan_amount, l.status, l.start_date
                                   FROM loan l 
                                   JOIN user u ON l.user_id = u.user_id 
                                   UNION ALL
                                   SELECT 'payment' as type, p.payment_id, u.user_name, p.amount, 'completed' as status, p.date
                                   FROM payment p 
                                   JOIN user u ON p.user_id = u.user_id 
                                   ORDER BY start_date DESC 
                                   LIMIT 5";
                $activity_result = mysqli_query($conn, $recent_activity);
                while ($activity = mysqli_fetch_assoc($activity_result)):
                ?>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 <?php echo $activity['type'] == 'loan' ? 'bg-blue-100' : 'bg-green-100'; ?> rounded-full flex items-center justify-center mr-3">
                        <i class="fa-solid <?php echo $activity['type'] == 'loan' ? 'fa-landmark' : 'fa-money-bills'; ?> <?php echo $activity['type'] == 'loan' ? 'text-blue-600' : 'text-green-600'; ?> text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900"><?php echo $activity['user_name']; ?></p>
                        <p class="text-xs text-gray-500">
                            <?php echo ucfirst($activity['type']); ?> • 
                            Rs.<?php echo number_format((float)str_replace(',', '', $activity['loan_amount'] ?: $activity['amount']), 2); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            <?php echo $activity['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                   ($activity['status'] == 'pending' ? 'bg-orange-100 text-orange-800' : 
                                   ($activity['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')); ?>">
                            <?php echo ucfirst($activity['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Performance Metrics</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Approval Rate</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?php echo $overall['total_loans'] > 0 ? round(($overall['approved_loans'] / $overall['total_loans']) * 100, 1) : 0; ?>%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $overall['total_loans'] > 0 ? ($overall['approved_loans'] / $overall['total_loans']) * 100 : 0; ?>%"></div>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Average Loan Amount</span>
                    <span class="text-sm font-semibold text-gray-900">
                        Rs.<?php echo $overall['total_loans'] > 0 ? number_format($overall['total_disbursed'] / $overall['approved_loans'], 2) : 0; ?>
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Average Payment</span>
                    <span class="text-sm font-semibold text-gray-900">
                        Rs.<?php echo $overall['total_payments'] > 0 ? number_format($overall['total_received'] / $overall['total_payments'], 2) : 0; ?>
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Active Users</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?php echo $overall['approved_loans']; ?> / <?php echo $overall['total_users']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <!-- <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Export Reports</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="exportReport('loans')" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fa-solid fa-download mr-2"></i>
                Export Loans Report
            </button>
            <button onclick="exportReport('payments')" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fa-solid fa-download mr-2"></i>
                Export Payments Report
            </button>
            <button onclick="exportReport('users')" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fa-solid fa-download mr-2"></i>
                Export Users Report
            </button>
        </div>
    </div> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Loan Applications Trend Chart
    const loanTrendCtx = document.getElementById('loanTrendChart').getContext('2d');
    new Chart(loanTrendCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                mysqli_data_seek($monthly_loans_result, 0);
                $labels = [];
                $applications = [];
                $approved = [];
                $pending = [];
                while ($row = mysqli_fetch_assoc($monthly_loans_result)) {
                    $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    $applications[] = $row['applications'];
                    $approved[] = $row['approved'];
                    $pending[] = $row['pending'];
                }
                echo implode(', ', array_reverse($labels));
                ?>
            ],
            datasets: [{
                label: 'Applications',
                data: [<?php echo implode(', ', array_reverse($applications)); ?>],
                borderColor: '#1c5f5f',
                backgroundColor: 'rgba(28, 95, 95, 0.1)',
                tension: 0.4
            }, {
                label: 'Approved',
                data: [<?php echo implode(', ', array_reverse($approved)); ?>],
                borderColor: '#59b76e',
                backgroundColor: 'rgba(89, 183, 110, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Payment Trends Chart
    const paymentTrendCtx = document.getElementById('paymentTrendChart').getContext('2d');
    new Chart(paymentTrendCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                mysqli_data_seek($payment_trends_result, 0);
                $payment_labels = [];
                $payment_amounts = [];
                while ($row = mysqli_fetch_assoc($payment_trends_result)) {
                    $payment_labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    $payment_amounts[] = $row['total_payments'];
                }
                echo implode(', ', array_reverse($payment_labels));
                ?>
            ],
            datasets: [{
                label: 'Payment Amount (Rs.)',
                data: [<?php echo implode(', ', array_reverse($payment_amounts)); ?>],
                backgroundColor: 'rgba(89, 183, 110, 0.8)',
                borderColor: '#59b76e',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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

    // Loan Type Distribution Chart
    const loanTypeCtx = document.getElementById('loanTypeChart').getContext('2d');
    new Chart(loanTypeCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                mysqli_data_seek($loan_types_result, 0);
                $type_labels = [];
                $type_counts = [];
                while ($row = mysqli_fetch_assoc($loan_types_result)) {
                    $type_labels[] = "'" . $row['loan_type'] . "'";
                    $type_counts[] = $row['count'];
                }
                echo implode(', ', $type_labels);
                ?>
            ],
            datasets: [{
                data: [<?php echo implode(', ', $type_counts); ?>],
                backgroundColor: [
                    '#1c5f5f',
                    '#59b76e',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Loan Status Chart
    const loanStatusCtx = document.getElementById('loanStatusChart').getContext('2d');
    new Chart(loanStatusCtx, {
        type: 'pie',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [<?php echo $overall['approved_loans']; ?>, <?php echo $overall['pending_loans']; ?>, <?php echo $overall['rejected_loans']; ?>],
                backgroundColor: ['#59b76e', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    function exportReport(type) {
        // Implement export functionality
        alert(`${type.charAt(0).toUpperCase() + type.slice(1)} report export functionality will be implemented here.`);
    }
</script> 