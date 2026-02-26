<?php
// config/layout.php — Shared HTML layout renderer
require_once __DIR__.'/auth.php';

function renderHead(string $title = 'Blood on Click'): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$title} — Blood on Click</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/boc/assets/style.css">
</head>
<body>
HTML;
}

function renderNav(): void {
    $role    = $_SESSION['role']      ?? '';
    $name    = htmlspecialchars($_SESSION['full_name'] ?? 'User');
    $unread  = getUnreadCount();
    $badge   = $unread > 0 ? "<span class='notif-badge'>{$unread}</span>" : '';

    $roleLinks = match($role) {
        'admin'      => '
            <a href="/boc/admin/index.php">Users</a>
            <a href="/boc/admin/assessments.php">Assessments</a>
            <a href="/boc/admin/notifications.php">Notify</a>
            <a href="/boc/admin/recommendations.php">Recommend</a>
            <a href="/search.php">Search</a>',
        'blood_bank' => '
            <a href="/boc/blood_bank/dashboard.php">Dashboard</a>
            <a href="/boc/blood_bank/stock.php">Stock</a>
            <a href="/boc/blood_bank/requests.php">Requests</a>
            <a href="/boc/search.php">Find Donors</a>',
        'donor'      => '
            <a href="/boc/donor/dashboard.php">Dashboard</a>
            <a href="/boc/donor/history.php">My History</a>
            <a href="/boc/donor/assessments.php">Health Reports</a>
            <a href="/boc/search.php">Find Banks</a>',
        'seeker'     => '
            <a href="/boc/seeker/dashboard.php">Dashboard</a>
            <a href="/boc/seeker/search.php">Find Donors</a>
            <a href="/boc/seeker/request.php">My Requests</a>
            <a href="/boc/search.php">Find Banks</a>',
        default      => '<a href="/boc/dashboard.php">Dashboard</a>',
    };

    $roleLabel = match($role) {
        'admin'      => 'Admin',
        'blood_bank' => 'Blood Bank',
        'donor'      => 'Donor',
        'seeker'     => 'Seeker',
        default      => '',
    };

    echo <<<HTML
<nav class="navbar">
  <a href="/boc/dashboard.php" class="brand">
    <span class="brand-drop"></span>
    Blood<em>on</em>Click
  </a>
  <div class="nav-links">{$roleLinks}</div>
  <div class="nav-user">
    <a href="/boc/notifications.php" class="notif-btn" title="Notifications">🔔{$badge}</a>
    <span class="user-chip">
      <span class="role-dot role-{$role}"></span>
      {$name}
      <span class="role-label">{$roleLabel}</span>
    </span>
    <a href="/boc/logout.php" class="btn-logout">Sign Out</a>
  </div>
</nav>
HTML;
}
