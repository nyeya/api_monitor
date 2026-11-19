<?php
require_once __DIR__ . '/../config/db.php';

session_start();

require_once __DIR__ . '/../includes/admin/auth.php';
require_once __DIR__ . '/../includes/admin/services.php';

require __DIR__ . '/../views/admin/page.php';
