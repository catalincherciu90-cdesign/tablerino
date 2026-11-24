// Landing page customizable settings — default values, ported from
// master/landing.php. Stored overrides live in the landing_settings table.

export const LANDING_DEFAULTS: Record<string, string> = {
  // Hero
  hero_eyebrow_ro: 'Soluția digitală pentru restaurante',
  hero_eyebrow_en: 'The digital solution for restaurants',
  hero_title_ro: 'Comenzi la masă,<br><em>reimaginate.</em>',
  hero_title_en: 'Table ordering,<br><em>reimagined.</em>',
  hero_desc_ro: 'Tablerino transformă experiența de comandă în restaurantul tău. Clienții comandă direct de pe tabletă sau telefon, bucătăria primește instant, tu controlezi totul.',
  hero_desc_en: 'Tablerino transforms the ordering experience in your restaurant. Customers order directly from the tablet or phone, the kitchen receives instantly, you control everything.',
  hero_btn_ro: 'Solicită acces',
  hero_btn_en: 'Request access',
  hero_btn2_ro: 'Cum funcționează',
  hero_btn2_en: 'How it works',
  hero_price: '€100',
  hero_price_period_ro: '/ lună',
  hero_price_period_en: '/ month',
  hero_price_label_ro: 'Abonament lunar',
  hero_price_label_en: 'Monthly subscription',

  // Stats
  stat1_num: '∞', stat1_ro: 'Mese configurabile', stat1_en: 'Configurable tables',
  stat2_num: '5s', stat2_ro: 'Timp de refresh comenzi', stat2_en: 'Order refresh time',
  stat3_num: '2', stat3_ro: 'Limbi disponibile', stat3_en: 'Available languages',
  stat4_num: 'PWA', stat4_ro: 'Funcționează ca aplicație', stat4_en: 'Works as an app',

  // Features title
  features_eyebrow_ro: 'Tot ce ai nevoie',
  features_eyebrow_en: 'Everything you need',
  features_title_ro: 'Platformă completă,<br><em>gândită pentru restaurante</em>',
  features_title_en: 'Complete platform,<br><em>built for restaurants</em>',

  // Features cards
  f1_title_ro: 'Comandă de pe orice dispozitiv', f1_title_en: 'Order from any device',
  f1_desc_ro: 'Clienții scanează codul QR de pe masă cu telefonul personal sau folosesc tableta restaurantului.', f1_desc_en: 'Customers scan the QR code from the table with their phone or use the restaurant tablet.',
  f2_title_ro: 'Dashboard în timp real', f2_title_en: 'Real-time dashboard',
  f2_desc_ro: 'Restaurantul vede comenzile instant, le gestionează per produs și urmărește statusul fiecărei mese live.', f2_desc_en: 'The restaurant sees orders instantly, manages them per product, and tracks each table status live.',
  f3_title_ro: 'Rapoarte zilnice', f3_title_en: 'Daily reports',
  f3_desc_ro: 'Statistici complete: vânzări totale, top produse, distribuție pe ore, metodă de plată. Export PDF.', f3_desc_en: 'Complete statistics: total sales, top products, hourly distribution, payment method. PDF export.',
  f4_title_ro: 'Design personalizabil', f4_title_en: 'Customizable design',
  f4_desc_ro: 'Logo, temă vizuală, imagine de fundal, mesaj de bun venit. Tableta arată exact ca brandul tău.', f4_desc_en: 'Logo, visual theme, background image, welcome message. The tablet looks exactly like your brand.',
  f5_title_ro: 'Reclame & Promoții', f5_title_en: 'Ads & Promotions',
  f5_desc_ro: 'Banner ticker cu oferte speciale pe tableta clientului.', f5_desc_en: 'Ticker banner with special offers on the customer tablet.',
  f6_title_ro: 'Bilingv RO / EN', f6_title_en: 'Bilingual RO / EN',
  f6_desc_ro: 'Interfața completă în română și engleză — atât pentru restaurant cât și pentru clienți.', f6_desc_en: 'Complete interface in Romanian and English — both for the restaurant and customers.',

  // How it works
  how_eyebrow_ro: 'Simplu de implementat', how_eyebrow_en: 'Easy to implement',
  how_title_ro: 'Pornești în <em>4 pași</em>', how_title_en: 'Get started in <em>4 steps</em>',
  step1_title_ro: 'Primești accesul', step1_title_en: 'Get access',
  step1_desc_ro: 'În baza abonamentului, restaurantul primește acces la platformă și configurează meniul.', step1_desc_en: 'Based on your subscription, the restaurant gets access and sets up the menu.',
  step2_title_ro: 'Adaugi mesele', step2_title_en: 'Add tables',
  step2_desc_ro: 'Fiecare masă primește un cod QR unic. Clientul îl scanează cu telefonul sau deschide linkul pe tabletă.', step2_desc_en: 'Each table gets a unique QR code. The customer scans it or opens the link on the tablet.',
  step3_title_ro: 'Clienții comandă', step3_title_en: 'Customers order',
  step3_desc_ro: 'Clienții văd meniul pe tabletă și comandă. Tu primești instant în dashboard.', step3_desc_en: 'Customers see the menu on the tablet and order. You receive it instantly in the dashboard.',
  step4_title_ro: 'Gestionezi & încasezi', step4_title_en: 'Manage & collect',
  step4_desc_ro: 'Marchezi produsele ca servite, clientul solicită nota, tu eliberezi masa.', step4_desc_en: 'Mark products as served, the customer requests the bill, you release the table.',

  // Testimonial
  testimonial_text_ro: 'De când am implementat Tablerino, chelnerul nostru se ocupă de servire, nu de luat comenzi. Clienții sunt mai mulțumiți, comenzile sunt mai precise.',
  testimonial_text_en: 'Since implementing Tablerino, our waiter focuses on serving, not taking orders. Customers are happier, orders are more accurate.',
  testimonial_author_ro: 'Restaurant partener', testimonial_author_en: 'Partner restaurant',
  testimonial_sub_ro: 'Client Tablerino', testimonial_sub_en: 'Tablerino client',

  // CTA
  cta_title_ro: 'Gata să <em>transformi</em><br>experiența din restaurant?',
  cta_title_en: 'Ready to <em>transform</em><br>your restaurant experience?',
  cta_desc_ro: 'Disponibil în baza unui abonament. Orice tabletă sau telefon cu browser funcționează — fără echipamente speciale.',
  cta_desc_en: 'Available on a subscription basis. Any tablet or phone with a browser works — no special equipment needed.',
  cta_btn_ro: 'Solicită acces — €100/lună', cta_btn_en: 'Request access — €100/month',
  cta_btn2_ro: 'Contactează-ne', cta_btn2_en: 'Contact us',
  contact_email: 'office@tablerino.ro',

  // Social media
  social_facebook: '', social_instagram: '', social_tiktok: '', social_whatsapp: '', social_youtube: '',

  // Colors
  culoare_gold: '#c9a84c', culoare_navy: '#0d1117', culoare_cream: '#f5f0e8',
};
