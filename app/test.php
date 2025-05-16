<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    include 'db.php';
    echo "<p>Database connection successful!</p>";
    
    // Test if sales table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'sales'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Sales table exists!</p>";
        
        // Count records
        $stmt = $conn->query("SELECT COUNT(*) as count FROM sales");
        $result = $stmt->fetch();
        echo "<p>Number of sales records: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color:red'>Sales table does not exist!</p>";
    }
    
    // Test if installments table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'installments'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Installments table exists!</p>";
        
        // Count records
        $stmt = $conn->query("SELECT COUNT(*) as count FROM installments");
        $result = $stmt->fetch();
        echo "<p>Number of installment records: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color:red'>Installments table does not exist!</p>";
    }
    
    // Test if customers table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Customers table exists!</p>";
        
        // Count records
        $stmt = $conn->query("SELECT COUNT(*) as count FROM customers");
        $result = $stmt->fetch();
        echo "<p>Number of customer records: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color:red'>Customers table does not exist!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test includes
echo "<h2>Testing includes:</h2>";
if (file_exists('includes/topbar.php')) {
    echo "<p>topbar.php exists!</p>";
} else {
    echo "<p style='color:red'>topbar.php not found!</p>";
}

if (file_exists('includes/sidebar.php')) {
    echo "<p>sidebar.php exists!</p>";
} else {
    echo "<p style='color:red'>sidebar.php not found!</p>";
}

// Test CSS
echo "<h2>Testing CSS:</h2>";
if (file_exists('../assets/css/styles.css')) {
    echo "<p>styles.css exists!</p>";
    echo "<p>Path: " . realpath('../assets/css/styles.css') . "</p>";
} else {
    echo "<p style='color:red'>styles.css not found!</p>";
    echo "<p>Current directory: " . realpath('.') . "</p>";
}
?> 