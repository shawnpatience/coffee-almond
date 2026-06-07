<?php
/* ══════════════════════════════════════════════════
   Coffee & Almond — notify.php
   PayFast Instant Payment Notification (IPN) handler
   ══════════════════════════════════════════════════
   This file is called by PayFast (not the customer)
   after a payment is completed. It validates the
   payment and emails the order details to you.
   ══════════════════════════════════════════════════ */

// ── Config ────────────────────────────────────────
$pf_sandbox      = false;
$pf_merchant_id  = '35024455';
$pf_passphrase   = '';                 // ← match what you set in PayFast dashboard

$notification_email = 'jodiepatience@gmail.com';
$from_email         = 'orders@coffeeandalmondscrub.co.za';

$pf_host = $pf_sandbox ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
$log_file = __DIR__ . '/payfast_ipn.log';

// ── Helpers ───────────────────────────────────────
function pf_log($msg) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function pf_fail($reason) {
    pf_log('FAILED: ' . $reason);
    http_response_code(400);
    exit;
}

// ── Only accept POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pf_log('Rejected: not a POST request.');
    http_response_code(405);
    exit;
}

$data = $_POST;
pf_log('IPN received: ' . json_encode($data));

// ── Step 1: Verify signature ──────────────────────
$sig_data = $data;
unset($sig_data['signature']);

$sig_parts = [];
foreach ($sig_data as $k => $v) {
    if ($v !== '') $sig_parts[] = $k . '=' . urlencode(stripslashes(trim($v)));
}
$sig_string = implode('&', $sig_parts);
if ($pf_passphrase !== '') {
    $sig_string .= '&passphrase=' . urlencode($pf_passphrase);
}

if (md5($sig_string) !== ($data['signature'] ?? '')) {
    pf_fail('Signature mismatch. Got: ' . ($data['signature'] ?? 'none'));
}
pf_log('Signature OK.');

// ── Step 2: Validate with PayFast servers ─────────
$verify_parts = [];
foreach ($data as $k => $v) {
    $verify_parts[] = $k . '=' . urlencode(stripslashes(trim($v)));
}
$verify_body = implode('&', $verify_parts);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "https://{$pf_host}/eng/query/validate",
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $verify_body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 30,
]);
$pf_response = curl_exec($ch);
$curl_error  = curl_error($ch);
curl_close($ch);

if ($curl_error) pf_fail('cURL error: ' . $curl_error);
if (trim($pf_response) !== 'VALID') pf_fail('PayFast validation returned: ' . $pf_response);
pf_log('PayFast validation: VALID');

// ── Step 3: Check payment status ─────────────────
if (($data['payment_status'] ?? '') !== 'COMPLETE') {
    pf_log('Payment not complete. Status: ' . ($data['payment_status'] ?? 'unknown'));
    http_response_code(200); // Still return 200 so PayFast doesn't retry
    exit;
}

// ── Step 4: Verify amount ─────────────────────────
$expected = 699.00;
$received = (float)($data['amount_gross'] ?? 0);
if ($received < $expected) {
    pf_fail("Amount mismatch. Expected: {$expected}, Received: {$received}");
}

// ── Step 5: Send order notification email ─────────
$name        = trim(($data['name_first'] ?? '') . ' ' . ($data['name_last'] ?? ''));
$cust_email  = $data['email_address'] ?? 'not provided';
$cell        = $data['cell_number']   ?? 'not provided';
$pudo_locker = $data['custom_str1']   ?? 'not specified';
$order_id    = $data['m_payment_id']  ?? 'unknown';
$pf_ref      = $data['pf_payment_id'] ?? 'unknown';
$amount_paid = number_format($received, 2);
$paid_at     = date('d F Y, H:i');

$subject = "☕ New Order — Coffee & Almond Bundle | {$name}";

$body = <<<EOT
NEW ORDER RECEIVED
══════════════════════════════════════

  Order ID:     {$order_id}
  PayFast Ref:  {$pf_ref}
  Amount Paid:  R {$amount_paid}
  Date / Time:  {$paid_at}

CUSTOMER DETAILS
──────────────────
  Name:         {$name}
  Email:        {$cust_email}
  Phone:        {$cell}

DELIVERY
──────────────────
  PUDO Locker:  {$pudo_locker}

ACTION REQUIRED
──────────────────
  Please pack the order and drop it at your
  nearest PUDO locker addressed to the customer's
  locker above.

  Reply to this email to contact the customer directly.

──────────────────
Coffee & Almond Organic Scrub
EOT;

$headers  = "From: Coffee & Almond Orders <{$from_email}>\r\n";
$headers .= "Reply-To: {$cust_email}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($notification_email, $subject, $body, $headers);
pf_log($sent
    ? "✓ Email sent to {$notification_email} for order {$order_id}"
    : "✗ Email FAILED for order {$order_id}"
);

http_response_code(200);
exit;
