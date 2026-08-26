<?php
require_once 'config.php';

// --- CONFIGURATION ---
if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY');
}
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't leak errors to output
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// The System Persona
$systemInstruction = "You are Paws AI, a friendly and expert pet care assistant for the 'Pet Rescue' platform. 

CRITICAL RULE: You MUST ONLY answer questions related to pets, pet care, animal welfare, rescue, adoption, and veterinary health. 
If the user asks about ANY other topic (e.g., general knowledge, math, coding, personal advice, etc.), you MUST politely refuse and redirect them back to pet-related topics.

Example refusal: 'I'm sorry, I'm here to help with pet care and rescue only! Do you have any questions about your furry friends?'

Keep your tone warm and helpful. Use simple HTML like <b> for bold or <br> for new lines. Never use markdown code blocks.";

$payload = [
    "model" => "openai/gpt-oss-120b",
    "messages" => [
        ["role" => "system", "content" => $systemInstruction],
        ["role" => "user", "content" => $userMessage]
    ],
    "temperature" => 0.7,
    "max_tokens" => 500
];

$ch = curl_init(GROQ_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . GROQ_API_KEY
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP/Localhost SSL issues
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

try {
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        throw new Exception("cURL Error: " . $curlError);
    }
} catch (Exception $e) {
    echo json_encode(['reply' => "Internal System Error: " . $e->getMessage()]);
    exit;
}

if ($result === false) {
    echo json_encode(['reply' => "Connection Error: " . $curlError]);
    exit;
}

$responseData = json_decode($result, true);

if ($httpCode !== 200) {
    $errorMessage = $responseData['error']['message'] ?? "Unknown API Error";
    echo json_encode(['reply' => "API Error ($httpCode): " . $errorMessage]);
    exit;
}

$botReply = $responseData['choices'][0]['message']['content'] ?? "I'm having a little trouble connecting to my brain. Please check your API key!";

echo json_encode(['reply' => $botReply]);
