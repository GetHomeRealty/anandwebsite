<?php
/**
 * GHR agent website: consultation form handler
 * Place at  <agent-domain>/api/lead.php  (next to index.html).
 * Emails each enquiry to the agent. Uses PHP mail(), which Bluehost supports.
 *
 * Settings: edit the three lines below.
 *   TO_EMAIL   where enquiries are sent (the agent)
 *   CC_EMAIL   optional copy (e.g. the brokerage); leave '' for none
 *   FROM_EMAIL must be an address on THIS website's domain (e.g. noreply@your-domain.ca),
 *              otherwise Bluehost may block or spam-folder the email.
 */
const TO_EMAIL   = 'anand.pericherla@gethomerealty.ca';
const CC_EMAIL   = '';
const FROM_EMAIL = '';            // '' = noreply@<this site's domain>

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out($code, $msg) { http_response_code($code); echo json_encode(['ok' => $code === 200, 'message' => $msg]); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, 'Method not allowed');

// Spam trap: real visitors never fill the hidden "website" field
if (!empty($_POST['website'])) out(200, 'Thank you');

// Basic rate limit: 5 enquiries per IP per 10 minutes
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rl = sys_get_temp_dir() . '/ghr_lead_' . md5($ip);
$hits = array_filter(array_map('intval', explode(',', (string)@file_get_contents($rl))), fn($t) => $t > time() - 600);
if (count($hits) >= 5) out(429, 'Too many requests. Please call or email instead.');
$hits[] = time(); @file_put_contents($rl, implode(',', $hits));

$clean = fn($k, $max = 200) => trim(mb_substr(str_replace(["\r", "\n"], ' ', strip_tags($_POST[$k] ?? '')), 0, $max));
$name   = $clean('name', 100);
$phone  = $clean('phone', 40);
$email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$intent = $clean('intent', 60);
$city   = $clean('city', 60);
$msg    = trim(mb_substr(strip_tags($_POST['msg'] ?? ''), 0, 3000));

if ($name === '' || $phone === '' || $email === '') out(422, 'Please enter your name, phone and a valid email.');

$host = preg_replace('/^www\./', '', preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$from = FROM_EMAIL ?: 'noreply@' . $host;

$subject = "New website enquiry: $name ($intent" . ($city ? ", $city" : '') . ')';
$body = "New consultation request from your website ($host)\n\n"
      . "Name:     $name\nPhone:    $phone\nEmail:    $email\n"
      . "Looking:  $intent\nCity:     $city\n\nMessage:\n" . ($msg !== '' ? $msg : '(none)') . "\n\n"
      . "Sent " . date('Y-m-d H:i T') . " · IP $ip\n"
      . "Reply to this email to respond directly to $name.\n";

$headers = "From: Website Enquiry <$from>\r\n"
         . "Reply-To: $name <$email>\r\n"
         . (CC_EMAIL ? "Cc: " . CC_EMAIL . "\r\n" : '')
         . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";

if (!@mail(TO_EMAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-f' . $from)) {
  out(500, 'Sorry, your request could not be sent. Please call or email instead.');
}
out(200, 'Thank you! Your request has been received.');
