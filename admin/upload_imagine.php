<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$rid = $rest['id'];

header('Content-Type: application/json');

$tip = $_POST['tip'] ?? ''; // 'logo' sau 'bg'
if (!in_array($tip, ['logo', 'bg'])) jsonResponse(['ok' => false, 'msg' => 'Tip invalid'], 400);

if (empty($_FILES['imagine']) || $_FILES['imagine']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'msg' => 'Fișier invalid'], 400);
}

$file = $_FILES['imagine'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

if (!in_array($ext, $allowed)) {
    jsonResponse(['ok' => false, 'msg' => 'Format neacceptat. Folosește JPG, PNG, WEBP sau SVG.'], 400);
}

if ($file['size'] > 3 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'msg' => 'Fișierul e prea mare. Maximum 3MB.'], 400);
}

// Sterge imaginea veche
$camp = $tip === 'logo' ? 'logo' : 'bg_imagine';
$q = db()->prepare("SELECT $camp FROM restaurante WHERE id = ?");
$q->execute([$rid]);
$r = $q->fetch();
if ($r[$camp] && file_exists(__DIR__ . '/../' . $r[$camp])) {
    unlink(__DIR__ . '/../' . $r[$camp]);
}

$folder = $tip === 'logo' ? 'uploads/logo' : 'uploads/bg';
if (!is_dir(__DIR__ . '/../' . $folder)) {
    mkdir(__DIR__ . '/../' . $folder, 0755, true);
}

$numeFisier = $tip . '_' . $rid . '_' . time() . '.' . $ext;
$caleRelativa = $folder . '/' . $numeFisier;
$caleAbsoluta = __DIR__ . '/../' . $caleRelativa;

if (!move_uploaded_file($file['tmp_name'], $caleAbsoluta)) {
    jsonResponse(['ok' => false, 'msg' => 'Eroare la salvarea fișierului'], 500);
}

$q = db()->prepare("UPDATE restaurante SET $camp = ? WHERE id = ?");
$q->execute([$caleRelativa, $rid]);

jsonResponse(['ok' => true, 'cale' => $caleRelativa]);
