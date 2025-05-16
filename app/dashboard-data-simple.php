<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Create dummy data
$response = [
    'total_sales' => 15750.50,
    'sales_count' => 42,
    'net_income' => 15750.50,
    'paid_installments' => 5250.75,
    'monthly_sales' => [
        ['month' => 'يناير 2023', 'total' => 1500],
        ['month' => 'فبراير 2023', 'total' => 2300],
        ['month' => 'مارس 2023', 'total' => 1800],
        ['month' => 'أبريل 2023', 'total' => 2100],
        ['month' => 'مايو 2023', 'total' => 2800],
        ['month' => 'يونيو 2023', 'total' => 3200],
        ['month' => 'يوليو 2023', 'total' => 2400],
        ['month' => 'أغسطس 2023', 'total' => 1900],
        ['month' => 'سبتمبر 2023', 'total' => 2600],
        ['month' => 'أكتوبر 2023', 'total' => 3100],
        ['month' => 'نوفمبر 2023', 'total' => 3500],
        ['month' => 'ديسمبر 2023', 'total' => 4200]
    ],
    'weekly_sales' => [
        ['day' => 1, 'date' => '2023-01-01', 'total' => 450],
        ['day' => 2, 'date' => '2023-01-02', 'total' => 380],
        ['day' => 3, 'date' => '2023-01-03', 'total' => 520],
        ['day' => 4, 'date' => '2023-01-04', 'total' => 490],
        ['day' => 5, 'date' => '2023-01-05', 'total' => 600],
        ['day' => 6, 'date' => '2023-01-06', 'total' => 750],
        ['day' => 7, 'date' => '2023-01-07', 'total' => 420]
    ],
    'recent_sales' => [
        [
            'id' => 1,
            'customer_name' => 'محمد أحمد',
            'total_amount' => 1250.00,
            'created_at' => '2023-01-15 14:30:00'
        ],
        [
            'id' => 2,
            'customer_name' => 'فاطمة علي',
            'total_amount' => 850.75,
            'created_at' => '2023-01-14 11:15:00'
        ],
        [
            'id' => 3,
            'customer_name' => 'أحمد محمود',
            'total_amount' => 2100.50,
            'created_at' => '2023-01-13 16:45:00'
        ],
        [
            'id' => 4,
            'customer_name' => 'نورا سعيد',
            'total_amount' => 750.25,
            'created_at' => '2023-01-12 09:20:00'
        ],
        [
            'id' => 5,
            'customer_name' => 'خالد عبدالله',
            'total_amount' => 1500.00,
            'created_at' => '2023-01-11 13:10:00'
        ]
    ],
    'recent_activities' => [
        [
            'id' => 1,
            'activity_type' => 'عملية بيع جديدة',
            'created_at' => '2023-01-15 14:30:00',
            'user_name' => 'محمد أحمد'
        ],
        [
            'id' => 2,
            'activity_type' => 'دفع قسط',
            'created_at' => '2023-01-14 11:15:00',
            'user_name' => 'فاطمة علي'
        ],
        [
            'id' => 3,
            'activity_type' => 'عملية بيع جديدة',
            'created_at' => '2023-01-13 16:45:00',
            'user_name' => 'أحمد محمود'
        ],
        [
            'id' => 4,
            'activity_type' => 'دفع قسط',
            'created_at' => '2023-01-12 09:20:00',
            'user_name' => 'نورا سعيد'
        ],
        [
            'id' => 5,
            'activity_type' => 'عملية بيع جديدة',
            'created_at' => '2023-01-11 13:10:00',
            'user_name' => 'خالد عبدالله'
        ]
    ]
];

// Return JSON response
echo json_encode($response);
?> 