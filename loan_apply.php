<?php
include_once('header.php');

// Pagination settings
$plans_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $plans_per_page;

// Get total number of active loan plans for pagination
$total_plans_query = "SELECT COUNT(*) as total FROM loan_plans WHERE is_active = 1";
$total_plans_result = mysqli_query($conn, $total_plans_query);
$total_plans = mysqli_fetch_assoc($total_plans_result)['total'];
$total_pages = ceil($total_plans / $plans_per_page);

// Get active loan plans from database with pagination
$plans_query = "SELECT * FROM loan_plans WHERE is_active = 1 ORDER BY loan_type, loan_plan LIMIT $plans_per_page OFFSET $offset";
$plans_result = mysqli_query($conn, $plans_query);
$plans_on_page = mysqli_num_rows($plans_result);
?>

<!--New loan-->
<div class="loan_container md:ml-80 pt-20 p-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Available Loan Plans</h2>
        
        <?php if ($plans_on_page > 0): ?>
        <!-- Loan Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php 
            $color_schemes = [
                'Business Loan' => [
                    'bg' => 'bg-gradient-to-br from-blue-500 to-blue-600',
                    'card' => 'bg-white border-blue-200 hover:border-blue-300',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600',
                    'button' => 'bg-blue-600 hover:bg-blue-700',
                    'accent' => 'text-blue-600',
                    'badge' => 'bg-blue-100 text-blue-800'
                ],
                'Education Loan' => [
                    'bg' => 'bg-gradient-to-br from-emerald-500 to-emerald-600',
                    'card' => 'bg-white border-emerald-200 hover:border-emerald-300',
                    'icon_bg' => 'bg-emerald-100',
                    'icon_color' => 'text-emerald-600',
                    'button' => 'bg-emerald-600 hover:bg-emerald-700',
                    'accent' => 'text-emerald-600',
                    'badge' => 'bg-emerald-100 text-emerald-800'
                ],
                'Small Business Loan' => [
                    'bg' => 'bg-gradient-to-br from-purple-500 to-purple-600',
                    'card' => 'bg-white border-purple-200 hover:border-purple-300',
                    'icon_bg' => 'bg-purple-100',
                    'icon_color' => 'text-purple-600',
                    'button' => 'bg-purple-600 hover:bg-purple-700',
                    'accent' => 'text-purple-600',
                    'badge' => 'bg-purple-100 text-purple-800'
                ],
                'Personal Loan' => [
                    'bg' => 'bg-gradient-to-br from-orange-500 to-orange-600',
                    'card' => 'bg-white border-orange-200 hover:border-orange-300',
                    'icon_bg' => 'bg-orange-100',
                    'icon_color' => 'text-orange-600',
                    'button' => 'bg-orange-600 hover:bg-orange-700',
                    'accent' => 'text-orange-600',
                    'badge' => 'bg-orange-100 text-orange-800'
                ]
            ];
            
            $icons = [
                'Business Loan' => 'fa-building',
                'Education Loan' => 'fa-graduation-cap',
                'Small Business Loan' => 'fa-store',
                'Personal Loan' => 'fa-user'
            ];
            
            while ($plan = mysqli_fetch_assoc($plans_result)): 
                $scheme = $color_schemes[$plan['loan_type']] ?? $color_schemes['Personal Loan'];
                $icon = $icons[$plan['loan_type']] ?? 'fa-money-bill';
            ?>
            <div class="group relative">
                <!-- Card Container -->
                <div class="<?php echo $scheme['card']; ?> rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-2 overflow-hidden">
                    <!-- Header with gradient background -->
                    <div class="<?php echo $scheme['bg']; ?> p-6 text-white relative overflow-hidden">
                        <!-- Background pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -mr-16 -mt-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full -ml-12 -mb-12"></div>
                        </div>
                        
                        <!-- Header content -->
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="<?php echo $scheme['icon_bg']; ?> w-12 h-12 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid <?php echo $icon; ?> <?php echo $scheme['icon_color']; ?> text-lg"></i>
                                </div>
                                <span class="<?php echo $scheme['badge']; ?> px-3 py-1 rounded-full text-xs font-semibold">
                                    <?php echo $plan['loan_type']; ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($plan['loan_plan']); ?></h3>
                            <p class="text-blue-100 text-sm opacity-90"><?php echo htmlspecialchars(substr($plan['description'], 0, 60)) . (strlen($plan['description']) > 60 ? '...' : ''); ?></p>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-6">
                        <!-- Interest Rate Highlight -->
                        <div class="text-center mb-6">
                            <div class="text-3xl font-bold <?php echo $scheme['accent']; ?> mb-1">
                                <?php echo $plan['interest_rate']; ?>
                            </div>
                            <div class="text-sm text-gray-600 font-medium">Interest Rate</div>
                        </div>
                        
                        <!-- Plan Details -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-calendar-days text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Duration</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800"><?php echo $plan['duration_months']; ?> months</span>
                            </div>
                            
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-exclamation-triangle text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Penalty Rate</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800"><?php echo $plan['penalty_rate']; ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-rupee-sign text-gray-400 mr-3"></i>
                                    <span class="text-sm text-gray-600">Amount Range</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-800">Rs.<?php echo number_format($plan['min_amount']/100000, 1); ?>L - Rs.<?php echo number_format($plan['max_amount']/100000, 1); ?>L</div>
                                    <div class="text-xs text-gray-500"><?php echo number_format($plan['min_amount']); ?> - <?php echo number_format($plan['max_amount']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Features -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fa-solid fa-star text-yellow-500 mr-2"></i>
                                Key Features
                            </h4>
                            <div class="space-y-2">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fa-solid fa-check text-green-500 mr-2 text-xs"></i>
                                    Quick approval process
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fa-solid fa-check text-green-500 mr-2 text-xs"></i>
                                    Flexible repayment terms
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fa-solid fa-check text-green-500 mr-2 text-xs"></i>
                                    Minimal documentation
                                </div>
                            </div>
                        </div>
                        
                        <!-- Apply Button -->
                        <button onclick="openLoanModal(<?php echo $plan['plan_id']; ?>)" 
                                class="w-full <?php echo $scheme['button']; ?> text-white py-3 px-4 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg hover:shadow-xl">
                            <i class="fa-solid fa-rocket mr-2"></i>
                            Apply Now
                        </button>
                        
                        <!-- Plan ID -->
                        <div class="text-center mt-3">
                            <span class="text-xs text-gray-400">Plan ID: <?php echo $plan['plan_id']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Hover effect overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-10 transition-opacity duration-300 rounded-2xl pointer-events-none"></div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex items-center justify-between">
            <!-- Page Info -->
            <div class="text-sm text-gray-600">
                Showing <?php echo $offset + 1; ?> to <?php echo $offset + $plans_on_page; ?> of <?php echo $total_plans; ?> loan plans
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
        <?php else: ?>
        <!-- No Plans Available -->
        <div class="text-center py-12">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-exclamation-triangle text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Loan Plans Available</h3>
            <p class="text-gray-500">Currently, there are no active loan plans. Please check back later or contact support.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Loan Application Modal -->
<div id="loanModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Loan Application Form</h3>
                    <button onclick="closeLoanModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="loanForm" action="process_loan_application.php" method="post">
                    <!-- Success Message Area -->
                    <div id="successMessage" class="hidden mb-6 p-6 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-check-circle text-green-600 mt-1 mr-3 text-xl"></i>
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-green-800 mb-3">Loan Application Submitted Successfully!</h4>
                                <div id="loanDetails" class="text-sm text-green-700 space-y-2">
                                    <!-- Loan details will be populated here -->
                                </div>
                                <div class="mt-4 flex space-x-3">
                                    <button type="button" onclick="closeLoanModal()" 
                                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 text-sm font-medium">
                                        <i class="fa-solid fa-check mr-2"></i>OK
                                    </button>
                                    <button type="button" onclick="viewDashboard()" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm font-medium">
                                        <i class="fa-solid fa-chart-line mr-2"></i>View Dashboard
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message Area -->
                    <div id="errorMessage" class="hidden mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-exclamation-circle text-red-600 mt-1 mr-3 text-xl"></i>
                            <div class="flex-1">
                                <h4 class="font-semibold text-red-800 mb-2">Application Failed</h4>
                                <p id="errorText" class="text-sm text-red-700"></p>
                                <button type="button" onclick="hideError()" 
                                        class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 text-sm font-medium">
                                    <i class="fa-solid fa-times mr-2"></i>Close
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Plan:</label>
                                <input type="text" name="loan_plan" id="selected_loan_plan" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Type:</label>
                                <input type="text" name="loan_type" id="selected_loan_type" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Loan Amount:</label>
                                <input type="number" name="amount" id="loan_amount" placeholder="Enter loan amount" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Min: Rs.<span id="min_amount">0</span> - Max: Rs.<span id="max_amount">0</span></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Interest Rate:</label>
                                <input type="text" id="interest_rate" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duration:</label>
                                <input type="text" id="duration" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Penalty Rate:</label>
                                <input type="text" id="penalty_rate" readonly 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Important Note -->
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-semibold text-yellow-800 mb-1">Important Notice</h4>
                                <p class="text-sm text-yellow-700">
                                    If the monthly installment is delayed, a penalty will be added as per the plan's penalty rate for each delayed month.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-center space-x-4">
                        <button type="submit" name="apply" id="submitBtn"
                                class="px-8 py-3 bg-success text-white rounded-lg hover:bg-success/90 transition-colors duration-200 font-medium">
                            Apply for Loan
                        </button>
                        <button type="button" onclick="closeLoanModal()" 
                                class="px-8 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                            Cancel
                        </button>
                    </div>
                    <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                    <input type="hidden" name="plan_id" id="selected_plan_id">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Store plan data for JavaScript access
const planData = <?php 
    mysqli_data_seek($plans_result, 0);
    $plans_array = [];
    while ($plan = mysqli_fetch_assoc($plans_result)) {
        $plans_array[] = $plan;
    }
    echo json_encode($plans_array);
?>;

function openLoanModal(planId) {
    // Find the selected plan
    const plan = planData.find(p => p.plan_id == planId);
    if (!plan) {
        alert('Plan not found!');
        return;
    }
    
    // Set form values
    document.getElementById('selected_loan_plan').value = plan.loan_plan;
    document.getElementById('selected_loan_type').value = plan.loan_type;
    document.getElementById('selected_plan_id').value = plan.plan_id;
    document.getElementById('interest_rate').value = plan.interest_rate;
    document.getElementById('duration').value = plan.duration_months + ' Months';
    document.getElementById('penalty_rate').value = plan.penalty_rate + ' per month';
    document.getElementById('min_amount').textContent = new Intl.NumberFormat('en-IN').format(plan.min_amount);
    document.getElementById('max_amount').textContent = new Intl.NumberFormat('en-IN').format(plan.max_amount);
    
    // Set amount input limits
    const amountInput = document.getElementById('loan_amount');
    amountInput.min = plan.min_amount;
    amountInput.max = plan.max_amount;
    amountInput.placeholder = `Enter amount between Rs.${new Intl.NumberFormat('en-IN').format(plan.min_amount)} - Rs.${new Intl.NumberFormat('en-IN').format(plan.max_amount)}`;
    
    // Show modal
    document.getElementById('loanModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLoanModal() {
    document.getElementById('loanModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset the submit button to its original state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Apply for Loan';
    submitBtn.className = 'px-8 py-3 bg-success text-white rounded-lg hover:bg-success/90 transition-colors duration-200 font-medium';
    
    // Reset form
    document.getElementById('loanForm').reset();
    
    // Hide any messages
    hideMessages();
}

// Close modal when clicking outside
document.getElementById('loanModal').addEventListener('click', function(e) {
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

// Form validation and submission
document.getElementById('loanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const amount = parseFloat(document.getElementById('loan_amount').value);
    const planId = document.getElementById('selected_plan_id').value;
    const plan = planData.find(p => p.plan_id == planId);
    
    if (!plan) {
        showError('Please select a valid loan plan.');
        return;
    }
    
    // Convert plan amounts to numbers (remove any formatting)
    const minAmount = parseFloat(plan.min_amount);
    const maxAmount = parseFloat(plan.max_amount);
    
    if (isNaN(amount) || amount < minAmount || amount > maxAmount) {
        showError(`Loan amount must be between Rs.${new Intl.NumberFormat('en-IN').format(minAmount)} and Rs.${new Intl.NumberFormat('en-IN').format(maxAmount)}`);
        return;
    }
    
    // Hide any existing messages
    hideMessages();
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Submit form via AJAX
    fetch('process_loan_application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            showSuccess(data.loan_details);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        showError('Network error. Please check your connection and try again.');
    });
});

function showSuccess(loanDetails) {
    const successDiv = document.getElementById('successMessage');
    const loanDetailsDiv = document.getElementById('loanDetails');
    const submitBtn = document.getElementById('submitBtn');
    
    // Disable the submit button and change its text
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-check mr-2"></i>Application Submitted';
    submitBtn.className = 'px-8 py-3 bg-gray-400 text-white rounded-lg font-medium cursor-not-allowed';
    
    // Populate loan details
    loanDetailsDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">Loan ID:</span>
                    <span class="font-semibold">#${loanDetails.loan_id}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Loan Plan:</span>
                    <span class="font-semibold">${loanDetails.loan_plan}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Loan Type:</span>
                    <span class="font-semibold">${loanDetails.loan_type}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Amount Requested:</span>
                    <span class="font-semibold">Rs.${new Intl.NumberFormat('en-IN').format(loanDetails.amount_requested)}</span>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">Total Amount:</span>
                    <span class="font-semibold">Rs.${new Intl.NumberFormat('en-IN').format(loanDetails.total_amount)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Interest Rate:</span>
                    <span class="font-semibold">${loanDetails.interest_rate}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Monthly Installment:</span>
                    <span class="font-semibold">Rs.${new Intl.NumberFormat('en-IN').format(loanDetails.monthly_installment)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Duration:</span>
                    <span class="font-semibold">${loanDetails.duration_months} months</span>
                </div>
            </div>
        </div>
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <i class="fa-solid fa-info-circle text-blue-600 mr-2"></i>
                <span class="text-sm text-blue-800">
                    Your loan application is now pending approval. You will be notified once the admin reviews your application.
                </span>
            </div>
        </div>
    `;
    
    // Show success message
    successDiv.classList.remove('hidden');
    
    // Scroll to success message
    successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    
    errorText.textContent = message;
    errorDiv.classList.remove('hidden');
    
    // Scroll to error message
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideMessages() {
    document.getElementById('successMessage').classList.add('hidden');
    document.getElementById('errorMessage').classList.add('hidden');
}

function hideError() {
    document.getElementById('errorMessage').classList.add('hidden');
}

function viewDashboard() {
    window.location.href = 'user_dashboard.php';
}
</script>
