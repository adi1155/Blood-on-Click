<?php session_start(); header('Location: '.( isset($_SESSION['user_id']) ? '/boc/dashboard.php' : '/boc/landing.php')); exit;
