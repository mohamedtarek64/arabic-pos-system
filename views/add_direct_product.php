<?php
// Simple script to directly add a test product to the database

// Include database connection
include '../app/db.php';

try {
    // Create a test product with simple name to avoid encoding issues
    $name = "Test Product " . date("Ymd-His");
    $code = "TEST" . rand(1000, 9999);
    $price = 99.99;
    $quantity = 10;
    $barcode = "TEST" . rand(10000000, 99999999);
    
    echo "Attempting to add test product: $name<br>";
    
    // Insert product into database
    $sql = "INSERT INTO products (name, code, price, quantity, barcode) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$name, $code, $price, $quantity, $barcode]);
    
    if ($result) {
        echo "Successfully added test product: $name<br>";
        echo "Product ID: " . $conn->lastInsertId() . "<br>";
    } else {
        echo "Failed to add test product<br>";
    }
    
    // Check if product was added
    $check_sql = "SELECT * FROM products WHERE name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$name]);
    $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "Product found in database:<br>";
        echo "ID: " . $product['id'] . "<br>";
        echo "Name: " . $product['name'] . "<br>";
        echo "Code: " . $product['code'] . "<br>";
        echo "Price: " . $product['price'] . "<br>";
        echo "Quantity: " . $product['quantity'] . "<br>";
    } else {
        echo "Product not found in database after insertion<br>";
    }
    
    // List all products
    echo "<br>All products in database:<br>";
    $all_sql = "SELECT * FROM products";
    $all_stmt = $conn->prepare($all_sql);
    $all_stmt->execute();
    $all_products = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_products as $prod) {
        echo "ID: " . $prod['id'] . ", Name: " . $prod['name'] . ", Quantity: " . $prod['quantity'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Error adding test product: " . $e->getMessage() . "<br>";
}
?> 