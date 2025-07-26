<?php
include_once('admin-header.php');
?>

<!-- Admin Dashboard Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Dashboard Stats -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>
        
        <?php
        // Total users
        $select_user = "SELECT * FROM user";
        $user_data = mysqli_query($conn, $select_user);
        $total_user = mysqli_num_rows($user_data);
        
        // Total approved loans
        $select_loan = "SELECT * FROM loan WHERE status='approved'";
        $loan_data = mysqli_query($conn, $select_loan);
        $total_loan = mysqli_num_rows($loan_data);
        
        // Total pending loans
        $select_loan = "SELECT * FROM loan WHERE status='pending'";
        $loan_data = mysqli_query($conn, $select_loan);
        $total_pending = mysqli_num_rows($loan_data);

        // Total loan amounts and payments
        $select_loan_sum = "SELECT 
            SUM(CAST(REPLACE(l.loan_amount, ',', '') AS DECIMAL(10,2))) AS total_loan_amount, 
            SUM(p.amount) AS total_loan_payed
         FROM loan as l
         LEFT JOIN payment as p ON l.loan_id=p.loan_id WHERE l.status='approved'";
        $loan_sum_result = mysqli_query($conn, $select_loan_sum);
        $loan_sum_row = mysqli_fetch_assoc($loan_sum_result);
        $total_loan_amount = $loan_sum_row['total_loan_amount'] ?: 0;
        $total_loan_payed = $loan_sum_row['total_loan_payed'] ?: 0;
        ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users Card -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-yellow-800 mb-1">TOTAL USERS</p>
                        <p class="text-3xl font-bold text-yellow-900"><?php echo $total_user; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-users text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Loans Card -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-800 mb-1">TOTAL LOANS</p>
                        <p class="text-3xl font-bold text-green-900"><?php echo $total_loan; ?></p>
                        <div class="mt-2">
                            <p class="text-xs text-green-700">
                                Pending: <span class="font-semibold"><?php echo $total_pending; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-landmark text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Amount Disbursed Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-800 mb-1">AMOUNT DISBURSED</p>
                        <p class="text-2xl font-bold text-blue-900">Rs.<?php echo number_format($total_loan_amount, 2); ?></p>
                        <div class="mt-2 space-y-1">
                            <p class="text-xs text-blue-700">
                                Received: <span class="font-semibold">Rs.<?php echo number_format($total_loan_payed, 2); ?></span>
                            </p>
                            <p class="text-xs text-blue-700">
                                Pending: <span class="font-semibold">Rs.<?php echo number_format($total_loan_amount - $total_loan_payed, 2); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-money-bills text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-800 mb-1">RECENT ACTIVITY</p>
                        <p class="text-3xl font-bold text-purple-900"><?php echo $total_pending; ?></p>
                        <p class="text-xs text-purple-700 mt-2">Pending Requests</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-bell text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Requests Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Loan Requests</h2>
            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                <?php echo $total_pending; ?> Pending
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $request_query = "SELECT `user_id`,`loan_id`, `loan_amount`, `loan_plan`, `loan_type`, `status` FROM `loan` WHERE status='pending' ORDER BY loan_id DESC";
                    $request_data = mysqli_query($conn, $request_query);

                    if (mysqli_num_rows($request_data) > 0) {
                        while ($result = mysqli_fetch_assoc($request_data)) {
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $result['user_id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $result['loan_id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    Rs.<?php echo number_format((float)str_replace(',', '', $result['loan_amount']), 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $result['loan_plan']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $result['loan_type']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                        <?php echo ucfirst($result['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="approve.php?id=<?php echo $result['loan_id']; ?>" 
                                       onclick="return confirmApprove()"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-success hover:bg-success/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success">
                                        <i class="fa-solid fa-check mr-1"></i>Approve
                                    </a>
                                    <a href="delete_request.php?id=<?php echo $result['loan_id']; ?>" 
                                       onclick="return confirmDelete()"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fa-solid fa-times mr-1"></i>Reject
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No pending loan requests found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        return confirm('Are you sure you want to reject this loan request?');
    }
    
    function confirmApprove() {
        return confirm('Are you sure you want to approve this loan request?');
    }
    
    function confirmDeleteUser() {
        return confirm('Are you sure you want to delete this user?');
    }
</script>