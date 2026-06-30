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
$tahun_lalu = $tahun - 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$pdo = Database::getConnection();

// Ambil semua item laba rugi
$stmt = $pdo->prepare("SELECT id, akun, kategori FROM laba_rugi_items ORDER BY urutan");
$stmt->execute();
$items = $stmt->fetchAll();

$data = [];
$pendapatan = $pendapatan_lalu = 0;
$beban = $beban_lalu = 0;

foreach ($items as $item) {
    $nilai = getNilaiLabaRugi($item['id'], $tahun);
    $nilai_lalu = getNilaiLabaRugi($item['id'], $tahun_lalu);
    // Filter pencarian
    if ($search != '' && stripos($item['akun'], $search) === false) continue;
    
    $perubahan = $nilai - $nilai_lalu;
    $persen = $nilai_lalu ? ($perubahan / abs($nilai_lalu)) * 100 : 0;
    $data[] = [
        'akun' => $item['akun'],
        'nilai' => $nilai,
        'nilai_lalu' => $nilai_lalu,
        'perubahan' => $perubahan,
        'persen' => $persen,
        'kategori' => $item['kategori']
    ];
    if ($item['kategori'] == 'PENDAPATAN') {
        $pendapatan += $nilai;
        $pendapatan_lalu += $nilai_lalu;
    } elseif ($item['kategori'] == 'BEBAN') {
        $beban += abs($nilai);
        $beban_lalu += abs($nilai_lalu);
    }
}
$laba_bersih = $pendapatan - $beban;
$laba_bersih_lalu = $pendapatan_lalu - $beban_lalu;
$laba_perubahan = $laba_bersih - $laba_bersih_lalu;
$laba_persen = $laba_bersih_lalu ? ($laba_perubahan / abs($laba_bersih_lalu)) * 100 : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laba Rugi - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> /* style sama seperti di neraca.php, bisa copy-paste */ </style>
</head>
<body>
<div class="sidebar"> <!-- salin sidebar dari neraca.php, ubah active sesuai halaman --> 
    <div class="brand"><h4><i class="fas fa-chart-line"></i> KeuanganApp</h4><p>PT Bukit Asam Tbk</p></div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php"><i class="fas fa-balance-scale"></i> Neraca</a>
    <a href="laba_rugi.php" class="active"><i class="fas fa-chart-line"></i> Laba Rugi</a>
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
        <h4><i class="fas fa-chart-line me-2"></i> Laporan Laba Rugi</h4>
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
                <a href="export_excel.php?jenis=laba_rugi&tahun=<?= $tahun ?>" class="btn btn-sm btn-success">Excel</a>
                <a href="export_pdf.php?jenis=laba_rugi&tahun=<?= $tahun ?>" class="btn btn-sm btn-danger">PDF</a>
            </div>
        </div>
    </div>
    <p class="text-muted small mb-3">Dalam jutaan Rupiah</p>

    <div class="card">
        <div class="card-header">Perbandingan Laba Rugi <?= $tahun ?> vs <?= $tahun_lalu ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Akun</th><th class="text-end"><?= $tahun ?></th><th class="text-end"><?= $tahun_lalu ?></th><th class="text-end">Perubahan (Rp)</th><th class="text-end">Perubahan (%)</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['akun']) ?></td>
                            <td class="text-end <?= $d['nilai'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($d['nilai'], 0, ',', '.') ?></td>
                            <td class="text-end <?= $d['nilai_lalu'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($d['nilai_lalu'], 0, ',', '.') ?></td>
                            <td class="text-end <?= $d['perubahan'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($d['perubahan'], 0, ',', '.') ?></td>
                            <td class="text-end <?= $d['persen'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($d['persen'], 2) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td><strong>LABA BERSIH TAHUN BERJALAN</strong></td>
                            <td class="text-end text-success"><strong><?= number_format($laba_bersih, 0, ',', '.') ?></strong></td>
                            <td class="text-end text-success"><strong><?= number_format($laba_bersih_lalu, 0, ',', '.') ?></strong></td>
                            <td class="text-end <?= $laba_perubahan >= 0 ? 'text-success' : 'text-danger' ?>"><strong><?= number_format($laba_perubahan, 0, ',', '.') ?></strong></td>
                            <td class="text-end <?= $laba_persen >= 0 ? 'text-success' : 'text-danger' ?>"><strong><?= number_format($laba_persen, 2) ?>%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>