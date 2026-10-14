<?php
/**
 * Database Connection using PDO & .env Configuration
 * Shree Laxmi Expenses - Lorry Fleet & Expense Management
 */

// Function to safely parse .env file
function load_env_file($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comment lines or empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip enclosing quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            if (!isset($_SERVER[$name]) && !isset($_ENV[$name])) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load environment variables from .env
load_env_file(__DIR__ . '/../.env');

// Retrieve DB Configuration with safe fallbacks
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'shree_laxmi_expenses');
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? '');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    die("<div style='font-family: sans-serif; padding: 32px; background: #fff1f2; color: #9f1239; max-width: 600px; margin: 40px auto; border-radius: 12px; border: 1px solid #fecdd3;'>
            <h2 style='margin-top:0;'>Database Connection Error</h2>
            <p>Could not connect to MySQL database <strong>" . htmlspecialchars($dbname) . "</strong> on <code>" . htmlspecialchars($host) . ":" . htmlspecialchars($port) . "</code>.</p>
            <p><small style='color: #881337;'>Error details: " . htmlspecialchars($e->getMessage()) . "</small></p>
            <p>Please check your <code>.env</code> file credentials and verify that MySQL is running.</p>
         </div>");
}
