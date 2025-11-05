<?php
echo "<h2>🔍 Database Connection Test</h2><hr>";

// Test 1: Basic Connection
echo "<h3>Test 1: MySQL Connection</h3>";
$conn = @new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    echo "❌ FAILED: " . $conn->connect_error . "<br>";
    die();
}
echo "✅ MySQL Connected<br><br>";

// Test 2: Select Database
echo "<h3>Test 2: Select Database</h3>";
if (!$conn->select_db('employee_attendance')) {
    echo "❌ FAILED: Database not found<br>";
    echo "Error: " . $conn->error . "<br>";
    die();
}
echo "✅ Database Selected<br><br>";

// Test 3: Check Admin
echo "<h3>Test 3: Check Admin User</h3>";
$result = $conn->query("SELECT * FROM admin WHERE username = 'admin'");
if (!$result) {
    echo "❌ FAILED: " . $conn->error . "<br>";
    die();
}
if ($result->num_rows > 0) {
    echo "✅ Admin exists!<br>";
    $admin = $result->fetch_assoc();
    echo "Username: " . $admin['username'] . "<br>";
    echo "Admin ID: " . $admin['admin_id'] . "<br>";
} else {
    echo "❌ Admin not found<br>";
}
echo "<br>";

// Test 4: Test Login
echo "<h3>Test 4: Test Admin Login</h3>";
$login = $conn->query("SELECT * FROM admin WHERE username = 'admin' AND password = MD5('admin123')");
if ($login && $login->num_rows > 0) {
    echo "✅ Login credentials CORRECT!<br>";
} else {
    echo "❌ Login credentials WRONG!<br>";
}
echo "<br>";

// Test 5: Check Employees
echo "<h3>Test 5: Check Employees</h3>";
$emp = $conn->query("SELECT * FROM employees");
if ($emp && $emp->num_rows > 0) {
    echo "✅ Employees found: " . $emp->num_rows . "<br>";
    while ($e = $emp->fetch_assoc()) {
        echo "- " . $e['emp_code'] . ": " . $e['first_name'] . " " . $e['last_name'] . "<br>";
    }
} else {
    echo "❌ No employees found<br>";
}

$conn->close();

echo "<hr>";
echo "<h3>✅ If all tests pass, database is ready!</h3>";
echo "<a href='index.php'>Go to Login Page</a>";
?>