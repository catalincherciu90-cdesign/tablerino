<?php
require_once __DIR__ . '/../config.php';

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

function getMasa(string $token): array {
    $q = db()->prepare('SELECT * FROM mese WHERE token = ? AND activa = 1');
    $q->execute([$token]);
    $m = $q->fetch();
    if (!$m) jsonResponse(['ok' => false, 'msg' => 'Masă invalidă'], 404);
    return $m;
}

switch ($actiune) {

    case 'meniu':
        $token = $_GET['token'] ?? '';
        $masa = getMasa($token);
        $rid = $masa['restaurant_id'];

        $qr = db()->prepare('SELECT tema, logo, text_bun_venit, bg_imagine, facebook, instagram, tiktok, whatsapp, limba FROM restaurante WHERE id = ?');
        $qr->execute([$rid]);
        $restaurant = $qr->fetch();
        $temaSlug = $restaurant['tema'] ?? 'italian';

        require_once __DIR__ . '/../teme.php';
        $tema = getTema($temaSlug);

        $qc = db()->prepare('SELECT * FROM meniu_categorii WHERE restaurant_id = ? ORDER BY ordine, id');
        $qc->execute([$rid]);
        $categorii = $qc->fetchAll();

        foreach ($categorii as &$cat) {
            $qp = db()->prepare('SELECT * FROM meniu_produse WHERE categorie_id = ? AND restaurant_id = ? ORDER BY ordine, id');
            $qp->execute([$cat['id'], $rid]);
            $cat['produse'] = $qp->fetchAll();
        }

        $social = [];
        if ($restaurant['facebook'])  $social['facebook']  = $restaurant['facebook'];
        if ($restaurant['instagram']) $social['instagram'] = $restaurant['instagram'];
        if ($restaurant['tiktok'])    $social['tiktok']    = $restaurant['tiktok'];
        if ($restaurant['whatsapp'])  $social['whatsapp']  = $restaurant['whatsapp'];

        $limba = $restaurant['limba'] ?? 'ro';

        jsonResponse([
            'ok' => true,
            'masa' => $masa,
            'categorii' => $categorii,
            'tema' => $tema,
            'tema_slug' => $temaSlug,
            'logo' => $restaurant['logo'],
            'text_bun_venit' => $restaurant['text_bun_venit'],
            'bg_imagine' => $restaurant['bg_imagine'],
            'social' => $social,
            'limba' => $limba
        ]);

    case 'trimite_comanda':
        $token = $body['token'] ?? '';
        $produse = $body['produse'] ?? [];
        $observatii = trim($body['observatii'] ?? '');

        if (!$token || empty($produse)) jsonResponse(['ok' => false, 'msg' => 'Date incomplete'], 400);

        $masa = getMasa($token);
        $rid = $masa['restaurant_id'];

        // Verifica daca exista deja o comanda activa pe masa (nu in plata_aleasa)
        $qex = db()->prepare("SELECT id FROM comenzi WHERE masa_id = ? AND status NOT IN ('servita','plata_aleasa') ORDER BY id DESC LIMIT 1");
        $qex->execute([$masa['id']]);
        $comandaExistenta = $qex->fetch();

        if ($comandaExistenta) {
            $comandaId = $comandaExistenta['id'];
        } else {
            $qnr = db()->prepare("SELECT COALESCE(MAX(nr_ordine_zi), 0) + 1 FROM comenzi WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()");
            $qnr->execute([$rid]);
            $nrOrdine = (int)$qnr->fetchColumn();

            $qins = db()->prepare('INSERT INTO comenzi (masa_id, restaurant_id, observatii, nr_ordine_zi) VALUES (?, ?, ?, ?)');
            $qins->execute([$masa['id'], $rid, $observatii, $nrOrdine]);
            $comandaId = db()->lastInsertId();
        }

        foreach ($produse as $p) {
            $pid = (int)($p['produs_id'] ?? 0);
            $qty = (int)($p['cantitate'] ?? 1);
            $qp = db()->prepare('SELECT * FROM meniu_produse WHERE id = ? AND restaurant_id = ? AND disponibil = 1');
            $qp->execute([$pid, $rid]);
            $produs = $qp->fetch();
            if (!$produs) continue;
            $total = $produs['pret'] * $qty;
            $qi = db()->prepare("INSERT INTO comanda_produse (comanda_id, produs_id, nume_produs, pret_unitar, cantitate, total, status) VALUES (?,?,?,?,?,?,'nou')");
            $qi->execute([$comandaId, $pid, $produs['nume'], $produs['pret'], $qty, $total]);
        }

        if ($comandaExistenta) {
            db()->prepare("UPDATE comenzi SET status = 'noua', updated_at = NOW() WHERE id = ?")->execute([$comandaId]);
        }

        jsonResponse(['ok' => true, 'comanda_id' => $comandaId]);

    case 'sumar_comanda':
        $token = $_GET['token'] ?? '';
        $masa = getMasa($token);
        // Arata comanda activa inclusiv plata_aleasa - dispare doar dupa elibereaza_masa (servita)
        $q = db()->prepare("SELECT id, status, metoda_plata FROM comenzi WHERE masa_id = ? AND status != 'servita' ORDER BY id DESC LIMIT 1");
        $q->execute([$masa['id']]);
        $comanda = $q->fetch();
        if (!$comanda) {
            jsonResponse(['ok' => true, 'produse' => [], 'total' => 0, 'status' => null]);
        }
        $qp = db()->prepare('SELECT * FROM comanda_produse WHERE comanda_id = ?');
        $qp->execute([$comanda['id']]);
        $produse = $qp->fetchAll();
        $total = array_sum(array_column($produse, 'total'));
        jsonResponse(['ok' => true, 'produse' => $produse, 'total' => $total, 'status' => $comanda['status'], 'metoda_plata' => $comanda['metoda_plata']]);

    case 'alege_plata':
        $token = $body['token'] ?? '';
        $metoda = $body['metoda'] ?? '';
        if (!in_array($metoda, ['cash','card'])) jsonResponse(['ok' => false, 'msg' => 'Metodă invalidă'], 400);
        $masa = getMasa($token);
        // Seteaza metoda de plata si trece la plata_aleasa - NU la servita
        $q = db()->prepare("UPDATE comenzi SET metoda_plata = ?, status = 'plata_aleasa' WHERE masa_id = ? AND status NOT IN ('servita','plata_aleasa')");
        $q->execute([$metoda, $masa['id']]);
        jsonResponse(['ok' => true]);

    case 'reclame':
        $token = $_GET['token'] ?? '';
        $masa = getMasa($token);
        $q = db()->prepare("SELECT * FROM reclame WHERE restaurant_id = ? AND activa = 1 ORDER BY ordine, id");
        $q->execute([$masa['restaurant_id']]);
        jsonResponse(['ok' => true, 'reclame' => $q->fetchAll()]);

    default:
        jsonResponse(['ok' => false, 'msg' => 'Actiune necunoscuta'], 400);
}
