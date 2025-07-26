<?php
include_once('admin-header.php');
?>

<!-- Admin Payments Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Management</h1>
        <p class="text-gray-600">Track and manage all payment transactions</p>
    </div>

    <?php
    // Get all payments with user and loan details
    $payments_query = "SELECT p.*, u.user_name, u.email, l.loan_type, l.loan_plan, l.loan_amount as total_loan_amount
                       FROM payment p 
                       LEFT JOIN user u ON p.user_id = u.user_id 
                       LEFT JOIN loan l ON p.loan_id = l.loan_id 
                       ORDER BY p.date DESC";
    $payments_data = mysqli_query($conn, $payments_query);
    $total_payments = mysqli_num_rows($payments_data);

    // Calculate payment statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_payments,
                        SUM(amount) as total_amount,
                        AVG(amount) as avg_amount,
                        COUNT(DISTINCT user_id) as unique_payers,
                        COUNT(DISTINCT loan_id) as unique_loans
                    FROM payment";
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);

    // Get monthly payment data for chart
    $monthly_query = "SELECT 
                        DATE_FORMAT(date, '%Y-%m') as month,
                        SUM(amount) as monthly_total,
                        COUNT(*) as payment_count
                      FROM payment 
                      GROUP BY DATE_FORMAT(date, '%Y-%m') 
                      ORDER BY month DESC 
                      LIMIT 12";
    $monthly_result = mysqli_query($conn, $monthly_query);
    ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-money-bills text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Payments</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_payments']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-indian-rupee-sign text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Amount</p>
                    <p class="text-lg font-bold text-gray-900">Rs.<?php echo number_format($stats['total_amount'], 2); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-calculator text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Average Payment</p>
                    <p class="text-lg font-bold text-gray-900">Rs.<?php echo number_format($stats['avg_amount'], 2); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-users text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Unique Payers</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['unique_payers']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-landmark text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Loans</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['unique_loans']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Payment Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Payment Trends</h3>
        <canvas id="paymentChart" width="400" height="200"></canvas>
    </div>

    <!-- Filter and Search -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Payments</label>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search by user name, bill number, or amount..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Date</label>
                <input type="date" id="dateFilter" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Loan Type</label>
                <select id="loanTypeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="Business Loan">Business Loan</option>
                    <option value="Education Loan">Education Loan</option>
                    <option value="Small Business Loan">Small Business Loan</option>
                    <option value="Personal Loan">Personal Loan</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">All Payments</h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo $total_payments; ?> Payments
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Information</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="paymentsTableBody">
                    <?php while ($payment = mysqli_fetch_assoc($payments_data)): ?>
                    <tr class="hover:bg-gray-50 payment-row" 
                        data-date="<?php echo date('Y-m-d', strtotime($payment['date'])); ?>"
                        data-loan-type="<?php echo $payment['loan_type']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                    <?php echo strtoupper(substr($payment['user_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $payment['user_name']; ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $payment['user_id']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $payment['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">Payment ID: <?php echo $payment['payment_id']; ?></div>
                            <div class="text-sm text-gray-500">Bill No: <?php echo $payment['bill_no']; ?></div>
                            <div class="text-sm text-gray-500">Loan ID: <?php echo $payment['loan_id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $payment['loan_type']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $payment['loan_plan']; ?></div>
                            <div class="text-sm text-gray-500">Total: Rs.<?php echo number_format((float)str_replace(',', '', $payment['total_loan_amount']), 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-semibold text-gray-900">Rs.<?php echo number_format($payment['amount'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($payment['date'])); ?></div>
                            <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($payment['date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="viewPaymentDetails(<?php echo $payment['payment_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fa-solid fa-eye mr-1"></i>View
                                </button>
                                <button onclick="downloadReceipt(<?php echo $payment['payment_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-green-300 text-xs font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fa-solid fa-download mr-1"></i>Receipt
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div id="paymentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto scrollbar-hide">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Payment Details</h3>
                    <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="paymentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    
    /* Hide scrollbar for IE, Edge and Firefox */
    .scrollbar-hide {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Payment Chart
    const ctx = document.getElementById('paymentChart').getContext('2d');
    const paymentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                mysqli_data_seek($monthly_result, 0);
                $labels = [];
                $data = [];
                while ($row = mysqli_fetch_assoc($monthly_result)) {
                    $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    $data[] = $row['monthly_total'];
                }
                echo implode(', ', array_reverse($labels));
                ?>
            ],
            datasets: [{
                label: 'Monthly Payments (Rs.)',
                data: [<?php echo implode(', ', array_reverse($data)); ?>],
                borderColor: '#1c5f5f',
                backgroundColor: 'rgba(28, 95, 95, 0.1)',
                tension: 0.4,
                fill: true
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

    function viewPaymentDetails(paymentId) {
        // Show modal
        document.getElementById('paymentDetailsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Show loading state
        document.getElementById('paymentDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
                </div>
                <p class="text-gray-600">Loading payment details...</p>
            </div>
        `;
        
        // Fetch payment details via AJAX
        fetch(`get_payment_details.php?payment_id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPaymentDetails(data);
                } else {
                    document.getElementById('paymentDetailsContent').innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <p class="text-red-600">Error: ${data.error}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('paymentDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <p class="text-red-600">Failed to load payment details. Please try again.</p>
                    </div>
                `;
            });
    }

    function displayPaymentDetails(data) {
        const payment = data.payment;
        const loan = data.loan;
        const payer = data.payer;
        
        const loanStatusClass = loan.status === 'approved' ? 'bg-green-100 text-green-800' : 
                               loan.status === 'pending' ? 'bg-orange-100 text-orange-800' : 
                               'bg-red-100 text-red-800';

        document.getElementById('paymentDetailsContent').innerHTML = `
            <div class="space-y-6">
                <!-- Payment Header -->
                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900">Payment ID: ${payment.payment_id}</h4>
                            <p class="text-gray-600">Bill No: ${payment.bill_no}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-green-600">Rs.${payment.amount}</p>
                            <p class="text-sm text-gray-600">Payment Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Payment Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Payment Date</p>
                            <p class="text-sm text-gray-900">${new Date(payment.date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Payment Time</p>
                            <p class="text-sm text-gray-900">${new Date(payment.date).toLocaleTimeString()}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Payment Position</p>
                            <p class="text-sm text-gray-900">${payment.payment_position} of ${payment.payment_count} payments</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Bill Number</p>
                            <p class="text-sm text-gray-900">${payment.bill_no}</p>
                        </div>
                    </div>
                </div>

                <!-- Loan Information -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Associated Loan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">Rs.${loan.loan_amount}</p>
                            <p class="text-sm text-gray-600">Loan Amount</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">Rs.${loan.total_paid}</p>
                            <p class="text-sm text-gray-600">Total Paid</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-orange-600">Rs.${loan.remaining_amount}</p>
                            <p class="text-sm text-gray-600">Remaining</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Loan Progress</span>
                            <span>${loan.payment_percentage}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${loan.payment_percentage}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Loan ID: <span class="text-gray-900 font-semibold">${loan.loan_id}</span></p>
                            <p class="text-gray-600">Loan Type: <span class="text-gray-900 font-semibold">${loan.loan_type}</span></p>
                            <p class="text-gray-600">Loan Plan: <span class="text-gray-900 font-semibold">${loan.loan_plan}</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">Application Date: <span class="text-gray-900 font-semibold">${new Date(loan.start_date).toLocaleDateString()}</span></p>
                            <p class="text-gray-600">Loan Duration: <span class="text-gray-900 font-semibold">${loan.months_elapsed} months</span></p>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${loanStatusClass}">
                                ${loan.status.charAt(0).toUpperCase() + loan.status.slice(1)}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payer Information -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Payer Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Full Name</p>
                            <p class="text-sm text-gray-900">${payer.user_name}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Email</p>
                            <p class="text-sm text-gray-900">${payer.email}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Gender</p>
                            <p class="text-sm text-gray-900">${payer.gender}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Profession</p>
                            <p class="text-sm text-gray-900">${payer.profession}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">PAN Number</p>
                            <p class="text-sm text-gray-900">${payer.pan_no}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-600">Address</p>
                            <p class="text-sm text-gray-900">${payer.address}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fa-solid fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h5 class="text-sm font-semibold text-green-800">Payment Successful</h5>
                            <p class="text-sm text-green-600">This payment has been successfully processed and recorded in the system.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function downloadReceipt(paymentId) {
        // Open receipt in new window/tab
        window.open(`download_receipt.php?payment_id=${paymentId}`, '_blank');
    }

    function closePaymentModal() {
        document.getElementById('paymentDetailsModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.payment-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Date filter functionality
    document.getElementById('dateFilter').addEventListener('change', function() {
        const filterValue = this.value;
        const rows = document.querySelectorAll('.payment-row');
        
        rows.forEach(row => {
            const date = row.getAttribute('data-date');
            if (filterValue === '' || date === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Loan type filter functionality
    document.getElementById('loanTypeFilter').addEventListener('change', function() {
        const filterValue = this.value;
        const rows = document.querySelectorAll('.payment-row');
        
        rows.forEach(row => {
            const type = row.getAttribute('data-loan-type');
            if (filterValue === '' || type === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    document.getElementById('paymentDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentModal();
        }
    });
</script> 