<?php

/**
 * Send a JSON response with appropriate headers.
 */
function jsonResponse(mixed $data, int $status = 200, array $headers = []): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    
    foreach ($headers as $name => $value) {
        header("$name: $value");
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a standardised error response.
 */
function errorResponse(string $message, int $status = 400, ?string $error = null): void
{
    $body = [
        'error' => $error ?? httpStatusText($status),
        'message' => $message,
        'status' => $status,
    ];
    jsonResponse($body, $status);
}

/**
 * Get HTTP status text for common codes.
 */
function httpStatusText(int $status): string
{
    return match ($status) {
        400 => 'Bad Request',
        404 => 'Not Found',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        default => 'Error',
    };
}

/**
 * Get a query parameter with optional default.
 */
function queryParam(string $name, mixed $default = null): mixed
{
    return $_GET[$name] ?? $default;
}

/**
 * Validate and return a positive integer from query params.
 */
function queryInt(string $name, int $default, int $min = 1, int $max = 100): int
{
    $value = queryParam($name, $default);
    $int = filter_var($value, FILTER_VALIDATE_INT);
    
    if ($int === false || $int < $min) {
        return $default;
    }
    
    return min($int, $max);
}

/**
 * Generate a simple request ID for logging/tracing.
 */
function generateRequestId(): string
{
    return bin2hex(random_bytes(8));
}

/**
 * Generate an ETag from data.
 */
function generateEtag(mixed $data): string
{
    return '"' . md5(json_encode($data)) . '"';
}

/**
 * Check if client cache is still valid (ETag match).
 */
function checkEtagMatch(string $etag): bool
{
    $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    return $clientEtag === $etag;
}

/**
 * Simple structured log output.
 */
function logMessage(string $level, string $message, array $context = []): void
{
    $entry = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'request_id' => $GLOBALS['request_id'] ?? null,
    ];
    
    if (!empty($context)) {
        $entry['context'] = $context;
    }
    
    error_log(json_encode($entry));
}



