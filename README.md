# Content Discovery API

A read-optimised REST API for discovering content resources. Built with vanilla PHP and designed to run in Docker.

## Quick Start

```bash
docker-compose up --build
```

The API will be available at `http://localhost:8080`

## Endpoints

### Health Check

```bash
curl http://localhost:8080/health
```

### List Resources

```bash
# Basic list
curl http://localhost:8080/resources

# With pagination
curl "http://localhost:8080/resources?page=1&limit=5"

# Filter by type
curl "http://localhost:8080/resources?type=plugin"

# Filter by platform
curl "http://localhost:8080/resources?platform=windows"

# Sort by popularity
curl "http://localhost:8080/resources?sort_by=download_count&order=desc"

# Combined
curl "http://localhost:8080/resources?type=theme&sort_by=name&order=asc&limit=5"
```

#### Query Parameters

| Parameter | Description | Default | Constraints |
|-----------|-------------|---------|-------------|
| `page` | Page number | 1 | 1-100 |
| `limit` | Items per page | 10 | 1-50 |
| `type` | Filter by type | - | `theme`, `plugin` |
| `platform` | Filter by platform | - | `all`, `windows`, `macos`, `linux` |
| `sort_by` | Sort field | `updated_at` | `name`, `download_count`, `updated_at` |
| `order` | Sort direction | `desc` | `asc`, `desc` |

### Get Single Resource

```bash
curl http://localhost:8080/resources/res-001
```

## Response Format

### Success (List)

```json
{
  "data": [
    {
      "id": "res-001",
      "name": "Dark Theme Pack",
      "description": "A collection of dark themes.",
      "version": "2.1.0",
      "compatibility": "1.0+",
      "type": "theme",
      "platform": "all",
      "download_count": 15420,
      "updated_at": "2024-12-01T10:30:00Z"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 10,
    "pages": 2
  }
}
```

### Error

```json
{
  "error": "Not Found",
  "message": "Resource not found: res-999",
  "status": 404
}
```

## Caching

The API implements two levels of caching:

### HTTP Caching
- `Cache-Control` headers with appropriate max-age
- `ETag` support for conditional requests
- Returns `304 Not Modified` when content unchanged

### Application Caching
- In-memory cache using the `CacheInterface`
- Cache-aside pattern in `ResourceRepository`
- Configurable TTL per operation type

The cache interface is designed to be swappable with Redis for production use.

## Planned Project Structure

```
├── docker/
│   ├── Dockerfile
│   └── nginx.conf
├── docker-compose.yml
├── public/
│   └── index.php         # Entry point and routing
├── src/
│   ├── Cache.php         # Cache interface and implementation
│   ├── ResourceRepository.php
│   └── helpers.php
├── data/
│   └── resources.php     # Seed data
└── README.md
```

## Design Decisions

- **Vanilla PHP**: No framework dependencies, HTTP fundamentals only
- **Cache Interface**: Abstracts caching to allow Redis swap without code changes
- **In-Memory Data**: Simplified for demo; production would use a database
- **Docker**: Single command to run for demo purposes
