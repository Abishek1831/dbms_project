<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'employee') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
$conn = getDBConnection();

$empId = $_SESSION['user_id'];

// Get leave balance
$leaveSql = "SELECT * FROM leave_balance WHERE emp_id = $empId AND year = YEAR(CURDATE())";
$leaveResult = $conn->query($leaveSql);
$leaveBalance = $leaveResult->fetch_assoc();

// Get ALL leave history (pending, approved, rejected)
$historySql = "SELECT la.*, 
               CONCAT(a.username) as approved_by_name,
               CASE 
                   WHEN la.status = 'pending' THEN 1
                   WHEN la.status = 'approved' THEN 2
                   WHEN la.status = 'rejected' THEN 3
               END as status_order
               FROM leave_applications la
               LEFT JOIN admin a ON la.approved_by = a.admin_id
               WHERE la.emp_id = $empId 
               ORDER BY status_order ASC, la.applied_date DESC";
$historyResult = $conn->query($historySql);

// Get employee details
$empSql = "SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE emp_id = $empId";
$empResult = $conn->query($empSql);
$employee = $empResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - <?php echo $employee['name']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar h1 {
            font-size: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .leave-balance-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .balance-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            border: 2px solid transparent;
        }

        .balance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .balance-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .balance-card .balance-value {
            font-size: 42px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .balance-card .balance-label {
            font-size: 13px;
            color: #999;
            margin-top: 8px;
        }

        .section {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 22px;
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .btn-submit {
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table th:first-child {
            border-radius: 10px 0 0 0;
        }

        table th:last-child {
            border-radius: 0 10px 0 0;
        }

        table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        table tr:last-child td:first-child {
            border-radius: 0 0 0 10px;
        }

        table tr:last-child td:last-child {
            border-radius: 0 0 10px 0;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge-sick {
            background: #ffe5e5;
            color: #c92a2a;
        }

        .badge-casual {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-earned {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 72px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            color: #666;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .leave-balance-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            table {
                font-size: 12px;
            }

            table th, table td {
                padding: 10px 8px;
            }
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏖️ Leave Management Portal</h1>
        <div class="nav-buttons">
            <a href="employee_dashboard.php" class="nav-btn">🏠 Dashboard</a>
            <button class="nav-btn" onclick="logout()">🚪 Logout</button>
        </div>
    </div>

    <div class="container">
        <div id="alertBox" class="alert"></div>

        <!-- Leave Balance Cards -->
        <div class="leave-balance-cards">
            <div class="balance-card">
                <h3>🤒 Sick Leave</h3>
                <div class="balance-value"><?php echo $leaveBalance['sick_leave'] ?? 0; ?></div>
                <div class="balance-label">days available</div>
            </div>
            <div class="balance-card">
                <h3>🏝️ Casual Leave</h3>
                <div class="balance-value"><?php echo $leaveBalance['casual_leave'] ?? 0; ?></div>
                <div class="balance-label">days available</div>
            </div>
            <div class="balance-card">
                <h3>✨ Earned Leave</h3>
                <div class="balance-value"><?php echo $leaveBalance['earned_leave'] ?? 0; ?></div>
                <div class="balance-label">days available</div>
            </div>
            <div class="balance-card">
                <h3>📊 Total Balance</h3>
                <div class="balance-value">
                    <?php 
                    echo ($leaveBalance['sick_leave'] ?? 0) + 
                         ($leaveBalance['casual_leave'] ?? 0) + 
                         ($leaveBalance['earned_leave'] ?? 0); 
                    ?>
                </div>
                <div class="balance-label">total days</div>
            </div>
        </div>

        <!-- Apply for Leave -->
        <div class="section">
            <h2 class="section-title">📝 Apply for New Leave</h2>
            
            <div class="info-box">
                💡 <strong>Note:</strong> Make sure to apply for leave at least 2 days in advance. Emergency leaves require manager approval.
            </div>

            <form id="leaveForm" onsubmit="submitLeave(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="leave_type">
                            Leave Type <span class="required">*</span>
                        </label>
                        <select id="leave_type" name="leave_type" required>
                            <option value="">-- Select Leave Type --</option>
                            <option value="sick">🤒 Sick Leave</option>
                            <option value="casual">🏝️ Casual Leave</option>
                            <option value="earned">✨ Earned Leave</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="days_count">
                            Number of Days <span class="required">*</span>
                        </label>
                        <input type="number" id="days_count" name="days_count" min="0" readonly 
                               style="background: #f5f5f5; font-weight: 600; color: #667eea;" value="0">
                    </div>

                    <div class="form-group">
                        <label for="start_date">
                            Start Date <span class="required">*</span>
                        </label>
                        <input type="date" id="start_date" name="start_date" required onchange="calculateDays()">
                    </div>

                    <div class="form-group">
                        <label for="end_date">
                            End Date <span class="required">*</span>
                        </label>
                        <input type="date" id="end_date" name="end_date" required onchange="calculateDays()">
                    </div>

                    <div class="form-group full-width">
                        <label for="reason">
                            Reason for Leave <span class="required">*</span>
                        </label>
                        <textarea id="reason" name="reason" required 
                                  placeholder="Please provide a detailed reason for your leave application..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">✓ Submit Leave Application</button>
            </form>
        </div>

        <!-- Leave History -->
        <div class="section">
            <h2 class="section-title">📋 My Leave History</h2>
            
            <?php if ($historyResult && $historyResult->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($leave = $historyResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php echo $leave['leave_type']; ?>">
                                    <?php echo ucfirst($leave['leave_type']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                            <td><strong><?php echo $leave['days_count']; ?></strong> days</td>
                            <td style="max-width: 200px;"><?php echo substr($leave['reason'], 0, 50) . (strlen($leave['reason']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $leave['status']; ?>">
                                    <?php 
                                    if ($leave['status'] == 'pending') echo '⏳ Pending';
                                    elseif ($leave['status'] == 'approved') echo '✓ Approved';
                                    else echo '✗ Rejected';
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($leave['applied_date'])); ?></td>
                            <td>
                                <?php 
                                if ($leave['admin_remarks']) {
                                    echo '<span style="color: #666;">' . $leave['admin_remarks'] . '</span>';
                                } elseif ($leave['status'] == 'approved') {
                                    echo '<span style="color: #28a745;">✓ Approved by Admin</span>';
                                } elseif ($leave['status'] == 'rejected') {
                                    echo '<span style="color: #dc3545;">✗ Rejected by Admin</span>';
                                } else {
                                    echo '<span style="color: #999;">Waiting for approval</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No leave applications yet. Apply for your first leave above!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').setAttribute('min', today);
        document.getElementById('end_date').setAttribute('min', today);

        function calculateDays() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = end - start;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (diffDays > 0) {
                    document.getElementById('days_count').value = diffDays;
                    document.getElementById('end_date').setAttribute('min', startDate);
                } else {
                    document.getElementById('days_count').value = 0;
                    showAlert('❌ End date must be same or after start date', 'error');
                }
            }
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert show alert-' + type;
            alertBox.textContent = message;
            
            setTimeout(() => {
                alertBox.className = 'alert';
            }, 5000);
        }

        function submitLeave(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('action', 'apply_leave');

            // Validate days
            const days = parseInt(document.getElementById('days_count').value);
            if (days <= 0) {
                showAlert('❌ Please select valid dates', 'error');
                return;
            }

            // Disable submit button
            const submitBtn = event.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Submitting...';

            fetch('leave.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;

                if (data.success) {
                    showAlert('✓ Leave application submitted successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message || '❌ Failed to submit leave application', 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                showAlert('❌ Connection error! Please try again.', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>