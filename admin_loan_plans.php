<?php
include_once('admin-header.php');
?>

<!-- Admin Loan Plans Content -->
<div class="md:ml-80 pt-20 p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Loan Plans Management</h1>
        <p class="text-gray-600">Create and manage loan plans for different loan types</p>
    </div>

    <?php
    // Get all loan plans
    $plans_query = "SELECT * FROM loan_plans ORDER BY loan_type, loan_plan";
    $plans_result = mysqli_query($conn, $plans_query);
    $total_plans = mysqli_num_rows($plans_result);

    // Get statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_plans,
                        COUNT(DISTINCT loan_type) as unique_types,
                        AVG(CAST(REPLACE(interest_rate, '%', '') AS DECIMAL(5,2))) as avg_interest,
                        AVG(CAST(REPLACE(penalty_rate, '%', '') AS DECIMAL(5,2))) as avg_penalty
                    FROM loan_plans";
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-list text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Plans</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_plans']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-tags text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Loan Types</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['unique_types']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-percentage text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg Interest</p>
                    <p class="text-lg font-bold text-gray-900"><?php echo round($stats['avg_interest'], 1); ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fa-solid fa-exclamation-triangle text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg Penalty</p>
                    <p class="text-lg font-bold text-gray-900"><?php echo round($stats['avg_penalty'], 1); ?>%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Loan Plans</h3>
                <p class="text-gray-600">Manage all loan plans and their configurations</p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="openCreateModal()" 
                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fa-solid fa-plus mr-2"></i>Create New Plan
                </button>
                <button onclick="exportPlans()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fa-solid fa-download mr-2"></i>Export
                </button>
            </div>
        </div>
    </div>

    <!-- Loan Plans Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">All Loan Plans</h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <?php echo $total_plans; ?> Plans
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rates & Terms</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($plan = mysqli_fetch_assoc($plans_result)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo $plan['loan_plan']; ?></div>
                                <div class="text-sm text-gray-500">ID: <?php echo $plan['plan_id']; ?></div>
                                <div class="text-sm text-gray-500">Created: <?php echo date('M d, Y', strtotime($plan['created_at'])); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $plan['loan_type']; ?></div>
                            <div class="text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($plan['description']); ?>"><?php echo htmlspecialchars($plan['description']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-1">
                                <div class="text-sm">
                                    <span class="text-gray-500">Interest:</span>
                                    <span class="font-medium text-gray-900"><?php echo $plan['interest_rate']; ?></span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-500">Penalty:</span>
                                    <span class="font-medium text-gray-900"><?php echo $plan['penalty_rate']; ?></span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-gray-500">Duration:</span>
                                    <span class="font-medium text-gray-900"><?php echo $plan['duration_months']; ?> months</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $status_class = $plan['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            $status_text = $plan['is_active'] ? 'Active' : 'Inactive';
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="editPlan(<?php echo $plan['plan_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <i class="fa-solid fa-edit mr-1"></i>Edit
                                </button>
                                <button onclick="togglePlanStatus(<?php echo $plan['plan_id']; ?>, <?php echo $plan['is_active']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border <?php echo $plan['is_active'] ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100' : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100'; ?> text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $plan['is_active'] ? 'focus:ring-red-500' : 'focus:ring-green-500'; ?>">
                                    <i class="fa-solid <?php echo $plan['is_active'] ? 'fa-pause' : 'fa-play'; ?> mr-1"></i>
                                    <?php echo $plan['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button onclick="deletePlan(<?php echo $plan['plan_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fa-solid fa-trash mr-1"></i>Delete
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

<!-- Create/Edit Plan Modal -->
<div id="planModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Create New Loan Plan</h3>
                    <button onclick="closePlanModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="planForm" onsubmit="savePlan(event)">
                    <input type="hidden" id="planId" name="plan_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Loan Type</label>
                            <select id="loanType" name="loan_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Select Loan Type</option>
                                <option value="Business Loan">Business Loan</option>
                                <option value="Education Loan">Education Loan</option>
                                <option value="Small Business Loan">Small Business Loan</option>
                                <option value="Personal Loan">Personal Loan</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name</label>
                            <input type="text" id="loanPlan" name="loan_plan" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., Standard Plan, Premium Plan">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Interest Rate (%)</label>
                            <input type="number" id="interestRate" name="interest_rate" required step="0.01" min="0" max="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 12.5">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Penalty Rate (%)</label>
                            <input type="number" id="penaltyRate" name="penalty_rate" required step="0.01" min="0" max="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 2.5">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Months)</label>
                            <input type="number" id="durationMonths" name="duration_months" required min="1" max="120"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 24">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Amount</label>
                            <input type="number" id="minAmount" name="min_amount" required min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 50000">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Amount</label>
                            <input type="number" id="maxAmount" name="max_amount" required min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 1000000">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Describe the loan plan features and benefits"></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="isActive" name="is_active" class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Active Plan (Available for applications)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button type="button" onclick="closePlanModal()" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fa-solid fa-save mr-2"></i>Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create New Loan Plan';
        document.getElementById('planForm').reset();
        document.getElementById('planId').value = '';
        document.getElementById('planModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function editPlan(planId) {
        // Fetch plan details and populate form
        fetch(`get_plan_details.php?plan_id=${planId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Edit Loan Plan';
                    document.getElementById('planId').value = data.plan.plan_id;
                    document.getElementById('loanType').value = data.plan.loan_type;
                    document.getElementById('loanPlan').value = data.plan.loan_plan;
                    document.getElementById('interestRate').value = data.plan.interest_rate.replace('%', '');
                    document.getElementById('penaltyRate').value = data.plan.penalty_rate.replace('%', '');
                    document.getElementById('durationMonths').value = data.plan.duration_months;
                    document.getElementById('minAmount').value = data.plan.min_amount;
                    document.getElementById('maxAmount').value = data.plan.max_amount;
                    document.getElementById('description').value = data.plan.description;
                    document.getElementById('isActive').checked = data.plan.is_active == 1;
                    
                    document.getElementById('planModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Failed to load plan details. Please try again.');
            });
    }

    function savePlan(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const planId = formData.get('plan_id');
        
        // Add % to rates
        formData.set('interest_rate', formData.get('interest_rate') + '%');
        formData.set('penalty_rate', formData.get('penalty_rate') + '%');
        
        fetch(planId ? 'update_plan.php' : 'create_plan.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closePlanModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Failed to save plan. Please try again.');
        });
    }

    function togglePlanStatus(planId, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        if (confirm(`Are you sure you want to ${action} this loan plan?`)) {
            fetch('toggle_plan_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `plan_id=${planId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Failed to update plan status. Please try again.');
            });
        }
    }

    function deletePlan(planId) {
        if (confirm('Are you sure you want to delete this loan plan? This action cannot be undone.')) {
            fetch('delete_plan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `plan_id=${planId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Failed to delete plan. Please try again.');
            });
        }
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function exportPlans() {
        window.open('export_plans.php', '_blank');
    }

    // Close modal when clicking outside
    document.getElementById('planModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePlanModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePlanModal();
        }
    });
</script> 