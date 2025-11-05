<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
$conn = getDBConnection();

// Get departments for dropdown
$deptSql = "SELECT * FROM departments ORDER BY dept_name";
$deptResult = $conn->query($deptSql);

// Get shifts for dropdown
$shiftSql = "SELECT * FROM shifts ORDER BY shift_name";
$shiftResult = $conn->query($shiftSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Employee</title>
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

        .nav-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
        }

        .nav-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .section {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 24px;
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
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

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>➕ Register New Employee</h1>
        <a href="admin_dashboard.php" class="nav-btn">← Back to Dashboard</a>
    </div>

    <div class="container">
        <div class="section">
            <h2 class="section-title">👤 Employee Registration Form</h2>
            
            <div id="alertBox" class="alert"></div>

            <form id="registerForm" onsubmit="registerEmployee(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Employee Code <span class="required">*</span></label>
                        <input type="text" name="emp_code" required placeholder="e.g., EMP006" 
                               pattern="[A-Z0-9]+" title="Use uppercase letters and numbers only">
                        <div class="help-text">Format: EMP + Number (e.g., EMP006)</div>
                    </div>

                    <div class="form-group">
                        <label>Department <span class="required">*</span></label>
                        <select name="dept_id" required>
                            <option value="">Select Department</option>
                            <?php while($dept = $deptResult->fetch_assoc()): ?>
                            <option value="<?php echo $dept['dept_id']; ?>">
                                <?php echo $dept['dept_name']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" required placeholder="First Name">
                    </div>

                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" required placeholder="Last Name">
                    </div>

                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="employee@company.com">
                    </div>

                    <div class="form-group">
                        <label>Phone <span class="required">*</span></label>
                        <input type="tel" name="phone" required placeholder="10-digit number" 
                               pattern="[0-9]{10}" title="Enter 10 digit phone number">
                    </div>

                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" name="password" required placeholder="Minimum 6 characters" 
                               minlength="6">
                        <div class="help-text">Minimum 6 characters</div>
                    </div>

                    <div class="form-group">
                        <label>Joining Date <span class="required">*</span></label>
                        <input type="date" name="joining_date" required>
                    </div>

                    <div class="form-group">
                        <label>Shift <span class="required">*</span></label>
                        <select name="shift_id" required>
                            <option value="">Select Shift</option>
                            <?php while($shift = $shiftResult->fetch_assoc()): ?>
                            <option value="<?php echo $shift['shift_id']; ?>">
                                <?php echo $shift['shift_name']; ?> 
                                (<?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                                 <?php echo date('h:i A', strtotime($shift['end_time'])); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-submit">✓ Register Employee</button>
            </form>
        </div>
    </div>

    <script>
        // Set max date to today
        document.querySelector('input[name="joining_date"]').setAttribute('max', 
            new Date().toISOString().split('T')[0]);

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = 'alert show alert-' + type;
            alertBox.textContent = message;
            
            setTimeout(() => {
                alertBox.className = 'alert';
            }, 5000);
        }

        function registerEmployee(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            formData.append('action', 'register_employee');

            fetch('employee_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✓ Employee registered successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php';
                    }, 2000);
                } else {
                    showAlert(data.message || 'Failed to register employee', 'error');
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