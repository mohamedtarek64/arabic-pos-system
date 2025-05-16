<?php
include '../app/db.php';

// Create categories table if it doesn't exist
$createTableSql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT
)";
$conn->exec($createTableSql);

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $deleteSql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->execute([$id]);
    
    // Redirect to avoid resubmission
    header("Location: categories.php");
    exit();
}

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    // إدخال تصنيف
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $description]);

    // Redirect to avoid resubmission
    header("Location: categories.php");
    exit();
}

// Get all categories
$sql = "SELECT * FROM categories ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total categories count
$totalCategories = count($categories);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التصنيفات</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .categories-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        
        .add-category-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e3e6f0;
        }
        
        .add-category-section h2 {
            color: #2196f3;
            margin-bottom: 20px;
            font-size: 1.5em;
            text-align: right;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        textarea.form-control {
            height: 100px;
            resize: vertical;
        }
        
        .btn-add {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-add:hover {
            background-color: #0d8aee;
        }
        
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .categories-table th {
            background-color: #2196f3;
            color: white;
            padding: 12px;
            text-align: right;
        }
        
        .categories-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .categories-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 5px;
            transition: background-color 0.3s;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        
        .page-header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item {
            font-size: 16px;
            color: #555;
        }
        
        .breadcrumb-item a {
            color: #2196f3;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb-item a:hover {
            color: #0d8aee;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            padding: 0 10px;
            color: #ccc;
        }
        
        .breadcrumb-item.active {
            color: #333;
            font-weight: 600;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
        }
        
        .quick-action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .quick-action-btn i {
            margin-left: 5px;
        }
        
        .quick-action-btn:hover {
            background-color: #0d8aee;
            transform: translateY(-2px);
        }
        
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            background-color: #e3f2fd;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
        }
        
        .stat-icon i {
            font-size: 24px;
            color: #2196f3;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: #777;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quick-actions {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        <div class="main-content-area">
            
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../app/main.php"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                        <li class="breadcrumb-item active">التصنيفات</li>
                    </ol>
                </nav>
                <div class="quick-actions">
                    <a href="#add-category" class="quick-action-btn">
                        <i class="fas fa-plus"></i> إضافة تصنيف
                    </a>
                    <a href="products.php" class="quick-action-btn">
                        <i class="fas fa-box"></i> المنتجات
                    </a>
                </div>
            </div>
            
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-value"><?php echo $totalCategories; ?></h3>
                        <p class="stat-label">إجمالي التصنيفات</p>
                    </div>
                </div>
            </div>
            
            <div class="categories-container">
                
                <div class="add-category-section" id="add-category">
                    <h2><i class="fas fa-plus-circle"></i> إضافة تصنيف جديد</h2>
                    <form action="categories.php" method="POST">
                        <div class="form-group">
                            <label for="name">اسم التصنيف:</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">وصف التصنيف:</label>
                            <textarea id="description" name="description" class="form-control"></textarea>
                        </div>
                        
                        <button type="submit" class="btn-add">
                            <i class="fas fa-plus"></i> إضافة التصنيف
                        </button>
                    </form>
                </div>
                
                
                <h2>قائمة التصنيفات</h2>
                <div class="table-responsive">
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم التصنيف</th>
                                <th>الوصف</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn-delete" 
                                               onclick="return confirm('هل أنت متأكد من حذف هذا التصنيف؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-message">لا توجد تصنيفات مضافة حتى الآن</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html> 