<?php
/* ══════════════════════════════════════════════════
   Coffee & Almond - checkout.php
   PayFast sandbox integration
   ══════════════════════════════════════════════════ */

// ── PayFast config ────────────────────────────────
$pf_merchant_id  = '10000100';           // SANDBOX ID - replace when live
$pf_merchant_key = '46f0cd694581a';      // SANDBOX KEY - replace when live
$pf_passphrase   = '';                   // Set only if you add one in PayFast dashboard
$pf_sandbox      = true;                 // ← set to false when going live

$pf_url = $pf_sandbox
    ? 'https://sandbox.payfast.co.za/eng/process'
    : 'https://www.payfast.co.za/eng/process';

// ── Site URLs - update to your real domain when live ──
$site_url   = 'https://yourdomain.co.za';  // ← change this
$return_url = $site_url . '/return.html';
$cancel_url = $site_url . '/cancel.html';
$notify_url = $site_url . '/notify.php';

// ── Product ───────────────────────────────────────
$amount    = '699.00';
$item_name = 'Coffee & Almond - The Complete Ritual Bundle';

// ── Form processing ───────────────────────────────
$errors    = [];
$pf_fields = [];
$go        = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_first  = trim(strip_tags($_POST['name_first']  ?? ''));
    $name_last   = trim(strip_tags($_POST['name_last']   ?? ''));
    $email       = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $cell        = preg_replace('/[^0-9+\s\-()]/', '', $_POST['cell'] ?? '');
    $pudo_locker = trim(strip_tags($_POST['pudo_locker'] ?? ''));

    if (!$name_first)  $errors[] = 'First name is required.';
    if (!$name_last)   $errors[] = 'Surname is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!$cell)        $errors[] = 'Phone number is required.';
    if (!$pudo_locker) $errors[] = 'Please enter your nearest PUDO locker location.';

    if (empty($errors)) {
        $m_payment_id = 'CAO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

        $pf_fields = [
            'merchant_id'   => $pf_merchant_id,
            'merchant_key'  => $pf_merchant_key,
            'return_url'    => $return_url,
            'cancel_url'    => $cancel_url,
            'notify_url'    => $notify_url,
            'name_first'    => $name_first,
            'name_last'     => $name_last,
            'email_address' => $email,
            'cell_number'   => $cell,
            'm_payment_id'  => $m_payment_id,
            'amount'        => $amount,
            'item_name'     => $item_name,
            'custom_str1'   => $pudo_locker,
        ];

        // Build signature string
        $sig_parts = [];
        foreach ($pf_fields as $k => $v) {
            if ($v !== '') $sig_parts[] = $k . '=' . urlencode(trim($v));
        }
        $sig_string = implode('&', $sig_parts);
        if ($pf_passphrase !== '') {
            $sig_string .= '&passphrase=' . urlencode($pf_passphrase);
        }
        $pf_fields['signature'] = md5($sig_string);
        $go = true;
    }
}

// Preserve field values on error
$v = [
    'name_first'  => htmlspecialchars($_POST['name_first']  ?? ''),
    'name_last'   => htmlspecialchars($_POST['name_last']   ?? ''),
    'email'       => htmlspecialchars($_POST['email']       ?? ''),
    'cell'        => htmlspecialchars($_POST['cell']        ?? ''),
    'pudo_locker' => htmlspecialchars($_POST['pudo_locker'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - Coffee &amp; Almond</title>
  <meta name="description" content="Order The Complete Ritual Bundle - Coffee &amp; Almond Organic Scrub, Gua Sha &amp; Weighted Eye Mask." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .checkout-page { min-height: 100vh; padding-top: 80px; }

    /* ── Page hero ── */
    .checkout-hero {
      background: var(--espresso);
      padding: clamp(40px, 6vw, 64px) 0 clamp(32px, 4vw, 52px);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .checkout-hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at 60% 40%, rgba(196,149,106,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .checkout-hero .section-label { justify-content: center; color: var(--almond); margin-bottom: 1rem; }
    .checkout-hero h1 { color: var(--cream); font-size: clamp(1.8rem, 3vw, 2.8rem); margin: 0 auto; }
    .checkout-hero h1 em { color: var(--latte); }

    /* ── Main layout ── */
    .checkout-main {
      max-width: 1100px;
      margin: 0 auto;
      padding: clamp(40px, 6vw, 72px) clamp(20px, 5vw, 48px);
      display: grid;
      grid-template-columns: 1fr 1.3fr;
      gap: clamp(32px, 5vw, 64px);
      align-items: start;
    }

    /* ── Order summary ── */
    .order-summary {
      background: var(--espresso);
      border-radius: 4px;
      padding: clamp(28px, 4vw, 44px);
      color: var(--cream);
      position: sticky;
      top: 100px;
    }
    .order-summary .section-label { color: var(--almond); margin-bottom: 1.25rem; }
    .order-summary h2 {
      font-family: var(--serif);
      font-size: clamp(1.4rem, 2.5vw, 2rem);
      color: var(--cream);
      margin-bottom: 0.5rem;
      font-weight: 400;
    }
    .order-summary h2 em { color: var(--latte); font-style: italic; }
    .order-summary-divider {
      height: 1px;
      background: rgba(212,184,150,0.25);
      margin: 1.5rem 0;
    }
    .order-includes {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 1.75rem;
    }
    .order-includes li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.88rem;
      color: rgba(245,239,227,0.82);
      font-weight: 300;
    }
    .order-includes li::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--teal-mid);
      flex-shrink: 0;
    }
    .order-price {
      font-family: var(--serif);
      font-size: clamp(2.2rem, 4vw, 3rem);
      color: var(--cream);
      line-height: 1;
      margin-bottom: 0.4rem;
    }
    .order-price-note {
      font-size: 0.72rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--teal-mid);
    }
    .order-delivery-note {
      margin-top: 1.5rem;
      font-size: 0.82rem;
      color: rgba(245,239,227,0.55);
      line-height: 1.65;
    }
    .secure-badge {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 1.75rem;
      padding-top: 1.25rem;
      border-top: 1px solid rgba(212,184,150,0.2);
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: rgba(245,239,227,0.45);
    }
    .secure-badge svg { width: 14px; height: 14px; color: var(--teal-mid); flex-shrink: 0; }

    /* ── Form card ── */
    .checkout-form-card {
      background: white;
      border-radius: 4px;
      padding: clamp(28px, 4vw, 48px);
      border: 1px solid var(--almond-light);
    }
    .checkout-form-card h3 {
      font-family: var(--serif);
      font-size: 1.35rem;
      font-weight: 400;
      color: var(--espresso);
      margin-bottom: 0.35rem;
    }
    .form-subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 2rem;
    }

    /* ── Error list ── */
    .form-errors {
      background: #fff3f3;
      border: 1px solid #e5a0a0;
      border-radius: 3px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.5rem;
    }
    .form-errors p {
      font-size: 0.82rem;
      font-weight: 500;
      color: #8b2b2b;
      margin-bottom: 0.5rem;
    }
    .form-errors ul { list-style: none; }
    .form-errors li {
      font-size: 0.8rem;
      color: #8b2b2b;
      padding: 2px 0;
    }
    .form-errors li::before { content: '· '; }

    /* ── Field groups ── */
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .field-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 1.1rem; }
    .field-group label {
      font-size: 0.72rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text-muted);
      font-weight: 500;
    }
    .field-group input,
    .field-group textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--almond-light);
      border-radius: 2px;
      font-family: var(--sans);
      font-size: 0.93rem;
      font-weight: 300;
      color: var(--espresso);
      background: var(--warm-white);
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }
    .field-group input:focus,
    .field-group textarea:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(74,140,150,0.12);
    }
    .field-group input::placeholder,
    .field-group textarea::placeholder { color: var(--almond); }

    /* ── PUDO helper ── */
    .pudo-helper {
      font-size: 0.78rem;
      color: var(--teal);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-top: 4px;
    }
    .pudo-helper:hover { color: var(--teal-dark); text-decoration: underline; }
    .pudo-helper svg { width: 11px; height: 11px; }

    /* ── Submit button ── */
    .btn-checkout {
      width: 100%;
      padding: 16px 24px;
      background: var(--teal);
      color: white;
      border: none;
      border-radius: 2px;
      font-family: var(--sans);
      font-size: 0.82rem;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 1.5rem;
      box-shadow: 0 4px 20px rgba(74,140,150,0.3);
      transition: background 0.25s, transform 0.2s, box-shadow 0.2s;
    }
    .btn-checkout:hover {
      background: var(--teal-dark);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(74,140,150,0.4);
    }
    .btn-checkout svg { width: 16px; height: 16px; }

    .form-note {
      font-size: 0.74rem;
      color: var(--text-muted);
      text-align: center;
      margin-top: 0.875rem;
      line-height: 1.55;
    }

    /* ── Sandbox warning ── */
    <?php if ($pf_sandbox): ?>
    .sandbox-banner {
      background: #fff8e1;
      border: 1px solid #f5c842;
      text-align: center;
      padding: 10px 20px;
      font-size: 0.78rem;
      color: #7a5c00;
      letter-spacing: 0.04em;
    }
    <?php endif; ?>

    @media (max-width: 860px) {
      .checkout-main { grid-template-columns: 1fr; }
      .order-summary { position: static; }
      .field-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="checkout-page">

<?php if ($pf_sandbox): ?>
<div class="sandbox-banner">
  ⚠ TEST MODE - Payments are processed through PayFast Sandbox and will not charge real money.
</div>
<?php endif; ?>

  <!-- ══ NAVBAR ══ -->
  <nav class="navbar scrolled" id="navbar">
    <a href="index.html" class="nav-logo">
      Coffee &amp; Almond
      <span>Organic Scrub</span>
    </a>
    <button class="nav-hamburger" id="nav-hamburger" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
    <ul class="nav-links" id="nav-links">
      <li><a href="index.html">Home</a></li>
      <li><a href="our-story.html">Grounds for Everything</a></li>
      <li><a href="scrub.html">The Scrub</a></li>
      <li><a href="guasha.html">Gua Sha</a></li>
      <li><a href="eyemask.html">Eye Mask</a></li>
      <li><a href="checkout.php" class="nav-cta active">Purchase</a></li>
    </ul>
  </nav>

  <!-- ══ PAGE HERO ══ -->
  <section class="checkout-hero">
    <div class="container">
      <span class="section-label">Secure Checkout</span>
      <h1>The Complete <em>Ritual Bundle</em></h1>
    </div>
  </section>

  <!-- ══ CHECKOUT MAIN ══ -->
  <main class="checkout-main">

    <!-- Order summary -->
    <aside class="order-summary">
      <span class="section-label">Your order</span>
      <h2>The Complete<br><em>Ritual Bundle</em></h2>

      <div class="order-summary-divider"></div>

      <ul class="order-includes">
        <li>Coffee &amp; Almond Organic Scrub (1 jar)</li>
        <li>Stainless Steel Gua Sha Tool</li>
        <li>Weighted Eye Mask (heat &amp; cool)</li>
        <li>Gift-ready packaging included</li>
        <li>Free delivery to your PUDO locker</li>
      </ul>

      <div class="order-summary-divider"></div>

      <div class="order-price">R 699</div>
      <div class="order-price-note">Complete set · Free PUDO delivery</div>

      <p class="order-delivery-note">
        We ship via PUDO locker-to-locker. Once your payment is confirmed
        we'll pack your order and drop it at a PUDO locker near us.
        You'll collect from the locker you choose below.
      </p>

      <div class="secure-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Secured by PayFast
      </div>
    </aside>

    <!-- Checkout form -->
    <div class="checkout-form-card">
      <h3>Your details</h3>
      <p class="form-subtitle">We'll send your order confirmation to the email below.</p>

      <?php if (!empty($errors)): ?>
      <div class="form-errors">
        <p>Please fix the following:</p>
        <ul>
          <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <form method="POST" action="checkout.php" id="checkout-form" novalidate>

        <div class="field-row">
          <div class="field-group">
            <label for="name_first">First Name</label>
            <input type="text" id="name_first" name="name_first"
                   value="<?= $v['name_first'] ?>"
                   placeholder="Jodie" autocomplete="given-name" required />
          </div>
          <div class="field-group">
            <label for="name_last">Surname</label>
            <input type="text" id="name_last" name="name_last"
                   value="<?= $v['name_last'] ?>"
                   placeholder="Smith" autocomplete="family-name" required />
          </div>
        </div>

        <div class="field-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 value="<?= $v['email'] ?>"
                 placeholder="you@example.com" autocomplete="email" required />
        </div>

        <div class="field-group">
          <label for="cell">Phone Number</label>
          <input type="tel" id="cell" name="cell"
                 value="<?= $v['cell'] ?>"
                 placeholder="082 000 0000" autocomplete="tel" required />
        </div>

        <div class="field-group">
          <label for="pudo_locker">Nearest PUDO Locker</label>
          <input type="text" id="pudo_locker" name="pudo_locker"
                 value="<?= $v['pudo_locker'] ?>"
                 placeholder="e.g. Checkers Bellville, Durbanville Pick n Pay…" required />
          <a href="https://pudo.co.za/find-a-locker" target="_blank" rel="noopener" class="pudo-helper">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Find my nearest PUDO locker →
          </a>
        </div>

        <button type="submit" class="btn-checkout">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Pay R 699 Securely
        </button>

        <p class="form-note">
          You'll be redirected to PayFast to complete your payment safely.<br>
          We never store your card details.
        </p>

      </form>
    </div>

  </main>

  <!-- ══ FOOTER ══ -->
  <footer>
    <div class="footer-inner">
      <div>
        <div class="footer-brand">Coffee &amp; Almond<span>Organic Scrub</span></div>
        <p class="footer-tagline">Three products. One ritual. Organic, intentional, and designed for every body.</p>
      </div>
      <div class="footer-col">
        <h5>Navigate</h5>
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="our-story.html">Grounds for Everything</a></li>
          <li><a href="scrub.html">The Scrub</a></li>
          <li><a href="guasha.html">Gua Sha</a></li>
          <li><a href="eyemask.html">Eye Mask</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Order &amp; Follow</h5>
        <ul>
          <li><a href="https://www.instagram.com/coffeealmondorganicscrub/" target="_blank" rel="noopener">Instagram</a></li>
          <li><a href="https://www.facebook.com/coffeealmondorganicscrub/" target="_blank" rel="noopener">Facebook</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2016–2026 Coffee &amp; Almond Organic Scrub. All rights reserved.</p>
    </div>
  </footer>

  <?php if ($go): ?>
  <!-- Auto-submit to PayFast once form validated -->
  <form id="pf-form" method="POST" action="<?= htmlspecialchars($pf_url) ?>">
    <?php foreach ($pf_fields as $k => $val): ?>
    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($val) ?>" />
    <?php endforeach; ?>
  </form>
  <script>document.getElementById('pf-form').submit();</script>
  <?php endif; ?>

  <script>
    const hamburger = document.getElementById('nav-hamburger');
    const navLinks  = document.getElementById('nav-links');
    if (hamburger && navLinks) {
      hamburger.addEventListener('click', () => {
        const open = navLinks.classList.toggle('open');
        hamburger.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
      });
      navLinks.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          navLinks.classList.remove('open');
          hamburger.classList.remove('open');
          document.body.style.overflow = '';
        });
      });
    }
  </script>

</body>
</html>
