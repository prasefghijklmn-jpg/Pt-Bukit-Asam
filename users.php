<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isAdmin()) { header("Location: dashboard.php"); exit; }

$pdo = Database::getConnection();
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama_lengkap']);
    $password = $_POST['password']; // plain text
    $role = $_POST['role'];
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $nama, $role]);
    header("Location: users.php?msg=added");
    exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: users.php?msg=deleted");
    exit;
}
$users = $pdo->query("SELECT id, username, nama_lengkap, role FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola User - PT Bukit Asam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 14px; }
        .sidebar {
            width: 260px;
            background: #ffffff;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 24px 0;
            box-shadow: 2px 0 12px rgba(0,0,0,0.03);
            border-right: 1px solid #e2e8f0;
        }
        .sidebar .brand { padding: 0 20px 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .sidebar .brand h4 { color: #0f172a; font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
        .sidebar .brand p { color: #64748b; font-size: 0.7rem; }
        .sidebar .menu-section { color: #94a3b8; font-size: 0.7rem; letter-spacing: 1px; padding: 12px 20px 4px; font-weight: 600; }
        .sidebar a {
            display: flex;
            gap: 12px;
            padding: 8px 20px;
            color: #334155;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: 0.2s;
            border-radius: 0 30px 30px 0;
            margin-right: 12px;
        }
        .sidebar a i { width: 24px; font-size: 0.9rem; color: #64748b; }
        .sidebar a:hover, .sidebar a.active { background: #eff6ff; color: #3b82f6; border-left: 3px solid #f59e0b; }
        .content { margin-left: 260px; padding: 24px 28px; }
        .card { border: none; border-radius: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 24px; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; font-weight: 600; padding: 12px 20px; font-size: 0.85rem; }
        .table { margin-bottom: 0; font-size: 0.75rem; }
        .table td, .table th { padding: 8px 12px; vertical-align: middle; }
        .topbar { margin-bottom: 24px; }
        .btn-sm { font-size: 0.7rem; padding: 0.2rem 0.5rem; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand"><h4><i class="fas fa-chart-line"></i> KeuanganApp</h4><p>PT Bukit Asam</p></div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php"><i class="fas fa-balance-scale"></i> Neraca</a>
    <a href="laba_rugi.php"><i class="fas fa-chart-line"></i> Laba Rugi</a>
    <a href="arus_kas.php"><i class="fas fa-money-bill-wave"></i> Arus Kas</a>
    <a href="rasio.php"><i class="fas fa-calculator"></i> Analisis Rasio</a>
    <div class="menu-section mt-3">ADMIN</div>
    <a href="users.php" class="active"><i class="fas fa-users"></i> Kelola User</a>
    <a href="audit_log.php"><i class="fas fa-history"></i> Audit Log</a>
    <a href="backup.php"><i class="fas fa-database"></i> Backup/Restore</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <h4><i class="fas fa-users me-2"></i> Manajemen Pengguna</h4>
    <?php if ($msg == 'added'): ?><div class="alert alert-success">User berhasil ditambahkan.</div><?php elseif ($msg == 'deleted'): ?><div class="alert alert-success">User berhasil dihapus.</div><?php endif; ?>
    <div class="card mb-3"><div class="card-header">Tambah User Baru</div><div class="card-body"><form method="POST" class="row g-2"><div class="col-md-3"><input type="text" name="username" class="form-control form-control-sm" placeholder="Username" required></div><div class="col-md-3"><input type="text" name="nama_lengkap" class="form-control form-control-sm" placeholder="Nama Lengkap" required></div><div class="col-md-3"><input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required></div><div class="col-md-2"><select name="role" class="form-select form-select-sm"><option value="admin">Admin</option><option value="finance">Finance</option><option value="viewer">Viewer</option></select></div><div class="col-md-1"><button type="submit" name="add_user" class="btn btn-success btn-sm w-100">Tambah</button></div></form></div></div>
    <div class="card"><div class="card-header">Daftar User</div><div class="card-body p-0"><table class="table table-bordered"><thead><tr><th>ID</th><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['username']) ?></td><td><?= htmlspecialchars($u['nama_lengkap']) ?></td><td><?= $u['role'] ?></td><td><?php if($u['id'] != $_SESSION['user_id']): ?><a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a><?php else: ?>-<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <a href="dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali ke Dashboard</a>
</div>
</body>
</html> 