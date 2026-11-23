<?php
/**
 * One-command PostgreSQL setup script:
 * Drops/recreates the api_monitor database, creates tables, and loads seed data.
 *
 * Usage: php database/setup.php
 */
$host   = getenv('DB_HOST') ?: 'localhost';
$port   = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'api_monitor';
$user   = getenv('DB_USER') ?: 'postgres';
$pass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres;", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Resetting database '{$dbname}'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS \"{$dbname}\" WITH (FORCE);");
    $pdo->exec("CREATE DATABASE \"{$dbname}\";");

    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname};", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Applying database/schema.sql...\n";
    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));

    echo "Applying database/seed.sql...\n";
    $pdo->exec(file_get_contents(__DIR__ . '/seed.sql'));

    echo "Database '{$dbname}' is ready!\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Database setup error: ' . $e->getMessage() . "\n");
    exit(1);
}
