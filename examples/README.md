# MLflow PHP Client Examples

This directory contains practical, runnable examples demonstrating common use cases.

## Prerequisites

1. **MLflow Server Running**

   **Option A: Using Docker Compose (Recommended)**
   ```bash
   docker-compose up -d
   ```

   **Option B: Local Installation**
   ```bash
   pip install mlflow==2.11.0
   mlflow server --host 0.0.0.0 --port 5000
   ```

   > **Note:** MLflow 3.x+ has authentication enabled by default. For testing, use MLflow 2.x or configure authentication in your client.

2. **Dependencies Installed**
   ```bash
   composer install
   ```

## Available Examples

### 01. Basic Usage
**File:** `01-basic-usage.php`

Learn the fundamentals:
- Connect to MLflow
- Create experiments and runs
- Log parameters, metrics, and tags
- Complete runs

```bash
php examples/01-basic-usage.php
```

---

### 02. Fluent Builder
**File:** `02-fluent-builder.php`

Use the fluent builder API for cleaner code:
- Chain multiple operations
- Configure runs declaratively
- Start runs with all settings in one go

```bash
php examples/02-fluent-builder.php
```

---

### 03. Model Registry
**File:** `03-model-registry.php`

Manage ML models lifecycle:
- Register models
- Create versions
- Transition through stages (Staging → Production)
- Set aliases for versions

```bash
php examples/03-model-registry.php
```

---

### 04. Batch Logging
**File:** `04-batch-logging.php`

Efficient batch operations:
- Log multiple metrics at once
- Log multiple parameters at once
- Improve performance with batch API calls

```bash
php examples/04-batch-logging.php
```

---

### 05. Laravel Integration
**File:** `05-laravel-integration.php`

Laravel-specific patterns (pseudo-code):
- Dependency injection
- Facades
- Testing with fakes
- Events
- Artisan commands
- Queue jobs

```bash
# This file contains code examples to copy into your Laravel app
cat examples/05-laravel-integration.php
```

---

### 06. Search and Compare
**File:** `06-search-and-compare.php`

Search and analyze runs:
- Search experiments
- Filter runs by metrics/parameters
- Compare multiple runs
- Find best performing models

```bash
php examples/06-search-and-compare.php
```

---

## Quick Start

```bash
# Start MLflow server
mlflow server --host 0.0.0.0 --port 5000

# In another terminal, run any example
php examples/01-basic-usage.php
```

## Troubleshooting

**Connection Error?**
- Ensure MLflow server is running on http://localhost:5000
- Check server logs: `mlflow server --host 0.0.0.0 --port 5000`

**Run Errors?**
- Make sure you ran `composer install`
- Check PHP version: `php -v` (requires 8.4+)

## Next Steps

After running these examples:

1. Read the [Testing Guide](../docs/TESTING.md)
2. Explore [Integration Patterns](../docs/INTEGRATION_PATTERNS.md)
3. Check [Best Practices](../docs/BEST_PRACTICES.md)
4. Review [Troubleshooting](../docs/TROUBLESHOOTING.md)

## Need Help?

- 📖 Main README: [../README.md](../README.md)
- 🐛 Issues: https://github.com/axyr/mlflow-php-client/issues
- 💬 Discussions: https://github.com/axyr/mlflow-php-client/discussions
