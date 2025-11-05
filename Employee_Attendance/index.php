<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: employee_dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance Management System</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-type-selector {
            display: flex;
            background: #f5f5f5;
            margin: 20px 30px;
            border-radius: 10px;
            padding: 5px;
        }
        
        .login-type-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }
        
        .login-type-btn.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-container {
            padding: 30px;
        }
        
        .login-form {
            display: none;
            animation: slideIn 0.3s ease-in-out;
        }
        
        .login-form.active {
            display: block;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .demo-credentials {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
        }
        
        .demo-credentials h4 {
            margin-bottom: 8px;
            color: #333;
        }
        
        .demo-credentials p {
            margin: 3px 0;
        }

        .alert {
            padding: 12px;
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

        .loading {
            display: none;
            text-align: center;
            padding: 10px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Attendance Portal</h1>
            <p>Employee Management System</p>
        </div>
        
        <div class="login-type-selector">
            <button class="login-type-btn active" onclick="switchLoginType('admin')">
                👨‍💼 Admin Login
            </button>
            <button class="login-type-btn" onclick="switchLoginType('employee')">
                👤 Employee Login
            </button>
        </div>
        
        <div class="form-container">
            <!-- Alert Messages -->
            <div id="alertBox" class="alert"></div>
            
            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Logging in...</p>
            </div>

            <!-- Admin Login Form -->
            <form id="adminLoginForm" class="login-form active" onsubmit="handleLogin(event, 'admin')">
                <div class="form-group">
                    <label for="admin-username">Username</label>
                    <input type="text" id="admin-username" name="username" required placeholder="Enter admin username">
                </div>
                <div class="form-group">
                    <label for="admin-password">Password</label>
                    <input type="password" id="admin-password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="login-btn">Login as Admin</button>
                
                <div class="demo-credentials">
                    <h4>📌 Demo Admin Login:</h4>
                    <p>Username: <strong>admin</strong></p>
                    <p>Password: <strong>admin123</strong></p>
                </div>
            </form>
            
            <!-- Employee Login Form -->
            <form id="employeeLoginForm" class="login-form" onsubmit="handleLogin(event, 'employee')">
                <div class="form-group">
                    <label for="emp-code">Employee Code</label>
                    <input type="text" id="emp-code" name="emp_code" required placeholder="Enter employee code">
                </div>
                <div class="form-group">
                    <label for="emp-password">Password</label>
                    <input type="password" id="emp-password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="login-btn">Login as Employee</button>
                
                <div class="demo-credentials">
                    <h4>📌 Demo Employee Login:</h4>
                    <p>Emp Code: <strong>EMP001</strong> / <strong>EMP002</strong> / <strong>EMP003</strong></p>
                    <p>Password: <strong>emp123</strong></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Switch between Admin and Employee login
        function switchLoginType(type) {
            const adminBtn = document.querySelectorAll('.login-type-btn')[0];
            const employeeBtn = document.querySelectorAll('.login-type-btn')[1];
            const adminForm = document.getElementById('adminLoginForm');
            const employeeForm = document.getElementById('employeeLoginForm');
            
            // Hide alert when switching
            hideAlert();
            
            if (type === 'admin') {
                adminBtn.classList.add('active');
                employeeBtn.classList.remove('active');
                adminForm.classList.add('active');
                employeeForm.classList.remove('active');
            } else {
                employeeBtn.classList.add('active');
                adminBtn.classList.remove('active');
                employeeForm.classList.add('active');
                adminForm.classList.remove('active');
            }
        }

        // Show alert message
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert show alert-' + type;
            alertBox.textContent = message;
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                hideAlert();
            }, 5000);
        }

        // Hide alert message
        function hideAlert() {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert';
        }

        // Show/Hide loading spinner
        function toggleLoading(show) {
            const spinner = document.getElementById('loadingSpinner');
            const forms = document.querySelectorAll('.login-form');
            
            if (show) {
                spinner.classList.add('show');
                forms.forEach(form => form.style.display = 'none');
            } else {
                spinner.classList.remove('show');
                document.querySelector('.login-form.active').style.display = 'block';
            }
        }

        // Handle login form submission
        function handleLogin(event, type) {
            event.preventDefault();
            
            hideAlert();
            toggleLoading(true);
            
            const formData = new FormData(event.target);
            formData.append('login_type', type);
            
            // Send AJAX request
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                toggleLoading(false);
                
                if (data.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    
                    // Redirect based on user type
                    setTimeout(() => {
                        if (data.type === 'admin') {
                            window.location.href = 'admin_dashboard.php';
                        } else {
                            window.location.href = 'employee_dashboard.php';
                        }
                    }, 1000);
                } else {
                    showAlert(data.message || 'Login failed! Please check your credentials.', 'error');
                }
            })
            .catch(error => {
                toggleLoading(false);
                showAlert('Connection error! Please try again.', 'error');
                console.error('Error:', error);
            });
        }

        // Auto-fill demo credentials on double click
        document.addEventListener('DOMContentLoaded', function() {
            // Admin username double-click
            document.getElementById('admin-username').addEventListener('dblclick', function() {
                this.value = 'admin';
                document.getElementById('admin-password').value = 'admin123';
            });
            
            // Employee code double-click
            document.getElementById('emp-code').addEventListener('dblclick', function() {
                this.value = 'EMP001';
                document.getElementById('emp-password').value = 'emp123';
            });
        });
    </script>
</body>
</html>