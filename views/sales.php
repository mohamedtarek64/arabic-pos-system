<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المبيعات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        
        .sales-container {
            padding: 20px;
        }
        
        .sales-row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px; 
            margin-bottom: 30px;
        }
        
        .sales-column {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .sales-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .sales-header h2 {
            font-size: 1.6em;
            color: #333;
            text-align: right;
        }
        
        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
            font-size: 1em;
        }
        
        .search-bar button {
            padding: 12px 20px;
            background-color: #2196f3;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
        }
        
        .product-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .product-item:hover {
            background-color: #f5f5f5;
        }
        
        .product-item .name {
            font-weight: bold;
            color: #333;
        }
        
        .product-item .price {
            color: #2196f3;
            font-weight: bold;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item .details {
            flex: 1;
        }
        
        .cart-item .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-item .quantity button {
            width: 30px;
            height: 30px;
            background-color: #f0f0f0;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .cart-item .quantity span {
            font-weight: bold;
        }
        
        .cart-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .checkout-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            margin-top: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .checkout-button:hover {
            background-color: #45a049;
        }
        
        
        @media (min-width: 992px) {
            .sales-row {
                gap: 40px; 
            }
        }
        
        @media (max-width: 991px) {
            .sales-column {
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle">☰</button>
    <div class="layout-wrapper">
        <?php include '../app/includes/sidebar.php'; ?>
        
        
        <div class="main-content-area">
            <div style="width: 100%; display: flex; justify-content: flex-end; align-items: flex-start; margin-top: 32px; margin-bottom: 24px;">
                <h1 style="font-family: 'Cairo', 'Segoe UI', Arial, sans-serif; font-size: 2.7em; font-weight: 900; color: #1976d2; letter-spacing: 1px; text-shadow: 0 2px 12px rgba(33,150,243,0.13), 0 1px 0 #fff; background: linear-gradient(90deg, #1976d2 60%, #2196f3 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; padding: 0 24px;">المبيعات</h1>
            </div>
            
            <div class="sales-container">
                <div class="sales-row">
                    
                    <div class="sales-column">
                        <div class="sales-header">
                            <h2>المنتجات</h2>
                        </div>
                        <div class="search-bar">
                            <input type="text" placeholder="بحث عن منتج...">
                            <button><i class="fas fa-search"></i></button>
                        </div>
                        <div class="product-list">
                            <div class="product-item">
                                <span class="name">منتج 1</span>
                                <span class="price">50 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 2</span>
                                <span class="price">75 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 3</span>
                                <span class="price">100 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 4</span>
                                <span class="price">120 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 5</span>
                                <span class="price">85 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 6</span>
                                <span class="price">60 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 7</span>
                                <span class="price">110 ريال</span>
                            </div>
                            <div class="product-item">
                                <span class="name">منتج 8</span>
                                <span class="price">95 ريال</span>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="sales-column">
                        <div class="sales-header">
                            <h2>سلة المشتريات</h2>
                        </div>
                        <div class="cart-items">
                            <div class="cart-item">
                                <div class="details">
                                    <div class="name">منتج 1</div>
                                    <div class="price">50 ريال</div>
                                </div>
                                <div class="quantity">
                                    <button>-</button>
                                    <span>2</span>
                                    <button>+</button>
                                </div>
                                <div class="total">100 ريال</div>
                            </div>
                            <div class="cart-item">
                                <div class="details">
                                    <div class="name">منتج 3</div>
                                    <div class="price">100 ريال</div>
                                </div>
                                <div class="quantity">
                                    <button>-</button>
                                    <span>1</span>
                                    <button>+</button>
                                </div>
                                <div class="total">100 ريال</div>
                            </div>
                        </div>
                        <div class="cart-total">
                            <span>المجموع:</span>
                            <span>200 ريال</span>
                        </div>
                        <button class="checkout-button">إتمام البيع</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('.menu-toggle').click(function() {
                $('#sidebar').toggleClass('open');
                $('body').toggleClass('sidebar-open');
                $('.sidebar-overlay').toggleClass('active');
            });
            
            // Close sidebar when clicking outside
            $('.sidebar-overlay').click(function() {
                $('#sidebar').removeClass('open');
                $('body').removeClass('sidebar-open');
                $(this).removeClass('active');
            });
            
            // Dropdown functionality
            $('.dropdown > a').click(function(e) {
                e.preventDefault();
                $(this).parent().toggleClass('open');
            });
        });
    </script>
</body>
</html> 