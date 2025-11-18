<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';

// Check if user is logged in and is admin
Auth::requireRole('admin');

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_general_settings'])) {
            // In a real application, you'd save these to a settings table or config file
            $_SESSION['general_settings_updated'] = true;
            $success_message = "General settings updated successfully!";
        }
        
        elseif (isset($_POST['update_email_settings'])) {
            // Validate and update email settings
            $smtp_host = trim($_POST['smtp_host']);
            $smtp_port = intval($_POST['smtp_port']);
            $smtp_username = trim($_POST['smtp_username']);
            $smtp_from = trim($_POST['smtp_from']);
            
            if (!empty($smtp_host) && !empty($smtp_username) && !empty($smtp_from)) {
                $_SESSION['email_settings_updated'] = true;
                $success_message = "Email settings updated successfully!";
            } else {
                $error_message = "Please fill all required email fields!";
            }
        }
        
        elseif (isset($_POST['update_user_settings'])) {
            $_SESSION['user_settings_updated'] = true;
            $success_message = "User settings updated successfully!";
        }
        
        elseif (isset($_POST['update_inventory_settings'])) {
            $_SESSION['inventory_settings_updated'] = true;
            $success_message = "Inventory settings updated successfully!";
        }
        
        elseif (isset($_POST['update_security_settings'])) {
            $_SESSION['security_settings_updated'] = true;
            $success_message = "Security settings updated successfully!";
        }
        
        elseif (isset($_POST['backup_database'])) {
            // Simple database backup (in real app, use proper backup methods)
            $backup_file = 'backup/database_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $_SESSION['backup_created'] = $backup_file;
            $success_message = "Database backup initiated! Backup file: " . $backup_file;
        }
        
        elseif (isset($_POST['clear_cache'])) {
            // Clear session cache and temp data
            if (isset($_SESSION['cache_data'])) {
                unset($_SESSION['cache_data']);
            }
            $success_message = "System cache cleared successfully!";
        }

    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings (in real app, fetch from database)
$current_settings = [
    'site_name' => 'BlubellSeek Inventory',
    'site_email' => 'admin@blubellseek.com',
    'currency' => 'USD',
    'timezone' => 'America/New_York',
    'low_stock_threshold' => 10,
    'enable_2fa' => true,
    'session_timeout' => 60,
    'max_login_attempts' => 5
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - BlubellSeek</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 20px;
        }
        .settings-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .nav-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .nav-btn.active {
            background: #2c3e50;
        }
        .settings-section {
            display: none;
            margin-bottom: 30px;
        }
        .settings-section.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn-save {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-backup {
            background: #e67e22;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-clear {
            background: #e74c3c;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .settings-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .settings-card h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .system-info {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>BlubellSeek Inventory System</h1>
            <div class="nav-links">
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
                <a href="user_management.php" class="nav-btn">Users</a>
                <a href="inventory.php" class="nav-btn">Inventory</a>
                <a href="admin_settings.php" class="nav-btn">Settings</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="settings-header">
            <h2>System Administration Settings</h2>
            <div class="system-info">
                <strong>System Status:</strong> Online | 
                <strong>PHP Version:</strong> <?php echo phpversion(); ?> |
                <strong>Users:</strong> Active
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <button class="nav-btn active" onclick="showSection('general')">General</button>
                <button class="nav-btn" onclick="showSection('email')">Email</button>
                <button class="nav-btn" onclick="showSection('users')">Users</button>
                <button class="nav-btn" onclick="showSection('inventory')">Inventory</button>
                <button class="nav-btn" onclick="showSection('security')">Security</button>
                <button class="nav-btn" onclick="showSection('maintenance')">Maintenance</button>
            </div>

            <!-- General Settings -->
            <div id="general-section" class="settings-section active">
                <form method="POST" action="">
                    <div class="settings-card">
                        <h4>📊 General System Settings</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_name">Site Name:</label>
                                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="site_email">Admin Email:</label>
                                <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($current_settings['site_email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="currency">Default Currency:</label>
                                <select id="currency" name="currency">
                                    <option value="USD" <?php echo $current_settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $current_settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo $current_settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timezone">Timezone:</label>
                                <select id="timezone" name="timezone">
                                    <option value="America/New_York" <?php echo $current_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?php echo $current_settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                    <option value="America/Denver" <?php echo $current_settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                    <option value="America/Los_Angeles" <?php echo $current_settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_general_settings" class="btn-save">Save General Settings</button>
                </form>
            </div>

            <!-- Email Settings -->
            <div id="email-section" class="settings-section">
                <form method="POST" action="">
                    <div class="settings-card">
                        <h4>📧 Email Configuration</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_host">SMTP Host:</label>
                                <input type="text" id="smtp_host" name="smtp_host" value="smtp.gmail.com" required>
                            </div>
                            <div class="form-group">
                                <label for="smtp_port">SMTP Port:</label>
                                <input type="number" id="smtp_port" name="smtp_port" value="587" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_username">SMTP Username:</label>
                                <input type="email" id="smtp_username" name="smtp_username" placeholder="your@gmail.com" required>
                            </div>
                            <div class="form-group">
                                <label for="smtp_password">SMTP Password:</label>
                                <input type="password" id="smtp_password" name="smtp_password" placeholder="App password" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="smtp_from">From Email:</label>
                            <input type="email" id="smtp_from" name="smtp_from" placeholder="noreply@blubellseek.com" required>
                        </div>
                    </div>
                    <button type="submit" name="update_email_settings" class="btn-save">Save Email Settings</button>
                </form>
            </div>

            <!-- User Settings -->
            <div id="users-section" class="settings-section">
                <form method="POST" action="">
                    <div class="settings-card">
                        <h4>👥 User Management Settings</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="default_role">Default User Role:</label>
                                <select id="default_role" name="default_role">
                                    <option value="customer">Customer</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="max_login_attempts">Max Login Attempts:</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo $current_settings['max_login_attempts']; ?>" min="1" max="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="allow_registration" name="allow_registration" checked>
                                <label for="allow_registration">Allow new user registration</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="email_verification" name="email_verification">
                                <label for="email_verification">Require email verification for new users</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_user_settings" class="btn-save">Save User Settings</button>
                </form>
            </div>

            <!-- Inventory Settings -->
            <div id="inventory-section" class="settings-section">
                <form method="POST" action="">
                    <div class="settings-card">
                        <h4>📦 Inventory Management Settings</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="low_stock_threshold">Low Stock Threshold:</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo $current_settings['low_stock_threshold']; ?>" min="1" max="100">
                                <small>Products with quantity below this will show as low stock</small>
                            </div>
                            <div class="form-group">
                                <label for="reorder_level">Auto Reorder Level:</label>
                                <input type="number" id="reorder_level" name="reorder_level" value="5" min="0" max="50">
                                <small>Automatically reorder when stock reaches this level</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="low_stock_alerts" name="low_stock_alerts" checked>
                                <label for="low_stock_alerts">Enable low stock email alerts</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="auto_calculate_prices" name="auto_calculate_prices">
                                <label for="auto_calculate_prices">Auto-calculate selling prices based on cost</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_inventory_settings" class="btn-save">Save Inventory Settings</button>
                </form>
            </div>

            <!-- Security Settings -->
            <div id="security-section" class="settings-section">
                <form method="POST" action="">
                    <div class="settings-card">
                        <h4>🔒 Security Settings</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="session_timeout">Session Timeout (minutes):</label>
                                <input type="number" id="session_timeout" name="session_timeout" value="<?php echo $current_settings['session_timeout']; ?>" min="5" max="480">
                            </div>
                            <div class="form-group">
                                <label for="password_expiry">Password Expiry (days):</label>
                                <input type="number" id="password_expiry" name="password_expiry" value="90" min="0" max="365">
                                <small>0 = never expire</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="enable_2fa" name="enable_2fa" <?php echo $current_settings['enable_2fa'] ? 'checked' : ''; ?>>
                                <label for="enable_2fa">Require Two-Factor Authentication for all users</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="force_ssl" name="force_ssl">
                                <label for="force_ssl">Force SSL/HTTPS connections</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="update_security_settings" class="btn-save">Save Security Settings</button>
                </form>
            </div>

            <!-- Maintenance Settings -->
            <div id="maintenance-section" class="settings-section">
                <div class="settings-card">
                    <h4>🛠️ System Maintenance</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <form method="POST" action="">
                                <button type="submit" name="backup_database" class="btn-backup">Create Database Backup</button>
                                <small>Creates a full backup of the database</small>
                            </form>
                        </div>
                        <div class="form-group">
                            <form method="POST" action="">
                                <button type="submit" name="clear_cache" class="btn-clear">Clear System Cache</button>
                                <small>Clears temporary files and cache data</small>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h4>📊 System Information</h4>
                    <div class="form-group">
                        <label>PHP Version:</label>
                        <input type="text" value="<?php echo phpversion(); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Database Size:</label>
                        <input type="text" value="Calculating..." readonly>
                    </div>
                    <div class="form-group">
                        <label>Last Backup:</label>
                        <input type="text" value="<?php echo isset($_SESSION['backup_created']) ? $_SESSION['backup_created'] : 'No backups found'; ?>" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <select id="currency" name="currency">
    <option value="KES" <?php echo $current_settings['currency'] === 'KES' ? 'selected' : ''; ?>>KES (KSh)</option>
    <option value="USD" <?php echo $current_settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
    <option value="EUR" <?php echo $current_settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
    <option value="GBP" <?php echo $current_settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
</select>

    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').classList.add('active');
            
            // Activate clicked button
            event.target.classList.add('active');
        }

        // Initialize first section as active
        document.addEventListener('DOMContentLoaded', function() {
            showSection('general');
        });
    </script>
</body>
</html>