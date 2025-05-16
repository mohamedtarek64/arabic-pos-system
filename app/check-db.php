<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSS for better display
echo '<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
    h1, h2 { color: #333; }
    .error { color: red; font-weight: bold; }
    .success { color: green; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>';

echo '<h1>Database Structure Check</h1>';

try {
    // Include database connection
    include 'db.php';
    echo '<p class="success">Database connection successful!</p>';
    
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<h2>Database Tables:</h2>';
    if (count($tables) > 0) {
        echo '<ul>';
        foreach ($tables as $table) {
            echo '<li>' . $table . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="error">No tables found in the database!</p>';
    }
    
    // Check specific tables
    $requiredTables = ['sales', 'installments', 'customers'];
    echo '<h2>Required Tables:</h2>';
    echo '<table>';
    echo '<tr><th>Table</th><th>Status</th><th>Record Count</th></tr>';
    
    foreach ($requiredTables as $table) {
        echo '<tr>';
        echo '<td>' . $table . '</td>';
        
        // Check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo '<td class="success">Exists</td>';
            
            // Count records
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo '<td>' . $result['count'] . '</td>';
            
            // Show table structure
            $tableStructure[$table] = [];
            $stmt = $conn->query("DESCRIBE $table");
            while ($row = $stmt->fetch()) {
                $tableStructure[$table][] = $row;
            }
        } else {
            echo '<td class="error">Missing</td>';
            echo '<td>N/A</td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    
    // Display table structures
    if (!empty($tableStructure)) {
        echo '<h2>Table Structures:</h2>';
        
        foreach ($tableStructure as $table => $columns) {
            echo '<h3>Table: ' . $table . '</h3>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . $column['Field'] . '</td>';
                echo '<td>' . $column['Type'] . '</td>';
                echo '<td>' . $column['Null'] . '</td>';
                echo '<td>' . $column['Key'] . '</td>';
                echo '<td>' . ($column['Default'] === null ? 'NULL' : $column['Default']) . '</td>';
                echo '<td>' . $column['Extra'] . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
    }
    
} catch (PDOException $e) {
    echo '<p class="error">Database Error: ' . $e->getMessage() . '</p>';
    echo '<p>Check that your MySQL server is running and that the database "store" exists.</p>';
    echo '<p>Current database settings:</p>';
    echo '<ul>';
    echo '<li>Host: localhost</li>';
    echo '<li>Database: store</li>';
    echo '<li>Username: root</li>';
    echo '<li>Password: (empty)</li>';
    echo '</ul>';
}
?> 