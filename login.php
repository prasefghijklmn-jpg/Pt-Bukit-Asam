<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        $pdo = Database::getConnection();
        // Langsung bandingkan plain text
        $stmt = $pdo->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $password == $user['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - PT Bukit Asam Tbk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 32px;
            width: 420px;
            max-width: 90%;
            padding: 40px 32px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-card .logo i { font-size: 48px; color: #3b82f6; margin-bottom: 16px; }
        .login-card h3 { font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .login-card p { color: #64748b; font-size: 0.85rem; margin-bottom: 32px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { font-size: 0.75rem; font-weight: 600; color: #334155; display: block; margin-bottom: 6px; }
        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .input-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        button {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.2s;
        }
        button:hover { background: #2563eb; transform: translateY(-2px); }
        .alert { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 12px; margin-bottom: 20px; font-size: 0.8rem; }
        .footer { margin-top: 24px; font-size: 0.7rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo"><i class="fas fa-chart-line"></i></div>
    <h3>PT Bukit Asam Tbk</h3>
    <p>Sistem Informasi Keuangan Terintegrasi</p>
    <?php if ($error): ?>
        <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" value="admin" required autofocus>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" value="123" required>
        </div>
        <button type="submit">Masuk ke Dashboard</button>
    </form>
    <div class="footer">© <?= date('Y') ?> PT Bukit Asam Tbk</div>
</div>
</body>
</html>