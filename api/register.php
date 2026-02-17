<?php

// ==============================
// ALLOW EVERYTHING (DEV MODE)
// ==============================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// Handle preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==============================
// DEBUG INFO (optional)
// ==============================
// file_put_contents("debug.txt", print_r($_SERVER, true));

// ==============================
// LOAD SECRET KEY
// ==============================

// If you have config file
// require_once __DIR__ . '/../includes/config.php';
// $SECRET_KEY = SECRET_KEY;

// OR directly define for now (DEV)
$SECRET_KEY = "YOUR_SECRET_KEY_HERE";

// ==============================
// GET INPUT (JSON or FORM)
// ==============================

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

// If JSON failed, try normal POST form
if (!$input) {
    $input = $_POST;
}

// If still empty
if (!$input) {
    http_response_code(400);
    echo json_encode([
        "status" => "Error",
        "message" => "No input received",
        "debug_method" => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

// ==============================
// REQUIRED FIELDS
// ==============================

$requiredFields = [
    'source',
    'college_id',
    'campus',
    'course',
    'name',
    'email',
    'mobile',
    'field_state_new'
];

foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode([
            "status" => "Error",
            "message" => "Missing field: $field"
        ]);
        exit();
    }
}

// ==============================
// PREPARE PAYLOAD
// ==============================

$payload = [
    "secret_key" => $SECRET_KEY,
    "source" => trim($input['source']),
    "college_id" => trim($input['college_id']),
    "campus" => trim($input['campus']),
    "course" => trim($input['course']),
    "name" => trim($input['name']),
    "email" => filter_var($input['email'], FILTER_SANITIZE_EMAIL),
    "mobile" => preg_replace('/[^0-9]/', '', $input['mobile']),
    "field_state_new" => trim($input['field_state_new'])
];

// Optional
if (!empty($input['medium'])) {
    $payload['medium'] = trim($input['medium']);
}

if (!empty($input['campaign'])) {
    $payload['campaign'] = trim($input['campaign']);
}

// ==============================
// CALL EXTERNAL API
// ==============================

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.in8.nopaperforms.com/dataporting/6205/stealth",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        "status" => "Error",
        "message" => "cURL Error",
        "error" => curl_error($ch)
    ]);
} else {
    http_response_code($httpCode);
    echo $response;
}

curl_close($ch);
