<?php
include_once('header.php');

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total number of payments for pagination
$total_payments_query = "SELECT COUNT(*) as total FROM payment p 
    JOIN loan l ON p.loan_id = l.loan_id 
    WHERE l.user_id = '$user_id'";
$total_payments_result = mysqli_query($conn, $total_payments_query);
$total_payments = mysqli_fetch_assoc($total_payments_result)['total'];
$total_pages = ceil($total_payments / $records_per_page);

// Get user's payment statistics
$payment_stats_query = "SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    AVG(amount) as avg_amount,
    MAX(date) as last_payment_date
    FROM payment p 
    JOIN loan l ON p.loan_id = l.loan_id 
    WHERE l.user_id = '$user_id'";
$payment_stats_result = mysqli_query($conn, $payment_stats_query);
$payment_stats = mysqli_fetch_assoc($payment_stats_result);
?>

<!-- payment details -->
<div class="payment_details md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment History</h1>
        <p class="text-gray-600">Track all your loan payment transactions and history</p>
    </div>

    <!-- Payment Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Payments -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 font-medium text-sm uppercase tracking-wide">Total Payments</p>
                    <span class="text-3xl font-bold"><?php echo $payment_stats['total_payments']; ?></span>
                    <div class="mt-2 text-sm text-blue-100">
                        Transactions made
                    </div>
                </div>
                <div class="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-credit-card text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 font-medium text-sm uppercase tracking-wide">Total Amount</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($payment_stats['total_amount']); ?></span>
                    <div class="mt-2 text-sm text-green-100">
                        Total paid
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Average Payment -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 font-medium text-sm uppercase tracking-wide">Average Payment</p>
                    <span class="text-2xl font-bold">Rs.<?php echo number_format($payment_stats['avg_amount']); ?></span>
                    <div class="mt-2 text-sm text-purple-100">
                        Per transaction
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Last Payment -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 rounded-xl shadow-lg text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 font-medium text-sm uppercase tracking-wide">Last Payment</p>
                    <span class="text-lg font-bold"><?php echo $payment_stats['last_payment_date'] ? date('M d, Y', strtotime($payment_stats['last_payment_date'])) : 'N/A'; ?></span>
                    <div class="mt-2 text-sm text-orange-100">
                        Most recent
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-400 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Details Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Payment Transactions</h3>
                <p class="text-sm text-gray-600 mt-1">Detailed list of all your payment history</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-receipt text-green-600 text-xl"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <?php
            // Execute the SQL query with pagination
            $user_query = "SELECT u.user_name, u.profession, u.pan_no, l.loan_id, l.loan_type, p.amount, p.date, p.bill_no
                FROM user AS u
                INNER JOIN payment AS p ON u.user_id = p.user_id 
                INNER JOIN loan AS l ON p.loan_id = l.loan_id
                WHERE u.user_id = '$user_id'
                ORDER BY p.date DESC
                LIMIT $records_per_page OFFSET $offset";

            $user_data = mysqli_query($conn, $user_query);
            $payments_on_page = mysqli_num_rows($user_data);

            // Check if query execution was successful
            if ($user_data) {
                ?>
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-green-50 to-emerald-50 border-b-2 border-green-200">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-receipt mr-2"></i>
                                    Bill No.
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-file-invoice mr-2"></i>
                                    Loan ID
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-building mr-2"></i>
                                    Loan Type
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                    Paid Amount
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-calendar mr-2"></i>
                                    Date
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php
                        if ($payments_on_page > 0) {
                            while ($result_user = mysqli_fetch_assoc($user_data)) {
                                // Loan type icons
                                $loan_icons = [
                                    'Business Loan' => 'fa-building',
                                    'Education Loan' => 'fa-graduation-cap',
                                    'Small Business Loan' => 'fa-store',
                                    'Personal Loan' => 'fa-user'
                                ];
                                
                                $loan_icon = $loan_icons[$result_user['loan_type']] ?? 'fa-money-bill';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200 group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-200 transition-colors duration-200">
                                                <i class="fa-solid fa-receipt text-green-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 group-hover:text-green-600 transition-colors duration-200">
                                                    <?php echo $result_user['bill_no']; ?>
                                                </div>
                                                <div class="text-xs text-gray-500">Payment Receipt</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fa-solid fa-file-invoice text-blue-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900">#<?php echo $result_user['loan_id']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fa-solid <?php echo $loan_icon; ?> text-purple-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900"><?php echo $result_user['loan_type']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-lg font-bold text-green-600">
                                            Rs.<?php echo number_format($result_user['amount']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <i class="fa-solid fa-credit-card mr-1"></i>
                                            Payment completed
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-1">
                                            <div class="text-sm font-semibold text-gray-900">
                                                <?php echo date('M d, Y', strtotime($result_user['date'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <i class="fa-solid fa-clock mr-1"></i>
                                                <?php echo date('h:i A', strtotime($result_user['date'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fa-solid fa-receipt text-gray-400 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-600 mb-2">No Payments Found</h4>
                                    <p class="text-gray-500 mb-4">You haven't made any payments yet.</p>
                                    <a href="payments.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 text-sm font-medium">
                                        <i class="fa-solid fa-credit-card mr-2"></i>
                                        Make Payment
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg'>Error executing the query: " . mysqli_error($conn) . "</div>";
            }
            ?>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex items-center justify-between">
            <!-- Page Info -->
            <div class="text-sm text-gray-600">
                Showing <?php echo $offset + 1; ?> to <?php echo $offset + $payments_on_page; ?> of <?php echo $total_payments; ?> payments
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
                            echo '<span class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium">' . $i . '</span>';
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