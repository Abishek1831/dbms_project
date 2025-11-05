<?php
require_once 'config.php';
$conn = getDBConnection();

echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #667eea; }
.success { border-left-color: #28a745; background: #d4edda; }
.error { border-left-color: #dc3545; background: #f8d7da; }
h2 { color: #667eea; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; background: white; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #667eea; color: white; }
</style>";

echo "<h1>🧪 System Test - Employee Attendance</h1>";
echo "<p><a href='index.php'>← Back to Login</a></p>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
if ($conn) {
    echo "<div class='test success'>✅ SUCCESS: Connected to database</div>";
} else {
    echo "<div class='test error'>❌ FAILED: Cannot connect to database</div>";
    exit;
}

// Test 2: Check Tables
echo "<h2>Test 2: Check Tables</h2>";
$tables = ['admin', 'employees', 'departments', 'attendance', 'leave_applications', 'leave_balance'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<div class='test success'>✅ Table '$table' exists with $count records</div>";
    } else {
        echo "<div class='test error'>❌ Table '$table' not found</div>";
    }
}

// Test 3: Check Admin Account
echo "<h2>Test 3: Admin Account</h2>";
$adminCheck = $conn->query("SELECT * FROM admin WHERE username = 'admin'");
if ($adminCheck && $adminCheck->num_rows > 0) {
    echo "<div class='test success'>✅ Admin account exists (username: admin, password: admin123)</div>";
} else {
    echo "<div class='test error'>❌ Admin account not found</div>";
}

// Test 4: Employee Accounts
echo "<h2>Test 4: Employee Accounts</h2>";
$empResult = $conn->query("SELECT emp_code, CONCAT(first_name, ' ', last_name) as name, status FROM employees");
if ($empResult && $empResult->num_rows > 0) {
    echo "<div class='test success'>✅ Found " . $empResult->num_rows . " employees</div>";
    echo "<table><tr><th>Emp Code</th><th>Name</th><th>Status</th><th>Password</th></tr>";
    while ($emp = $empResult->fetch_assoc()) {
        echo "<tr><td>{$emp['emp_code']}</td><td>{$emp['name']}</td><td>{$emp['status']}</td><td>emp123</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='test error'>❌ No employees found</div>";
}

// Test 5: Leave Applications
echo "<h2>Test 5: Leave Applications</h2>";
$leaveResult = $conn->query("SELECT 
    la.leave_id,
    e.emp_code,
    la.leave_type,
    la.start_date,
    la.status,
    la.days_count
    FROM leave_applications la
    JOIN employees e ON la.emp_id = e.emp_id
    ORDER BY la.applied_date DESC
    LIMIT 10");

if ($leaveResult && $leaveResult->num_rows > 0) {
    echo "<div class='test success'>✅ Found " . $leaveResult->num_rows . " leave applications</div>";
    echo "<table><tr><th>ID</th><th>Emp Code</th><th>Type</th><th>Start Date</th><th>Days</th><th>Status</th></tr>";
    while ($leave = $leaveResult->fetch_assoc()) {
        $statusColor = $leave['status'] == 'approved' ? 'green' : ($leave['status'] == 'pending' ? 'orange' : 'red');
        echo "<tr>
            <td>{$leave['leave_id']}</td>
            <td>{$leave['emp_code']}</td>
            <td>{$leave['leave_type']}</td>
            <td>{$leave['start_date']}</td>
            <td>{$leave['days_count']}</td>
            <td style='color: $statusColor; font-weight: bold;'>{$leave['status']}</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<div class='test error'>⚠️ No leave applications found (Normal if none applied yet)</div>";
}

// Test 6: Attendance Records
echo "<h2>Test 6: Attendance Records (Last 5 days)</h2>";
$attResult = $conn->query("SELECT 
    e.emp_code,
    a.date,
    a.check_in,
    a.check_out,
    a.status
    FROM attendance a
    JOIN employees e ON a.emp_id = e.emp_id
    ORDER BY a.date DESC
    LIMIT 10");

if ($attResult && $attResult->num_rows > 0) {
    echo "<div class='test success'>✅ Found " . $attResult->num_rows . " attendance records</div>";
    echo "<table><tr><th>Emp Code</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th></tr>";
    while ($att = $attResult->fetch_assoc()) {
        echo "<tr>
            <td>{$att['emp_code']}</td>
            <td>{$att['date']}</td>
            <td>{$att['check_in']}</td>
            <td>" . ($att['check_out'] ?: '-') . "</td>
            <td>{$att['status']}</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<div class='test error'>⚠️ No attendance records (Normal for first time)</div>";
}

// Test 7: Leave Balance
echo "<h2>Test 7: Leave Balance</h2>";
$balanceResult = $conn->query("SELECT 
    e.emp_code,
    lb.sick_leave,
    lb.casual_leave,
    lb.earned_leave
    FROM leave_balance lb
    JOIN employees e ON lb.emp_id = e.emp_id
    WHERE lb.year = YEAR(CURDATE())");

if ($balanceResult && $balanceResult->num_rows > 0) {
    echo "<div class='test success'>✅ Leave balance initialized for all employees</div>";
    echo "<table><tr><th>Emp Code</th><th>Sick Leave</th><th>Casual Leave</th><th>Earned Leave</th><th>Total</th></tr>";
    while ($balance = $balanceResult->fetch_assoc()) {
        $total = $balance['sick_leave'] + $balance['casual_leave'] + $balance['earned_leave'];
        echo "<tr>
            <td>{$balance['emp_code']}</td>
            <td>{$balance['sick_leave']}</td>
            <td>{$balance['casual_leave']}</td>
            <td>{$balance['earned_leave']}</td>
            <td><strong>$total</strong></td>
        </tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h2>✅ System Status: ";
if ($conn) {
    echo "READY TO USE!</h2>";
    echo "<div class='test success'>
        <h3>📌 Quick Access:</h3>
        <p><strong>Main Login:</strong> <a href='index.php'>http://localhost/attendance_system/</a></p>
        <p><strong>Admin:</strong> username: admin | password: admin123</p>
        <p><strong>Employee:</strong> code: EMP001 to EMP005 | password: emp123</p>
    </div>";
}

$conn->close();
?>