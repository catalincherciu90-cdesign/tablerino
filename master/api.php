<?php
require_once __DIR__ . '/../config.php';
authMaster();

header('Content-Type: application/json');

$actiune = $_GET['actiune'] ?? $_POST['actiune'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (isset($body['actiune'])) $actiune = $body['actiune'];

switch ($actiune) {

    case 'statistici':
        $stats = [];
        $stats['total_restaurante']   = db()->query("SELECT COUNT(*) FROM restaurante WHERE activ = 1")->fetchColumn();
        $stats['restaurante_active']  = db()->query("SELECT COUNT(*) FROM restaurante WHERE activ = 1")->fetchColumn();
        $stats['comenzi_azi']         = db()->query("SELECT COUNT(*) FROM comenzi WHERE DATE(created_at) = CURDATE() AND status = 'servita'")->fetchColumn();
        $stats['vanzari_azi']         = db()->query("SELECT COALESCE(SUM(cp.total),0) FROM comenzi c JOIN comanda_produse cp ON cp.comanda_id = c.id WHERE DATE(c.created_at) = CURDATE() AND c.status = 'servita'")->fetchColumn();
        $stats['total_comenzi_all']   = db()->query("SELECT COUNT(*) FROM comenzi WHERE status = 'servita'")->fetchColumn();
        $stats['cereri_pending']      = db()->query("SELECT COUNT(*) FROM restaurante WHERE activ = 0")->fetchColumn();
        jsonResponse(['ok' => true, 'stats' => $stats]);

    case 'restaurante':
        $q = db()->query("
            SELECT r.*,
                (SELECT COUNT(*) FROM mese WHERE restaurant_id = r.id) AS nr_mese,
                (SELECT COUNT(*) FROM comenzi WHERE restaurant_id = r.id AND DATE(created_at) = CURDATE() AND status = 'servita') AS comenzi_azi,
                (SELECT COALESCE(SUM(cp.total),0) FROM comenzi c JOIN comanda_produse cp ON cp.comanda_id = c.id WHERE c.restaurant_id = r.id AND DATE(c.created_at) = CURDATE() AND c.status = 'servita') AS vanzari_azi
            FROM restaurante r
            WHERE r.activ = 1
            ORDER BY r.id DESC
        ");
        jsonResponse(['ok' => true, 'restaurante' => $q->fetchAll()]);

    case 'cereri_inregistrare':
        $q = db()->query("SELECT id, nume, email, telefon, created_at FROM restaurante WHERE activ = 0 ORDER BY created_at DESC");
        jsonResponse(['ok' => true, 'cereri' => $q->fetchAll()]);

    case 'adauga_restaurant':
        $nume   = trim($body['nume'] ?? '');
        $email  = trim($body['email'] ?? '');
        $tel    = trim($body['telefon'] ?? '');
        $parola = $body['parola'] ?? '';
        if (!$nume || !$email || !$parola) jsonResponse(['ok' => false, 'msg' => 'Date incomplete'], 400);
        $hash = password_hash($parola, PASSWORD_DEFAULT);
        $q = db()->prepare("INSERT INTO restaurante (nume, email, telefon, parola, activ, tema, limba) VALUES (?,?,?,?,1,'italian','ro')");
        $q->execute([$nume, $email, $tel ?: null, $hash]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'editeaza_restaurant':
        $id    = (int)($body['id'] ?? 0);
        $nume  = trim($body['nume'] ?? '');
        $email = trim($body['email'] ?? '');
        $tel   = trim($body['telefon'] ?? '');
        $parola = $body['parola'] ?? '';
        if (!$id || !$nume || !$email) jsonResponse(['ok' => false, 'msg' => 'Date incomplete'], 400);
        if ($parola) {
            $hash = password_hash($parola, PASSWORD_DEFAULT);
            $q = db()->prepare("UPDATE restaurante SET nume=?, email=?, telefon=?, parola=? WHERE id=?");
            $q->execute([$nume, $email, $tel ?: null, $hash, $id]);
        } else {
            $q = db()->prepare("UPDATE restaurante SET nume=?, email=?, telefon=? WHERE id=?");
            $q->execute([$nume, $email, $tel ?: null, $id]);
        }
        jsonResponse(['ok' => true]);

    case 'toggle_activ':
        $id = (int)($body['id'] ?? 0);
        $q = db()->prepare("UPDATE restaurante SET activ = 1 - activ WHERE id = ?");
        $q->execute([$id]);
        jsonResponse(['ok' => true]);

    default:
        jsonResponse(['ok' => false, 'msg' => 'Acțiune necunoscută'], 400);
}
