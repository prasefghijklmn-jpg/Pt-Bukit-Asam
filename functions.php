<?php
// config/functions.php
require_once __DIR__ . '/database.php';

function logActivity(int $userId, string $action, string $table, int $recordId, $oldValue = null, $newValue = null): void {
    $pdo = Database::getConnection();
    $username = $_SESSION['username'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $oldStr = is_null($oldValue) ? null : (string) $oldValue;
    $newStr = is_null($newValue) ? null : (string) $newValue;
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, username, action, table_name, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $username, $action, $table, $recordId, $oldStr, $newStr, $ip, $ua]);
}

function getNilaiNeraca(int $itemId, int $tahun): float {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT nilai FROM neraca_values WHERE item_id = ? AND tahun = ?");
    $stmt->execute([$itemId, $tahun]);
    return (float) $stmt->fetchColumn();
}

function getNilaiLabaRugi(int $itemId, int $tahun): float {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT nilai FROM laba_rugi_values WHERE item_id = ? AND tahun = ?");
    $stmt->execute([$itemId, $tahun]);
    return (float) $stmt->fetchColumn();
}

function getNilaiArusKas(int $itemId, int $tahun): float {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT nilai FROM arus_kas_values WHERE item_id = ? AND tahun = ?");
    $stmt->execute([$itemId, $tahun]);
    return (float) $stmt->fetchColumn();
}

function totalByTipe(string $tipe, int $tahun): float {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(v.nilai), 0) as total FROM neraca_values v JOIN neraca_items i ON v.item_id = i.id WHERE i.tipe_akun = ? AND v.tahun = ?");
    $stmt->execute([$tipe, $tahun]);
    return (float) $stmt->fetchColumn();
}

function getAvailableYears(): array {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT DISTINCT tahun FROM neraca_values UNION SELECT DISTINCT tahun FROM laba_rugi_values ORDER BY tahun DESC");
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $years ?: [date('Y')];
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isFinance(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'finance']);
}
function predictNextYearProfit() {
    $pdo = Database::getConnection();
    // Ambil data laba bersih dari tahun yang tersedia
    $stmt = $pdo->query("
        SELECT v.tahun, v.nilai 
        FROM laba_rugi_values v 
        JOIN laba_rugi_items i ON v.item_id = i.id 
        WHERE i.akun LIKE '%Laba tahun berjalan%' OR i.akun = 'Laba bersih'
        ORDER BY v.tahun
    ");
    $data = $stmt->fetchAll();
    if (count($data) < 2) return null;
    
    $years = array_column($data, 'tahun');
    $profits = array_column($data, 'nilai');
    $n = count($years);
    $sumX = array_sum($years);
    $sumY = array_sum($profits);
    $sumXY = 0;
    $sumX2 = 0;
    for ($i = 0; $i < $n; $i++) {
        $sumXY += $years[$i] * $profits[$i];
        $sumX2 += $years[$i] * $years[$i];
    }
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    $nextYear = max($years) + 1;
    return [
        'tahun' => $nextYear,
        'nilai' => $slope * $nextYear + $intercept
    ];
}
?>