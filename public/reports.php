<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Only managers and admins can access reports
Auth::requireAnyRole(['manager', 'admin']);

$db = new Database();
$conn = $db->getConnection();

// Date range filter (default to last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales reports
$sales_report = $conn->prepare("
    SELECT 
        DATE(o.order_date) as order_date,
        COUNT(*) as order_count,
        SUM(o.total_amount) as daily_revenue,
        AVG(o.total_amount) as avg_order_value
    FROM orders o
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY DATE(o.order_date)
    ORDER BY order_date DESC
");
$sales_report->execute([$start_date, $end_date]);
$sales_data = $sales_report->fetchAll(PDO::FETCH_ASSOC);

// Get top selling products
$top_products = $conn->prepare("
    SELECT 
        p.name as product_name,
        c.name as category_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY p.id, p.name, c.name
    ORDER BY total_sold DESC
    LIMIT 10
");
$top_products->execute([$start_date, $end_date]);
$top_products_data = $top_products->fetchAll(PDO::FETCH_ASSOC);

// Get category performance
$category_performance = $conn->prepare("
    SELECT 
        c.name as category_name,
        COUNT(DISTINCT o.id) as order_count,
        SUM(oi.quantity) as items_sold,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total_revenue DESC
");
$category_performance->execute([$start_date, $end_date]);
$category_data = $category_performance->fetchAll(PDO::FETCH_ASSOC);

// Get inventory alerts
$low_stock = $conn->query("
    SELECT name, on_hand_quantity, unit_price
    FROM products 
    WHERE on_hand_quantity < 10 AND on_hand_quantity > 0 AND is_active = TRUE
    ORDER BY on_hand_quantity ASC
")->fetchAll(PDO::FETCH_ASSOC);

$out_of_stock = $conn->query("
    SELECT name, unit_price
    FROM products 
    WHERE on_hand_quantity = 0 AND is_active = TRUE
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_revenue = array_sum(array_column($sales_data, 'daily_revenue'));
$total_orders = array_sum(array_column($sales_data, 'order_count'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Bluebell Inventory</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reports-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .date-filter {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            text-align: center;
        }
        
        .stat-card.revenue {
            border-left-color: #27ae60;
            background: #f8fff8;
        }
        
        .stat-card.orders {
            border-left-color: #3498db;
            background: #f0f8ff;
        }
        
        .stat-card.average {
            border-left-color: #f39c12;
            background: #fffbf0;
        }
        
        .stat-card.alerts {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .report-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .report-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background: #3498db;
            color: white;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .alert-low {
            background: #fff3cd !important;
            color: #856404;
        }
        
        .alert-out {
            background: #f8d7da !important;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1>Bluebell Inventory System</h1>
            <?php include 'nav.php'; ?>
        </div>
    </div>

    <div class="container">
        <div class="reports-container">
            <h2>📊 Sales & Inventory Reports</h2>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <div>
                    <label><strong>Start Date:</strong></label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           onchange="this.form.submit()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label><strong>End Date:</strong></label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           onchange="this.form.submit()" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <button type="submit" class="btn-secondary" style="border: none; cursor: pointer;">Apply Filter</button>
                    <a href="reports.php" class="btn-secondary" style="background: #7f8c8d; text-decoration: none;">Reset</a>
                </div>
            </div>
            
            <!-- Key Statistics -->
            <div class="stats-grid">
                <div class="stat-card revenue">
                    <div class="stat-number">KSh <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card orders">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card average">
                    <div class="stat-number">KSh <?php echo number_format($avg_order_value, 2); ?></div>
                    <div class="stat-label">Average Order Value</div>
                </div>
                <div class="stat-card alerts">
                    <div class="stat-number"><?php echo count($low_stock) + count($out_of_stock); ?></div>
                    <div class="stat-label">Stock Alerts</div>
                </div>
            </div>
            
            <!-- Sales Report -->
            <div class="report-section">
                <h3>📈 Sales Report (<?php echo $start_date; ?> to <?php echo $end_date; ?>)</h3>
                <?php if (empty($sales_data)): ?>
                    <div class="no-data">No sales data for the selected period</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Average Order</th>
                        </tr>
                        <?php foreach ($sales_data as $sale): ?>
                        <tr>
                            <td><?php echo $sale['order_date']; ?></td>
                            <td><?php echo $sale['order_count']; ?></td>
                            <td>KSh <?php echo number_format($sale['daily_revenue'], 2); ?></td>
                            <td>KSh <?php echo number_format($sale['avg_order_value'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Top Products -->
            <div class="report-section">
                <h3>🔥 Top Selling Products</h3>
                <?php if (empty($top_products_data)): ?>
                    <div class="no-data">No product sales data for the selected period</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                        <?php foreach ($top_products_data as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo $product['total_sold']; ?></td>
                            <td>KSh <?php echo number_format($product['total_revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Category Performance -->
            <div class="report-section">
                <h3>📂 Category Performance</h3>
                <?php if (empty($category_data)): ?>
                    <div class="no-data">No category data for the selected period</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Items Sold</th>
                            <th>Revenue</th>
                        </tr>
                        <?php foreach ($category_data as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td><?php echo $category['order_count']; ?></td>
                            <td><?php echo $category['items_sold']; ?></td>
                            <td>KSh <?php echo number_format($category['total_revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Inventory Alerts -->
            <div class="report-section">
                <h3>⚠️ Inventory Alerts</h3>
                
                <h4>Low Stock (< 10 units)</h4>
                <?php if (empty($low_stock)): ?>
                    <div class="no-data">No low stock items</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Price</th>
                        </tr>
                        <?php foreach ($low_stock as $product): ?>
                        <tr class="alert-low">
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['on_hand_quantity']; ?> units</td>
                            <td>KSh <?php echo number_format($product['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                
                <h4 style="margin-top: 20px;">Out of Stock</h4>
                <?php if (empty($out_of_stock)): ?>
                    <div class="no-data">No out of stock items</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                        </tr>
                        <?php foreach ($out_of_stock as $product): ?>
                        <tr class="alert-out">
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>KSh <?php echo number_format($product['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>