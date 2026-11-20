<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['master_auth'])) {
    header('Location: /master/index.php');
    exit;
}

$eroare = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user  = trim($_POST['user'] ?? '');
    $parola = $_POST['parola'] ?? '';
    $q = db()->prepare('SELECT * FROM master_users WHERE email = ?');
    $q->execute([$user]);
    $master = $q->fetch();
    if ($master && password_verify($parola, $master['parola'])) {
        $_SESSION['master_auth'] = true;
        $_SESSION['master_email'] = $master['email'];
        header('Location: /master/index.php');
        exit;
    }
    $eroare = 'Credențiale incorecte.';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" sizes="32x32" href="/uploads/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/uploads/favicon-16.png">
<link rel="apple-touch-icon" href="/uploads/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino Master</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #0f0f1a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.card { background: #1a1a2e; border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 8px 40px rgba(0,0,0,0.4); }
h1 { color: #fff; font-size: 22px; margin-bottom: 4px; }
p.sub { color: #666; font-size: 13px; margin-bottom: 28px; }
.badge { display: inline-block; background: #6c47ff; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: 8px; letter-spacing: .5px; }
label { display: block; font-size: 12px; font-weight: 600; color: #888; margin-bottom: 6px; }
input { width: 100%; padding: 11px 14px; border: 1px solid #2a2a3e; border-radius: 8px; font-size: 14px; margin-bottom: 16px; outline: none; background: #0f0f1a; color: #fff; transition: border-color .2s; }
input:focus { border-color: #6c47ff; }
button { width: 100%; padding: 13px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
button:hover { background: #5a38e0; }
.eroare { background: rgba(220,53,69,0.15); color: #e57373; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
</style>
</head>
<body>
<div class="card">
    <h1>🍽️ Tablerino <span class="badge">MASTER</span></h1>
    <p class="sub">Panou de administrare platformă</p>
    <?php if ($eroare): ?>
        <div class="eroare"><?= htmlspecialchars($eroare) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="user" required autofocus>
        <label>Parolă</label>
        <input type="password" name="parola" required>
        <button type="submit">Intră în Master</button>
    </form>
</div>
</body>
</html>
