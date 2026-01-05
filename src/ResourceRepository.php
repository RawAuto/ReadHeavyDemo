<?php

/**
 * Repository for accessing resource data with caching.
 * Demonstrates the cache-aside pattern.
 */
class ResourceRepository
{
    private CacheInterface $cache;
    private array $data;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->data = require __DIR__ . '/../data/resources.php';
    }

    /**
     * Find a single resource by ID.
     * Uses cache-aside pattern with 5-minute TTL.
     */
    public function findById(string $id): ?array
    {
        $cacheKey = "resource:{$id}";

        // Check cache first
        if ($this->cache->has($cacheKey)) {
            logMessage('debug', 'Cache hit', ['key' => $cacheKey]);
            return $this->cache->get($cacheKey);
        }

        // Cache miss - fetch from source
        logMessage('debug', 'Cache miss', ['key' => $cacheKey]);
        $resource = $this->findInData($id);

        if ($resource !== null) {
            $this->cache->set($cacheKey, $resource, 300); // 5 min TTL
        }

        return $resource;
    }

    /**
     * Find resources with filtering, sorting, and pagination.
     * Results are cached based on query parameters.
     */
    public function findAll(array $params = []): array
    {
        $cacheKey = 'resources:' . md5(json_encode($params));

        if ($this->cache->has($cacheKey)) {
            logMessage('debug', 'Cache hit', ['key' => $cacheKey]);
            return $this->cache->get($cacheKey);
        }

        logMessage('debug', 'Cache miss', ['key' => $cacheKey]);

        $results = $this->data;

        // Apply filters
        $results = $this->applyFilters($results, $params);

        // Get total before pagination
        $total = count($results);

        // Apply sorting
        $results = $this->applySorting($results, $params);

        // Apply pagination
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $offset = ($page - 1) * $limit;
        $results = array_slice($results, $offset, $limit);

        $response = [
            'data' => array_values($results),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
            ],
        ];

        $this->cache->set($cacheKey, $response, 60); // 1 min TTL for lists

        return $response;
    }

    /**
     * Get total count of resources.
     */
    public function count(): int
    {
        return count($this->data);
    }

    private function findInData(string $id): ?array
    {
        foreach ($this->data as $resource) {
            if ($resource['id'] === $id) {
                return $resource;
            }
        }
        return null;
    }

    private function applyFilters(array $data, array $params): array
    {
        // Filter by type
        if (!empty($params['type'])) {
            $type = $params['type'];
            $data = array_filter($data, fn($r) => $r['type'] === $type);
        }

        // Filter by platform
        if (!empty($params['platform'])) {
            $platform = $params['platform'];
            $data = array_filter($data, fn($r) => 
                $r['platform'] === $platform || $r['platform'] === 'all'
            );
        }

        return $data;
    }

    private function applySorting(array $data, array $params): array
    {
        $sortBy = $params['sort_by'] ?? 'updated_at';
        $order = strtolower($params['order'] ?? 'desc');

        // Validate sort field
        $allowedSorts = ['name', 'download_count', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'updated_at';
        }

        usort($data, function ($a, $b) use ($sortBy, $order) {
            $valA = $a[$sortBy];
            $valB = $b[$sortBy];

            if ($valA === $valB) {
                return 0;
            }

            $result = $valA < $valB ? -1 : 1;
            return $order === 'desc' ? -$result : $result;
        });

        return $data;
    }
}



