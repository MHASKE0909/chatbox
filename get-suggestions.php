<?php
header("Content-Type: application/json");

$apiKey = "AIzaSyA5DSLgexrpa50LDnfIJrhu6uCKza-uotU";  // Replace with actual API key
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userMessage = isset($_POST["message"]) ? $_POST["message"] : "";

    if (empty($userMessage)) {
        echo json_encode(["error" => "Message cannot be empty."]);
        exit;
    }

    // AI Prompt for casual chat suggestions
    $data = json_encode([
        "contents" => [["parts" => [[
            "text" => "Generate three short (exactly 3 words) casual chat replies in English and three in Hindi (written in English letters) for this message: \"$userMessage\". Keep them friendly, fun, and natural."
        ]]]]
    ]);

    $options = [
        "http" => [
            "header" => "Content-Type: application/json",
            "method" => "POST",
            "content" => $data
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(["error" => "API request failed."]);
        exit;
    }
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData["candidates"][0]["content"]["parts"][0]["text"])) {
        $rawSuggestions = explode("\n", $responseData["candidates"][0]["content"]["parts"][0]["text"]);

        // Keep only 3-word suggestions
        $cleanedSuggestions = array_filter(array_map(function ($text) {
            return preg_replace('/^\s*\d+[\.\)-]\s*/', '', trim($text));
        }, $rawSuggestions), function ($text) {
            return str_word_count($text) === 3;
        });

        echo json_encode(["suggestions" => array_values($cleanedSuggestions)]);
    } else {
        echo json_encode(["error" => "Invalid API response."]);
    }
}
?>
