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

// Statistik
$total_aset = totalByTipe('ASET_LANCAR', $tahun) + totalByTipe('ASET_TIDAK_LANCAR', $tahun);
$total_aset_lalu = totalByTipe('ASET_LANCAR', $tahun_lalu) + totalByTipe('ASET_TIDAK_LANCAR', $tahun_lalu);
$aset_persen = $total_aset_lalu ? (($total_aset - $total_aset_lalu) / $total_aset_lalu) * 100 : 0;

$total_liab = totalByTipe('LIABILITAS_JANGKA_PENDEK', $tahun) + totalByTipe('LIABILITAS_JANGKA_PANJANG', $tahun);
$total_liab_lalu = totalByTipe('LIABILITAS_JANGKA_PENDEK', $tahun_lalu) + totalByTipe('LIABILITAS_JANGKA_PANJANG', $tahun_lalu);
$liab_persen = $total_liab_lalu ? (($total_liab - $total_liab_lalu) / $total_liab_lalu) * 100 : 0;

$pendapatan = getNilaiLabaRugi(1, $tahun); // ID 1 = Pendapatan
$beban = abs(getNilaiLabaRugi(2, $tahun)) + abs(getNilaiLabaRugi(3, $tahun)) + abs(getNilaiLabaRugi(4, $tahun)) + abs(getNilaiLabaRugi(7, $tahun)) + abs(getNilaiLabaRugi(9, $tahun));
$laba = $pendapatan - $beban;
$pendapatan_lalu = getNilaiLabaRugi(1, $tahun_lalu);
$beban_lalu = abs(getNilaiLabaRugi(2, $tahun_lalu)) + abs(getNilaiLabaRugi(3, $tahun_lalu)) + abs(getNilaiLabaRugi(4, $tahun_lalu)) + abs(getNilaiLabaRugi(7, $tahun_lalu)) + abs(getNilaiLabaRugi(9, $tahun_lalu));
$laba_lalu = $pendapatan_lalu - $beban_lalu;
$laba_persen = $laba_lalu ? (($laba - $laba_lalu) / abs($laba_lalu)) * 100 : 0;

$kas = getNilaiNeraca(1, $tahun);
$kas_lalu = getNilaiNeraca(1, $tahun_lalu);
$kas_persen = $kas_lalu ? (($kas - $kas_lalu) / $kas_lalu) * 100 : 0;

// Data untuk grafik
$trend_aset = [];
$trend_pendapatan = [];
foreach ($years as $th) {
    $trend_aset[] = totalByTipe('ASET_LANCAR', $th) + totalByTipe('ASET_TIDAK_LANCAR', $th);
    $trend_pendapatan[] = getNilaiLabaRugi(1, $th);
}

// Transaksi terbaru (5 data dari arus kas)
$pdo = Database::getConnection();
$stmt = $pdo->prepare("SELECT a.akun, v.nilai, v.tahun FROM arus_kas_values v JOIN arus_kas_items a ON v.item_id = a.id WHERE v.tahun = ? ORDER BY v.id DESC LIMIT 5");
$stmt->execute([$tahun]);
$recent = $stmt->fetchAll();

// Prediksi laba
$prediksi = predictNextYearProfit();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .stat-card .title { font-size: 0.7rem; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .stat-card .value { font-size: 1.6rem; font-weight: 700; color: #0f172a; }
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .card-custom {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header-custom {
            padding: 14px 20px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .tahun-filter { background: white; border: 1px solid #cbd5e1; border-radius: 40px; padding: 6px 16px; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand">
        <h4><i class="fas fa-chart-line"></i> KeuanganApp</h4>
        <p>PT Bukit Asam Tbk</p>
    </div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php"><i class="fas fa-balance-scale"></i> Neraca</a>
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
        <div>
            <h4 class="mb-0" style="font-size:1.3rem;">Financial Dashboard</h4>
            <p class="text-muted small">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> (<?= $_SESSION['role'] ?>)</p>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="small">Tahun:</label>
            <select name="tahun" class="tahun-filter" onchange="this.form.submit()">
                <?php foreach ($years as $th): ?>
                    <option value="<?= $th ?>" <?= $th == $tahun ? 'selected' : '' ?>><?= $th ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="title">Total Aset</div>
                <div class="value">Rp <?= number_format($total_aset,0,',','.') ?></div>
                <div class="trend <?= $aset_persen>=0?'trend-up':'trend-down' ?>">
                    <?= $aset_persen>=0?'↑':'↓' ?> <?= number_format(abs($aset_persen),1) ?>% vs tahun lalu
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="title">Total Liabilitas</div>
                <div class="value">Rp <?= number_format($total_liab,0,',','.') ?></div>
                <div class="trend <?= $liab_persen>=0?'trend-up':'trend-down' ?>">
                    <?= $liab_persen>=0?'↑':'↓' ?> <?= number_format(abs($liab_persen),1) ?>% vs tahun lalu
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="title">Laba Bersih</div>
                <div class="value">Rp <?= number_format($laba,0,',','.') ?></div>
                <div class="trend <?= $laba_persen>=0?'trend-up':'trend-down' ?>">
                    <?= $laba_persen>=0?'↑':'↓' ?> <?= number_format(abs($laba_persen),1) ?>% vs tahun lalu
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="title">Kas & Setara Kas</div>
                <div class="value">Rp <?= number_format($kas,0,',','.') ?></div>
                <div class="trend <?= $kas_persen>=0?'trend-up':'trend-down' ?>">
                    <?= $kas_persen>=0?'↑':'↓' ?> <?= number_format(abs($kas_persen),1) ?>% vs tahun lalu
                </div>
            </div>
        </div>
    </div>

    <!-- Prediksi Laba -->
    <?php if ($prediksi): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-chart-line"></i> <strong>Prediksi Laba Bersih Tahun <?= $prediksi['tahun'] ?>:</strong> 
                Rp <?= number_format($prediksi['nilai'], 0, ',', '.') ?> juta (berdasarkan regresi linear data <?= implode(', ', $years) ?>)
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grafik & Transaksi -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-line text-primary me-2"></i> Tren Aset & Pendapatan</span>
                    <div>
                        <select id="chartType" class="form-select form-select-sm" style="width:130px;">
                            <option value="line">Line Chart</option>
                            <option value="bar">Bar Chart</option>
                            <option value="radar">Radar Chart</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-3">
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom">
                <div class="card-header-custom">
                    <i class="fas fa-clock text-warning me-2"></i> Transaksi Terbaru (Arus Kas)
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent)): ?>
                        <div class="p-3 text-muted text-center">Belum ada data</div>
                    <?php else: ?>
                        <?php foreach ($recent as $tr): ?>
                        <div class="transaction-item">
                            <div>
                                <div class="fw-bold small"><?= htmlspecialchars(substr($tr['akun'], 0, 35)) ?></div>
                                <div class="text-muted small">Tahun <?= $tr['tahun'] ?></div>
                            </div>
                            <div class="fw-bold <?= $tr['nilai'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                Rp <?= number_format($tr['nilai'], 0, ',', '.') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Data untuk chart
const chartLabels = <?= json_encode($years) ?>;
const chartDataAset = <?= json_encode($trend_aset) ?>;
const chartDataPendapatan = <?= json_encode($trend_pendapatan) ?>;

const ctx = document.getElementById('trendChart').getContext('2d');
let chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Total Aset (Juta)',
                data: chartDataAset,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.05)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#3b82f6'
            },
            {
                label: 'Pendapatan (Juta)',
                data: chartDataPendapatan,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.05)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#f59e0b'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        let label = ctx.dataset.label || '';
                        let value = ctx.raw;
                        return `${label}: Rp ${value.toLocaleString('id-ID')} Jt`;
                    }
                }
            },
            legend: { position: 'top', labels: { font: { size: 10 } } }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Juta Rupiah', font: { size: 10 } },
                ticks: { font: { size: 9 }, callback: (val) => 'Rp ' + val.toLocaleString('id-ID') }
            }
        }
    }
});

// Event listener untuk mengganti jenis chart
document.getElementById('chartType').addEventListener('change', function(e) {
    const newType = e.target.value;
    chart.destroy();
    chart = new Chart(ctx, {
        type: newType,
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Total Aset (Juta)',
                    data: chartDataAset,
                    borderColor: '#3b82f6',
                    backgroundColor: newType === 'radar' ? 'rgba(59,130,246,0.2)' : 'rgba(59,130,246,0.05)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Pendapatan (Juta)',
                    data: chartDataPendapatan,
                    borderColor: '#f59e0b',
                    backgroundColor: newType === 'radar' ? 'rgba(245,158,11,0.2)' : 'rgba(245,158,11,0.05)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: Rp ${ctx.raw.toLocaleString('id-ID')} Jt`
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>