<?php
include_once('admin-header.php');
?>

<!-- Admin Loan Requests Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Loan Requests Management</h1>
        <p class="text-gray-600">Review and manage pending loan applications</p>
    </div>

    <?php
    // Get pending loan requests with user details
    $request_query = "SELECT l.*, u.user_name, u.email 
                     FROM loan l 
                     LEFT JOIN user u ON l.user_id = u.user_id 
                     WHERE l.status = 'pending' 
                     ORDER BY l.loan_id DESC";
    $request_data = mysqli_query($conn, $request_query);
    $total_pending = mysqli_num_rows($request_data);
    ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-clock text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_pending; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-money-bills text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Amount</p>
                    <p class="text-2xl font-bold text-gray-900">
                        Rs.<?php 
                        $amount_query = "SELECT SUM(CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2))) as total FROM loan WHERE status = 'pending'";
                        $amount_result = mysqli_query($conn, $amount_query);
                        $amount_data = mysqli_fetch_assoc($amount_result);
                        echo number_format($amount_data['total'] ?: 0, 2);
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-users text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Unique Applicants</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        $unique_query = "SELECT COUNT(DISTINCT user_id) as unique_users FROM loan WHERE status = 'pending'";
                        $unique_result = mysqli_query($conn, $unique_query);
                        $unique_data = mysqli_fetch_assoc($unique_result);
                        echo $unique_data['unique_users'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Requests Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Pending Loan Requests</h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo $total_pending; ?> Pending
                    </span>
                </div>
            </div>
        </div>

        <?php if ($total_pending > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan & Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($result = mysqli_fetch_assoc($request_data)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                    <?php echo strtoupper(substr($result['user_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $result['user_name']; ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $result['user_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">Loan ID: <?php echo $result['loan_id']; ?></div>
                            <div class="text-sm text-gray-500">Applied: <?php echo date('M d, Y', strtotime($result['start_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-semibold text-gray-900">Rs.<?php echo number_format((float)str_replace(',', '', $result['loan_amount']), 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $result['loan_plan']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $result['loan_type']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $result['email']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="viewLoanDetails(<?php echo $result['loan_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fa-solid fa-eye mr-1"></i>View
                                </button>
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
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Pending Requests</h3>
            <p class="text-gray-500">All loan requests have been processed.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Loan Details Modal -->
<div id="loanDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Loan Application Details</h3>
                    <button onclick="closeLoanModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="loanDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
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

    function viewLoanDetails(loanId) {
        // Show modal
        document.getElementById('loanDetailsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Load loan details via AJAX (you can implement this)
        document.getElementById('loanDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
                </div>
                <p class="text-gray-600">Loading loan details...</p>
            </div>
        `;
    }

    function closeLoanModal() {
        document.getElementById('loanDetailsModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.getElementById('loanDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLoanModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLoanModal();
        }
    });
</script> 