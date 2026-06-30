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
$tipe_list = ['OPERASI', 'INVESTASI', 'PENDANAAN'];
$total_per_tipe = [];
$items_per_tipe = [];

$pdo = Database::getConnection();
foreach ($tipe_list as $tipe) {
    if ($search != '') {
        $stmt = $pdo->prepare("SELECT i.id, i.akun, COALESCE(v.nilai, 0) as nilai 
                               FROM arus_kas_items i 
                               LEFT JOIN arus_kas_values v ON i.id = v.item_id AND v.tahun = ? 
                               WHERE i.tipe_arus = ? AND i.akun LIKE ? 
                               ORDER BY i.urutan");
        $stmt->execute([$tahun, $tipe, "%$search%"]);
    } else {
        $stmt = $pdo->prepare("SELECT i.id, i.akun, COALESCE(v.nilai, 0) as nilai 
                               FROM arus_kas_items i 
                               LEFT JOIN arus_kas_values v ON i.id = v.item_id AND v.tahun = ? 
                               WHERE i.tipe_arus = ? 
                               ORDER BY i.urutan");
        $stmt->execute([$tahun, $tipe]);
    }
    $items = $stmt->fetchAll();
    $total = array_sum(array_column($items, 'nilai'));
    $items_per_tipe[$tipe] = $items;
    $total_per_tipe[$tipe] = $total;
}

$kenaikan_neto = $total_per_tipe['OPERASI'] + $total_per_tipe['INVESTASI'] + $total_per_tipe['PENDANAAN'];
$kas_awal = getNilaiNeraca(1, $tahun - 1);
$kas_akhir = getNilaiNeraca(1, $tahun);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Arus Kas - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> /* sama seperti sebelumnya */ </style>
</head>
<body>
<div class="sidebar"> <!-- salin sidebar dari neraca.php, ubah active ke arus_kas.php --> 
    <div class="brand"><h4><i class="fas fa-chart-line"></i> KeuanganApp</h4><p>PT Bukit Asam Tbk</p></div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php"><i class="fas fa-balance-scale"></i> Neraca</a>
    <a href="laba_rugi.php"><i class="fas fa-chart-line"></i> Laba Rugi</a>
    <a href="arus_kas.php" class="active"><i class="fas fa-money-bill-wave"></i> Arus Kas</a>
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
        <h4><i class="fas fa-money-bill-wave me-2"></i> Laporan Arus Kas</h4>
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
                <a href="export_excel.php?jenis=arus_kas&tahun=<?= $tahun ?>" class="btn btn-sm btn-success">Excel</a>
                <a href="export_pdf.php?jenis=arus_kas&tahun=<?= $tahun ?>" class="btn btn-sm btn-danger">PDF</a>
            </div>
        </div>
    </div>
    <p class="text-muted small mb-3">Dalam jutaan Rupiah</p>

    <?php foreach ($tipe_list as $tipe): ?>
    <div class="card">
        <div class="card-header">AKTIVITAS <?= $tipe ?></div>
        <div class="card-body p-0">
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Akun</th><th class="text-end">Jumlah (Juta)</th></tr></thead>
                <tbody>
                    <?php foreach ($items_per_tipe[$tipe] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['akun']) ?></td>
                        <td class="text-end <?= $item['nilai'] >= 0 ? 'positive' : 'negative' ?>"><?= number_format($item['nilai'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Arus Kas Bersih dari <?= $tipe ?></strong></td>
                        <td class="text-end <?= $total_per_tipe[$tipe] >= 0 ? 'positive' : 'negative' ?>"><strong><?= number_format($total_per_tipe[$tipe], 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-header">RINGKASAN ARUS KAS</div>
        <div class="card-body p-0">
            <table class="table table-bordered">
                <tr><td style="width:60%">Kenaikan (Penurunan) Neto Kas</td><td class="text-end"><?= number_format($kenaikan_neto, 0, ',', '.') ?></td></tr>
                <tr><td>Kas dan Setara Kas Awal Tahun</td><td class="text-end"><?= number_format($kas_awal, 0, ',', '.') ?></td></tr>
                <tr class="total-row"><td><strong>Kas dan Setara Kas Akhir Tahun</strong></td><td class="text-end"><strong><?= number_format($kas_akhir, 0, ',', '.') ?></strong></td></tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>