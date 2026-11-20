<?php
require_once __DIR__ . '/../config.php';
$rest = authRestaurant();
$rid = $rest['id'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'msg' => 'Metodă invalidă'], 405);
}

// Upload poză
if (!empty($_FILES['poza']['tmp_name'])) {
    $file = $_FILES['poza'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
        jsonResponse(['ok' => false, 'msg' => 'Format invalid. Acceptat: JPG, PNG, WEBP']);
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        jsonResponse(['ok' => false, 'msg' => 'Poza este prea mare (max 10MB)']);
    }

    $imageData = base64_encode(file_get_contents($file['tmp_name']));
    $mediaType = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : 'image/webp');

    $apiKey = defined('GROQ_KEY') ? GROQ_KEY : '';

    $payload = [
        'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
        'messages' => [[
            'role' => 'user',
            'content' => [
                [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:' . $mediaType . ';base64,' . $imageData]
                ],
                [
                    'type' => 'text',
                    'text' => 'Analyze this restaurant menu image and extract all products. Reply ONLY with valid JSON, no extra text, no markdown, no explanations.

The JSON format must be exactly:
{"categorii":[{"nume":"Category name","produse":[{"nume":"Product name","descriere":"Short description","pret":0.00,"ingrediente":"ingredient1, ingredient2","calorii":0,"proteine":0.00,"carbohidrati":0.00,"grasimi":0.00,"alergeni":"gluten, lactoza"}]}]}

Rules:
- Group products into logical categories (Appetizers, Main course, Desserts, Drinks etc.)
- Price must be decimal number. If not visible use 0.00
- Description max 80 characters
- ingrediente: comma separated list of ingredients if visible, empty string if not
- calorii: integer, 0 if not visible
- proteine/carbohidrati/grasimi: decimal numbers in grams, 0.00 if not visible
- alergeni: comma separated list of allergens if visible, empty string if not
- Reply ONLY with JSON, nothing else'
                ]
            ]
        ]],
        'max_tokens' => 2000,
        'temperature' => 0.1
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        $err = json_decode($response, true);
        $msg = $err['error']['message'] ?? 'Eroare la conectarea cu AI.';
        jsonResponse(['ok' => false, 'msg' => $msg]);
    }

    $result = json_decode($response, true);
    $text = $result['choices'][0]['message']['content'] ?? '';

    // Curăță răspunsul
    $text = preg_replace('/^```json\s*/i', '', trim($text));
    $text = preg_replace('/\s*```$/i', '', $text);
    $text = trim($text);

    $meniu = json_decode($text, true);

    if (!$meniu || !isset($meniu['categorii'])) {
        jsonResponse(['ok' => false, 'msg' => 'Nu am putut extrage produse din această imagine. Încearcă cu o poză mai clară a meniului.']);
    }

    jsonResponse(['ok' => true, 'meniu' => $meniu]);
}

// Salvare produse confirmate
$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (($body['actiune'] ?? '') === 'salveaza') {
    $categorii = $body['categorii'] ?? [];
    $salvate = 0;

    foreach ($categorii as $cat) {
        $numeCat = trim($cat['nume'] ?? '');
        if (!$numeCat) continue;

        $qc = db()->prepare('SELECT id FROM meniu_categorii WHERE restaurant_id = ? AND nume = ?');
        $qc->execute([$rid, $numeCat]);
        $catRow = $qc->fetch();

        if ($catRow) {
            $catId = $catRow['id'];
        } else {
            $qi = db()->prepare('INSERT INTO meniu_categorii (restaurant_id, nume) VALUES (?, ?)');
            $qi->execute([$rid, $numeCat]);
            $catId = db()->lastInsertId();
        }

        foreach ($cat['produse'] ?? [] as $produs) {
            $numeProd     = trim($produs['nume'] ?? '');
            $desc         = trim($produs['descriere'] ?? '');
            $pret         = (float)($produs['pret'] ?? 0);
            $ingrediente  = trim($produs['ingrediente'] ?? '');
            $calorii      = (int)($produs['calorii'] ?? 0);
            $proteine     = (float)($produs['proteine'] ?? 0);
            $carbohidrati = (float)($produs['carbohidrati'] ?? 0);
            $grasimi      = (float)($produs['grasimi'] ?? 0);
            $alergeni     = trim($produs['alergeni'] ?? '');
            if (!$numeProd) continue;

            $qp = db()->prepare('INSERT INTO meniu_produse (categorie_id, restaurant_id, nume, descriere, pret, ingrediente, calorii, proteine, carbohidrati, grasimi, alergeni) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $qp->execute([$catId, $rid, $numeProd, $desc ?: null, $pret, $ingrediente ?: null, $calorii ?: null, $proteine ?: null, $carbohidrati ?: null, $grasimi ?: null, $alergeni ?: null]);
            $salvate++;
        }
    }

    jsonResponse(['ok' => true, 'salvate' => $salvate]);
}

jsonResponse(['ok' => false, 'msg' => 'Acțiune necunoscută'], 400);
