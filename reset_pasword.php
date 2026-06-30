<?php
require_once 'config/database.php';
$pdo = Database::getConnection();
$hash = password_hash('123', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$hash]);
echo "Password admin sekarang: 123";
?>