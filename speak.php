<?php
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $text = isset($_POST["text"]) ? $_POST["text"] : "";

    if (empty($text)) {
        echo json_encode(["error" => "Text cannot be empty."]);
        exit;
    }

    $apiKey = "ef5994caec644a7e9d74ebcbfc2e5c3a"; // Get from https://www.voicerss.org/
    $url = "https://api.voicerss.org/?key=$apiKey&hl=en-us&src=" . urlencode($text);

    echo json_encode(["audio_url" => $url]);
}
?>
