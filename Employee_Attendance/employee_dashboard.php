<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'employee') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
$conn = getDBConnection();

$empId = $_SESSION['user_id'];
$today = date('Y-m-d');
$currentMonth = date('Y-m');

// Get employee details
$empSql = "SELECT e.*, d.dept_name 
           FROM employees e 
           LEFT JOIN departments d ON e.dept_id = d.dept_id 
           WHERE e.emp_id = $empId";
$empResult = $conn->query($empSql);
$employee = $empResult->fetch_assoc();

// Get today's attendance status
$todayAttSql = "SELECT * FROM attendance WHERE emp_id = $empId AND date = '$today'";
$todayAttResult = $conn->query($todayAttSql);
$todayAttendance = $todayAttResult->fetch_assoc();

// Get monthly statistics
$statsSql = "SELECT 
    COUNT(CASE WHEN status IN ('present', 'late') THEN 1 END) as present_days,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
    COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as leave_days
    FROM attendance 
    WHERE emp_id = $empId AND DATE_FORMAT(date, '%Y-%m') = '$currentMonth'";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

// Get leave balance
$leaveSql = "SELECT * FROM leave_balance WHERE emp_id = $empId AND year = YEAR(CURDATE())";
$leaveResult = $conn->query($leaveSql);
$leaveBalance = $leaveResult->fetch_assoc();
$totalLeave = ($leaveBalance['sick_leave'] ?? 0) + ($leaveBalance['casual_leave'] ?? 0) + ($leaveBalance['earned_leave'] ?? 0);

// Get recent attendance history
$historySql = "SELECT * FROM attendance 
               WHERE emp_id = $empId 
               ORDER BY date DESC 
               LIMIT 10";
$historyResult = $conn->query($historySql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - <?php echo $employee['first_name']; ?></title>
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

        .navbar-left {
            display: flex;
            flex-direction: column;
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar .user-details {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-leave {
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

        .btn-leave:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .date-display {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 25px;
            font-weight: 600;
            color: #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .attendance-clock-section {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            margin-bottom: 30px;
        }

        .current-time {
            font-size: 56px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }

        .attendance-status {
            padding: 15px 30px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 400px;
            font-weight: 600;
            font-size: 16px;
        }

        .status-checked-in {
            background: #d4edda;
            color: #155724;
        }

        .status-not-checked-in {
            background: #fff3cd;
            color: #856404;
        }

        .status-checked-out {
            background: #d1ecf1;
            color: #0c5460;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-attendance {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-checkin {
            background: #28a745;
            color: white;
        }

        .btn-checkin:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-checkout {
            background: #ffc107;
            color: #333;
        }

        .btn-checkout:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-attendance:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
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

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <h1>👤 Employee Dashboard</h1>
            <div class="user-details">
                <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?> 
                (<?php echo $employee['emp_code']; ?>) - 
                <?php echo $employee['dept_name']; ?>
            </div>
        </div>
        <div class="navbar-right">
            <a href="employee_leave.php" class="btn-leave">🏖️ Apply Leave</a>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="container">
        <div id="alertBox" class="alert"></div>

        <div class="date-display">
            📅 Today: <?php echo date('l, F d, Y'); ?>
        </div>

        <!-- Attendance Clock Section -->
        <div class="attendance-clock-section">
            <h2>🕐 Attendance Tracker</h2>
            <div class="current-time" id="currentTime">--:--:--</div>
            
            <div class="attendance-status" id="attendanceStatus">
                <?php if ($todayAttendance): ?>
                    <?php if ($todayAttendance['check_out']): ?>
                        <span class="status-checked-out">
                            ✓ Checked Out at <?php echo date('h:i A', strtotime($todayAttendance['check_out'])); ?>
                        </span>
                    <?php else: ?>
                        <span class="status-checked-in">
                            ✓ Checked In at <?php echo date('h:i A', strtotime($todayAttendance['check_in'])); ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="status-not-checked-in">⏰ Not Checked In Yet</span>
                <?php endif; ?>
            </div>

            <div class="btn-group">
                <button class="btn-attendance btn-checkin" id="checkInBtn" onclick="checkIn()" 
                    <?php echo $todayAttendance ? 'disabled' : ''; ?>>
                    ✓ Check In
                </button>
                <button class="btn-attendance btn-checkout" id="checkOutBtn" onclick="checkOut()"
                    <?php echo (!$todayAttendance || $todayAttendance['check_out']) ? 'disabled' : ''; ?>>
                    ✓ Check Out
                </button>
            </div>
        </div>

        <!-- Monthly Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Present Days</h3>
                <div class="stat-value"><?php echo $stats['present_days'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Late Arrivals</h3>
                <div class="stat-value"><?php echo $stats['late_days'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Leave Balance</h3>
                <div class="stat-value"><?php echo $totalLeave; ?></div>
            </div>
            <div class="stat-card">
                <h3>Absent Days</h3>
                <div class="stat-value"><?php echo $stats['absent_days'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="section">
            <h2 class="section-title">📊 My Attendance History</h2>
            
            <?php if ($historyResult && $historyResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Work Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($history = $historyResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($history['date'])); ?></td>
                        <td><?php echo $history['check_in'] ? date('h:i A', strtotime($history['check_in'])) : '-'; ?></td>
                        <td><?php echo $history['check_out'] ? date('h:i A', strtotime($history['check_out'])) : '-'; ?></td>
                        <td><?php echo $history['work_hours'] ? $history['work_hours'] . ' hrs' : '-'; ?></td>
                        <td>
                            <?php 
                            if ($history['status'] == 'present') {
                                echo '<span class="badge badge-success">Present</span>';
                            } elseif ($history['status'] == 'late') {
                                echo '<span class="badge badge-warning">Late</span>';
                            } elseif ($history['status'] == 'on_leave') {
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
                <p>No attendance history available</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Leave Balance Details -->
        <div class="section">
            <h2 class="section-title">🏖️ Leave Balance Details</h2>
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Available</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sick Leave</td>
                        <td><strong><?php echo $leaveBalance['sick_leave'] ?? 0; ?></strong> days</td>
                    </tr>
                    <tr>
                        <td>Casual Leave</td>
                        <td><strong><?php echo $leaveBalance['casual_leave'] ?? 0; ?></strong> days</td>
                    </tr>
                    <tr>
                        <td>Earned Leave</td>
                        <td><strong><?php echo $leaveBalance['earned_leave'] ?? 0; ?></strong> days</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Update clock every second
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('currentTime').textContent = hours + ':' + minutes + ':' + seconds;
        }

        // Start clock
        updateClock();
        setInterval(updateClock, 1000);

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

        function checkIn() {
            if (!confirm('Do you want to check in now?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'check_in');

            fetch('attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✓ Checked in successfully at ' + data.time, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message || 'Failed to check in', 'error');
                }
            })
            .catch(error => {
                showAlert('Connection error! Please try again.', 'error');
                console.error('Error:', error);
            });
        }

        function checkOut() {
            if (!confirm('Do you want to check out now?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'check_out');

            fetch('attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✓ Checked out successfully at ' + data.time + '. Have a great day!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message || 'Failed to check out', 'error');
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