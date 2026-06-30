<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$years = getAvailableYears();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $years[0];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

function getItemsByTipe($tipe, $tahun, $search = '') {
    $pdo = Database::getConnection();
    if ($search != '') {
        $stmt = $pdo->prepare("SELECT i.akun, COALESCE(v.nilai, 0) as nilai 
                               FROM neraca_items i 
                               LEFT JOIN neraca_values v ON i.id = v.item_id AND v.tahun = ? 
                               WHERE i.tipe_akun = ? AND i.akun LIKE ? 
                               ORDER BY i.urutan");
        $stmt->execute([$tahun, $tipe, "%$search%"]);
    } else {
        $stmt = $pdo->prepare("SELECT i.akun, COALESCE(v.nilai, 0) as nilai 
                               FROM neraca_items i 
                               LEFT JOIN neraca_values v ON i.id = v.item_id AND v.tahun = ? 
                               WHERE i.tipe_akun = ? 
                               ORDER BY i.urutan");
        $stmt->execute([$tahun, $tipe]);
    }
    return $stmt->fetchAll();
}

$al = totalByTipe('ASET_LANCAR', $tahun);
$at = totalByTipe('ASET_TIDAK_LANCAR', $tahun);
$ljp = totalByTipe('LIABILITAS_JANGKA_PENDEK', $tahun);
$ljpn = totalByTipe('LIABILITAS_JANGKA_PANJANG', $tahun);
$ek = totalByTipe('EKUITAS', $tahun);
$total_aset = $al + $at;
$total_liab_ek = $ljp + $ljpn + $ek;

// Ambil items dengan filter search
$aset_lancar_items = getItemsByTipe('ASET_LANCAR', $tahun, $search);
$aset_tidak_lancar_items = getItemsByTipe('ASET_TIDAK_LANCAR', $tahun, $search);
$liabilitas_pendek_items = getItemsByTipe('LIABILITAS_JANGKA_PENDEK', $tahun, $search);
$liabilitas_panjang_items = getItemsByTipe('LIABILITAS_JANGKA_PANJANG', $tahun, $search);
$ekuitas_items = getItemsByTipe('EKUITAS', $tahun, $search);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Neraca - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 14px; }
        .sidebar { width: 260px; background: #fff; position: fixed; left: 0; top: 0; bottom: 0; padding: 24px 0; box-shadow: 2px 0 12px rgba(0,0,0,0.03); border-right: 1px solid #e2e8f0; }
        .sidebar .brand { padding: 0 20px 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .sidebar .brand h4 { color: #0f172a; font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
        .sidebar .brand p { color: #64748b; font-size: 0.7rem; }
        .sidebar .menu-section { color: #94a3b8; font-size: 0.7rem; letter-spacing: 1px; padding: 12px 20px 4px; font-weight: 600; }
        .sidebar a { display: flex; gap: 12px; padding: 8px 20px; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 500; transition: 0.2s; border-radius: 0 30px 30px 0; margin-right: 12px; }
        .sidebar a i { width: 24px; font-size: 0.9rem; color: #64748b; }
        .sidebar a:hover, .sidebar a.active { background: #eff6ff; color: #3b82f6; border-left: 3px solid #f59e0b; }
        .content { margin-left: 260px; padding: 24px 28px; }
        .card { border: none; border-radius: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 24px; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; font-weight: 600; padding: 12px 20px; font-size: 0.85rem; }
        .table { margin-bottom: 0; font-size: 0.75rem; }
        .table td, .table th { padding: 8px 12px; vertical-align: middle; }
        .total-row { background: #f8fafc; font-weight: 600; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .tahun-filter { background: white; border: 1px solid #cbd5e1; border-radius: 40px; padding: 6px 16px; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand"><h4><i class="fas fa-chart-line"></i> KeuanganApp</h4><p>PT Bukit Asam Tbk</p></div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php" class="active"><i class="fas fa-balance-scale"></i> Neraca</a>
    <a href="laba_rugi.php"><i class="fas fa-chart-line"></i> Laba Rugi</a>
    <a href="arus_kas.php"><i class="fas fa-money-bill-wave"></i> Arus Kas</a>
    <a href="rasio.php"><i class="fas fa-calculator"></i> Analisis Rasio</a>
    <?php if (isAdmin()): ?>
        <div class="menu-section mt-3">ADMIN</div>
        <a href="users.php"><i class="fas fa-users"></i> Kelola User</a>
        <a href="audit_log.php"><i class="fas fa-history"></i> Audit Log</a>
        <a href="backup.php"><i class="fas fa-database"></i> Backup/Restore</a>
    <?php endif; ?>
    <?php if (isFinance()): ?>
        <a href="input_data.php"><i class="fas fa-edit"></i> Input Data</a>
    <?php endif; ?>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <div class="topbar">
        <h4><i class="fas fa-balance-scale me-2"></i> Laporan Neraca</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari akun..." value="<?= htmlspecialchars($search) ?>">
                <select name="tahun" class="tahun-filter" onchange="this.form.submit()">
                    <?php foreach ($years as $th): ?>
                        <option value="<?= $th ?>" <?= $th == $tahun ? 'selected' : '' ?>><?= $th ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
            </form>
            <div class="btn-group">
                <a href="export_excel.php?jenis=neraca&tahun=<?= $tahun ?>" class="btn btn-sm btn-success">Excel</a>
                <a href="export_pdf.php?jenis=neraca&tahun=<?= $tahun ?>" class="btn btn-sm btn-danger">PDF</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <!-- ASET LANCAR -->
            <div class="card">
                <div class="card-header">ASET LANCAR</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($aset_lancar_items as $item): ?>
                            <tr><td><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'], 0, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td><strong>Jumlah Aset Lancar</strong></td><td class="text-end"><strong><?= number_format($al, 0, ',', '.') ?></strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- ASET TIDAK LANCAR -->
            <div class="card mt-3">
                <div class="card-header">ASET TIDAK LANCAR</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($aset_tidak_lancar_items as $item): ?>
                            <tr><td><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'], 0, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td><strong>Jumlah Aset Tidak Lancar</strong></td><td class="text-end"><strong><?= number_format($at, 0, ',', '.') ?></strong></td></tr>
                            <tr class="total-row bg-light"><td><strong>TOTAL ASET</strong></td><td class="text-end"><strong><?= number_format($total_aset, 0, ',', '.') ?></strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <!-- LIABILITAS JANGKA PENDEK -->
            <div class="card">
                <div class="card-header">LIABILITAS JANGKA PENDEK</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($liabilitas_pendek_items as $item): ?>
                            <tr><td><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'], 0, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td><strong>Jumlah Liabilitas Jangka Pendek</strong></td><td class="text-end"><strong><?= number_format($ljp, 0, ',', '.') ?></strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- LIABILITAS JANGKA PANJANG -->
            <div class="card mt-3">
                <div class="card-header">LIABILITAS JANGKA PANJANG</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($liabilitas_panjang_items as $item): ?>
                            <tr><td><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'], 0, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td><strong>Jumlah Liabilitas Jangka Panjang</strong></td><td class="text-end"><strong><?= number_format($ljpn, 0, ',', '.') ?></strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- EKUITAS -->
            <div class="card mt-3">
                <div class="card-header">EKUITAS</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($ekuitas_items as $item): ?>
                            <tr><td><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'], 0, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td><strong>Jumlah Ekuitas</strong></td><td class="text-end"><strong><?= number_format($ek, 0, ',', '.') ?></strong></td></tr>
                            <tr class="total-row bg-light"><td><strong>TOTAL LIABILITAS & EKUITAS</strong></td><td class="text-end"><strong><?= number_format($total_liab_ek, 0, ',', '.') ?></strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>