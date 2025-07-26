<?php
include_once('admin-header.php');
?>

<!-- Admin Users Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">User Management</h1>
        <p class="text-gray-600">Manage all registered users and their information</p>
    </div>

    <?php
    // Get all users with their loan statistics
    $users_query = "SELECT u.*, 
                           COUNT(l.loan_id) as total_loans,
                           SUM(CASE WHEN l.status = 'approved' THEN 1 ELSE 0 END) as approved_loans,
                           SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END) as pending_loans
                    FROM user u 
                    LEFT JOIN loan l ON u.user_id = l.user_id 
                    GROUP BY u.user_id 
                    ORDER BY u.user_id DESC";
    $users_data = mysqli_query($conn, $users_query);
    $total_users = mysqli_num_rows($users_data);
    ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-users text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_users; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-user-check text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Users</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        $active_query = "SELECT COUNT(DISTINCT user_id) as active FROM loan WHERE status = 'approved'";
                        $active_result = mysqli_query($conn, $active_query);
                        $active_data = mysqli_fetch_assoc($active_result);
                        echo $active_data['active'];
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-clock text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Users</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        $pending_query = "SELECT COUNT(DISTINCT user_id) as pending FROM loan WHERE status = 'pending'";
                        $pending_result = mysqli_query($conn, $pending_query);
                        $pending_data = mysqli_fetch_assoc($pending_result);
                        echo $pending_data['pending'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search by name or email..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Users</option>
                    <option value="active">Active Users</option>
                    <option value="pending">Pending Users</option>
                    <option value="inactive">Inactive Users</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo $total_users; ?> Users
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personal Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                    <?php while ($user = mysqli_fetch_assoc($users_data)): ?>
                    <tr class="hover:bg-gray-50 user-row" data-status="<?php echo $user['total_loans'] > 0 ? 'active' : 'inactive'; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm">
                                    <?php echo strtoupper(substr($user['user_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $user['user_name']; ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $user['user_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $user['email']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $user['profession']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $user['gender']; ?> • <?php echo $user['pan_no']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $user['address']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="font-semibold"><?php echo $user['total_loans']; ?></span> Total Loans
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php if ($user['approved_loans'] > 0): ?>
                                    <span class="text-green-600"><?php echo $user['approved_loans']; ?> Approved</span>
                                <?php endif; ?>
                                <?php if ($user['pending_loans'] > 0): ?>
                                    <span class="text-orange-600"><?php echo $user['pending_loans']; ?> Pending</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($user['total_loans'] > 0): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="viewUserDetails(<?php echo $user['user_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fa-solid fa-eye mr-1"></i>View
                                </button>
                                <button onclick="viewUserLoans(<?php echo $user['user_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fa-solid fa-landmark mr-1"></i>Loans
                                </button>
                                <a href="user_delete.php?id=<?php echo $user['user_id']; ?>" 
                                   onclick="return confirmDeleteUser()"
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fa-solid fa-trash mr-1"></i>Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div id="userDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto scrollbar-hide">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">User Details</h3>
                    <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="userDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Loans Modal -->
<div id="userLoansModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto scrollbar-hide">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">User Loans</h3>
                    <button onclick="closeUserLoansModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="userLoansContent">
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
    function confirmDeleteUser() {
        return confirm('⚠️ Are you sure you want to delete this user?\n\nThis action will permanently remove:\n• User account\n• All loan applications\n• All payment records\n• All associated data\n\nThis action cannot be undone!');
    }

    function viewUserDetails(userId) {
        // Show modal
        document.getElementById('userDetailsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Show loading state
        document.getElementById('userDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
                </div>
                <p class="text-gray-600">Loading user details...</p>
            </div>
        `;
        
        // Fetch user details via AJAX
        fetch(`get_user_details.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserDetails(data);
                } else {
                    document.getElementById('userDetailsContent').innerHTML = `
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
                document.getElementById('userDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <p class="text-red-600">Failed to load user details. Please try again.</p>
                    </div>
                `;
            });
    }

    function displayUserDetails(data) {
        const user = data.user;
        const stats = data.statistics;
        
        let loansHtml = '';
        if (data.recent_loans.length > 0) {
            data.recent_loans.forEach(loan => {
                const statusClass = loan.status === 'approved' ? 'bg-green-100 text-green-800' : 
                                   loan.status === 'pending' ? 'bg-orange-100 text-orange-800' : 
                                   'bg-red-100 text-red-800';
                loansHtml += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Loan ID: ${loan.loan_id}</p>
                            <p class="text-xs text-gray-500">${loan.loan_type} • ${loan.loan_plan}</p>
                            <p class="text-xs text-gray-500">Applied: ${new Date(loan.start_date).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rs.${parseFloat(loan.loan_amount.replace(/,/g, '')).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                                ${loan.status.charAt(0).toUpperCase() + loan.status.slice(1)}
                            </span>
                        </div>
                    </div>
                `;
            });
        } else {
            loansHtml = '<p class="text-gray-500 text-sm">No loan history found.</p>';
        }

        let paymentsHtml = '';
        if (data.recent_payments.length > 0) {
            data.recent_payments.forEach(payment => {
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
            paymentsHtml = '<p class="text-gray-500 text-sm">No payment history found.</p>';
        }

        document.getElementById('userDetailsContent').innerHTML = `
            <div class="space-y-6">
                <!-- User Basic Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Full Name</p>
                            <p class="text-sm text-gray-900">${user.user_name}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Email</p>
                            <p class="text-sm text-gray-900">${user.email}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Gender</p>
                            <p class="text-sm text-gray-900">${user.gender}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Profession</p>
                            <p class="text-sm text-gray-900">${user.profession}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">PAN Number</p>
                            <p class="text-sm text-gray-900">${user.pan_no}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-gray-600">Address</p>
                            <p class="text-sm text-gray-900">${user.address}</p>
                        </div>
                    </div>
                </div>

                <!-- Loan Statistics -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Loan Statistics</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">${stats.total_loans}</p>
                            <p class="text-xs text-gray-600">Total Loans</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">${stats.approved_loans}</p>
                            <p class="text-xs text-gray-600">Approved</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-orange-600">${stats.pending_loans}</p>
                            <p class="text-xs text-gray-600">Pending</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">${stats.rejected_loans}</p>
                            <p class="text-xs text-gray-600">Rejected</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900">Rs.${stats.total_borrowed}</p>
                            <p class="text-xs text-gray-600">Total Borrowed</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-green-600">Rs.${stats.total_paid}</p>
                            <p class="text-xs text-gray-600">Total Paid</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-orange-600">Rs.${stats.remaining_amount}</p>
                            <p class="text-xs text-gray-600">Remaining</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Payment Progress</span>
                            <span>${stats.payment_percentage}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${stats.payment_percentage}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Loans -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Recent Loans</h4>
                    ${loansHtml}
                </div>

                <!-- Recent Payments -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Recent Payments</h4>
                    ${paymentsHtml}
                </div>
            </div>
        `;
    }

    function viewUserLoans(userId) {
        // Show modal
        document.getElementById('userLoansModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Show loading state
        document.getElementById('userLoansContent').innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-spinner fa-spin text-blue-600"></i>
                </div>
                <p class="text-gray-600">Loading user loans...</p>
            </div>
        `;
        
        // Fetch user loans via AJAX
        fetch(`get_user_loans.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserLoansContent(data);
                } else {
                    document.getElementById('userLoansContent').innerHTML = `
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
                document.getElementById('userLoansContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <p class="text-red-600">Failed to load user loans. Please try again.</p>
                    </div>
                `;
            });
    }

    function displayUserLoansContent(data) {
        const user = data.user;
        const stats = data.statistics;
        
        let loansHtml = '';
        if (data.loans.length > 0) {
            data.loans.forEach(loan => {
                const statusClass = loan.status === 'approved' ? 'bg-green-100 text-green-800' : 
                                   loan.status === 'pending' ? 'bg-orange-100 text-orange-800' : 
                                   'bg-red-100 text-red-800';
                
                const loanAmount = parseFloat(loan.loan_amount.replace(/,/g, ''));
                const totalPaid = parseFloat(loan.total_paid);
                const remainingAmount = loanAmount - totalPaid;
                const paymentPercentage = loanAmount > 0 ? (totalPaid / loanAmount) * 100 : 0;
                
                loansHtml += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h5 class="text-lg font-semibold text-gray-900">Loan ID: ${loan.loan_id}</h5>
                                <p class="text-sm text-gray-600">${loan.loan_type} • ${loan.loan_plan}</p>
                            </div>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${statusClass}">
                                ${loan.status.charAt(0).toUpperCase() + loan.status.slice(1)}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Loan Amount</p>
                                <p class="text-lg font-bold text-gray-900">Rs.${loanAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Paid</p>
                                <p class="text-lg font-bold text-green-600">Rs.${totalPaid.toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Remaining</p>
                                <p class="text-lg font-bold text-orange-600">Rs.${remainingAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Payment Progress</span>
                                <span>${paymentPercentage.toFixed(1)}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: ${Math.min(100, paymentPercentage)}%"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Applied Date: <span class="text-gray-900">${new Date(loan.start_date).toLocaleDateString()}</span></p>
                                <p class="text-gray-600">Payments Made: <span class="text-gray-900">${loan.payment_count}</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-gray-600">Loan Type: <span class="text-gray-900">${loan.loan_type}</span></p>
                                <p class="text-gray-600">Loan Plan: <span class="text-gray-900">${loan.loan_plan}</span></p>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            loansHtml = `
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-landmark text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Loans Found</h3>
                    <p class="text-gray-500">This user hasn't applied for any loans yet.</p>
                </div>
            `;
        }

        document.getElementById('userLoansContent').innerHTML = `
            <div class="space-y-6">
                <!-- User Header -->
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center font-semibold text-lg mr-4">
                            ${user.user_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">${user.user_name}</h4>
                            <p class="text-gray-600">${user.email}</p>
                        </div>
                    </div>
                </div>

                <!-- Loan Statistics -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Loan Overview</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">${stats.total_loans}</p>
                            <p class="text-xs text-gray-600">Total Loans</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">${stats.approved_loans}</p>
                            <p class="text-xs text-gray-600">Approved</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-orange-600">${stats.pending_loans}</p>
                            <p class="text-xs text-gray-600">Pending</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">${stats.rejected_loans}</p>
                            <p class="text-xs text-gray-600">Rejected</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900">Rs.${stats.total_borrowed}</p>
                            <p class="text-xs text-gray-600">Total Borrowed</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-green-600">Rs.${stats.total_paid}</p>
                            <p class="text-xs text-gray-600">Total Paid</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-orange-600">Rs.${stats.remaining_amount}</p>
                            <p class="text-xs text-gray-600">Remaining</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Overall Payment Progress</span>
                            <span>${stats.payment_percentage}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${stats.payment_percentage}%"></div>
                        </div>
                    </div>
                </div>

                <!-- All Loans -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">All Loans</h4>
                    ${loansHtml}
                </div>
            </div>
        `;
    }

    function closeUserModal() {
        document.getElementById('userDetailsModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function closeUserLoansModal() {
        document.getElementById('userLoansModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.user-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
        const filterValue = this.value;
        const rows = document.querySelectorAll('.user-row');
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (filterValue === '' || status === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    document.getElementById('userDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });

    // Close modal when clicking outside
    document.getElementById('userLoansModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserLoansModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUserModal();
            closeUserLoansModal(); // Also close loans modal on escape
        }
    });
</script> 