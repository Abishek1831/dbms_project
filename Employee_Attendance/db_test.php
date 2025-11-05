<?php
echo "<h2>Database Connection Test</h2>";
echo "<hr>";

// Test 1: Can we connect to MySQL?
echo "<h3>Test 1: MySQL Connection</h3>";
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    echo "❌ FAILED: Cannot connect to MySQL<br>";
    echo "Error: " . $conn->connect_error . "<br>";
    echo "<b>Fix:</b> Make sure MySQL is running in XAMPP<br>";
} else {
    echo "✅ SUCCESS: MySQL connected<br>";
}

// Test 2: Does database exist?
echo "<h3>Test 2: Database Check</h3>";
$db_selected = $conn->select_db('employee_attendance');

if (!$db_selected) {
    echo "❌ FAILED: Database 'employee_attendance' not found<br>";
    echo "<b>Fix:</b> Create database and run SQL script<br>";
} else {
    echo "✅ SUCCESS: Database exists<br>";
}

// Test 3: Check admin table
echo "<h3>Test 3: Admin Table Check</h3>";
$result = $conn->query("SELECT * FROM admin");

if (!$result) {
    echo "❌ FAILED: Admin table not found<br>";
    echo "Error: " . $conn->error . "<br>";
    echo "<b>Fix:</b> Run SQL script to create tables<br>";
} else {
    echo "✅ SUCCESS: Admin table exists<br>";
    echo "Rows found: " . $result->num_rows . "<br>";
}

// Test 4: Check admin user
echo "<h3>Test 4: Admin User Check</h3>";
$admin = $conn->query("SELECT * FROM admin WHERE username = 'admin'");

if ($admin && $admin->num_rows > 0) {
    echo "✅ SUCCESS: Admin user exists<br>";
    $data = $admin->fetch_assoc();
    echo "Username: " . $data['username'] . "<br>";
} else {
    echo "❌ FAILED: Admin user not found<br>";
    echo "<b>Fix:</b> Insert admin user<br>";
}

// Test 5: Test password
echo "<h3>Test 5: Password Verification</h3>";
$pwd_check = $conn->query("SELECT * FROM admin WHERE username = 'admin' AND password = MD5('admin123')");

if ($pwd_check && $pwd_check->num_rows > 0) {
    echo "✅ SUCCESS: Password matches!<br>";
} else {
    echo "❌ FAILED: Password doesn't match<br>";
    echo "<b>Fix:</b> Reset admin password<br>";
}

$conn->close();

echo "<hr>";
echo "<h3>Summary</h3>";
echo "If all tests show ✅, your database is ready!<br>";
echo "If any test shows ❌, follow the fix mentioned.<br>";
?>