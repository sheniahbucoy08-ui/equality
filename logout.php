<?php
require_once __DIR__ . '/includes/auth.php';
clearUserSession();
header('Location: /equalvoice/index.php');
exit;
