<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
$conn = getDBConnection();

$today = date('Y-m-d');

// Get statistics
$totalEmpQuery = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
$totalEmp = $totalEmpQuery->fetch_assoc()['count'];

$presentQuery = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND (status = 'present' OR status = 'late')");
$presentToday = $presentQuery->fetch_assoc()['count'];

$onLeaveQuery = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'on_leave'");
$onLeave = $onLeaveQuery->fetch_assoc()['count'];

$absent = $totalEmp - $presentToday - $onLeave;

// Get today's attendance list
$attendanceSql = "SELECT 
    e.emp_code,
    CONCAT(e.first_name, ' ', e.last_name) as emp_name,
    d.dept_name,
    a.check_in,
    a.check_out,
    a.status,
    a.work_hours
    FROM employees e
    LEFT JOIN attendance a ON e.emp_id = a.emp_id AND a.date = '$today'
    LEFT JOIN departments d ON e.dept_id = d.dept_id
    WHERE e.status = 'active'
    ORDER BY a.check_in DESC";
$attendanceResult = $conn->query($attendanceSql);

// Get pending leave requests
$leaveSql = "SELECT 
    la.leave_id,
    CONCAT(e.first_name, ' ', e.last_name) as emp_name,
    e.emp_code,
    la.leave_type,
    la.start_date,
    la.end_date,
    la.days_count,
    la.reason,
    la.applied_date
    FROM leave_applications la
    JOIN employees e ON la.emp_id = e.emp_id
    WHERE la.status = 'pending'
    ORDER BY la.applied_date DESC";
$leaveResult = $conn->query($leaveSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-register {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-register:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .logout-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .stat-icon {
            font-size: 40px;
            float: right;
            opacity: 0.3;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
        }

        .refresh-btn {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            background: #5568d3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f8f9fa;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .date-display {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
            color: #667eea;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👨‍💼 Admin Dashboard</h1>
        <div class="user-info">
            <a href="register_employee.php" class="btn-register">➕ Register New Employee</a>
            <span>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="container">
        <div id="alertBox" class="alert"></div>

        <div class="date-display">
            📅 Today: <?php echo date('l, F d, Y'); ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">👥</span>
                <h3>Total Employees</h3>
                <div class="stat-value"><?php echo $totalEmp; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">✅</span>
                <h3>Present Today</h3>
                <div class="stat-value"><?php echo $presentToday; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🏖️</span>
                <h3>On Leave</h3>
                <div class="stat-value"><?php echo $onLeave; ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">❌</span>
                <h3>Absent</h3>
                <div class="stat-value"><?php echo $absent; ?></div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📊 Today's Attendance Overview</h2>
                <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
            </div>

            <?php if ($attendanceResult && $attendanceResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Emp Code</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $attendanceResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['emp_code']; ?></td>
                        <td><?php echo $row['emp_name']; ?></td>
                        <td><?php echo $row['dept_name']; ?></td>
                        <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                        <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                        <td><?php echo $row['work_hours'] ? $row['work_hours'] . ' hrs' : '-'; ?></td>
                        <td>
                            <?php 
                            if ($row['status'] == 'present') {
                                echo '<span class="badge badge-success">Present</span>';
                            } elseif ($row['status'] == 'late') {
                                echo '<span class="badge badge-warning">Late</span>';
                            } elseif ($row['status'] == 'on_leave') {
                                echo '<span class="badge badge-info">On Leave</span>';
                            } else {
                                echo '<span class="badge badge-danger">Absent</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No attendance records for today</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pending Leave Requests -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📝 Pending Leave Requests</h2>
            </div>

            <?php if ($leaveResult && $leaveResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Emp Code</th>
                        <th>Employee Name</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($leave = $leaveResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $leave['emp_code']; ?></td>
                        <td><?php echo $leave['emp_name']; ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst($leave['leave_type']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                        <td><?php echo $leave['days_count']; ?></td>
                        <td><?php echo substr($leave['reason'], 0, 50) . '...'; ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-approve" onclick="handleLeave(<?php echo $leave['leave_id']; ?>, 'approved')">✓ Approve</button>
                                <button class="btn btn-reject" onclick="handleLeave(<?php echo $leave['leave_id']; ?>, 'rejected')">✗ Reject</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <p>No pending leave requests</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        function handleLeave(leaveId, status) {
            const action = status === 'approved' ? 'approve' : 'reject';
            
            if (!confirm(`Are you sure you want to ${action} this leave request?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'approve_leave');
            formData.append('leave_id', leaveId);
            formData.append('status', status);
            formData.append('remarks', '');

            fetch('leave.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(`Leave request ${status} successfully!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Failed to process leave request', 'error');
                }
            })
            .catch(error => {
                showAlert('Connection error! Please try again.', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>