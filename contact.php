<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST erlaubt.']);
    exit;
}

// Spam-Schutz: Honeypot & JS-Check
$honeypot = $_POST['website'] ?? '';
$jscheck = $_POST['jscheck'] ?? '';
if (!empty($honeypot) || $jscheck !== '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Spamverdacht.']);
    exit;
}

// CSRF-Token prüfen (Double Submit Cookie)
$csrf_cookie = $_COOKIE['csrf_token'] ?? '';
$csrf_post = $_POST['csrf_token'] ?? '';
if (empty($csrf_cookie) || empty($csrf_post) || !hash_equals($csrf_cookie, $csrf_post)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ungültiges CSRF-Token.']);
    exit;
}

// Whitelist & Validierung
$fields = [
    'name', 'residence', 'email', 'ceremony', 'date', 'location', 'message', 'csrf_token', 'jscheck', 'website'
];
foreach ($_POST as $key => $val) {
    if (!in_array($key, $fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unerlaubtes Feld erkannt.']);
        exit;
    }
}

const LINE_BREAK_PATTERN = '/[\r\n]/';

// Sanitize helper
function cleanText($s, $maxLength = 2000) {
    return substr(preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', strip_tags(trim($s))), 0, $maxLength);
}

// Validierung
$errors = [];

$name = cleanText($_POST['name'] ?? '', 100);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$ceremony = $_POST['ceremony'] ?? '';
$message = cleanText($_POST['message'] ?? '', 2000);

if ($name === '' || strlen($name) > 100 || preg_match(LINE_BREAK_PATTERN, $name)) {
    $errors[] = 'Name ungültig.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match(LINE_BREAK_PATTERN, $email)) {
    $errors[] = 'E-Mail ungültig.';
}
if (!in_array($ceremony, ['freie-trauung', 'kinderwillkommensfest'])) {
    $errors[] = 'Zeremonienart ungültig.';
}
if (strlen($message) < 10) {
    $errors[] = 'Nachricht zu kurz.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Optionale Felder
$residence = cleanText($_POST['residence'] ?? '', 100);
$date = cleanText($_POST['date'] ?? '', 30);
$location = cleanText($_POST['location'] ?? '', 100);

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
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Header ungültig.']);
        exit;
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
