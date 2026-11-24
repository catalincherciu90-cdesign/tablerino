<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$rid = $rest['id'];

header('Content-Type: application/json');

$metoda = $_SERVER['REQUEST_METHOD'];
$actiune = '';
$body = [];

if ($metoda === 'GET') {
    $actiune = $_GET['actiune'] ?? '';
} else {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $actiune = $body['actiune'] ?? '';
}

switch ($actiune) {

    case 'salveaza_tema':
        $tema = $body['tema'] ?? '';
        require_once __DIR__ . '/../teme.php';
        if (!array_key_exists($tema, TEME)) jsonResponse(['ok' => false, 'msg' => 'Temă invalidă'], 400);
        $q = db()->prepare('UPDATE restaurante SET tema = ? WHERE id = ?');
        $q->execute([$tema, $rid]);
        jsonResponse(['ok' => true]);

    case 'sumar_zi':
        $q = db()->prepare("
            SELECT COUNT(*) AS total_comenzi,
                COALESCE(SUM(cp.total), 0) AS total_vanzari,
                COALESCE(SUM(CASE WHEN c.metoda_plata = 'cash' THEN 1 ELSE 0 END), 0) AS comenzi_cash,
                COALESCE(SUM(CASE WHEN c.metoda_plata = 'card' THEN 1 ELSE 0 END), 0) AS comenzi_card
            FROM comenzi c
            LEFT JOIN comanda_produse cp ON cp.comanda_id = c.id
            WHERE c.restaurant_id = ? AND DATE(c.created_at) = CURDATE() AND c.status = 'servita'
        ");
        $q->execute([$rid]);
        $sumar = $q->fetch();
        jsonResponse(['ok' => true, 'total_comenzi' => $sumar['total_comenzi'], 'total_vanzari' => $sumar['total_vanzari'], 'comenzi_cash' => $sumar['comenzi_cash'], 'comenzi_card' => $sumar['comenzi_card']]);

    case 'comenzi':
        $q = db()->prepare("
            SELECT c.*, m.nume AS masa_nume FROM comenzi c
            JOIN mese m ON m.id = c.masa_id
            WHERE c.restaurant_id = ? AND c.status NOT IN ('servita')
            ORDER BY FIELD(c.status,'noua','in_pregatire','plata_aleasa'), c.created_at DESC
        ");
        $q->execute([$rid]);
        $comenzi = $q->fetchAll();
        foreach ($comenzi as &$c) {
            $qp = db()->prepare('SELECT * FROM comanda_produse WHERE comanda_id = ?');
            $qp->execute([$c['id']]);
            $c['produse'] = $qp->fetchAll();
        }
        jsonResponse(['ok' => true, 'comenzi' => $comenzi]);

    case 'istoric':
        $masa_id = isset($_GET['masa_id']) ? (int)$_GET['masa_id'] : 0;
        $data    = $_GET['data'] ?? '';
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = 20;
        $offset  = ($page - 1) * $limit;
        $where = "c.restaurant_id = ? AND c.status = 'servita'";
        $params = [$rid];
        if ($masa_id) { $where .= ' AND c.masa_id = ?'; $params[] = $masa_id; }
        if ($data)    { $where .= ' AND DATE(c.created_at) = ?'; $params[] = $data; }
        $qcount = db()->prepare("SELECT COUNT(*) FROM comenzi c WHERE $where");
        $qcount->execute($params);
        $total = (int)$qcount->fetchColumn();
        $q = db()->prepare("SELECT c.*, m.nume AS masa_nume FROM comenzi c JOIN mese m ON m.id = c.masa_id WHERE $where ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset");
        $q->execute($params);
        $comenzi = $q->fetchAll();
        foreach ($comenzi as &$c) {
            $qp = db()->prepare('SELECT * FROM comanda_produse WHERE comanda_id = ?');
            $qp->execute([$c['id']]);
            $c['produse'] = $qp->fetchAll();
            $c['total'] = array_sum(array_column($c['produse'], 'total'));
        }
        jsonResponse(['ok' => true, 'comenzi' => $comenzi, 'total' => $total, 'pagini' => ceil($total / $limit), 'page' => $page]);

    case 'schimba_status':
        $id = (int)($body['comanda_id'] ?? 0);
        $status = $body['status'] ?? '';
        if (!in_array($status, ['noua','in_pregatire','servita','plata_aleasa'])) jsonResponse(['ok' => false], 400);
        $q = db()->prepare('UPDATE comenzi SET status = ? WHERE id = ? AND restaurant_id = ?');
        $q->execute([$status, $id, $rid]);
        jsonResponse(['ok' => true]);

    case 'livreaza_produs':
        $prodId = (int)($body['comanda_produs_id'] ?? 0);
        $q = db()->prepare("UPDATE comanda_produse SET status = 'livrat' WHERE id = ?");
        $q->execute([$prodId]);
        $qcheck = db()->prepare("SELECT comanda_id FROM comanda_produse WHERE id = ?");
        $qcheck->execute([$prodId]);
        $row = $qcheck->fetch();
        if ($row) {
            $qall = db()->prepare("SELECT COUNT(*) FROM comanda_produse WHERE comanda_id = ? AND status = 'nou'");
            $qall->execute([$row['comanda_id']]);
            $raman = (int)$qall->fetchColumn();
            if ($raman === 0) {
                db()->prepare("UPDATE comenzi SET status = 'in_pregatire' WHERE id = ? AND status = 'noua'")->execute([$row['comanda_id']]);
            }
        }
        jsonResponse(['ok' => true]);

    case 'livreaza_toate':
        $comandaId = (int)($body['comanda_id'] ?? 0);
        $q = db()->prepare("UPDATE comanda_produse SET status = 'livrat' WHERE comanda_id = ? AND status = 'nou'");
        $q->execute([$comandaId]);
        $q2 = db()->prepare("UPDATE comenzi SET status = 'in_pregatire' WHERE id = ? AND restaurant_id = ? AND status = 'noua'");
        $q2->execute([$comandaId, $rid]);
        jsonResponse(['ok' => true]);

    case 'inchide_comanda':
        $id = (int)($body['comanda_id'] ?? 0);
        $metoda = $body['metoda_plata'] ?? '';
        if (!in_array($metoda, ['cash','card'])) jsonResponse(['ok' => false, 'msg' => 'Metodă de plată invalidă'], 400);
        $q = db()->prepare("UPDATE comenzi SET status = 'servita', metoda_plata = ? WHERE id = ? AND restaurant_id = ?");
        $q->execute([$metoda, $id, $rid]);
        jsonResponse(['ok' => true]);

    case 'elibereaza_masa':
        $masaId = (int)($body['masa_id'] ?? 0);
        $q = db()->prepare("UPDATE comenzi SET status = 'servita' WHERE masa_id = ? AND restaurant_id = ? AND status != 'servita'");
        $q->execute([$masaId, $rid]);
        jsonResponse(['ok' => true]);

    case 'categorii':
        $q = db()->prepare('SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id');
        $q->execute([$rid]);
        jsonResponse(['ok' => true, 'categorii' => $q->fetchAll()]);

    case 'produse':
        $catId = (int)($_GET['categorie_id'] ?? 0);
        $q = db()->prepare('SELECT * FROM meniu_produse WHERE restaurant_id = ? AND categorie_id = ? ORDER BY ordine, id');
        $q->execute([$rid, $catId]);
        jsonResponse(['ok' => true, 'produse' => $q->fetchAll()]);

    case 'adauga_categorie':
        $nume = trim($body['nume'] ?? '');
        if (!$nume) jsonResponse(['ok' => false, 'msg' => 'Numele este obligatoriu'], 400);
        $q = db()->prepare('INSERT INTO meniu_categorii (restaurant_id, nume) VALUES (?, ?)');
        $q->execute([$rid, $nume]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'sterge_categorie':
        $catId = (int)($body['categorie_id'] ?? 0);
        $q = db()->prepare('DELETE FROM meniu_categorii WHERE id = ? AND restaurant_id = ?');
        $q->execute([$catId, $rid]);
        jsonResponse(['ok' => true]);

    case 'adauga_produs':
        $catId = (int)($body['categorie_id'] ?? 0);
        $nume  = trim($body['nume'] ?? '');
        $pret  = (float)($body['pret'] ?? 0);
        $desc  = trim($body['descriere'] ?? '');
        if (!$catId || !$nume || $pret <= 0) jsonResponse(['ok' => false, 'msg' => 'Date incomplete'], 400);
        $q = db()->prepare('INSERT INTO meniu_produse (categorie_id, restaurant_id, nume, descriere, pret) VALUES (?,?,?,?,?)');
        $q->execute([$catId, $rid, $nume, $desc, $pret]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'editeaza_produs':
        $pid  = (int)($body['produs_id'] ?? 0);
        $nume = trim($body['nume'] ?? '');
        $desc = trim($body['descriere'] ?? '');
        $pret = (float)($body['pret'] ?? 0);
        $ingrediente  = trim($body['ingrediente'] ?? '');
        $alergeni     = trim($body['alergeni'] ?? '');
        $calorii      = (int)($body['calorii'] ?? 0);
        $proteine     = (float)($body['proteine'] ?? 0);
        $carbohidrati = (float)($body['carbohidrati'] ?? 0);
        $grasimi      = (float)($body['grasimi'] ?? 0);
        if (!$pid || !$nume || $pret <= 0) jsonResponse(['ok' => false, 'msg' => 'Date incomplete'], 400);
        $q = db()->prepare('UPDATE meniu_produse SET nume=?, descriere=?, pret=?, ingrediente=?, alergeni=?, calorii=?, proteine=?, carbohidrati=?, grasimi=? WHERE id=? AND restaurant_id=?');
        $q->execute([$nume, $desc ?: null, $pret, $ingrediente ?: null, $alergeni ?: null, $calorii ?: null, $proteine ?: null, $carbohidrati ?: null, $grasimi ?: null, $pid, $rid]);
        jsonResponse(['ok' => true]);

    case 'sterge_produs':
        $pid = (int)($body['produs_id'] ?? 0);
        $q = db()->prepare('DELETE FROM meniu_produse WHERE id = ? AND restaurant_id = ?');
        $q->execute([$pid, $rid]);
        jsonResponse(['ok' => true]);

    case 'toggle_disponibil':
        $pid = (int)($body['produs_id'] ?? 0);
        $q = db()->prepare('UPDATE meniu_produse SET disponibil = 1 - disponibil WHERE id = ? AND restaurant_id = ?');
        $q->execute([$pid, $rid]);
        jsonResponse(['ok' => true]);

    case 'reordoneaza_produse':
        $ordine = $body['ordine'] ?? [];
        $stmt = db()->prepare('UPDATE meniu_produse SET ordine = ? WHERE id = ? AND restaurant_id = ?');
        foreach ($ordine as $index => $produsId) {
            $stmt->execute([$index, (int)$produsId, $rid]);
        }
        jsonResponse(['ok' => true]);

    case 'reordoneaza_categorii':
        $ordine = $body['ordine'] ?? [];
        $stmt = db()->prepare('UPDATE meniu_categorii SET ordine = ? WHERE id = ? AND restaurant_id = ?');
        foreach ($ordine as $index => $catId) {
            $stmt->execute([$index, (int)$catId, $rid]);
        }
        jsonResponse(['ok' => true]);

    case 'mese':
        $q = db()->prepare('SELECT * FROM mese WHERE restaurant_id = ? ORDER BY id');
        $q->execute([$rid]);
        jsonResponse(['ok' => true, 'mese' => $q->fetchAll()]);

    case 'adauga_masa':
        $nume = trim($body['nume'] ?? '');
        if (!$nume) jsonResponse(['ok' => false], 400);
        $token = bin2hex(random_bytes(16));
        $q = db()->prepare('INSERT INTO mese (restaurant_id, nume, token) VALUES (?, ?, ?)');
        $q->execute([$rid, $nume, $token]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId(), 'token' => $token]);

    case 'sterge_masa':
        $mid = (int)($body['masa_id'] ?? 0);
        $q = db()->prepare('DELETE FROM mese WHERE id = ? AND restaurant_id = ?');
        $q->execute([$mid, $rid]);
        jsonResponse(['ok' => true]);

    case 'salveaza_profil_vizual':
        $text = trim($body['text_bun_venit'] ?? '');
        $q = db()->prepare('UPDATE restaurante SET text_bun_venit = ? WHERE id = ?');
        $q->execute([$text ?: null, $rid]);
        jsonResponse(['ok' => true]);

    case 'salveaza_social':
        $facebook  = trim($body['facebook'] ?? '');
        $instagram = trim($body['instagram'] ?? '');
        $tiktok    = trim($body['tiktok'] ?? '');
        $whatsapp  = trim($body['whatsapp'] ?? '');
        $q = db()->prepare('UPDATE restaurante SET facebook = ?, instagram = ?, tiktok = ?, whatsapp = ? WHERE id = ?');
        $q->execute([$facebook ?: null, $instagram ?: null, $tiktok ?: null, $whatsapp ?: null, $rid]);
        jsonResponse(['ok' => true]);

    case 'sterge_imagine':
        $tip = $body['tip'] ?? '';
        if (!in_array($tip, ['logo', 'bg_imagine'])) jsonResponse(['ok' => false], 400);
        $q = db()->prepare("SELECT $tip FROM restaurante WHERE id = ?");
        $q->execute([$rid]);
        $r = $q->fetch();
        if ($r && $r[$tip] && file_exists(__DIR__ . '/../' . $r[$tip])) unlink(__DIR__ . '/../' . $r[$tip]);
        $q2 = db()->prepare("UPDATE restaurante SET $tip = NULL WHERE id = ?");
        $q2->execute([$rid]);
        jsonResponse(['ok' => true]);

    case 'reclame':
        $q = db()->prepare('SELECT * FROM reclame WHERE restaurant_id = ? ORDER BY ordine, id');
        $q->execute([$rid]);
        jsonResponse(['ok' => true, 'reclame' => $q->fetchAll()]);

    case 'adauga_reclama':
        $titlu = trim($body['titlu'] ?? '');
        $text  = trim($body['text'] ?? '');
        $durata = (int)($body['durata'] ?? 5);
        $q = db()->prepare('INSERT INTO reclame (restaurant_id, titlu, text, durata) VALUES (?, ?, ?, ?)');
        $q->execute([$rid, $titlu ?: null, $text ?: null, $durata]);
        jsonResponse(['ok' => true, 'id' => db()->lastInsertId()]);

    case 'editeaza_reclama':
        $id    = (int)($body['id'] ?? 0);
        $titlu = trim($body['titlu'] ?? '');
        $text  = trim($body['text'] ?? '');
        $durata = (int)($body['durata'] ?? 5);
        $q = db()->prepare('UPDATE reclame SET titlu = ?, text = ?, durata = ? WHERE id = ? AND restaurant_id = ?');
        $q->execute([$titlu ?: null, $text ?: null, $durata, $id, $rid]);
        jsonResponse(['ok' => true]);

    case 'toggle_reclama':
        $id = (int)($body['reclama_id'] ?? $body['id'] ?? 0);
        $q = db()->prepare('UPDATE reclame SET activa = 1 - activa WHERE id = ? AND restaurant_id = ?');
        $q->execute([$id, $rid]);
        jsonResponse(['ok' => true]);

    case 'sterge_reclama':
        $id = (int)($body['reclama_id'] ?? $body['id'] ?? 0);
        $q = db()->prepare('SELECT imagine FROM reclame WHERE id = ? AND restaurant_id = ?');
        $q->execute([$id, $rid]);
        $r = $q->fetch();
        if ($r && $r['imagine'] && file_exists(__DIR__ . '/../' . $r['imagine'])) unlink(__DIR__ . '/../' . $r['imagine']);
        $q = db()->prepare('DELETE FROM reclame WHERE id = ? AND restaurant_id = ?');
        $q->execute([$id, $rid]);
        jsonResponse(['ok' => true]);

    case 'audit_seteaza_plata':
        $comandaId  = (int)($body['comanda_id'] ?? 0);
        $metoda     = $body['metoda_plata'] ?? '';
        $parola     = trim($body['parola'] ?? '');
        if (!in_array($metoda, ['cash','card'])) jsonResponse(['ok' => false, 'msg' => 'Metodă invalidă'], 400);
        $qp = db()->prepare('SELECT parola_reset FROM restaurante WHERE id = ?');
        $qp->execute([$rid]);
        $pr = $qp->fetchColumn();
        if (!$pr || !password_verify($parola, $pr)) jsonResponse(['ok' => false, 'msg' => t('audit_wrong_password')], 403);
        $q = db()->prepare("UPDATE comenzi SET metoda_plata = ?, status = 'servita' WHERE id = ? AND restaurant_id = ?");
        $q->execute([$metoda, $comandaId, $rid]);
        jsonResponse(['ok' => true]);

    case 'audit_inchide':
        $comandaId = (int)($body['comanda_id'] ?? 0);
        $parola    = trim($body['parola'] ?? '');
        $qp = db()->prepare('SELECT parola_reset FROM restaurante WHERE id = ?');
        $qp->execute([$rid]);
        $pr = $qp->fetchColumn();
        if (!$pr || !password_verify($parola, $pr)) jsonResponse(['ok' => false, 'msg' => t('audit_wrong_password')], 403);
        $q = db()->prepare("UPDATE comenzi SET status = 'servita' WHERE id = ? AND restaurant_id = ?");
        $q->execute([$comandaId, $rid]);
        jsonResponse(['ok' => true]);

    case 'audit_inchide_tot':
        $parola = trim($body['parola'] ?? '');
        $qp = db()->prepare('SELECT parola_reset FROM restaurante WHERE id = ?');
        $qp->execute([$rid]);
        $pr = $qp->fetchColumn();
        if (!$pr || !password_verify($parola, $pr)) jsonResponse(['ok' => false, 'msg' => t('audit_wrong_password')], 403);
        $q = db()->prepare("UPDATE comenzi SET status = 'servita' WHERE restaurant_id = ? AND status NOT IN ('servita')");
        $q->execute([$rid]);
        jsonResponse(['ok' => true]);

    case 'salveaza_parola_reset':
        $parola = trim($body['parola'] ?? '');
        if (strlen($parola) < 4) jsonResponse(['ok' => false, 'msg' => t('audit_password_too_short')], 400);
        $hash = password_hash($parola, PASSWORD_DEFAULT);
        $q = db()->prepare('UPDATE restaurante SET parola_reset = ? WHERE id = ?');
        $q->execute([$hash, $rid]);
        jsonResponse(['ok' => true]);

    default:
        jsonResponse(['ok' => false, 'msg' => 'Actiune necunoscuta'], 400);
}
