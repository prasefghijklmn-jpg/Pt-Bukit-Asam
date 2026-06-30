<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';
if (!isFinance()) { header("Location: dashboard.php"); exit; }

$csrf = generateCSRFToken();
$pdo = Database::getConnection();
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_neraca'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) die("CSRF invalid");
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $tahun = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);
    $nilai = filter_input(INPUT_POST, 'nilai', FILTER_VALIDATE_FLOAT);
    if ($item_id && $tahun && $nilai !== false) {
        $stmt = $pdo->prepare("SELECT id, nilai FROM neraca_values WHERE item_id = ? AND tahun = ?");
        $stmt->execute([$item_id, $tahun]);
        $existing = $stmt->fetch();
        if ($existing) {
            $old = $existing['nilai'];
            $upd = $pdo->prepare("UPDATE neraca_values SET nilai = ? WHERE item_id = ? AND tahun = ?");
            $upd->execute([$nilai, $item_id, $tahun]);
            logActivity($_SESSION['user_id'], 'UPDATE_NERACA', 'neraca_values', $item_id, $old, $nilai);
        } else {
            $ins = $pdo->prepare("INSERT INTO neraca_values (item_id, tahun, nilai) VALUES (?, ?, ?)");
            $ins->execute([$item_id, $tahun, $nilai]);
            logActivity($_SESSION['user_id'], 'INSERT_NERACA', 'neraca_values', $item_id, null, $nilai);
        }
        header("Location: input_data.php?msg=success");
        exit;
    }
}
$items = $pdo->query("SELECT id, akun, tipe_akun FROM neraca_items ORDER BY urutan")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Input Data Keuangan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; padding: 24px; }
        .card { border-radius: 20px; border: 1px solid #e2e8f0; }
        .card-header { background: white; border-bottom: 1px solid #f1f5f9; font-weight: 600; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4><i class="fas fa-edit me-2"></i> Input Data Keuangan</h4>
    <?php if ($msg == 'success'): ?><div class="alert alert-success">Data tersimpan</div><?php endif; ?>
    <div class="card"><div class="card-header">Tambah/Ubah Nilai Neraca</div><div class="card-body"><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><div class="row g-2"><div class="col-md-4"><select name="item_id" class="form-select form-select-sm" required><option value="">Pilih Akun</option><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['akun']) ?> (<?= $i['tipe_akun'] ?>)</option><?php endforeach; ?></select></div><div class="col-md-2"><input type="number" name="tahun" class="form-control form-control-sm" placeholder="Tahun" required></div><div class="col-md-4"><input type="number" step="any" name="nilai" class="form-control form-control-sm" placeholder="Nilai (Juta)" required></div><div class="col-md-2"><button type="submit" name="simpan_neraca" class="btn btn-primary btn-sm w-100">Simpan</button></div></div></form></div></div>
    <a href="dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali</a>
</div>
</body>
</html>