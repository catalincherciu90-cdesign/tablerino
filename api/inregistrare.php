<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'msg' => 'Metodă invalidă'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$nume   = trim($body['nume'] ?? '');
$email  = trim($body['email'] ?? '');
$tel    = trim($body['telefon'] ?? '');
$parola = $body['parola'] ?? '';

if (!$nume || !$email || !$parola) {
    jsonResponse(['ok' => false, 'msg' => 'Completează toate câmpurile obligatorii.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'msg' => 'Adresa de email nu este validă.']);
}
if (strlen($parola) < 6) {
    jsonResponse(['ok' => false, 'msg' => 'Parola trebuie să aibă minim 6 caractere.']);
}

// Verifică dacă emailul există deja
$q = db()->prepare('SELECT id, activ FROM restaurante WHERE email = ?');
$q->execute([$email]);
$existent = $q->fetch();

if ($existent) {
    if ($existent['activ']) {
        jsonResponse(['ok' => false, 'msg' => 'Există deja un cont activ cu această adresă de email.']);
    } else {
        jsonResponse(['ok' => false, 'msg' => 'Cererea ta este deja în așteptare. Te vom contacta în curând.']);
    }
}

// Creează contul în așteptare (activ = 0, pending = 1)
$hash = password_hash($parola, PASSWORD_DEFAULT);
$q = db()->prepare('INSERT INTO restaurante (nume, email, telefon, parola, activ, tema, limba) VALUES (?, ?, ?, ?, 0, \'italian\', \'ro\')');
$q->execute([$nume, $email, $tel ?: null, $hash]);

jsonResponse(['ok' => true]);
