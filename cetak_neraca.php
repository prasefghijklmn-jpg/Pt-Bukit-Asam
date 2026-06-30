<?php
// cetak_neraca.php
// File ini dipanggil oleh export_excel.php atau export_pdf.php
// Sudah memiliki variabel $tahun dan $jenis dari file pemanggil

// Jika tidak ada variabel $tahun (misal dipanggil langsung), ambil dari GET
if (!isset($tahun)) {
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
}

// Koneksi database (pastikan fungsi helper tersedia)
require_once 'config/database.php';
require_once 'config/functions.php';

// Ambil data untuk laporan (gunakan fungsi yang sudah ada)
function getItemsByTipeCetak($tipe, $tahun) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT i.akun, COALESCE(v.nilai, 0) as nilai 
                           FROM neraca_items i 
                           LEFT JOIN neraca_values v ON i.id = v.item_id AND v.tahun = ? 
                           WHERE i.tipe_akun = ? 
                           ORDER BY i.urutan");
    $stmt->execute([$tahun, $tipe]);
    return $stmt->fetchAll();
}

$al = totalByTipe('ASET_LANCAR', $tahun);
$at = totalByTipe('ASET_TIDAK_LANCAR', $tahun);
$ljp = totalByTipe('LIABILITAS_JANGKA_PENDEK', $tahun);
$ljpn = totalByTipe('LIABILITAS_JANGKA_PANJANG', $tahun);
$ek = totalByTipe('EKUITAS', $tahun);
$total_aset = $al + $at;
$total_liab_ek = $ljp + $ljpn + $ek;

$aset_lancar_items = getItemsByTipeCetak('ASET_LANCAR', $tahun);
$aset_tidak_lancar_items = getItemsByTipeCetak('ASET_TIDAK_LANCAR', $tahun);
$liabilitas_pendek_items = getItemsByTipeCetak('LIABILITAS_JANGKA_PENDEK', $tahun);
$liabilitas_panjang_items = getItemsByTipeCetak('LIABILITAS_JANGKA_PANJANG', $tahun);
$ekuitas_items = getItemsByTipeCetak('EKUITAS', $tahun);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Neraca PT Bukit Asam <?= $tahun ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        .text-end { text-align: right; }
        .total-row { background: #f0f0f0; font-weight: bold; }
        h3, h4 { text-align: center; margin-bottom: 5px; }
        .sub-title { text-align: center; font-size: 10pt; margin-bottom: 20px; }
    </style>
</head>
<body>
<h3>PT Bukit Asam Tbk</h3>
<h4>Laporan Neraca Per 31 Desember <?= $tahun ?></h4>
<div class="sub-title">(Dalam jutaan Rupiah)</div>

<table>
    <tr><th colspan="2">ASET</th><th colspan="2">LIABILITAS & EKUITAS</th></tr>
    <tr style="background:#eaeaea;"><td><strong>ASET LANCAR</strong></td><td class="text-end"><strong><?= number_format($al,0,',','.') ?></strong></td><td><strong>LIABILITAS JANGKA PENDEK</strong></td><td class="text-end"><strong><?= number_format($ljp,0,',','.') ?></strong></td></tr>
    <?php foreach ($aset_lancar_items as $item): ?>
    <tr><td style="padding-left:20px;"><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'],0,',','.') ?></td><td></td><td></td></tr>
    <?php endforeach; ?>
    <tr><td><strong>ASET TIDAK LANCAR</strong></td><td class="text-end"><strong><?= number_format($at,0,',','.') ?></strong></td><td><strong>LIABILITAS JANGKA PANJANG</strong></td><td class="text-end"><strong><?= number_format($ljpn,0,',','.') ?></strong></td></tr>
    <?php foreach ($aset_tidak_lancar_items as $item): ?>
    <tr><td style="padding-left:20px;"><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'],0,',','.') ?></td><td></td><td></td></tr>
    <?php endforeach; ?>
    <tr class="total-row"><td><strong>TOTAL ASET</strong></td><td class="text-end"><?= number_format($total_aset,0,',','.') ?></td><td><strong>EKUITAS</strong></td><td class="text-end"><strong><?= number_format($ek,0,',','.') ?></strong></td></tr>
    <?php foreach ($ekuitas_items as $item): ?>
    <tr><td></td><td></td><td style="padding-left:20px;"><?= htmlspecialchars($item['akun']) ?></td><td class="text-end"><?= number_format($item['nilai'],0,',','.') ?></td></tr>
    <?php endforeach; ?>
    <tr class="total-row"><td></td><td></td><td><strong>TOTAL LIABILITAS & EKUITAS</strong></td><td class="text-end"><?= number_format($total_liab_ek,0,',','.') ?></td></tr>
</table>
<p style="font-size:9pt; margin-top:20px;">* Laporan ini dihasilkan secara otomatis oleh Sistem Informasi Keuangan PT Bukit Asam Tbk.</p>
</body>
</html>