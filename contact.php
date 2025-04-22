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
    'name', 'phone', 'email', 'ceremony', 'date', 'location', 'message', 'csrf_token', 'jscheck', 'website'
];
foreach ($_POST as $key => $val) {
    if (!in_array($key, $fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unerlaubtes Feld erkannt.']);
        exit;
    }
}

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$ceremony = $_POST['ceremony'] ?? '';
$message = trim($_POST['message'] ?? '');

if ($name === '' || strlen($name) > 100 || preg_match('/[\r\n]/', $name)) {
    $errors[] = 'Name ungültig.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    $errors[] = 'E-Mail ungültig.';
}
if (!in_array($ceremony, ['freie-trauung', 'kinderwillkommensfest'])) {
    $errors[] = 'Zeremonienart ungültig.';
}
if (strlen($message) < 10 || strlen($message) > 2000) {
    $errors[] = 'Nachricht zu kurz oder zu lang.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Optionale Felder
$phone = trim($_POST['phone'] ?? '');
$date = $_POST['date'] ?? '';
$location = trim($_POST['location'] ?? '');

// Mail vorbereiten
$to = 'kontakt@janine-lindenmann.de';
$subject = 'Neue Kontaktanfrage';
$body = "Neue Nachricht:\n\n" .
        "Name: $name\nE-Mail: $email\nTelefon: $phone\nZeremonie: $ceremony\nDatum: $date\nOrt: $location\n\nNachricht:\n$message\n";

$headers = [
    'From' => 'webformular@janine-lindenmann.de',
    'Reply-To' => $email,
    'Content-Type' => 'text/plain; charset=UTF-8'
];

$success = mail(
    $to,
    $subject,
    $body,
    implode("\r\n", array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers))
);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Nachricht erfolgreich gesendet.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fehler beim Mailversand.']);
}
