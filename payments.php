<?php
include_once('header.php');

// Pagination settings
$cards_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $cards_per_page;

// Get user's payment statistics
$payment_stats_query = "SELECT 
    COUNT(*) as total_loans,
    SUM(CASE WHEN remaining_loan > 0 THEN 1 ELSE 0 END) as active_loans,
    SUM(CASE WHEN remaining_loan = 0 THEN 1 ELSE 0 END) as completed_loans,
    SUM(CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2))) as total_borrowed,
    SUM(CAST(REPLACE(remaining_loan, ',', '') AS DECIMAL(10,2))) as total_remaining
    FROM loan WHERE user_id = '$user_id' AND status = 'approved'";
$payment_stats_result = mysqli_query($conn, $payment_stats_query);
$payment_stats = mysqli_fetch_assoc($payment_stats_result);

// Get total number of approved loans for pagination
$total_loans_query = "SELECT COUNT(*) as total FROM loan WHERE user_id = '$user_id' AND status = 'approved'";
$total_loans_result = mysqli_query($conn, $total_loans_query);
$total_loans = mysqli_fetch_assoc($total_loans_result)['total'];
$total_pages = ceil($total_loans / $cards_per_page);
?>

<!-- payment -->
<div class="payment_container md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Loan Payments</h1>
        <p class="text-gray-600">Make payments for your approved loans and track your progress</p>
    </div>

    <!-- Payment Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Loans -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 font-medium text-sm uppercase tracking-wide">Total Loans</p>
                    <span class="text-3xl font-bold"><?php echo $payment_stats['total_loans']; ?></span>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="text-xs bg-blue-400 px-2 py-1 rounded-full"><?php echo $payment_stats['active_loans']; ?> Active</span>
                        <span class="text-xs bg-green-400 px-2 py-1 rounded-full"><?php echo $payment_stats['completed_loans']; ?> Paid</span>
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
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($payment_stats['total_borrowed']); ?></span>
                    <div class="mt-2 text-sm text-green-100">
                        Approved loans
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Remaining -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 font-medium text-sm uppercase tracking-wide">Total Remaining</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($payment_stats['total_remaining']); ?></span>
                    <div class="mt-2 text-sm text-orange-100">
                        Outstanding amount
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Payment Progress -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 font-medium text-sm uppercase tracking-wide">Payment Progress</p>
                    <?php 
                    $payment_progress = $payment_stats['total_borrowed'] > 0 ? 
                        (($payment_stats['total_borrowed'] - $payment_stats['total_remaining']) / $payment_stats['total_borrowed']) * 100 : 0;
                    ?>
                    <span class="text-2xl font-bold"><?php echo round($payment_progress, 1); ?>%</span>
                    <div class="mt-2 text-sm text-purple-100">
                        Overall progress
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-percentage text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans List -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Your Approved Loans</h3>
                <p class="text-sm text-gray-600 mt-1">Select a loan to make a payment</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-credit-card text-blue-600 text-xl"></i>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            // Get approved loans for the current user with pagination
            $loans_query = "SELECT * FROM loan WHERE user_id='$user_id' AND status='approved' ORDER BY loan_id DESC LIMIT $cards_per_page OFFSET $offset";
            $loans_data = mysqli_query($conn, $loans_query);
            $loans_on_page = mysqli_num_rows($loans_data);
            
            if ($loans_data && $loans_on_page > 0) {
                while ($loan = mysqli_fetch_assoc($loans_data)) {
                    $loan_amount = (float)str_replace(',', '', $loan['loan_amount']);
                    $remaining_loan = (float)str_replace(',', '', $loan['remaining_loan']);
                    $paid_amount = $loan_amount - $remaining_loan;
                    $payment_progress = $loan_amount > 0 ? ($paid_amount / $loan_amount) * 100 : 0;
                    
                    // Get loan plan details for monthly installment calculation
                    $plan_query = "SELECT * FROM loan_plans WHERE loan_type = '" . mysqli_real_escape_string($conn, $loan['loan_type']) . "' AND loan_plan = '" . mysqli_real_escape_string($conn, $loan['loan_plan']) . "' LIMIT 1";
                    $plan_result = mysqli_query($conn, $plan_query);
                    $plan_data = mysqli_fetch_assoc($plan_result);
                    
                    // Calculate monthly installment
                    $monthly_installment = 0;
                    $interest_rate = 0;
                    $duration_months = 0;
                    $penalty_rate = 0;
                    
                    if ($plan_data) {
                        $interest_rate = (float)str_replace('%', '', $plan_data['interest_rate']);
                        $duration_months = $plan_data['duration_months'];
                        $penalty_rate = (float)str_replace('%', '', $plan_data['penalty_rate']);
                        
                        // Calculate monthly installment based on total loan amount (principal + interest)
                        // The loan_amount stored in database already includes interest, so no need to add it again
                        $monthly_installment = $loan_amount / $duration_months;
                    }
                    
                    // Calculate penalty
                    $start_date = $loan['start_date'];
                    $start = new DateTime($start_date);
                    $now = new DateTime();
                    $interval = $start->diff($now);
                    $months_passed = ($interval->y * 12) + $interval->m;
                    if ($interval->d >= 1) {
                        $months_passed += 1;
                    }
                    
                    $loan_id = $loan['loan_id'];
                    $payment_query = "SELECT COUNT(*) as payment_count FROM payment WHERE loan_id = $loan_id";
                    $payment_data = mysqli_query($conn, $payment_query);
                    $payment_count = mysqli_fetch_assoc($payment_data)['payment_count'];
                    
                    // Calculate penalty based on monthly installment and penalty rate
                    if ($months_passed == $payment_count || $months_passed <= $payment_count) {
                        $penalty = "No Penalty";
                        $penalty_class = "text-green-600";
                    } else {
                        $penalty_month = $months_passed - $payment_count;
                        $penalty_amount = $monthly_installment * ($penalty_rate / 100) * $penalty_month;
                        $penalty = "Rs." . number_format($penalty_amount, 2);
                        $penalty_class = "text-red-600";
                    }
                    
                    // Loan type icons and colors
                    $loan_config = [
                        'Business Loan' => [
                            'icon' => 'fa-building',
                            'bg' => 'bg-gradient-to-br from-blue-500 to-blue-600',
                            'card_bg' => 'bg-gradient-to-br from-blue-50 to-blue-100',
                            'border' => 'border-blue-200',
                            'text' => 'text-blue-800'
                        ],
                        'Education Loan' => [
                            'icon' => 'fa-graduation-cap',
                            'bg' => 'bg-gradient-to-br from-emerald-500 to-emerald-600',
                            'card_bg' => 'bg-gradient-to-br from-emerald-50 to-emerald-100',
                            'border' => 'border-emerald-200',
                            'text' => 'text-emerald-800'
                        ],
                        'Small Business Loan' => [
                            'icon' => 'fa-store',
                            'bg' => 'bg-gradient-to-br from-purple-500 to-purple-600',
                            'card_bg' => 'bg-gradient-to-br from-purple-50 to-purple-100',
                            'border' => 'border-purple-200',
                            'text' => 'text-purple-800'
                        ],
                        'Personal Loan' => [
                            'icon' => 'fa-user',
                            'bg' => 'bg-gradient-to-br from-orange-500 to-orange-600',
                            'card_bg' => 'bg-gradient-to-br from-orange-50 to-orange-100',
                            'border' => 'border-orange-200',
                            'text' => 'text-orange-800'
                        ]
                    ];
                    
                    $config = $loan_config[$loan['loan_type']] ?? $loan_config['Personal Loan'];
                    $is_paid = $remaining_loan <= 0;
                    
                    ?>
                    
                    <div class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 <?php echo $config['card_bg']; ?> <?php echo $config['border']; ?> border-2">
                        <!-- Card Header -->
                        <div class="<?php echo $config['bg']; ?> p-4 text-white relative overflow-hidden">
                            <!-- Background pattern -->
                            <div class="absolute inset-0 opacity-10">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-white rounded-full -mr-10 -mt-10"></div>
                                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full -ml-8 -mb-8"></div>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid <?php echo $config['icon']; ?> text-white text-lg"></i>
                                    </div>
                                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full font-medium">
                                        #<?php echo $loan['loan_id']; ?>
                                    </span>
                                </div>
                                
                                <h4 class="text-lg font-bold mb-1"><?php echo $loan['loan_type']; ?></h4>
                                <p class="text-sm opacity-90"><?php echo $loan['loan_plan']; ?></p>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="p-4">
                            <!-- Amount Information -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                                    <div class="text-lg font-bold text-gray-800">Rs.<?php echo number_format($loan_amount); ?></div>
                                    <div class="text-xs text-gray-600">Total Amount</div>
                                </div>
                                <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                                    <div class="text-lg font-bold <?php echo $is_paid ? 'text-green-600' : 'text-orange-600'; ?>">
                                        Rs.<?php echo number_format($remaining_loan); ?>
                                    </div>
                                    <div class="text-xs text-gray-600">Remaining</div>
                                </div>
                            </div>
                            
                            <!-- Monthly Installment Info -->
                            <?php if ($monthly_installment > 0): ?>
                            <div class="mb-4 p-3 bg-white rounded-lg shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-calendar-check text-blue-600 mr-2"></i>
                                        <div>
                                            <div class="text-sm font-medium text-gray-700">Monthly Installment</div>
                                            <div class="text-xs text-gray-500">Based on remaining amount</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600">Rs.<?php echo number_format($monthly_installment, 2); ?></div>
                                        <div class="text-xs text-gray-500">Per month</div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Loan Plan Information -->
                            <div class="mb-4 p-3 bg-white rounded-lg shadow-sm">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-file-contract text-purple-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Loan Plan</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800"><?php echo $loan['loan_plan']; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-percentage text-green-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Interest Rate</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800"><?php echo $interest_rate; ?>%</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-calendar-days text-orange-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Duration</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800"><?php echo $duration_months; ?> months</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Penalty Information -->
                            <div class="mb-4 p-3 bg-white rounded-lg shadow-sm">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-exclamation-triangle text-red-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Penalty <?php echo $penalty_rate."%";?></span>
                                        </div>
                                        <span class="text-sm font-semibold <?php echo $penalty_class; ?>"><?php echo $penalty; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-calendar text-blue-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Months Passed</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800"><?php echo $months_passed; ?> months</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-credit-card text-green-600 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Payments Made</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800"><?php echo $payment_count; ?> payments</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Progress -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Payment Progress</span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo round($payment_progress, 1); ?>%</span>
                                </div>
                                <div class="w-full bg-white rounded-full h-2 shadow-inner">
                                    <div class="<?php echo $config['bg']; ?> h-2 rounded-full transition-all duration-500 ease-out" 
                                         style="width: <?php echo $payment_progress; ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>Paid: Rs.<?php echo number_format($paid_amount); ?></span>
                                    <span><?php echo $payment_progress > 0 ? 'In Progress' : 'Not Started'; ?></span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="mt-4 space-y-2">
                                <?php if (!$is_paid): ?>
                                    <!-- Create clean loan data for JavaScript -->
                                    <?php
                                    $clean_loan_data = [
                                        'loan_id' => $loan['loan_id'],
                                        'loan_type' => $loan['loan_type'],
                                        'loan_plan' => $loan['loan_plan'],
                                        'loan_amount' => $loan_amount, // Use cleaned numeric value
                                        'remaining_loan' => $remaining_loan // Use cleaned numeric value
                                    ];
                                    ?>
                                    
                                    <!-- Make Payment Button -->
                                    <button onclick="openPaymentModal(<?php echo htmlspecialchars(json_encode($clean_loan_data)); ?>, <?php echo $monthly_installment; ?>, '<?php echo $penalty; ?>', <?php echo $months_passed; ?>, <?php echo $payment_count; ?>, <?php echo $penalty_rate; ?>)" 
                                            class="w-full <?php echo $config['bg']; ?> text-white py-3 px-4 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg hover:shadow-xl">
                                        <i class="fa-solid fa-credit-card mr-2"></i>
                                        Make Payment
                                    </button>
                                    
                                    <!-- Pay Installment Button -->
                                    <?php if ($monthly_installment > 0 && $penalty == "No Penalty"): ?>
                                    <button onclick="openInstallmentModal(<?php echo htmlspecialchars(json_encode($clean_loan_data)); ?>, <?php echo $monthly_installment; ?>)" 
                                            class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2 px-4 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md hover:shadow-lg">
                                        <i class="fa-solid fa-calendar-check mr-2"></i>
                                        Pay Installment
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-4 rounded-lg font-semibold text-center shadow-lg">
                                        <i class="fa-solid fa-check-circle mr-2"></i>
                                        Loan Paid
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <?php if ($is_paid): ?>
                        <div class="absolute top-4 right-4">
                            <div class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow-lg">
                                <i class="fa-solid fa-check mr-1"></i>Paid
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-span-full">
                    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fa-solid fa-credit-card text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Approved Loans Found</h3>
                        <p class="text-gray-500 mb-6">You don't have any approved loans for payment yet.</p>
                        <a href="loan_apply.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                            <i class="fa-solid fa-plus mr-2"></i>Apply for Loan
                        </a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex items-center justify-between">
            <!-- Page Info -->
            <div class="text-sm text-gray-600">
                Showing <?php echo $offset + 1; ?> to <?php echo $offset + $loans_on_page; ?> of <?php echo $total_loans; ?> loans
            </div>
            
            <!-- Pagination Buttons -->
            <div class="flex items-center space-x-2">
                <!-- Previous Button -->
                <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>" 
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm font-medium">
                    <i class="fa-solid fa-chevron-left mr-2"></i>Previous
                </a>
                <?php else: ?>
                <span class="px-4 py-2 bg-gray-100 border border-gray-300 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left mr-2"></i>Previous
                </span>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <div class="flex items-center space-x-1">
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Show first page if not in range
                    if ($start_page > 1) {
                        echo '<a href="?page=1" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm font-medium">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="px-2 text-gray-400">...</span>';
                        }
                    }
                    
                    // Show page numbers in range
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $current_page) {
                            echo '<span class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . '" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm font-medium">' . $i . '</a>';
                        }
                    }
                    
                    // Show last page if not in range
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="px-2 text-gray-400">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . '" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm font-medium">' . $total_pages . '</a>';
                    }
                    ?>
                </div>
                
                <!-- Next Button -->
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>" 
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm font-medium">
                    Next<i class="fa-solid fa-chevron-right ml-2"></i>
                </a>
                <?php else: ?>
                <span class="px-4 py-2 bg-gray-100 border border-gray-300 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                    Next<i class="fa-solid fa-chevron-right ml-2"></i>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Make Payment</h3>
                    <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="paymentForm" action="payment.php" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bill No.:</label>
                                <input type="text" name="bill_no" id="bill_no" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan ID:</label>
                                <input type="text" name="loan_id" id="loan_id" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Type:</label>
                                <input type="text" name="loan_type" id="loan_type" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Amount:</label>
                                <input type="text" name="loan_amount" id="loan_amount" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Plan:</label>
                                <input type="text" name="loan_plan" id="loan_plan" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Loan:</label>
                                <input type="text" name="remaining_loan" id="remaining_loan" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            
                            <!-- Penalty Information Labels (shown when penalty exists) -->
                            <div id="penaltyLabels" class="hidden">
                                <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="font-medium text-red-700">Months Passed:</span>
                                            <span id="label_months_passed" class="text-red-800 font-semibold"></span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-red-700">Payments Made:</span>
                                            <span id="label_payment_count" class="text-red-800 font-semibold"></span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-red-700">Monthly Installment:</span>
                                            <span id="label_monthly_installment" class="text-red-800 font-semibold"></span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-red-700">Penalty Amount:</span>
                                            <span id="label_penalty_amount" class="text-red-800 font-semibold"></span>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-red-200">
                                        <span class="font-bold text-red-800">Total Amount (Installment + Penalty): </span>
                                        <span id="label_total_amount" class="text-red-900 font-bold text-sm"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Amount:</label>
                                <input type="number" name="amount" id="payment_amount" placeholder="Enter payment amount" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-center space-x-4">
                        <button type="submit" name="payment" id="submitPaymentBtn"
                                class="px-8 py-3 bg-success text-white rounded-lg hover:bg-success/90 transition-colors duration-200 font-medium">
                            Make Payment
                        </button>
                        <button type="button" onclick="closePaymentModal()" 
                                class="px-8 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                            Cancel
                        </button>
                    </div>
                    <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Installment Modal -->
<div id="installmentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Pay Installment</h3>
                    <button onclick="closeInstallmentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="installmentForm" action="payment.php" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bill No.:</label>
                                <input type="text" name="bill_no" id="installment_bill_no" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan ID:</label>
                                <input type="text" name="loan_id" id="installment_loan_id" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Type:</label>
                                <input type="text" name="loan_type" id="installment_loan_type" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Amount:</label>
                                <input type="text" name="loan_amount" id="installment_loan_amount" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Plan:</label>
                                <input type="text" name="loan_plan" id="installment_loan_plan" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Loan:</label>
                                <input type="text" name="remaining_loan" id="installment_remaining_loan" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Amount:</label>
                                <input type="number" name="amount" id="installment_payment_amount" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-center space-x-4">
                        <button type="submit" name="installment" id="submitInstallmentBtn"
                                class="px-8 py-3 bg-success text-white rounded-lg hover:bg-success/90 transition-colors duration-200 font-medium">
                            Pay Installment
                        </button>
                        <button type="button" onclick="closeInstallmentModal()" 
                                class="px-8 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                            Cancel
                        </button>
                    </div>
                    <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openPaymentModal(loanData, monthlyInstallment, penalty, monthsPassed, paymentCount, penaltyRate) {
    // Generate random bill number
    const billNo = Math.floor(1000 + Math.random() * 9000);
    
    // Populate form fields
    document.getElementById('bill_no').value = billNo;
    document.getElementById('loan_id').value = loanData.loan_id;
    document.getElementById('loan_type').value = loanData.loan_type;
    document.getElementById('loan_amount').value = loanData.loan_amount;
    document.getElementById('loan_plan').value = loanData.loan_plan;
    document.getElementById('remaining_loan').value = loanData.remaining_loan;
    
    // Store monthly installment for validation
    document.getElementById('paymentModal').setAttribute('data-monthly-installment', monthlyInstallment);
    
    // Show modal
    document.getElementById('paymentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Populate penalty information section if penalty exists
    const penaltyLabels = document.getElementById('penaltyLabels');
    if (penalty !== 'No Penalty') {
        // Populate penalty labels
        document.getElementById('label_months_passed').textContent = monthsPassed + ' months';
        document.getElementById('label_payment_count').textContent = paymentCount + ' payments';
        document.getElementById('label_monthly_installment').textContent = 'Rs.' + monthlyInstallment.toFixed(2);
        document.getElementById('label_penalty_amount').textContent = penalty;
        
        // Calculate total amount (monthly installment + penalty)
        const penaltyAmount = parseFloat(penalty.replace('Rs.', '').replace(/,/g, ''));
        const totalAmount = monthlyInstallment + penaltyAmount;
        document.getElementById('label_total_amount').textContent = 'Rs.' + totalAmount.toFixed(2);
        
        // Show penalty labels and pre-fill payment amount with readonly
        penaltyLabels.classList.remove('hidden');
        const paymentAmountField = document.getElementById('payment_amount');
        paymentAmountField.value = totalAmount.toFixed(2);
        paymentAmountField.readOnly = true;
        paymentAmountField.classList.add('bg-gray-50');
    } else {
        penaltyLabels.classList.add('hidden');
        const paymentAmountField = document.getElementById('payment_amount');
        paymentAmountField.readOnly = false;
        paymentAmountField.classList.remove('bg-gray-50');
    }
}

function openInstallmentModal(loanData, monthlyInstallment) {
    // Generate random bill number
    const billNo = Math.floor(1000 + Math.random() * 9000);
    
    // Populate form fields
    document.getElementById('installment_bill_no').value = billNo;
    document.getElementById('installment_loan_id').value = loanData.loan_id;
    document.getElementById('installment_loan_type').value = loanData.loan_type;
    document.getElementById('installment_loan_amount').value = loanData.loan_amount;
    document.getElementById('installment_loan_plan').value = loanData.loan_plan;
    document.getElementById('installment_remaining_loan').value = loanData.remaining_loan;
    document.getElementById('installment_payment_amount').value = monthlyInstallment.toFixed(2);
    
    // Show modal
    document.getElementById('installmentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('paymentForm').reset();
    
    // Remove any existing messages
    const existingMessage = document.querySelector('.payment-message');
    if (existingMessage) {
        existingMessage.remove();
    }
}

function closeInstallmentModal() {
    document.getElementById('installmentModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('installmentForm').reset();
    
    // Remove any existing messages
    const existingMessage = document.querySelector('.installment-message');
    if (existingMessage) {
        existingMessage.remove();
    }
}

// Handle payment form submission with monthly installment validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitPaymentBtn');
    const originalText = submitBtn.innerHTML;
    
    // Get monthly installment for validation
    const monthlyInstallment = parseFloat(document.getElementById('paymentModal').getAttribute('data-monthly-installment') || 0);
    const paymentAmount = parseFloat(formData.get('amount'));
    
    // Validate minimum payment amount
    if (monthlyInstallment > 0 && paymentAmount < monthlyInstallment) {
        // Show error message
        const messageDiv = document.createElement('div');
        messageDiv.className = 'payment-message p-4 rounded-lg mb-4 bg-red-50 border border-red-200 text-red-800';
        messageDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                <div>
                    <h4 class="font-semibold">Payment Amount Too Low</h4>
                    <p>Payment amount must be at least Rs. ${monthlyInstallment.toLocaleString('en-IN', {minimumFractionDigits: 2})} (monthly installment amount).</p>
                </div>
            </div>
        `;
        
        const form = document.getElementById('paymentForm');
        form.parentNode.insertBefore(messageDiv, form);
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
    submitBtn.disabled = true;
    
    // Remove any existing messages
    const existingMessage = document.querySelector('.payment-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    fetch('payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `payment-message p-4 rounded-lg mb-4 ${
            data.success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'
        }`;
        
        if (data.success) {
            messageDiv.innerHTML = `
                <div class="flex items-start">
                    <i class="fa-solid fa-check-circle text-green-600 mt-1 mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold mb-2 text-lg">${data.message}</h4>
                        <div class="text-sm space-y-1">
                            <p><strong>Payment ID:</strong> ${data.payment_details.payment_id}</p>
                            <p><strong>Bill No:</strong> ${data.payment_details.bill_no}</p>
                            <p><strong>Amount Paid:</strong> Rs. ${data.payment_details.amount_paid}</p>
                            <p><strong>Previous Remaining:</strong> Rs. ${data.payment_details.previous_remaining}</p>
                            <p><strong>New Remaining:</strong> Rs. ${data.payment_details.new_remaining}</p>
                            <p><strong>Payment Date:</strong> ${data.payment_details.payment_date}</p>
                            ${data.payment_details.is_completed ? '<p class="text-green-700 font-semibold">🎉 Loan Fully Paid!</p>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Reset form on success
            document.getElementById('paymentForm').reset();
            
            // Close modal after 4 seconds for completed loans, 3 seconds for regular payments
            setTimeout(() => {
                closePaymentModal();
                // Reload page to refresh loan list
                location.reload();
            }, data.payment_details.is_completed ? 4000 : 3000);
            
        } else {
            messageDiv.innerHTML = `
                <div class="flex items-start">
                    <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold">Payment Failed</h4>
                        <p>${data.message}</p>
                    </div>
                </div>
            `;
        }
        
        // Insert message before the form
        const form = document.getElementById('paymentForm');
        form.parentNode.insertBefore(messageDiv, form);
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .catch(error => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Show error message
        const messageDiv = document.createElement('div');
        messageDiv.className = 'payment-message p-4 rounded-lg mb-4 bg-red-50 border border-red-200 text-red-800';
        messageDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                <div>
                    <h4 class="font-semibold">Network Error</h4>
                    <p>Please check your internet connection and try again.</p>
                </div>
            </div>
        `;
        
        const form = document.getElementById('paymentForm');
        form.parentNode.insertBefore(messageDiv, form);
    });
});

// Handle installment form submission
document.getElementById('installmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitInstallmentBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
    submitBtn.disabled = true;
    
    // Remove any existing messages
    const existingMessage = document.querySelector('.installment-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    fetch('payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `installment-message p-4 rounded-lg mb-4 ${
            data.success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'
        }`;
        
        if (data.success) {
            messageDiv.innerHTML = `
                <div class="flex items-start">
                    <i class="fa-solid fa-check-circle text-green-600 mt-1 mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold mb-2 text-lg">${data.message}</h4>
                        <div class="text-sm space-y-1">
                            <p><strong>Payment ID:</strong> ${data.payment_details.payment_id}</p>
                            <p><strong>Bill No:</strong> ${data.payment_details.bill_no}</p>
                            <p><strong>Amount Paid:</strong> Rs. ${data.payment_details.amount_paid}</p>
                            <p><strong>Previous Remaining:</strong> Rs. ${data.payment_details.previous_remaining}</p>
                            <p><strong>New Remaining:</strong> Rs. ${data.payment_details.new_remaining}</p>
                            <p><strong>Payment Date:</strong> ${data.payment_details.payment_date}</p>
                            ${data.payment_details.is_completed ? '<p class="text-green-700 font-semibold">🎉 Loan Fully Paid!</p>' : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Reset form on success
            document.getElementById('installmentForm').reset();
            
            // Close modal after 4 seconds for completed loans, 3 seconds for regular payments
            setTimeout(() => {
                closeInstallmentModal();
                // Reload page to refresh loan list
                location.reload();
            }, data.payment_details.is_completed ? 4000 : 3000);
            
        } else {
            messageDiv.innerHTML = `
                <div class="flex items-start">
                    <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold">Payment Failed</h4>
                        <p>${data.message}</p>
                    </div>
                </div>
            `;
        }
        
        // Insert message before the form
        const form = document.getElementById('installmentForm');
        form.parentNode.insertBefore(messageDiv, form);
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .catch(error => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Show error message
        const messageDiv = document.createElement('div');
        messageDiv.className = 'installment-message p-4 rounded-lg mb-4 bg-red-50 border border-red-200 text-red-800';
        messageDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                <div>
                    <h4 class="font-semibold">Network Error</h4>
                    <p>Please check your internet connection and try again.</p>
                </div>
            </div>
        `;
        
        const form = document.getElementById('installmentForm');
        form.parentNode.insertBefore(messageDiv, form);
    });
});

// Close modal when clicking outside
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

document.getElementById('installmentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeInstallmentModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
        closeInstallmentModal();
    }
});
</script> 