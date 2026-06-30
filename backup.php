<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isAdmin()) { header("Location: dashboard.php"); exit; }

$backupDir = getenv('BACKUP_PATH') ?: '../backups/';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$msg = '';
if (isset($_POST['backup'])) {
    $filename = $backupDir . 'ptba_' . date('Ymd_His') . '.sql';
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s > %s',
        escapeshellarg(getenv('DB_USER')),
        escapeshellarg(getenv('DB_PASS')),
        escapeshellarg(getenv('DB_HOST')),
        escapeshellarg(getenv('DB_NAME')),
        escapeshellarg($filename)
    );
    system($command, $returnVar);
    if ($returnVar === 0 && file_exists($filename)) {
        logActivity($_SESSION['user_id'], 'BACKUP_DATABASE', 'system', 0, null, $filename);
        $msg = "Backup berhasil: " . basename($filename);
    } else {
        $msg = "Backup gagal. Pastikan mysqldump tersedia dan folder backups writable.";
    }
}
if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $file = $_FILES['restore_file']['tmp_name'];
    $command = sprintf(
        'mysql --user=%s --password=%s --host=%s %s < %s',
        escapeshellarg(getenv('DB_USER')),
        escapeshellarg(getenv('DB_PASS')),
        escapeshellarg(getenv('DB_HOST')),
        escapeshellarg(getenv('DB_NAME')),
        escapeshellarg($file)
    );
    system($command, $ret);
    if ($ret === 0) {
        logActivity($_SESSION['user_id'], 'RESTORE_DATABASE', 'system', 0, null, $_FILES['restore_file']['name']);
        $msg = "Restore berhasil.";
    } else {
        $msg = "Restore gagal.";
    }
}
$backup_files = glob($backupDir . '*.sql');
arsort($backup_files);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup & Restore - PT Bukit Asam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> /* sama seperti users.php */ </style>
</head>
<body>
<div class="sidebar">...</div>
<div class="content">
    <h4><i class="fas fa-database me-2"></i> Backup & Restore Database</h4>
    <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-6"><div class="card"><div class="card-header">Buat Backup Baru</div><div class="card-body"><form method="POST"><button type="submit" name="backup" class="btn btn-success btn-sm">Backup Sekarang</button></form></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header">Restore Database</div><div class="card-body"><form method="POST" enctype="multipart/form-data"><input type="file" name="restore_file" class="form-control form-control-sm mb-2" accept=".sql" required><button type="submit" name="restore" class="btn btn-warning btn-sm" onclick="return confirm('Restore akan menimpa data saat ini. Lanjut?')">Restore</button></form></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-header">Daftar Backup Tersedia</div><div class="card-body p-0"><ul class="list-group list-group-flush"><?php foreach($backup_files as $file): ?><li class="list-group-item d-flex justify-content-between align-items-center"><?= basename($file) ?><a href="?download=<?= urlencode(basename($file)) ?>" class="btn btn-sm btn-primary">Download</a></li><?php endforeach; ?></ul></div></div>
    <a href="dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali</a>
</div>
</body>
</html>