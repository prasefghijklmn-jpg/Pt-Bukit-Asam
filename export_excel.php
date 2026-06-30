<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'neraca';

// Pastikan tahun yang diminta valid
$availableYears = getAvailableYears();
if (!in_array($tahun, $availableYears)) {
    $tahun = $availableYears[0];
}

// Set header untuk Excel (force download)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_$jenis-$tahun.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Matikan buffering agar tidak ada output lain sebelum file cetak
ob_end_clean();

// Sertakan file cetak (yang sudah menampilkan data dalam bentuk tabel HTML)
include "cetak_$jenis.php";
exit;
?>