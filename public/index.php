<?php

/**
 * Content Discovery API - Entry Point
 * 
 * A read-optimised REST API demonstrating caching, error handling,
 * and production-ready patterns.
 */

// Load dependencies
require_once __DIR__ . '/../src/Cache.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/ResourceRepository.php';

// Generate request ID for tracing
$GLOBALS['request_id'] = generateRequestId();
header('X-Request-Id: ' . $GLOBALS['request_id']);

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

logMessage('info', 'Request received', [
    'method' => $method,
    'path' => $path,
]);

// Only allow GET requests
if ($method !== 'GET') {
    errorResponse('Method not allowed', 405);
}

// Initialize repository with cache
$cache = new ArrayCache();
$repository = new ResourceRepository($cache);

// Route the request
match (true) {
    $path === '/health' => handleHealth(),
    $path === '/resources' => handleResourceList($repository),
    preg_match('#^/resources/([a-z0-9-]+)$#', $path, $m) === 1 => handleResourceDetail($repository, $m[1]),
    default => errorResponse('Not found', 404),
};

/**
 * Health check endpoint.
 */
function handleHealth(): void
{
    jsonResponse([
        'status' => 'healthy',
        'timestamp' => date('c'),
    ]);
}

/**
 * List resources with filtering, sorting, and pagination.
 * 
 * Query params:
 *   - page: Page number (default: 1, max: 100)
 *   - limit: Items per page (default: 10, max: 50)
 *   - type: Filter by type (theme, plugin)
 *   - platform: Filter by platform (all, windows, macos, linux)
 *   - sort_by: Sort field (name, download_count, updated_at)
 *   - order: Sort order (asc, desc)
 */
function handleResourceList(ResourceRepository $repository): void
{
    $params = [
        'page' => queryInt('page', 1, 1, 100),
        'limit' => queryInt('limit', 10, 1, 50),
        'type' => queryParam('type'),
        'platform' => queryParam('platform'),
        'sort_by' => queryParam('sort_by', 'updated_at'),
        'order' => queryParam('order', 'desc'),
    ];

    // Validate type if provided
    if ($params['type'] !== null && !in_array($params['type'], ['theme', 'plugin'], true)) {
        errorResponse('Invalid type. Must be: theme, plugin', 400);
    }

    // Validate platform if provided
    $validPlatforms = ['all', 'windows', 'macos', 'linux'];
    if ($params['platform'] !== null && !in_array($params['platform'], $validPlatforms, true)) {
        errorResponse('Invalid platform. Must be: all, windows, macos, linux', 400);
    }

    // Validate order
    if (!in_array(strtolower($params['order']), ['asc', 'desc'], true)) {
        $params['order'] = 'desc';
    }

    $result = $repository->findAll($params);

    // Generate ETag for caching
    $etag = generateEtag($result);
    
    if (checkEtagMatch($etag)) {
        http_response_code(304);
        exit;
    }

    jsonResponse($result, 200, [
        'Cache-Control' => 'public, max-age=60',
        'ETag' => $etag,
    ]);
}

/**
 * Get a single resource by ID.
 */
function handleResourceDetail(ResourceRepository $repository, string $id): void
{
    $resource = $repository->findById($id);

    if ($resource === null) {
        errorResponse("Resource not found: {$id}", 404);
    }

    // Generate ETag for caching
    $etag = generateEtag($resource);
    
    if (checkEtagMatch($etag)) {
        http_response_code(304);
        exit;
    }

    jsonResponse($resource, 200, [
        'Cache-Control' => 'public, max-age=300',
        'ETag' => $etag,
    ]);
}



