<?php
/**
 * API Monitor - Continuous Background Worker
 * Provides an automated execution loop for local development and live demonstrations.
 * Simulates cron execution by checking due services every 10 seconds.
 */

echo "=====================================================\n";
echo " API Monitor - Continuous Background Worker Started\n";
echo " Polling for due services every 10 seconds...\n";
echo " Press Ctrl+C at any time to stop.\n";
echo "=====================================================\n\n";

$checkScript = __DIR__ . '/check_service.php';

while (true) {
    passthru(PHP_BINARY . ' ' . escapeshellarg($checkScript));
    sleep(10);
}
