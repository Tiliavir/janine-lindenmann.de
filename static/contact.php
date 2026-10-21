<?php
header('Content-Type: application/json');

// Every input is read through these helpers: arrays (name[]=x), missing values or other
// non-strings become '' instead of a TypeError. Attacker-triggerable fatal errors are the
// abnormal request termination PHP2Shell (CVE-2026-109618/-109619) builds on.
function post_str(string $key): string {
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}
function cookie_str(string $key): string {
    $v = $_COOKIE[$key] ?? '';
    return is_string($v) ? $v : '';
}
function fail(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode(['success' => false] + $payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    fail(405, ['message' => 'Nur POST erlaubt.']);
}

// The form is a few short text fields: refuse anything large or with uploaded files.
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384 || !empty($_FILES)) {
    fail(413, ['message' => 'Anfrage zu groß.']);
}

// Spam-Schutz: Honeypot & JS-Check
$honeypot = post_str('website');
$jscheck = post_str('jscheck');
if ($honeypot !== '' || $jscheck !== '1') {
    fail(403, ['message' => 'Spamverdacht.']);
}

// CSRF-Token prüfen (Double Submit Cookie)
$csrf_cookie = cookie_str('csrf_token');
$csrf_post = post_str('csrf_token');
if ($csrf_cookie === '' || $csrf_post === '' || !hash_equals($csrf_cookie, $csrf_post)) {
    fail(403, ['message' => 'Ungültiges CSRF-Token.']);
}

// Whitelist & Validierung
$fields = [
    'name', 'residence', 'email', 'ceremony', 'date', 'location', 'message', 'csrf_token', 'jscheck', 'website'
];
foreach ($_POST as $key => $val) {
    if (!in_array($key, $fields, true) || !is_string($val)) {
        fail(400, ['message' => 'Unerlaubtes Feld erkannt.']);
    }
}

const LINE_BREAK_PATTERN = '/[\r\n]/';

// Sanitize helper
function cleanText(string $s, int $maxLength = 2000): string {
    // preg_replace returns null on invalid UTF-8; treat that as empty input instead of passing null on
    $clean = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', strip_tags(trim($s))) ?? '';
    return substr($clean, 0, $maxLength);
}

// Validierung
$errors = [];

$name = cleanText(post_str('name'), 100);
$email = filter_var(post_str('email'), FILTER_SANITIZE_EMAIL);
$ceremony = post_str('ceremony');
$message = cleanText(post_str('message'), 2000);

if ($name === '' || strlen($name) > 100 || preg_match(LINE_BREAK_PATTERN, $name)) {
    $errors[] = 'Name ungültig.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match(LINE_BREAK_PATTERN, $email)) {
    $errors[] = 'E-Mail ungültig.';
}
if (!in_array($ceremony, ['freie-trauung', 'kinderwillkommensfest'], true)) {
    $errors[] = 'Zeremonienart ungültig.';
}
if (strlen($message) < 10) {
    $errors[] = 'Nachricht zu kurz.';
}

if (!empty($errors)) {
    fail(422, ['errors' => $errors]);
}

// Optionale Felder
$residence = cleanText(post_str('residence'), 100);
$date = cleanText(post_str('date'), 30);
$location = cleanText(post_str('location'), 100);

// Mail-Vorbereitung
$to = 'kontakt@janine-lindenmann.de';
$subject = 'Neue Kontaktanfrage';

$body = "Neue Nachricht:\n\n" .
        "Name: $name\n" .
        "E-Mail: $email\n" .
        "Wohnort: $residence\n" .
        "Zeremonie: $ceremony\n" .
        "Datum: $date\n" .
        "Ort der Zeremonie: $location\n\n" .
        "Nachricht:\n$message\n";

$headers = [
    'From' => 'webformular@janine-lindenmann.de',
    'Reply-To' => $email,
    'Content-Type' => 'text/plain; charset=UTF-8'
];

foreach ($headers as $k => $v) {
    if (preg_match(LINE_BREAK_PATTERN, $v)) {
        fail(400, ['message' => 'Header ungültig.']);
    }
}

$headersFormatted = implode("\r\n", array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers));

// Mail senden
$success = mail($to, $subject, $body, $headersFormatted);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Nachricht erfolgreich gesendet.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Bitte später erneut versuchen.']);
}
