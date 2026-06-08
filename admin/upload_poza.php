<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$rid = $rest['id'];

header('Content-Type: application/json');

$pid = (int)($_POST['produs_id'] ?? 0);
if (!$pid) jsonResponse(['ok' => false, 'msg' => 'ID produs lipsă'], 400);

// Verifica ca produsul apartine acestui restaurant
$q = db()->prepare('SELECT id, poza FROM meniu_produse WHERE id = ? AND restaurant_id = ?');
$q->execute([$pid, $rid]);
$produs = $q->fetch();
if (!$produs) jsonResponse(['ok' => false, 'msg' => 'Produs negăsit'], 404);

if (empty($_FILES['poza']) || $_FILES['poza']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'msg' => 'Fișier invalid'], 400);
}

$file = $_FILES['poza'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    jsonResponse(['ok' => false, 'msg' => 'Format neacceptat. Folosește JPG, PNG sau WEBP.'], 400);
}

if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'msg' => 'Poza e prea mare. Maximum 2MB.'], 400);
}

// Sterge poza veche daca exista
if ($produs['poza'] && file_exists(__DIR__ . '/../' . $produs['poza'])) {
    unlink(__DIR__ . '/../' . $produs['poza']);
}

$numeFisier = 'prod_' . $rid . '_' . $pid . '_' . time() . '.' . $ext;
$caleRelativa = 'uploads/produse/' . $numeFisier;
$caleAbsoluta = __DIR__ . '/../' . $caleRelativa;

if (!move_uploaded_file($file['tmp_name'], $caleAbsoluta)) {
    jsonResponse(['ok' => false, 'msg' => 'Eroare la salvarea fișierului'], 500);
}

$q = db()->prepare('UPDATE meniu_produse SET poza = ? WHERE id = ? AND restaurant_id = ?');
$q->execute([$caleRelativa, $pid, $rid]);

jsonResponse(['ok' => true, 'poza' => $caleRelativa]);
