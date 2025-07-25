<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isServerRunning($port) {
    $connection = @fsockopen('localhost', $port);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

function runOllamaModel($model, $port, $query) {
    // Ensure Ollama models are running
    exec("pgrep -f 'ollama serve {$model}' || ollama serve {$model} -p {$port} > /dev/null 2>&1 &");
    sleep(2);  // Give time for model to start

    // Check if the server is running
    if (!isServerRunning($port)) {
        return "Error: Unable to connect to Ollama server on port {$port}. Please ensure the server is running.";
    }

    // Prepare curl request to local Ollama instance
    $ch = curl_init("http://localhost:{$port}/api/generate");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'prompt' => $query,
        'stream' => false
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);

    // Check for curl errors
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return "Curl error: {$error}";
    }

    curl_close($ch);
    
    $result = json_decode($response, true);

    // Check for JSON decoding errors or missing response
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "JSON decode error: " . json_last_error_msg();
    }

    // Ensure response is properly formatted
    if (!isset($result['response'])) {
        return "Error processing query: No valid response from model.";
    }

    return $result['response'];
}

// Validate and sanitize input
$query = trim($_POST['query'] ?? '');
if (empty($query)) {
    echo json_encode(['response' => 'No query provided.']);
    exit;
}

try {
    // Use DeepSeek-R1 to answer the query
    $finalResponse = runOllamaModel('deepseek-r1', 11434, $query);

    // Ensure session conversations array exists
    if (!isset($_SESSION['conversations']) || !is_array($_SESSION['conversations'])) {
        $_SESSION['conversations'] = [];
    }

    // Add current conversation
    $_SESSION['conversations'][] = [
        ['type' => 'user', 'content' => $query],
        ['type' => 'assistant', 'content' => $finalResponse]
    ];

    // Limit conversation history to the last 10 entries
    if (count($_SESSION['conversations']) > 10) {
        array_shift($_SESSION['conversations']);
    }

    // Return response
    echo json_encode(['response' => $finalResponse]);
} catch (Exception $e) {
    echo json_encode(['response' => 'An error occurred: ' . $e->getMessage()]);
}
?>