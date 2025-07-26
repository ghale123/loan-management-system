<?php
session_start();
include("connection.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admin WHERE admin_id='$admin_id'";
$data = mysqli_query($conn, $query);
if (mysqli_num_rows($data) == 1) {
    $admin_data = mysqli_fetch_assoc($data);
} else {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Import Google font - Poppins  -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#1c5f5f',
                        secondary: '#d6e7d5',
                        body: '#edeef3',
                        lightGrey: '#f4f5fa',
                        lightBlack: '#454955',
                        darkPurple: '#2c2f4e',
                        success: '#59b76e',
                        danger: '#ff3700',
                        info: '#3474f5',
                        warning: '#f59e0b',
                    }
                }
            }
        }
    </script>
    <style>
        .status-pending {
            @apply text-orange-600;
        }
        .status-approved {
            @apply text-green-600;
        }
        .status-rejected {
            @apply text-red-600;
        }
    </style>
</head>

<body class="font-poppins bg-gray-50">
    <div class="flex h-auto bg-gray-50">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0 md:fixed md:inset-y-0 md:left-0 md:z-50">
            <div class="flex flex-col w-80 bg-white shadow-lg h-full">
                <!-- Logo Section -->
                <div class="flex flex-col items-center justify-center h-16 px-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <img src="image/loan.png" alt="Logo" class="w-10 h-10">
                        <span class="text-lg font-semibold text-primary">GETLOAN ADMIN</span>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-gauge w-5 h-5 mr-3"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    
                    <a href="admin_loan_requests.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_loan_requests.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-bell w-5 h-5 mr-3"></i>
                        <span class="font-medium">Loan Requests</span>
                        <?php
                        // Count pending loan requests
                        $pending_query = "SELECT COUNT(*) as pending_count FROM loan WHERE status = 'pending'";
                        $pending_result = mysqli_query($conn, $pending_query);
                        $pending_count = mysqli_fetch_assoc($pending_result)['pending_count'];
                        if ($pending_count > 0):
                        ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="admin_users.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                        <span class="font-medium">Users</span>
                    </a>
                    
                    <a href="admin_loans.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_loans.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-landmark w-5 h-5 mr-3"></i>
                        <span class="font-medium">All Loans</span>
                    </a>
                    
                    <a href="admin_loan_plans.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_loan_plans.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-list-check w-5 h-5 mr-3"></i>
                        <span class="font-medium">Loan Plans</span>
                    </a>
                    
                    <a href="admin_payments.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_payments.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-money-bills w-5 h-5 mr-3"></i>
                        <span class="font-medium">Payment Details</span>
                    </a>
                    
                    <a href="admin_reports.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-primary hover:text-white transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'bg-primary text-white' : ''; ?>">
                        <i class="fa-solid fa-chart-line w-5 h-5 mr-3"></i>
                        <span class="font-medium">Reports</span>
                    </a>
                </nav>

                <!-- Logout Section -->
                <div class="p-4 border-t border-gray-200">
                    <a href="logout.php" class="flex items-center px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors duration-200">
                        <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-3"></i>
                        <span class="font-medium">Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col md:ml-80">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 right-0 left-0 md:left-80 z-40 h-16">
                <div class="flex items-center justify-between px-6 h-full">
                    <!-- Mobile menu button -->
                    <button class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                        <i class="fa-solid fa-bars w-6 h-6"></i>
                    </button>

                    <!-- Search Bar -->
                    <div class="flex-1 max-w-lg mx-4">
                        <div class="relative">
                            <input type="text" placeholder="Search users, loans, payments..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Profile & Notifications -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                <i class="fa-solid fa-bell w-5 h-5"></i>
                                <?php if ($pending_count > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $pending_count; ?></span>
                                <?php endif; ?>
                            </button>
                        </div>

                        <!-- Admin Profile -->
                        <div class="flex items-center space-x-3 bg-gray-100 rounded-full px-4 py-2">
                            <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-lg">
                                <?php echo strtoupper(substr($admin_data['admin_name'], 0, 1)); ?>
                            </div>
                            <div class="hidden md:block">
                                <span class="font-medium text-gray-700"><?php echo $admin_data['admin_name']; ?></span>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 pt-16">
                <!-- Content will be inserted here by other pages -->
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="mobile-sidebar" class="fixed inset-0 z-40 md:hidden hidden">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="toggleMobileSidebar()"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button onclick="toggleMobileSidebar()" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <i class="fa-solid fa-times text-white"></i>
                </button>
            </div>
            <!-- Mobile sidebar content -->
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-4">
                    <img src="image/loan.png" alt="Logo" class="w-8 h-8">
                    <span class="ml-2 text-lg font-semibold text-primary">GETLOAN ADMIN</span>
                </div>
                <nav class="mt-5 px-2 space-y-1">
                    <a href="admin_dashboard.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-gauge mr-4"></i>
                        Dashboard
                    </a>
                    <a href="admin_loan_requests.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-bell mr-4"></i>
                        Loan Requests
                        <?php if ($pending_count > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="admin_users.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-users mr-4"></i>
                        Users
                    </a>
                    <a href="admin_loans.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-landmark mr-4"></i>
                        All Loans
                    </a>
                    <a href="admin_loan_plans.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-list-check mr-4"></i>
                        Loan Plans
                    </a>
                    <a href="admin_payments.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-money-bills mr-4"></i>
                        Payment Details
                    </a>
                    <a href="admin_reports.php" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 hover:bg-primary hover:text-white">
                        <i class="fa-solid fa-chart-line mr-4"></i>
                        Reports
                    </a>
                </nav>
            </div>
            <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                <a href="logout.php" class="flex items-center text-red-600 hover:text-red-800">
                    <i class="fa-solid fa-right-from-bracket mr-3"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Add click event to mobile menu button
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.querySelector('button.md\\:hidden');
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', toggleMobileSidebar);
            }
        });
    </script>
</body>
</html> 