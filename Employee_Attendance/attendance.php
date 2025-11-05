<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = getDBConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_in':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            $today = date('Y-m-d');
            $checkInTime = date('H:i:s');
            
            // Check if already checked in today
            $checkSql = "SELECT * FROM attendance WHERE emp_id = $empId AND date = '$today'";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Already checked in today']);
            } else {
                // Get shift timing to determine status
                $shiftSql = "SELECT s.start_time, s.grace_period 
                            FROM employee_shifts es 
                            JOIN shifts s ON es.shift_id = s.shift_id 
                            WHERE es.emp_id = $empId 
                            ORDER BY es.effective_date DESC LIMIT 1";
                $shiftResult = $conn->query($shiftSql);
                
                $status = 'present';
                if ($shiftResult->num_rows > 0) {
                    $shift = $shiftResult->fetch_assoc();
                    $startTime = strtotime($shift['start_time']);
                    $currentTime = strtotime($checkInTime);
                    $gracePeriod = $shift['grace_period'] * 60; // Convert to seconds
                    
                    if ($currentTime > ($startTime + $gracePeriod)) {
                        $status = 'late';
                    }
                }
                
                $sql = "INSERT INTO attendance (emp_id, date, check_in, status) 
                        VALUES ($empId, '$today', '$checkInTime', '$status')";
                
                if ($conn->query($sql)) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Checked in successfully',
                        'time' => date('h:i:s A'),
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error']);
                }
            }
        }
        break;
        
    case 'check_out':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            $today = date('Y-m-d');
            $checkOutTime = date('H:i:s');
            
            $sql = "UPDATE attendance 
                    SET check_out = '$checkOutTime', 
                        work_hours = TIMESTAMPDIFF(HOUR, check_in, '$checkOutTime')
                    WHERE emp_id = $empId AND date = '$today' AND check_out IS NULL";
            
            if ($conn->query($sql)) {
                if ($conn->affected_rows > 0) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Checked out successfully',
                        'time' => date('h:i:s A')
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not checked in or already checked out']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;
        
    case 'get_today_status':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            $today = date('Y-m-d');
            
            $sql = "SELECT * FROM attendance WHERE emp_id = $empId AND date = '$today'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $attendance = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'checked_in' => !empty($attendance['check_in']),
                    'checked_out' => !empty($attendance['check_out']),
                    'check_in_time' => $attendance['check_in'],
                    'check_out_time' => $attendance['check_out'],
                    'status' => $attendance['status']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'checked_in' => false,
                    'checked_out' => false
                ]);
            }
        }
        break;
        
    case 'get_employee_stats':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            $currentMonth = date('Y-m');
            
            // Get attendance stats for current month
            $statsSql = "SELECT 
                COUNT(CASE WHEN status = 'present' OR status = 'late' THEN 1 END) as present_days,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as leave_days
                FROM attendance 
                WHERE emp_id = $empId AND DATE_FORMAT(date, '%Y-%m') = '$currentMonth'";
            $statsResult = $conn->query($statsSql);
            $stats = $statsResult->fetch_assoc();
            
            // Get leave balance
            $leaveSql = "SELECT * FROM leave_balance 
                        WHERE emp_id = $empId AND year = YEAR(CURDATE())";
            $leaveResult = $conn->query($leaveSql);
            $leave = $leaveResult->fetch_assoc();
            
            $totalLeave = ($leave['sick_leave'] ?? 0) + ($leave['casual_leave'] ?? 0) + ($leave['earned_leave'] ?? 0);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'present_days' => $stats['present_days'] ?? 0,
                    'absent_days' => $stats['absent_days'] ?? 0,
                    'late_days' => $stats['late_days'] ?? 0,
                    'leave_balance' => $totalLeave
                ]
            ]);
        }
        break;
        
    case 'get_admin_dashboard':
        if ($_SESSION['user_type'] == 'admin') {
            $today = date('Y-m-d');
            
            // Total employees
            $totalEmp = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
            
            // Present today
            $presentToday = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND (status = 'present' OR status = 'late')")->fetch_assoc()['count'];
            
            // On leave
            $onLeave = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'on_leave'")->fetch_assoc()['count'];
            
            // Absent
            $absent = $totalEmp - $presentToday - $onLeave;
            
            // Today's attendance details
            $attendanceSql = "SELECT 
                e.emp_code,
                CONCAT(e.first_name, ' ', e.last_name) as emp_name,
                d.dept_name,
                a.check_in,
                a.check_out,
                a.status
                FROM employees e
                LEFT JOIN attendance a ON e.emp_id = a.emp_id AND a.date = '$today'
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                WHERE e.status = 'active'
                ORDER BY a.check_in ASC";
            $attendanceResult = $conn->query($attendanceSql);
            $attendanceList = [];
            while ($row = $attendanceResult->fetch_assoc()) {
                $attendanceList[] = $row;
            }
            
            // Pending leave requests
            $leaveSql = "SELECT 
                la.leave_id,
                CONCAT(e.first_name, ' ', e.last_name) as emp_name,
                la.leave_type,
                la.start_date,
                la.end_date,
                la.days_count,
                la.reason
                FROM leave_applications la
                JOIN employees e ON la.emp_id = e.emp_id
                WHERE la.status = 'pending'
                ORDER BY la.applied_date DESC";
            $leaveResult = $conn->query($leaveSql);
            $leaveRequests = [];
            while ($row = $leaveResult->fetch_assoc()) {
                $leaveRequests[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_employees' => $totalEmp,
                    'present_today' => $presentToday,
                    'on_leave' => $onLeave,
                    'absent' => $absent
                ],
                'attendance_list' => $attendanceList,
                'leave_requests' => $leaveRequests
            ]);
        }
        break;
}

$conn->close();
?>