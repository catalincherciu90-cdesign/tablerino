<?php
require_once __DIR__ . '/../config.php';
authMaster();

// Valori default pentru toate câmpurile
$defaults = [
    // Hero
    'hero_eyebrow_ro'       => 'Soluția digitală pentru restaurante',
    'hero_eyebrow_en'       => 'The digital solution for restaurants',
    'hero_title_ro'         => 'Comenzi la masă,<br><em>reimaginate.</em>',
    'hero_title_en'         => 'Table ordering,<br><em>reimagined.</em>',
    'hero_desc_ro'          => 'Tablerino transformă experiența de comandă în restaurantul tău. Clienții comandă direct de pe tabletă sau telefon, bucătăria primește instant, tu controlezi totul.',
    'hero_desc_en'          => 'Tablerino transforms the ordering experience in your restaurant. Customers order directly from the tablet or phone, the kitchen receives instantly, you control everything.',
    'hero_btn_ro'           => 'Solicită acces',
    'hero_btn_en'           => 'Request access',
    'hero_btn2_ro'          => 'Cum funcționează',
    'hero_btn2_en'          => 'How it works',
    'hero_price'            => '€100',
    'hero_price_period_ro'  => '/ lună',
    'hero_price_period_en'  => '/ month',
    'hero_price_label_ro'   => 'Abonament lunar',
    'hero_price_label_en'   => 'Monthly subscription',

    // Stats
    'stat1_num'             => '∞',
    'stat1_ro'              => 'Mese configurabile',
    'stat1_en'              => 'Configurable tables',
    'stat2_num'             => '5s',
    'stat2_ro'              => 'Timp de refresh comenzi',
    'stat2_en'              => 'Order refresh time',
    'stat3_num'             => '2',
    'stat3_ro'              => 'Limbi disponibile',
    'stat3_en'              => 'Available languages',
    'stat4_num'             => 'PWA',
    'stat4_ro'              => 'Funcționează ca aplicație',
    'stat4_en'              => 'Works as an app',

    // Features titlu
    'features_eyebrow_ro'   => 'Tot ce ai nevoie',
    'features_eyebrow_en'   => 'Everything you need',
    'features_title_ro'     => 'Platformă completă,<br><em>gândită pentru restaurante</em>',
    'features_title_en'     => 'Complete platform,<br><em>built for restaurants</em>',

    // Features cards
    'f1_title_ro' => 'Comandă de pe orice dispozitiv', 'f1_title_en' => 'Order from any device',
    'f1_desc_ro'  => 'Clienții scanează codul QR de pe masă cu telefonul personal sau folosesc tableta restaurantului.', 'f1_desc_en' => 'Customers scan the QR code from the table with their phone or use the restaurant tablet.',
    'f2_title_ro' => 'Dashboard în timp real', 'f2_title_en' => 'Real-time dashboard',
    'f2_desc_ro'  => 'Restaurantul vede comenzile instant, le gestionează per produs și urmărește statusul fiecărei mese live.', 'f2_desc_en' => 'The restaurant sees orders instantly, manages them per product, and tracks each table status live.',
    'f3_title_ro' => 'Rapoarte zilnice', 'f3_title_en' => 'Daily reports',
    'f3_desc_ro'  => 'Statistici complete: vânzări totale, top produse, distribuție pe ore, metodă de plată. Export PDF.', 'f3_desc_en' => 'Complete statistics: total sales, top products, hourly distribution, payment method. PDF export.',
    'f4_title_ro' => 'Design personalizabil', 'f4_title_en' => 'Customizable design',
    'f4_desc_ro'  => 'Logo, temă vizuală, imagine de fundal, mesaj de bun venit. Tableta arată exact ca brandul tău.', 'f4_desc_en' => 'Logo, visual theme, background image, welcome message. The tablet looks exactly like your brand.',
    'f5_title_ro' => 'Reclame & Promoții', 'f5_title_en' => 'Ads & Promotions',
    'f5_desc_ro'  => 'Banner ticker cu oferte speciale pe tableta clientului.', 'f5_desc_en' => 'Ticker banner with special offers on the customer tablet.',
    'f6_title_ro' => 'Bilingv RO / EN', 'f6_title_en' => 'Bilingual RO / EN',
    'f6_desc_ro'  => 'Interfața completă în română și engleză — atât pentru restaurant cât și pentru clienți.', 'f6_desc_en' => 'Complete interface in Romanian and English — both for the restaurant and customers.',

    // How it works
    'how_eyebrow_ro'        => 'Simplu de implementat',
    'how_eyebrow_en'        => 'Easy to implement',
    'how_title_ro'          => 'Pornești în <em>4 pași</em>',
    'how_title_en'          => 'Get started in <em>4 steps</em>',
    'step1_title_ro' => 'Primești accesul', 'step1_title_en' => 'Get access',
    'step1_desc_ro'  => 'În baza abonamentului, restaurantul primește acces la platformă și configurează meniul.', 'step1_desc_en' => 'Based on your subscription, the restaurant gets access and sets up the menu.',
    'step2_title_ro' => 'Adaugi mesele', 'step2_title_en' => 'Add tables',
    'step2_desc_ro'  => 'Fiecare masă primește un cod QR unic. Clientul îl scanează cu telefonul sau deschide linkul pe tabletă.', 'step2_desc_en' => 'Each table gets a unique QR code. The customer scans it or opens the link on the tablet.',
    'step3_title_ro' => 'Clienții comandă', 'step3_title_en' => 'Customers order',
    'step3_desc_ro'  => 'Clienții văd meniul pe tabletă și comandă. Tu primești instant în dashboard.', 'step3_desc_en' => 'Customers see the menu on the tablet and order. You receive it instantly in the dashboard.',
    'step4_title_ro' => 'Gestionezi & încasezi', 'step4_title_en' => 'Manage & collect',
    'step4_desc_ro'  => 'Marchezi produsele ca servite, clientul solicită nota, tu eliberezi masa.', 'step4_desc_en' => 'Mark products as served, the customer requests the bill, you release the table.',

    // Testimonial
    'testimonial_text_ro'   => 'De când am implementat Tablerino, chelnerul nostru se ocupă de servire, nu de luat comenzi. Clienții sunt mai mulțumiți, comenzile sunt mai precise.',
    'testimonial_text_en'   => 'Since implementing Tablerino, our waiter focuses on serving, not taking orders. Customers are happier, orders are more accurate.',
    'testimonial_author_ro' => 'Restaurant partener',
    'testimonial_author_en' => 'Partner restaurant',
    'testimonial_sub_ro'    => 'Client Tablerino',
    'testimonial_sub_en'    => 'Tablerino client',

    // CTA
    'cta_title_ro'          => 'Gata să <em>transformi</em><br>experiența din restaurant?',
    'cta_title_en'          => 'Ready to <em>transform</em><br>your restaurant experience?',
    'cta_desc_ro'           => 'Disponibil în baza unui abonament. Orice tabletă sau telefon cu browser funcționează — fără echipamente speciale.',
    'cta_desc_en'           => 'Available on a subscription basis. Any tablet or phone with a browser works — no special equipment needed.',
    'cta_btn_ro'            => 'Solicită acces — €100/lună',
    'cta_btn_en'            => 'Request access — €100/month',
    'cta_btn2_ro'           => 'Contactează-ne',
    'cta_btn2_en'           => 'Contact us',
    'contact_email'         => 'office@tablerino.ro',

    // Social media
    'social_facebook'   => '',
    'social_instagram'  => '',
    'social_tiktok'     => '',
    'social_whatsapp'   => '',
    'social_youtube'    => '',

    // Culori
    'culoare_gold'          => '#c9a84c',
    'culoare_navy'          => '#0d1117',
    'culoare_cream'         => '#f5f0e8',
];

// Citește valorile salvate din DB
$settings = $defaults;
try {
    $q = db()->prepare('SELECT cheie, valoare FROM landing_settings');
    $q->execute();
    foreach ($q->fetchAll() as $row) {
        $settings[$row['cheie']] = $row['valoare'];
    }
} catch (Exception $e) {}

// Salvare
$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = db()->prepare('INSERT INTO landing_settings (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
        foreach ($_POST as $cheie => $valoare) {
            if (array_key_exists($cheie, $defaults)) {
                $stmt->execute([$cheie, trim($valoare)]);
                $settings[$cheie] = trim($valoare);
            }
        }
        // Upload imagini
        $uploadFields = ['hero_bg_img' => 'landing-hero-bg', 'galerie_img1' => 'landing-img1', 'galerie_img2' => 'landing-img2', 'galerie_img3' => 'landing-img3'];
        foreach ($uploadFields as $field => $filename) {
            if (!empty($_FILES[$field]['tmp_name'])) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $dest = __DIR__ . '/../uploads/' . $filename . '.' . strtolower($ext);
                move_uploaded_file($_FILES[$field]['tmp_name'], $dest);
            }
        }
        $saved = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function s($key) {
    global $settings;
    return htmlspecialchars($settings[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Master — Editor Landing Page</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f0f0f5; color: #111; }
.sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #1a1a2e; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; z-index: 50; overflow-y: auto; }
.sidebar .logo { color: var(--gold, #c9a84c); font-size: 18px; font-weight: 700; margin-bottom: 20px; padding: 0 8px; }
.sidebar a { color: #aaa; text-decoration: none; padding: 9px 12px; border-radius: 8px; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: background .15s; }
.sidebar a:hover, .sidebar a.activ { background: #6c47ff; color: #fff; }
.sidebar .logout { margin-top: auto; }
.main { margin-left: 220px; padding: 28px; max-width: 1100px; }
.main h1 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
.main p.sub { color: #888; font-size: 14px; margin-bottom: 28px; }

.toast-bar { background: #10b981; color: #fff; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.error-bar { background: #e53e3e; }

.sectiune { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
.sectiune-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; cursor: pointer; user-select: none; }
.sectiune-header h2 { font-size: 16px; font-weight: 700; flex: 1; }
.sectiune-header .toggle { color: #888; font-size: 18px; transition: transform .2s; }
.sectiune-header.collapsed .toggle { transform: rotate(-90deg); }
.sectiune-body { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.sectiune-body.single { grid-template-columns: 1fr; }
.sectiune-body.triple { grid-template-columns: 1fr 1fr 1fr; }

.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 12px; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .4px; }
.field input[type="text"],
.field input[type="email"],
.field input[type="color"],
.field textarea { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; font-family: inherit; transition: border-color .2s; }
.field input:focus, .field textarea:focus { border-color: #6c47ff; }
.field textarea { resize: vertical; min-height: 80px; }
.field small { font-size: 11px; color: #aaa; }
.field input[type="color"] { height: 38px; padding: 3px 6px; cursor: pointer; }

.upload-zone { border: 2px dashed #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; position: relative; transition: border-color .2s; }
.upload-zone:hover { border-color: #6c47ff; background: #f5f3ff; }
.upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-zone p { font-size: 13px; color: #888; margin-top: 6px; }
.upload-preview { margin-top: 10px; max-height: 120px; border-radius: 8px; object-fit: cover; max-width: 100%; }

.separator { grid-column: 1 / -1; border: none; border-top: 1px solid #f0f0f0; margin: 4px 0; }
.grup-label { grid-column: 1 / -1; font-size: 12px; font-weight: 700; color: #6c47ff; text-transform: uppercase; letter-spacing: .5px; padding-top: 4px; }

.btn-save { position: fixed; bottom: 28px; right: 28px; background: #6c47ff; color: #fff; border: none; border-radius: 12px; padding: 14px 32px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(108,71,255,0.4); z-index: 100; display: flex; align-items: center; gap: 8px; transition: background .2s; }
.btn-save:hover { background: #5535e0; }
.btn-preview { background: #fff; color: #6c47ff; border: 2px solid #6c47ff; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px; }

@media (max-width: 768px) {
    .main { margin-left: 0; padding: 14px 12px 80px; }
    .sectiune-body, .sectiune-body.triple { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">⚡ Master</div>
    <a href="/master/index.php">🏢 Restaurante</a>
    <a href="/master/landing.php" class="activ">🌐 Landing Page</a>
    <a href="/master/logout.php" class="logout">🚪 Ieșire</a>
</div>

<div class="main">
    <h1>🌐 Editor Landing Page</h1>
    <p class="sub">Modifică toate textele, imaginile, culorile și prețurile paginii de prezentare.</p>

    <a href="/" target="_blank" class="btn-preview">👁️ Previzualizează landing page →</a>

    <?php if ($saved): ?>
    <div class="toast-bar">✅ Modificările au fost salvate cu succes!</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="toast-bar error-bar">❌ Eroare: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

    <!-- ══ HERO ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>🦸 Hero — Secțiunea principală</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>Eyebrow 🇷🇴</label>
                <input type="text" name="hero_eyebrow_ro" value="<?= s('hero_eyebrow_ro') ?>">
            </div>
            <div class="field">
                <label>Eyebrow 🇬🇧</label>
                <input type="text" name="hero_eyebrow_en" value="<?= s('hero_eyebrow_en') ?>">
            </div>
            <div class="field">
                <label>Titlu principal 🇷🇴 <small>(HTML permis: &lt;em&gt;, &lt;br&gt;)</small></label>
                <textarea name="hero_title_ro"><?= s('hero_title_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Titlu principal 🇬🇧</label>
                <textarea name="hero_title_en"><?= s('hero_title_en') ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇷🇴</label>
                <textarea name="hero_desc_ro"><?= s('hero_desc_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇬🇧</label>
                <textarea name="hero_desc_en"><?= s('hero_desc_en') ?></textarea>
            </div>
            <div class="field">
                <label>Buton principal 🇷🇴</label>
                <input type="text" name="hero_btn_ro" value="<?= s('hero_btn_ro') ?>">
            </div>
            <div class="field">
                <label>Buton principal 🇬🇧</label>
                <input type="text" name="hero_btn_en" value="<?= s('hero_btn_en') ?>">
            </div>
            <div class="field">
                <label>Buton secundar 🇷🇴</label>
                <input type="text" name="hero_btn2_ro" value="<?= s('hero_btn2_ro') ?>">
            </div>
            <div class="field">
                <label>Buton secundar 🇬🇧</label>
                <input type="text" name="hero_btn2_en" value="<?= s('hero_btn2_en') ?>">
            </div>
            <div class="field">
                <label>Preț abonament</label>
                <input type="text" name="hero_price" value="<?= s('hero_price') ?>">
                <small>Ex: €100</small>
            </div>
            <div class="field">
                <label>Perioadă 🇷🇴</label>
                <input type="text" name="hero_price_period_ro" value="<?= s('hero_price_period_ro') ?>">
            </div>
            <div class="field">
                <label>Email contact (butoane)</label>
                <input type="email" name="contact_email" value="<?= s('contact_email') ?>">
            </div>
            <div class="field">
                <label>Imagine fundal hero (stânga)</label>
                <div class="upload-zone">
                    <input type="file" name="hero_bg_img" accept="image/*">
                    <div>📁</div>
                    <p>Click pentru a schimba imaginea de fundal<br><small>JPG, PNG, WEBP · max 5MB</small></p>
                    <img src="/uploads/landing-hero-bg.jpg" class="upload-preview" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </div>

    <!-- ══ STATS ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>📊 Stats Bar — 4 statistici</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body triple">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="grup-label" style="grid-column:1/-1">Stat #<?= $i ?></div>
            <div class="field">
                <label>Număr / Simbol</label>
                <input type="text" name="stat<?= $i ?>_num" value="<?= s("stat{$i}_num") ?>">
            </div>
            <div class="field">
                <label>Eticheta 🇷🇴</label>
                <input type="text" name="stat<?= $i ?>_ro" value="<?= s("stat{$i}_ro") ?>">
            </div>
            <div class="field">
                <label>Eticheta 🇬🇧</label>
                <input type="text" name="stat<?= $i ?>_en" value="<?= s("stat{$i}_en") ?>">
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ══ FEATURES ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>✨ Features — 6 funcționalități</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>Eyebrow 🇷🇴</label>
                <input type="text" name="features_eyebrow_ro" value="<?= s('features_eyebrow_ro') ?>">
            </div>
            <div class="field">
                <label>Eyebrow 🇬🇧</label>
                <input type="text" name="features_eyebrow_en" value="<?= s('features_eyebrow_en') ?>">
            </div>
            <div class="field">
                <label>Titlu secțiune 🇷🇴</label>
                <textarea name="features_title_ro" style="min-height:60px"><?= s('features_title_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Titlu secțiune 🇬🇧</label>
                <textarea name="features_title_en" style="min-height:60px"><?= s('features_title_en') ?></textarea>
            </div>
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <hr class="separator">
            <div class="grup-label">Feature #<?= $i ?></div>
            <div class="field">
                <label>Titlu 🇷🇴</label>
                <input type="text" name="f<?= $i ?>_title_ro" value="<?= s("f{$i}_title_ro") ?>">
            </div>
            <div class="field">
                <label>Titlu 🇬🇧</label>
                <input type="text" name="f<?= $i ?>_title_en" value="<?= s("f{$i}_title_en") ?>">
            </div>
            <div class="field">
                <label>Descriere 🇷🇴</label>
                <textarea name="f<?= $i ?>_desc_ro" style="min-height:70px"><?= s("f{$i}_desc_ro") ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇬🇧</label>
                <textarea name="f<?= $i ?>_desc_en" style="min-height:70px"><?= s("f{$i}_desc_en") ?></textarea>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ══ HOW IT WORKS ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>🔢 Cum funcționează — 4 pași</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>Eyebrow 🇷🇴</label>
                <input type="text" name="how_eyebrow_ro" value="<?= s('how_eyebrow_ro') ?>">
            </div>
            <div class="field">
                <label>Eyebrow 🇬🇧</label>
                <input type="text" name="how_eyebrow_en" value="<?= s('how_eyebrow_en') ?>">
            </div>
            <div class="field">
                <label>Titlu 🇷🇴</label>
                <input type="text" name="how_title_ro" value="<?= s('how_title_ro') ?>">
            </div>
            <div class="field">
                <label>Titlu 🇬🇧</label>
                <input type="text" name="how_title_en" value="<?= s('how_title_en') ?>">
            </div>
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <hr class="separator">
            <div class="grup-label">Pasul #<?= $i ?></div>
            <div class="field">
                <label>Titlu 🇷🇴</label>
                <input type="text" name="step<?= $i ?>_title_ro" value="<?= s("step{$i}_title_ro") ?>">
            </div>
            <div class="field">
                <label>Titlu 🇬🇧</label>
                <input type="text" name="step<?= $i ?>_title_en" value="<?= s("step{$i}_title_en") ?>">
            </div>
            <div class="field">
                <label>Descriere 🇷🇴</label>
                <textarea name="step<?= $i ?>_desc_ro" style="min-height:70px"><?= s("step{$i}_desc_ro") ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇬🇧</label>
                <textarea name="step<?= $i ?>_desc_en" style="min-height:70px"><?= s("step{$i}_desc_en") ?></textarea>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ══ GALERIE ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>🖼️ Galerie — 3 imagini</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body triple">
            <?php
            $galerie = [
                ['landing-img1', 'Imagine 1 (stânga)'],
                ['landing-img2', 'Imagine 2 (centru)'],
                ['landing-img3', 'Imagine 3 (dreapta)'],
            ];
            $galFields = ['galerie_img2', 'galerie_img1', 'galerie_img3'];
            foreach ($galerie as $idx => [$fname, $label]):
            ?>
            <div class="field">
                <label><?= $label ?></label>
                <div class="upload-zone">
                    <input type="file" name="<?= $galFields[$idx] ?>" accept="image/*">
                    <div>📁</div>
                    <p><small>Click pentru a schimba</small></p>
                    <img src="/uploads/<?= $fname ?>.jpg" class="upload-preview" onerror="this.style.display='none'">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══ TESTIMONIAL ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>💬 Testimonial</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>Citat 🇷🇴</label>
                <textarea name="testimonial_text_ro"><?= s('testimonial_text_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Citat 🇬🇧</label>
                <textarea name="testimonial_text_en"><?= s('testimonial_text_en') ?></textarea>
            </div>
            <div class="field">
                <label>Autor 🇷🇴</label>
                <input type="text" name="testimonial_author_ro" value="<?= s('testimonial_author_ro') ?>">
            </div>
            <div class="field">
                <label>Autor 🇬🇧</label>
                <input type="text" name="testimonial_author_en" value="<?= s('testimonial_author_en') ?>">
            </div>
            <div class="field">
                <label>Subtitlu 🇷🇴</label>
                <input type="text" name="testimonial_sub_ro" value="<?= s('testimonial_sub_ro') ?>">
            </div>
            <div class="field">
                <label>Subtitlu 🇬🇧</label>
                <input type="text" name="testimonial_sub_en" value="<?= s('testimonial_sub_en') ?>">
            </div>
        </div>
    </div>

    <!-- ══ CTA ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>🎯 CTA — Call to action</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>Titlu 🇷🇴 <small>(HTML permis)</small></label>
                <textarea name="cta_title_ro"><?= s('cta_title_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Titlu 🇬🇧</label>
                <textarea name="cta_title_en"><?= s('cta_title_en') ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇷🇴</label>
                <textarea name="cta_desc_ro"><?= s('cta_desc_ro') ?></textarea>
            </div>
            <div class="field">
                <label>Descriere 🇬🇧</label>
                <textarea name="cta_desc_en"><?= s('cta_desc_en') ?></textarea>
            </div>
            <div class="field">
                <label>Buton principal 🇷🇴</label>
                <input type="text" name="cta_btn_ro" value="<?= s('cta_btn_ro') ?>">
            </div>
            <div class="field">
                <label>Buton principal 🇬🇧</label>
                <input type="text" name="cta_btn_en" value="<?= s('cta_btn_en') ?>">
            </div>
            <div class="field">
                <label>Buton secundar 🇷🇴</label>
                <input type="text" name="cta_btn2_ro" value="<?= s('cta_btn2_ro') ?>">
            </div>
            <div class="field">
                <label>Buton secundar 🇬🇧</label>
                <input type="text" name="cta_btn2_en" value="<?= s('cta_btn2_en') ?>">
            </div>
        </div>
    </div>

    <!-- ══ SOCIAL MEDIA ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>📱 Social Media — Footer landing page</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body">
            <div class="field">
                <label>🔵 Facebook URL</label>
                <input type="url" name="social_facebook" value="<?= s('social_facebook') ?>" placeholder="https://facebook.com/pagina-ta">
                <small>Lasă gol pentru a ascunde butonul</small>
            </div>
            <div class="field">
                <label>📸 Instagram URL</label>
                <input type="url" name="social_instagram" value="<?= s('social_instagram') ?>" placeholder="https://instagram.com/contul-tau">
                <small>Lasă gol pentru a ascunde butonul</small>
            </div>
            <div class="field">
                <label>🎵 TikTok URL</label>
                <input type="url" name="social_tiktok" value="<?= s('social_tiktok') ?>" placeholder="https://tiktok.com/@contul-tau">
                <small>Lasă gol pentru a ascunde butonul</small>
            </div>
            <div class="field">
                <label>💬 WhatsApp (număr internațional)</label>
                <input type="text" name="social_whatsapp" value="<?= s('social_whatsapp') ?>" placeholder="40712345678">
                <small>Format: 40XXXXXXXXX — fără + sau spații</small>
            </div>
            <div class="field">
                <label>▶️ YouTube URL</label>
                <input type="url" name="social_youtube" value="<?= s('social_youtube') ?>" placeholder="https://youtube.com/@canalul-tau">
                <small>Lasă gol pentru a ascunde butonul</small>
            </div>
        </div>
    </div>

    <!-- ══ CULORI ══ -->
    <div class="sectiune">
        <div class="sectiune-header" onclick="toggleSect(this)">
            <h2>🎨 Culori principale</h2>
            <span class="toggle">▾</span>
        </div>
        <div class="sectiune-body triple">
            <div class="field">
                <label>Auriu (gold)</label>
                <input type="color" name="culoare_gold" value="<?= s('culoare_gold') ?>">
                <small>Titluri italic, accente</small>
            </div>
            <div class="field">
                <label>Navy (fundal dark)</label>
                <input type="color" name="culoare_navy" value="<?= s('culoare_navy') ?>">
                <small>Hero stânga, navbar, CTA</small>
            </div>
            <div class="field">
                <label>Cream (fundal light)</label>
                <input type="color" name="culoare_cream" value="<?= s('culoare_cream') ?>">
                <small>Fundal general, hero dreapta</small>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-save">💾 Salvează toate modificările</button>

    </form>
</div>

<script>
function toggleSect(header) {
    header.classList.toggle('collapsed');
    const body = header.nextElementSibling;
    body.style.display = body.style.display === 'none' ? '' : 'none';
}
// Preview imagini la upload
document.querySelectorAll('.upload-zone input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (!this.files.length) return;
        const preview = this.closest('.upload-zone').querySelector('.upload-preview');
        const url = URL.createObjectURL(this.files[0]);
        if (preview) { preview.src = url; preview.style.display = 'block'; }
        else {
            const img = document.createElement('img');
            img.src = url; img.className = 'upload-preview';
            this.closest('.upload-zone').appendChild(img);
        }
    });
});
</script>
</body>
</html>
