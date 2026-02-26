<?php
// config/auth.php — Auth helpers, guards, notifications

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /boc/login.php'); exit;
    }
}
function requireRole(string|array $roles): void {
    requireLogin();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'] ?? '', $allowed)) {
        header('Location: /boc/dashboard.php'); exit;
    }
}
function requireAdmin():      void { requireRole('admin'); }
function requireBloodBank():  void { requireRole(['admin','blood_bank']); }
function requireDonor():      void { requireRole(['admin','donor']); }

function flash(string $key): string {
    if (isset($_SESSION[$key])) { $msg = $_SESSION[$key]; unset($_SESSION[$key]); return $msg; }
    return '';
}

function getUnreadCount(): int {
    if (!isset($_SESSION['user_id'])) return 0;
    require_once __DIR__.'/db.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function getBloodBankId(): ?int {
    if (!isset($_SESSION['user_id'])) return null;
    require_once __DIR__.'/db.php';
    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM blood_banks WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row  = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// Blood group color codes
function bloodGroupColor(string $bg): string {
    $map = [
        'O-'  => '#dc2626', 'O+'  => '#ea580c',
        'A-'  => '#7c3aed', 'A+'  => '#2563eb',
        'B-'  => '#059669', 'B+'  => '#0891b2',
        'AB-' => '#d97706', 'AB+' => '#be185d',
    ];
    return $map[$bg] ?? '#6b7280';
}

function bloodGroupBadge(string $bg, bool $large = false): string {
    $color = bloodGroupColor($bg);
    $size  = $large ? 'font-size:1rem;padding:.35rem .8rem;border-radius:8px;' : 'font-size:.7rem;padding:.15rem .5rem;border-radius:5px;';
    return "<span class='badge-blood' style='background:{$color}22;border-color:{$color}55;color:{$color};{$size}'>{$bg}</span>";
}

// Distance between two coordinates (Haversine formula, returns km)
function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($R * $c, 1);
}
