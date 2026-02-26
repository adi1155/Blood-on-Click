<?php
session_start();
require_once 'config/auth.php';
requireLogin();

$redirects = [
    'admin'      => '/admin/index.php',
    'blood_bank' => '/blood_bank/dashboard.php',
    'donor'      => '/donor/dashboard.php',
    'seeker'     => '/seeker/dashboard.php',
];
header('Location: ' . ($redirects[$_SESSION['role']] ?? '/boc/login.php'));
exit;
