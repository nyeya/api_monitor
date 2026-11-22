<?php
$host   = getenv('DB_HOST') ?: 'localhost';
$port   = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'api_monitor';
$user   = getenv('DB_USER') ?: 'postgres';
$pass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';

try {
    // 1. Connect to PostgreSQL default database
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres;", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 2. Drop existing database if it exists and create it fresh
    echo "Resetting database '{$dbname}'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS \"{$dbname}\" WITH (FORCE);");
    $pdo->exec("CREATE DATABASE \"{$dbname}\";");

    // 3. Connect to the fresh database
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname};", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 4. Run schema.sql
    echo "Running schema.sql...\n";
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);

    echo "Database '{$dbname}' set up successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
