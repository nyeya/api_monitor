<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

requireAdminLogin();

require __DIR__ . '/../views/dashboard.php';
