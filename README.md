# Content Discovery API

A read-optimised REST API for discovering content resources. Built with vanilla PHP and designed to run in Docker.

## Quick Start

```bash
docker-compose up --build
```

The API will be available at `http://localhost:8080`


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
