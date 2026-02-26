<?php
session_start();
require_once 'config/auth.php';
requireLogin();

$redirects = [
    'admin'      => '/boc/admin/index.php',
    'blood_bank' => '/boc/blood_bank/dashboard.php',
    'donor'      => '/boc/donor/dashboard.php',
    'seeker'     => '/boc/seeker/dashboard.php',
];
header('Location: ' . ($redirects[$_SESSION['role']] ?? '/boc/login.php'));
exit;
