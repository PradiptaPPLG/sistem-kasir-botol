<?php
// test_dashboard.php
echo "<h1>Testing Dashboard...</h1>";

// Test includes
echo "<h3>1. Testing includes...</h3>";
if (file_exists('includes/config.php')) {
    echo "✅ config.php found<br>";
} else {
    echo "❌ config.php NOT found<br>";
}

if (file_exists('includes/auth.php')) {
    echo "✅ auth.php found<br>";
} else {
    echo "❌ auth.php NOT found<br>";
}

if (file_exists('includes/database.php')) {
    echo "✅ database.php found<br>";
} else {
    echo "❌ database.php NOT found<br>";
}

if (file_exists('includes/functions.php')) {
    echo "✅ functions.php found<br>";
} else {
    echo "❌ functions.php NOT found<br>";
}

// Test session
echo "<h3>2. Testing session...</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";

// Test database connection
echo "<h3>3. Testing database...</h3>";
try {
    $conn = new mysqli('localhost', 'root', '', 'sistem_toko_botol');
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error;
    } else {
        echo "✅ Database connected successfully!<br>";
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) as total FROM karyawan");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Total karyawan: " . $row['total'] . "<br>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}

echo "<h3>4. Testing complete!</h3>";
echo "<a href='dashboard.php'>Go to Dashboard</a>";
?>