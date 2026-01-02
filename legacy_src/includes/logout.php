<?php
// logout.php
require_once __DIR__ . '/function.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
session_signout();
flash_set('success', 'Anda telah logout.');
header('Location: ../login.php');
exit;
