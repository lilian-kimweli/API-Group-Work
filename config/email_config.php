<?php
// email_config.php - Gmail Configuration

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Email configuration constants
define('SMTP_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'BlubellSeek');

// For PHP mail() function, set the sendmail path (optional)
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i');
?>