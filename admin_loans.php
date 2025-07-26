<?php
include_once('admin-header.php');
?>

<!-- Admin Loans Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">All Loans Management</h1>
        <p class="text-gray-600">Comprehensive view of all loan applications and their status</p>
    </div>

    <?php
    // Get all loans with user details and payment information
    $loans_query = "SELECT l.*, u.user_name, u.email,
                           COALESCE(SUM(p.amount), 0) as total_paid,
                           COUNT(p.payment_id) as payment_count
                    FROM loan l 
                    LEFT JOIN user u ON l.user_id = u.user_id 
                    LEFT JOIN payment p ON l.loan_id = p.loan_id 
                    GROUP BY l.loan_id 
                    ORDER BY l.loan_id DESC";
    $loans_data = mysqli_query($conn, $loans_query);
    $total_loans = mysqli_num_rows($loans_data);

    // Calculate statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_loans,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_loans,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_loans,
                        SUM(CAST(REPLACE(loan_amount, ',', '') AS DECIMAL(10,2))) as total_amount
                    FROM loan";
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-landmark text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Loans</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_loans']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-check text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['approved_loans']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-clock text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_loans']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-times text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['rejected_loans']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-money-bills text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Amount</p>
                    <p class="text-lg font-bold text-gray-900">Rs.<?php echo number_format($stats['total_amount'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Loans</label>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search by user name, loan ID, or amount..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Type</label>
                <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Types</option>
                    <option value="Business Loan">Business Loan</option>
                    <option value="Education Loan">Education Loan</option>
                    <option value="Small Business Loan">Small Business Loan</option>
                    <option value="Personal Loan">Personal Loan</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">All Loans</h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo $total_loans; ?> Loans
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount & Payments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan & Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="loansTableBody">
                    <?php while ($loan = mysqli_fetch_assoc($loans_data)): 
                        $loan_amount = (float)str_replace(',', '', $loan['loan_amount']);
                        $total_paid = (float)$loan['total_paid'];
                        $remaining_amount = $loan_amount - $total_paid;
                        $payment_percentage = $loan_amount > 0 ? ($total_paid / $loan_amount) * 100 : 0;
                    ?>
                    <tr class="hover:bg-gray-50 loan-row" 
                        data-status="<?php echo $loan['status']; ?>" 
                        data-type="<?php echo $loan['loan_type']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                    <?php echo strtoupper(substr($loan['user_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $loan['user_name']; ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $loan['user_id']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $loan['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">Loan ID: <?php echo $loan['loan_id']; ?></div>
                            <div class="text-sm text-gray-500">Applied: <?php echo date('M d, Y', strtotime($loan['start_date'])); ?></div>
                            <div class="text-sm text-gray-500"><?php echo $loan['payment_count']; ?> payments made</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-semibold text-gray-900">Rs.<?php echo number_format($loan_amount, 2); ?></div>
                            <div class="text-sm text-gray-500">
                                Paid: Rs.<?php echo number_format($total_paid, 2); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                Remaining: Rs.<?php echo number_format($remaining_amount, 2); ?>
                            </div>
                            <div class="mt-1">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min(100, $payment_percentage); ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1"><?php echo round($payment_percentage, 1); ?>% paid</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $loan['loan_plan']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $loan['loan_type']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($loan['status'] == 'approved'): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            <?php elseif ($loan['status'] == 'pending'): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    Pending
                                </span>
                            <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Rejected
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="viewLoanDetails(<?php echo $loan['loan_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fa-solid fa-eye mr-1"></i>View
                                </button>
                                <button onclick="viewPayments(<?php echo $loan['loan_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fa-solid fa-money-bills mr-1"></i>Payments
                                </button>
                                <?php if ($loan['status'] == 'pending'): ?>
                                <a href="approve.php?id=<?php echo $loan['loan_id']; ?>" 
                                   onclick="return confirmApprove()"
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-success hover:bg-success/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success">
                                    <i class="fa-solid fa-check mr-1"></i>Approve
                                </a>
                                <a href="delete_request.php?id=<?php echo $loan['loan_id']; ?>" 
                                   onclick="return confirmDelete()"
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fa-solid fa-times mr-1"></i>Reject
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loan Details Modal -->
<div id="loanDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto scrollbar-hide">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Loan Details</h3>
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

<script>
    function confirmDelete() {
        return confirm('Are you sure you want to reject this loan?');
    }
    
    function confirmApprove() {
        return confirm('Are you sure you want to approve this loan?');
    }

    function viewLoanDetails(loanId) {
        // Show modal
        document.getElementById('loanDetailsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Show loading state
        document.getElementById('loanDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
                </div>
                <p class="text-gray-600">Loading loan details...</p>
            </div>
        `;
        
        // Fetch loan details via AJAX
        fetch(`get_loan_details.php?loan_id=${loanId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayLoanDetails(data);
                } else {
                    document.getElementById('loanDetailsContent').innerHTML = `
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
                document.getElementById('loanDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <p class="text-red-600">Failed to load loan details. Please try again.</p>
                    </div>
                `;
            });
    }

    function displayLoanDetails(data) {
        const loan = data.loan;
        const borrower = data.borrower;
        
        let paymentsHtml = '';
        if (data.payments.length > 0) {
            data.payments.forEach(payment => {
                paymentsHtml += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Payment ID: ${payment.payment_id}</p>
                            <p class="text-xs text-gray-500">${payment.loan_type} • ${payment.loan_plan}</p>
                            <p class="text-xs text-gray-500">${new Date(payment.date).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rs.${parseFloat(payment.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Completed
                            </span>
                        </div>
                    </div>
                `;
            });
        } else {
            paymentsHtml = '<p class="text-gray-500 text-sm">No payments made yet.</p>';
        }

        const statusClass = loan.status === 'approved' ? 'bg-green-100 text-green-800' : 
                           loan.status === 'pending' ? 'bg-orange-100 text-orange-800' : 
                           'bg-red-100 text-red-800';

        document.getElementById('loanDetailsContent').innerHTML = `
            <div class="space-y-6">
                <!-- Loan Header -->
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900">Loan ID: ${loan.loan_id}</h4>
                            <p class="text-gray-600">${loan.loan_type} • ${loan.loan_plan}</p>
                        </div>
                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${statusClass}">
                            ${loan.status.charAt(0).toUpperCase() + loan.status.slice(1)}
                        </span>
                    </div>
                </div>

                <!-- Financial Overview -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Financial Overview</h4>
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
                            <span>Payment Progress</span>
                            <span>${loan.payment_percentage}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${loan.payment_percentage}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Payments Made: <span class="text-gray-900 font-semibold">${loan.payment_count}</span></p>
                            <p class="text-gray-600">Loan Duration: <span class="text-gray-900 font-semibold">${loan.months_elapsed} months</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">Application Date: <span class="text-gray-900 font-semibold">${new Date(loan.start_date).toLocaleDateString()}</span></p>
                            <p class="text-gray-600">Loan Type: <span class="text-gray-900 font-semibold">${loan.loan_type}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Borrower Information -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Borrower Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Full Name</p>
                            <p class="text-sm text-gray-900">${borrower.user_name}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Email</p>
                            <p class="text-sm text-gray-900">${borrower.email}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Gender</p>
                            <p class="text-sm text-gray-900">${borrower.gender}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Profession</p>
                            <p class="text-sm text-gray-900">${borrower.profession}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">PAN Number</p>
                            <p class="text-sm text-gray-900">${borrower.pan_no}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-600">Address</p>
                            <p class="text-sm text-gray-900">${borrower.address}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Payment History</h4>
                    ${paymentsHtml}
                </div>
            </div>
        `;
    }

    function viewPayments(loanId) {
        // Redirect to payments page or show modal
        window.open(`admin_payments.php?loan_id=${loanId}`, '_blank');
    }

    function closeLoanModal() {
        document.getElementById('loanDetailsModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.loan-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Status filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
        const filterValue = this.value;
        const rows = document.querySelectorAll('.loan-row');
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (filterValue === '' || status === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Type filter functionality
    document.getElementById('typeFilter').addEventListener('change', function() {
        const filterValue = this.value;
        const rows = document.querySelectorAll('.loan-row');
        
        rows.forEach(row => {
            const type = row.getAttribute('data-type');
            if (filterValue === '' || type === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

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