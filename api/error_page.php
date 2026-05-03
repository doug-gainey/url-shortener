<?php

function outputErrorPage(string $title, string $message, int $statusCode = 404): void {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .error-title {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .error-message {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        .error-code {
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔗</div>
        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$message}</p>
        <p class="error-code">Error Code: {$statusCode}</p>
    </div>
</body>
</html>
HTML;

    http_response_code($statusCode);
    echo $html;
}