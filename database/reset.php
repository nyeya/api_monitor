<?php
/**
 * One-command PostgreSQL reset script:
 * Truncates tables, resets sequences, and reloads seed data for services.
 *
 * Usage: php database/reset.php
 */
$host   = getenv('DB_HOST') ?: 'localhost';
$port   = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'api_monitor';
$user   = getenv('DB_USER') ?: 'postgres';
$pass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname};", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Truncating tables in '{$dbname}'...\n";
    $pdo->exec('TRUNCATE TABLE monitoring_history, services RESTART IDENTITY CASCADE;');

    echo "Applying database/seed.sql...\n";
    $pdo->exec(file_get_contents(__DIR__ . '/seed.sql'));

    echo "Tables in '{$dbname}' have been reset!\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Database reset error: ' . $e->getMessage() . "\n");
    exit(1);
}
