<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$years = getAvailableYears();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $years[0];

$aset_lancar = totalByTipe('ASET_LANCAR', $tahun);
$persediaan = getNilaiNeraca(5, $tahun);
$kas = getNilaiNeraca(1, $tahun);
$liab_pendek = totalByTipe('LIABILITAS_JANGKA_PENDEK', $tahun);
$liab_panjang = totalByTipe('LIABILITAS_JANGKA_PANJANG', $tahun);
$total_aset = totalByTipe('ASET_LANCAR', $tahun) + totalByTipe('ASET_TIDAK_LANCAR', $tahun);
$ekuitas = totalByTipe('EKUITAS', $tahun);
$pendapatan = getNilaiLabaRugi(1, $tahun);
$hpp = abs(getNilaiLabaRugi(2, $tahun));
$piutang = getNilaiNeraca(3, $tahun);
$ebit = $pendapatan + getNilaiLabaRugi(2,$tahun) + getNilaiLabaRugi(3,$tahun) + getNilaiLabaRugi(4,$tahun);
$beban_bunga = abs(getNilaiLabaRugi(7, $tahun));

$current_ratio = $liab_pendek ? $aset_lancar / $liab_pendek : 0;
$quick_ratio = $liab_pendek ? ($aset_lancar - $persediaan) / $liab_pendek : 0;
$cash_ratio = $liab_pendek ? $kas / $liab_pendek : 0;
$dar = $total_aset ? ($liab_pendek+$liab_panjang) / $total_aset : 0;
$der = $ekuitas ? ($liab_pendek+$liab_panjang) / $ekuitas : 0;
$interest_coverage = $beban_bunga ? $ebit / $beban_bunga : 0;
$inventory_turnover = $persediaan ? $hpp / $persediaan : 0;
$receivable_turnover = $piutang ? $pendapatan / $piutang : 0;
$total_asset_turnover = $total_aset ? $pendapatan / $total_aset : 0;
$dso = $receivable_turnover ? 365 / $receivable_turnover : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Analisis Rasio - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: #f8fafc; font-family: 'Inter', sans-serif; font-size: 14px; }
        .sidebar { width: 260px; background: #ffffff; position: fixed; left: 0; top: 0; bottom: 0; padding: 24px 0; box-shadow: 2px 0 12px rgba(0,0,0,0.03); border-right: 1px solid #e2e8f0; }
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
        .table td, .table th { padding: 8px 12px; font-size: 0.75rem; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .tahun-filter { background: white; border: 1px solid #cbd5e1; border-radius: 40px; padding: 6px 16px; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand"><h4><i class="fas fa-chart-line"></i> KeuanganApp</h4><p>PT Bukit Asam Tbk</p></div>
    <div class="menu-section">MAIN MENU</div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="neraca.php"><i class="fas fa-balance-scale"></i> Neraca</a>
    <a href="laba_rugi.php"><i class="fas fa-chart-line"></i> Laba Rugi</a>
    <a href="arus_kas.php"><i class="fas fa-money-bill-wave"></i> Arus Kas</a>
    <a href="rasio.php" class="active"><i class="fas fa-calculator"></i> Analisis Rasio</a>
    <?php if (isAdmin()): ?><a href="users.php"><i class="fas fa-users"></i> Kelola User</a><a href="audit_log.php"><i class="fas fa-history"></i> Audit Log</a><a href="backup.php"><i class="fas fa-database"></i> Backup</a><?php endif; ?>
    <?php if (isFinance()): ?><a href="input_data.php"><i class="fas fa-edit"></i> Input Data</a><?php endif; ?>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <div class="topbar">
        <h4><i class="fas fa-calculator me-2"></i> Analisis Rasio Keuangan</h4>
        <form method="GET"><select name="tahun" class="tahun-filter" onchange="this.form.submit()"><?php foreach($years as $th) echo "<option value='$th' ".($th==$tahun?'selected':'').">$th</option>"; ?></select></form>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header">A. Likuiditas</div><div class="card-body p-0"><table class="table table-bordered"><tr><th>Rasio</th><th>Nilai</th><th>Interpretasi</th></tr><tr><td>Current Ratio</td><td><?= number_format($current_ratio,2) ?> x</td><td><?= $current_ratio>=1.5 ? 'Baik' : ($current_ratio>=1 ? 'Cukup' : 'Kurang') ?></td></tr><tr><td>Quick Ratio</td><td><?= number_format($quick_ratio,2) ?> x</td><td><?= $quick_ratio>=1 ? 'Baik' : 'Perhatian' ?></td></tr><tr><td>Cash Ratio</td><td><?= number_format($cash_ratio,2) ?> x</td><td><?= $cash_ratio>=0.5 ? 'Cukup' : 'Rendah' ?></td></tr></table></div></div>
            <div class="card mt-3"><div class="card-header">B. Solvabilitas</div><div class="card-body p-0"><table class="table table-bordered"><tr><th>Rasio</th><th>Nilai</th><th>Interpretasi</th></tr><tr><td>Debt to Asset (DAR)</td><td><?= number_format($dar*100,2) ?>%</td><td><?= $dar<=0.5 ? 'Solvabel' : 'Cukup' ?></td></tr><tr><td>Debt to Equity (DER)</td><td><?= number_format($der,2) ?> x</td><td><?= $der<=1 ? 'Sehat' : 'Tinggi' ?></td></tr><tr><td>Interest Coverage</td><td><?= number_format($interest_coverage,2) ?> x</td><td><?= $interest_coverage>=3 ? 'Sangat Baik' : 'Cukup' ?></td></tr></table></div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header">C. Aktivitas</div><div class="card-body p-0"><table class="table table-bordered"><tr><th>Rasio</th><th>Nilai</th></tr><tr><td>Inventory Turnover</td><td><?= number_format($inventory_turnover,2) ?> x</td></tr><tr><td>Receivable Turnover</td><td><?= number_format($receivable_turnover,2) ?> x</td></tr><tr><td>Total Asset Turnover</td><td><?= number_format($total_asset_turnover,2) ?> x</td></tr><tr><td>Days Sales Outstanding (DSO)</td><td><?= number_format($dso,0) ?> hari</td></tr></table></div></div>
        </div>
    </div>
</div>
</body>
</html>