<?php
/**
 * Continuous local background worker (for Windows / local development).
 * Runs cli/check_services.php every 10 seconds.
 */
echo "=====================================================\n";
echo " API Monitor - Continuous Background Worker Started\n";
echo " Polling for due services every 10 seconds...\n";
echo " Press Ctrl+C at any time to stop.\n";
echo "=====================================================\n\n";

$checkScript = __DIR__ . '/check_services.php';

while (true) {
    passthru(PHP_BINARY . ' ' . escapeshellarg($checkScript));
    sleep(10);
}
