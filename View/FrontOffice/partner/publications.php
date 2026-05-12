<?php
require_once dirname(__DIR__, 2) . '/session_check.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

header('Location: ' . caremeal_path('View/FrontOffice/feed.php'), true, 302);
exit;
