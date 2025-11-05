<?php
// Disable any output before JSON
ob_start();

// Error reporting OFF for production (important for JSON)
error_reporting(0);
ini_set('display_errors', 0);

// Start session first
session_start();

// Include config
require_once 'config.php';

// Clean any output buffer
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $loginType = isset($_POST['login_type']) ? $_POST['login_type'] : '';
    
    if (empty($loginType)) {
        echo json_encode([
            'success' => false,
            'message' => 'Login type not specified'
        ]);
        exit;
    }
    
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }
    
    if ($loginType == 'admin') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        
        $sql = "SELECT * FROM admin WHERE username = '$username' AND password = MD5('$password')";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            
            echo json_encode([
                'success' => true,
                'type' => 'admin',
                'message' => 'Login successful'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid admin credentials'
            ]);
        }
    } else {
        // Employee login
        $empCode = mysqli_real_escape_string($conn, $_POST['emp_code']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        
        $sql = "SELECT e.*, d.dept_name 
                FROM employees e 
                LEFT JOIN departments d ON e.dept_id = d.dept_id 
                WHERE e.emp_code = '$empCode' AND e.password = MD5('$password') 
                AND e.status = 'active'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            $_SESSION['user_type'] = 'employee';
            $_SESSION['user_id'] = $employee['emp_id'];
            $_SESSION['emp_code'] = $employee['emp_code'];
            $_SESSION['emp_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            
            echo json_encode([
                'success' => true,
                'type' => 'employee',
                'message' => 'Login successful',
                'employee' => [
                    'name' => $_SESSION['emp_name'],
                    'dept' => $employee['dept_name']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid employee credentials'
            ]);
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}

// End output buffering
ob_end_flush();
?>