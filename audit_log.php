<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isAdmin()) { header("Location: dashboard.php"); exit; }
$pdo = Database::getConnection();
$logs = $pdo->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Log - PT Bukit Asam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> /* sama seperti users.php */ </style>
</head>
<body>
<div class="sidebar">...</div>
<div class="content">
    <h4><i class="fas fa-history me-2"></i> Log Aktivitas Sistem</h4>
    <div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Tabel</th><th>ID Record</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?= $log['created_at'] ?></td><td><?= htmlspecialchars($log['username']) ?></td><td><?= htmlspecialchars($log['action']) ?></td><td><?= htmlspecialchars($log['table_name']) ?></td><td><?= $log['record_id'] ?></td><td><?= htmlspecialchars($log['ip_address']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <a href="dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali</a>
</div>
</body>
</html>