<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tablerino - Instalare</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.card { background: #fff; border-radius: 12px; padding: 40px; width: 100%; max-width: 480px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
h1 { font-size: 24px; margin-bottom: 8px; color: #111; }
p.sub { color: #666; margin-bottom: 32px; font-size: 14px; }
label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }
input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 20px; outline: none; transition: border-color .2s; }
input:focus { border-color: #6c47ff; }
button { width: 100%; padding: 12px; background: #6c47ff; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
button:hover { background: #5a38e0; }
.msg { margin-top: 20px; padding: 12px; border-radius: 8px; font-size: 14px; display: none; }
.msg.success { background: #e8f5e9; color: #2e7d32; display: block; }
.msg.error { background: #ffebee; color: #c62828; display: block; }
</style>
</head>
<body>
<div class="card">
    <h1>🍽️ Tablerino</h1>
    <p class="sub">Configurează platforma înainte de prima utilizare.</p>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $host   = trim($_POST['db_host'] ?? '');
        $name   = trim($_POST['db_name'] ?? '');
        $user   = trim($_POST['db_user'] ?? '');
        $pass   = trim($_POST['db_pass'] ?? '');
        $url    = rtrim(trim($_POST['base_url'] ?? ''), '/');

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $sql = file_get_contents(__DIR__ . '/data/schema.sql');
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
                $pdo->exec($q);
            }

            $config = file_get_contents(__DIR__ . '/config.php');
            $config = str_replace(['{{DB_HOST}}','{{DB_NAME}}','{{DB_USER}}','{{DB_PASS}}','{{BASE_URL}}'], [$host,$name,$user,$pass,$url], $config);
            file_put_contents(__DIR__ . '/config.php', $config);

            echo '<div class="msg success">✅ Instalare finalizată! <a href="/admin/login.php">Mergi la login</a></div>';
        } catch (Exception $e) {
            echo '<div class="msg error">Eroare: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    ?>

    <form method="POST">
        <label>Host bază de date</label>
        <input type="text" name="db_host" value="localhost" required>
        <label>Nume bază de date</label>
        <input type="text" name="db_name" required>
        <label>Utilizator</label>
        <input type="text" name="db_user" required>
        <label>Parolă</label>
        <input type="password" name="db_pass">
        <label>URL platformă (ex: https://tablerino.ro)</label>
        <input type="text" name="base_url" required>
        <button type="submit">Instalează Tablerino</button>
    </form>
</div>
</body>
</html>
