<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'apply_leave':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            $leaveType = mysqli_real_escape_string($conn, trim($_POST['leave_type']));
            $startDate = mysqli_real_escape_string($conn, $_POST['start_date']);
            $endDate = mysqli_real_escape_string($conn, $_POST['end_date']);
            $reason = mysqli_real_escape_string($conn, trim($_POST['reason']));
            
            // Calculate days
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $daysCount = $end->diff($start)->days + 1;

            // Check if sufficient leave balance
            $balanceSql = "SELECT {$leaveType}_leave as balance FROM leave_balance 
                          WHERE emp_id = $empId AND year = YEAR(CURDATE())";
            $balanceResult = $conn->query($balanceSql);
            
            if ($balanceResult && $balanceResult->num_rows > 0) {
                $balance = $balanceResult->fetch_assoc()['balance'];
                
                if ($balance < $daysCount) {
                    echo json_encode([
                        'success' => false, 
                        'message' => "Insufficient leave balance. You have only $balance days available."
                    ]);
                    exit;
                }
            }
            
            // Insert leave application
            $sql = "INSERT INTO leave_applications 
                    (emp_id, leave_type, start_date, end_date, days_count, reason, status, applied_date) 
                    VALUES ($empId, '$leaveType', '$startDate', '$endDate', $daysCount, '$reason', 'pending', NOW())";
            
            if ($conn->query($sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Leave application submitted successfully',
                    'leave_id' => $conn->insert_id
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to submit leave: ' . $conn->error
                ]);
            }
        }
        break;
        
    case 'approve_leave':
        if ($_SESSION['user_type'] == 'admin') {
            $leaveId = (int)$_POST['leave_id'];
            $adminId = $_SESSION['user_id'];
            $status = mysqli_real_escape_string($conn, $_POST['status']); // 'approved' or 'rejected'
            $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
            
            // Get leave details first
            $leaveSql = "SELECT emp_id, leave_type, days_count FROM leave_applications WHERE leave_id = $leaveId";
            $leaveResult = $conn->query($leaveSql);
            
            if (!$leaveResult || $leaveResult->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Leave application not found']);
                exit;
            }
            
            $leave = $leaveResult->fetch_assoc();
            
            // Update leave application status
            $updateSql = "UPDATE leave_applications 
                         SET status = '$status', 
                             approved_by = $adminId, 
                             approval_date = NOW(),
                             admin_remarks = '$remarks'
                         WHERE leave_id = $leaveId";
            
            if ($conn->query($updateSql)) {
                // If approved, deduct from leave balance
                if ($status == 'approved') {
                    $leaveColumn = $leave['leave_type'] . '_leave';
                    $updateBalanceSql = "UPDATE leave_balance 
                                        SET $leaveColumn = $leaveColumn - {$leave['days_count']}
                                        WHERE emp_id = {$leave['emp_id']} AND year = YEAR(CURDATE())";
                    $conn->query($updateBalanceSql);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Leave request $status successfully",
                    'status' => $status
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to update leave status: ' . $conn->error
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        }
        break;

    case 'get_employee_leaves':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            
            $sql = "SELECT la.*, 
                    CONCAT(a.username) as approved_by_name
                    FROM leave_applications la
                    LEFT JOIN admin a ON la.approved_by = a.admin_id
                    WHERE la.emp_id = $empId 
                    ORDER BY la.applied_date DESC";
            
            $result = $conn->query($sql);
            $leaves = [];
            
            while ($row = $result->fetch_assoc()) {
                $leaves[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'leaves' => $leaves
            ]);
        }
        break;

    case 'get_leave_balance':
        if ($_SESSION['user_type'] == 'employee') {
            $empId = $_SESSION['user_id'];
            
            $sql = "SELECT * FROM leave_balance 
                   WHERE emp_id = $empId AND year = YEAR(CURDATE())";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $balance = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'balance' => $balance
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Leave balance not found'
                ]);
            }
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
ob_end_flush();
?>