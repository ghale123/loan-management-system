<?php
include_once('header.php');

// Pagination settings
$cards_per_page = 4;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $cards_per_page;

// Get user's loan statistics
$loan_stats_query = "SELECT 
    COUNT(*) as total_loans,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_loans,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_loans,
    SUM(CASE WHEN status = 'approved' THEN CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2)) ELSE 0 END) as total_borrowed,
    SUM(CASE WHEN status = 'approved' THEN CAST(REPLACE(remaining_loan, ',', '') AS DECIMAL(10,2)) ELSE 0 END) as total_remaining
    FROM loan WHERE user_id = '$user_id'";
$loan_stats_result = mysqli_query($conn, $loan_stats_query);
$loan_stats = mysqli_fetch_assoc($loan_stats_result);

// Get total number of loans for pagination
$total_loans_query = "SELECT COUNT(*) as total FROM loan WHERE user_id = '$user_id'";
$total_loans_result = mysqli_query($conn, $total_loans_query);
$total_loans = mysqli_fetch_assoc($total_loans_result)['total'];
$total_pages = ceil($total_loans / $cards_per_page);

// Get loans with pagination
$loan_query = "SELECT l.loan_id, l.loan_amount, l.remaining_loan, l.loan_type, l.loan_plan, l.status, l.start_date
               FROM loan as l
               WHERE l.user_id='$user_id' 
               ORDER BY l.loan_id DESC 
               LIMIT $cards_per_page OFFSET $offset";
$loan_data = mysqli_query($conn, $loan_query);
$total_loans_on_page = mysqli_num_rows($loan_data);
?>

<!-- MY loan -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Loans</h1>
        <p class="text-gray-600">Manage and track all your loan applications and payments</p>
    </div>

    <!-- Loan Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Loans -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 font-medium text-sm uppercase tracking-wide">Total Loans</p>
                    <span class="text-3xl font-bold"><?php echo $loan_stats['total_loans']; ?></span>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="text-xs bg-blue-400 px-2 py-1 rounded-full"><?php echo $loan_stats['approved_loans']; ?> Approved</span>
                        <span class="text-xs bg-yellow-400 px-2 py-1 rounded-full"><?php echo $loan_stats['pending_loans']; ?> Pending</span>
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
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($loan_stats['total_borrowed']); ?></span>
                    <div class="mt-2 text-sm text-green-100">
                        From approved loans
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
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($loan_stats['total_remaining']); ?></span>
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
                    $payment_progress = $loan_stats['total_borrowed'] > 0 ? 
                        (($loan_stats['total_borrowed'] - $loan_stats['total_remaining']) / $loan_stats['total_borrowed']) * 100 : 0;
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

    <!-- Loans Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php
        if ($loan_data && mysqli_num_rows($loan_data) > 0) {
            while ($row_loan = mysqli_fetch_assoc($loan_data)) {
                // Calculate penalty
                $start_date = $row_loan['start_date'];
                $loan_amount = (float)str_replace(',', '', $row_loan['loan_amount']);
                $remaining_loan = (float)str_replace(',', '', $row_loan['remaining_loan']);

                $start = new DateTime($start_date);
                $now = new DateTime();
                $interval = $start->diff($now);
                $months_passed = ($interval->y * 12) + $interval->m;
                if ($interval->d >= 1) {
                    $months_passed += 1;
                }

                $loan_id = $row_loan['loan_id'];
                $payment_query = "SELECT COUNT(*) as payment_count FROM payment WHERE loan_id = $loan_id";
                $payment_data = mysqli_query($conn, $payment_query);
                $payment_count = mysqli_fetch_assoc($payment_data)['payment_count'];
                
                // Get loan plan details for monthly installment calculation
                $plan_query = "SELECT * FROM loan_plans WHERE loan_type = '" . mysqli_real_escape_string($conn, $row_loan['loan_type']) . "' AND loan_plan = '" . mysqli_real_escape_string($conn, $row_loan['loan_plan']) . "' LIMIT 1";
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

                // Calculate payment progress
                $paid_amount = $loan_amount - $remaining_loan;
                $payment_progress = $loan_amount > 0 ? ($paid_amount / $loan_amount) * 100 : 0;

                // Status colors
                $status_colors = [
                    'approved' => 'bg-green-100 text-green-800 border-green-200',
                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    'rejected' => 'bg-red-100 text-red-800 border-red-200'
                ];

                // Loan type icons
                $loan_icons = [
                    'Business Loan' => 'fa-building',
                    'Education Loan' => 'fa-graduation-cap',
                    'Small Business Loan' => 'fa-store',
                    'Personal Loan' => 'fa-user'
                ];

                $icon = $loan_icons[$row_loan['loan_type']] ?? 'fa-money-bill';
                ?>
                
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid <?php echo $icon; ?> text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Loan #<?php echo $row_loan['loan_id']; ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $row_loan['loan_type']; ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $status_colors[$row_loan['status']]; ?>">
                                <?php echo ucfirst($row_loan['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <!-- Loan Details -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-800">Rs.<?php echo number_format($loan_amount); ?></div>
                                <div class="text-sm text-gray-600">Total Amount</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600">Rs.<?php echo number_format($remaining_loan); ?></div>
                                <div class="text-sm text-gray-600">Remaining</div>
                            </div>
                        </div>

                        <!-- Monthly Installment Info -->
                        <?php if ($monthly_installment > 0): ?>
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-calendar-check text-blue-600 mr-3"></i>
                                    <div>
                                        <div class="text-sm font-medium text-blue-800">Monthly Installment</div>
                                        <div class="text-xs text-blue-600">Based on <?php echo $interest_rate; ?>% interest for <?php echo $duration_months; ?> months</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-blue-800">Rs.<?php echo number_format($monthly_installment, 2); ?></div>
                                    <div class="text-xs text-blue-600">Per month</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Payment Progress -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Payment Progress</span>
                                <span class="text-sm font-medium text-gray-700"><?php echo round($payment_progress, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: <?php echo $payment_progress; ?>%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>Paid: Rs.<?php echo number_format($paid_amount); ?></span>
                                <span><?php echo $payment_count; ?> payments</span>
                            </div>
                        </div>

                        <!-- Loan Information -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-calendar-days text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Plan</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800"><?php echo $row_loan['loan_plan']; ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-calendar text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Start Date</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800"><?php echo date('M d, Y', strtotime($row_loan['start_date'])); ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-clock text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Duration</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800"><?php echo $months_passed; ?> months</span>
                            </div>
                            
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-exclamation-triangle text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Penalty <?php echo $penalty_rate."%";?></span>
                                </div>
                                <span class="text-sm font-semibold <?php echo $penalty_class; ?>"><?php echo $penalty; ?></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <?php if ($row_loan['status'] == 'approved' && $remaining_loan > 0): ?>
                            <a href="payments.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center text-sm font-medium">
                                <i class="fa-solid fa-credit-card mr-2"></i>Make Payment
                            </a>
                            <?php endif; ?>
                            
                            <a href="payment-details.php" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors duration-200 text-center text-sm font-medium">
                                <i class="fa-solid fa-history mr-2"></i>View History
                            </a>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <!-- No Loans State -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-file-invoice text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Loans Found</h3>
                    <p class="text-gray-500 mb-6">You haven't applied for any loans yet. Start your journey by applying for a loan.</p>
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
            Showing <?php echo $offset + 1; ?> to <?php echo $offset + $total_loans_on_page; ?> of <?php echo $total_loans; ?> loans
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
