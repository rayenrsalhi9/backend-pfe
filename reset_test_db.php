<?php

// Basic .env loader for standalone script
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (trim($line) === '' || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER');
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'db_ged_test';

if (!$user) {
    die("Error: DB_USER environment variable is not set. Please check your .env file.\n");
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Safety check: only allow resetting databases with 'test' in the name
    if (strpos($db, 'test') === false || !preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
        die("Error: For safety, this script can only reset databases containing 'test' in their name and using valid characters.\n");
    }

    $pdo->exec("DROP DATABASE IF EXISTS `$db` ");
    $pdo->exec("CREATE DATABASE `$db` ");
    echo "Database '$db' reset successfully.\n";
} catch (PDOException $e) {
    die("Database reset failed: " . $e->getMessage() . "\n");
}
