<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'config.php';

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register_employee':
        if ($_SESSION['user_type'] != 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $empCode = mysqli_real_escape_string($conn, strtoupper(trim($_POST['emp_code'])));
        $firstName = mysqli_real_escape_string($conn, trim($_POST['first_name']));
        $lastName = mysqli_real_escape_string($conn, trim($_POST['last_name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $deptId = (int)$_POST['dept_id'];
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $joiningDate = mysqli_real_escape_string($conn, $_POST['joining_date']);
        $shiftId = (int)$_POST['shift_id'];

        // Check if employee code already exists
        $checkSql = "SELECT emp_id FROM employees WHERE emp_code = '$empCode'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Employee code already exists']);
            exit;
        }

        // Check if email already exists
        $emailCheckSql = "SELECT emp_id FROM employees WHERE email = '$email'";
        $emailCheckResult = $conn->query($emailCheckSql);
        
        if ($emailCheckResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        // Insert employee
        $insertSql = "INSERT INTO employees 
                      (emp_code, first_name, last_name, email, phone, dept_id, password, status, joining_date) 
                      VALUES 
                      ('$empCode', '$firstName', '$lastName', '$email', '$phone', $deptId, MD5('$password'), '$status', '$joiningDate')";
        
        if ($conn->query($insertSql)) {
            $empId = $conn->insert_id;

            // Assign shift
            $shiftSql = "INSERT INTO employee_shifts (emp_id, shift_id, effective_date) 
                        VALUES ($empId, $shiftId, '$joiningDate')";
            $conn->query($shiftSql);

            // Create leave balance
            $year = date('Y');
            $leaveSql = "INSERT INTO leave_balance (emp_id, year, sick_leave, casual_leave, earned_leave) 
                        VALUES ($empId, $year, 10, 10, 10)";
            $conn->query($leaveSql);

            echo json_encode([
                'success' => true, 
                'message' => 'Employee registered successfully',
                'emp_id' => $empId,
                'emp_code' => $empCode
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to register employee: ' . $conn->error
            ]);
        }
        break;

    case 'get_all_employees':
        if ($_SESSION['user_type'] != 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $sql = "SELECT e.*, d.dept_name, s.shift_name 
                FROM employees e 
                LEFT JOIN departments d ON e.dept_id = d.dept_id 
                LEFT JOIN employee_shifts es ON e.emp_id = es.emp_id 
                LEFT JOIN shifts s ON es.shift_id = s.shift_id 
                ORDER BY e.emp_code";
        
        $result = $conn->query($sql);
        $employees = [];
        
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        echo json_encode([
            'success' => true,
            'employees' => $employees
        ]);
        break;

    case 'update_employee_status':
        if ($_SESSION['user_type'] != 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $empId = (int)$_POST['emp_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $sql = "UPDATE employees SET status = '$status' WHERE emp_id = $empId";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
ob_end_flush();
?>