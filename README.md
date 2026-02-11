# MLflow PHP Client

Track, debug, and monitor your RAG applications and LLM integrations in PHP. Built for Laravel with comprehensive tracing utilities and Laravel-native developer experience.

[![Tests](https://img.shields.io/badge/tests-73%20passing-brightgreen)]()
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen)]()
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-blue)]()
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red)]()
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Why MLflow for PHP?

PHP powers applications that consume AI services, not train models. This package helps you:

- 🔍 **Debug RAG pipelines** - Track retrieval quality, chunk relevance, and generation steps
- 📊 **Monitor LLM costs** - Log OpenAI/Claude/Ollama API calls with tokens and latency
- 🧪 **Test prompts** - Version, compare, and optimize prompt templates
- 🐛 **Find issues fast** - Search traces for slow/failed LLM calls in production
- 🎯 **Improve retrieval** - Analyze what documents are retrieved and why

## Features

- ✅ **LLM Tracing** - Track OpenAI, Claude, Ollama, and custom LLM calls
- ✅ **RAG Observability** - Monitor retrieval, reranking, and generation steps
- ✅ **Prompt Management** - Version and compare prompt templates
- ✅ **Laravel First** - Facades, Events, Commands, Testing fakes, Dependency Injection
- ✅ **Type Safety** - PHPStan Level 9, full type hints, contracts/interfaces
- ✅ **Testing Utilities** - `MLflow::fake()` with assertions for TDD
- ✅ **Production Ready** - Error handling, logging, caching, batch operations

## Installation

```bash
composer require martijn/mlflow-php-client
```

### Laravel Setup

```bash
# Install package
composer require martijn/mlflow-php-client

# Install (optional - auto-discovered)
php artisan mlflow:install

# Test connection
php artisan mlflow:test-connection
```

### Configuration

```env
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_API_TOKEN=your-token-here
MLFLOW_DEFAULT_EXPERIMENT=rag-application
```

## Quick Start

### Track an OpenAI API Call

```php
use MLflow\Laravel\Facades\MLflow;
use OpenAI\Laravel\Facades\OpenAI;

// Start a trace for this user query
$trace = MLflow::createTraceBuilder()
    ->withRequestMetadata('user_id', auth()->id())
    ->withRequestMetadata('query', $userQuery)
    ->start();

// Make the LLM call
$response = OpenAI::chat()->create([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'user', 'content' => $userQuery]
    ],
]);

// Log the trace with costs and latency
MLflow::traces()->logTrace([
    'request_id' => $trace->requestId,
    'timestamp_ms' => now()->timestamp * 1000,
    'request' => ['messages' => [['role' => 'user', 'content' => $userQuery]]],
    'response' => ['content' => $response->choices[0]->message->content],
    'tags' => [
        'model' => 'gpt-4',
        'tokens_prompt' => $response->usage->promptTokens,
        'tokens_completion' => $response->usage->completionTokens,
        'cost_usd' => $this->calculateCost($response->usage),
    ],
]);
```

### Track a Complete RAG Pipeline

```php
use MLflow\Laravel\Facades\MLflow;

class DocumentSearchService
{
    public function search(string $query): array
    {
        // Create experiment for RAG tracking
        $experiment = MLflow::experiments()->create('document-rag');

        // Start a run for this query
        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName('rag-query-' . now()->format('Y-m-d-His'))
            ->withParam('query', $query)
            ->withParam('embedding_model', 'text-embedding-ada-002')
            ->withParam('top_k', 5)
            ->withTag('user_id', auth()->id())
            ->start();

        // Step 1: Generate embedding
        $embeddingStart = microtime(true);
        $embedding = $this->generateEmbedding($query);
        MLflow::runs()->logMetric($run->info->runId, 'embedding_latency_ms',
            (microtime(true) - $embeddingStart) * 1000
        );

        // Step 2: Search vector database
        $searchStart = microtime(true);
        $documents = $this->vectorSearch($embedding, limit: 5);
        MLflow::runs()->logMetric($run->info->runId, 'search_latency_ms',
            (microtime(true) - $searchStart) * 1000
        );
        MLflow::runs()->logMetric($run->info->runId, 'documents_retrieved',
            count($documents)
        );

        // Step 3: Rerank documents
        $rerankedDocs = $this->rerank($query, $documents);
        MLflow::runs()->logMetric($run->info->runId, 'avg_relevance_score',
            collect($rerankedDocs)->avg('score')
        );

        // Step 4: Generate response with context
        $llmStart = microtime(true);
        $response = $this->generateResponse($query, $rerankedDocs);
        MLflow::runs()->logMetric($run->info->runId, 'llm_latency_ms',
            (microtime(true) - $llmStart) * 1000
        );

        // Log final results
        MLflow::runs()->logParam($run->info->runId, 'response_length',
            strlen($response)
        );

        return [
            'response' => $response,
            'documents' => $rerankedDocs,
            'run_id' => $run->info->runId,
        ];
    }
}
```

### Prompt Versioning and Testing

```php
// Create a prompt template
$prompt = MLflow::prompts()->createPrompt(
    name: 'customer-support-v1',
    template: 'You are a helpful customer support agent. Answer: {{question}}'
);

// Use the prompt
$promptText = str_replace('{{question}}', $userQuestion, $prompt->template);
$response = OpenAI::chat()->create([
    'model' => 'gpt-4',
    'messages' => [['role' => 'user', 'content' => $promptText]],
]);

// Track which prompt version was used
MLflow::traces()->logTrace([
    'request' => ['prompt_version' => 'customer-support-v1'],
    'response' => ['content' => $response->choices[0]->message->content],
]);

// Later: compare v1 vs v2
$results = MLflow::traces()->searchTraces(
    experimentId: $experimentId,
    filter: "tags.prompt_version = 'customer-support-v2'"
);
```

### Debug Slow or Failed LLM Calls

```php
// Search for slow LLM calls
$slowCalls = MLflow::runs()->search(
    experimentIds: [$experimentId],
    filter: "metrics.llm_latency_ms > 5000",
    orderBy: ['metrics.llm_latency_ms DESC'],
    maxResults: 10
);

foreach ($slowCalls['runs'] as $run) {
    echo "Slow query: {$run->data->params->getByKey('query')->value}\n";
    echo "Latency: {$run->data->metrics->getLatestByKey()['llm_latency_ms']->value}ms\n";
}

// Find failed API calls
$failures = MLflow::traces()->searchTraces(
    experimentId: $experimentId,
    filter: "tags.status = 'error'"
);
```

## Laravel Integration

### Dependency Injection

```php
use MLflow\Contracts\MLflowClientContract;
use MLflow\Contracts\TraceApiContract;

class ChatService
{
    public function __construct(
        private MLflowClientContract $mlflow,
        private TraceApiContract $traces
    ) {}

    public function chat(string $message): string
    {
        $trace = $this->traces->logTrace([
            'request' => ['message' => $message],
            'tags' => ['user_id' => auth()->id()],
        ]);

        // Chat logic...
    }
}
```

### Testing

```php
use MLflow\Laravel\Facades\MLflow;

public function test_rag_search()
{
    MLflow::fake();

    // Your RAG code
    $result = $this->searchService->search('How do I reset my password?');

    // Assert MLflow interactions
    MLflow::assertRunCreated();
    MLflow::assertMetricLogged($result['run_id'], 'documents_retrieved', 5);
    MLflow::assertParamLogged($result['run_id'], 'query', 'How do I reset my password?');
}
```

### Events

```php
use MLflow\Laravel\Events\{TraceLogged, RunStarted};

Event::listen(TraceLogged::class, function ($event) {
    // Send LLM usage to monitoring
    if ($cost = $event->trace->tags['cost_usd'] ?? null) {
        Metrics::increment('llm.cost', $cost);
    }
});

Event::listen(RunStarted::class, function ($event) {
    Log::info('RAG query started', [
        'run_id' => $event->run->info->runId,
        'query' => $event->run->data->params['query'] ?? null,
    ]);
});
```

### Artisan Commands

```bash
# List experiments
php artisan mlflow:experiments:list

# View recent traces
php artisan mlflow:traces:list --experiment=rag-application --max=20

# Clear cache
php artisan mlflow:clear-cache
```

### Helper Functions

```php
// Get client instance
$client = mlflow();

// Quick logging
mlflow_log_metric($runId, 'retrieval_score', 0.85);
mlflow_log_param($runId, 'embedding_model', 'text-embedding-ada-002');

// Get experiment/run
$experiment = mlflow_experiment('exp-123');
$run = mlflow_run('run-456');
```

## API Overview

```php
// Traces (LLM tracking)
MLflow::traces()->logTrace($traceData)
MLflow::traces()->getById($requestId)
MLflow::traces()->searchTraces($experimentId, $filter)

// Prompts (Prompt management)
MLflow::prompts()->createPrompt('name', 'template')
MLflow::prompts()->getPrompt('name', $version)
MLflow::prompts()->searchPrompts($filter)

// Experiments & Runs (RAG pipeline tracking)
MLflow::experiments()->create('rag-app')
MLflow::runs()->create($experimentId)
MLflow::runs()->logMetric($runId, 'relevance', 0.95)
MLflow::runs()->logParameter($runId, 'top_k', '5')
MLflow::runs()->search($experimentIds, $filter)

// Datasets (Track training data)
MLflow::datasets()->createDataset('faq-dataset')
MLflow::datasets()->searchDatasets($experimentId)
```

## Documentation

- [RAG Tracing Guide](docs/RAG_TRACING.md) - Complete RAG observability patterns
- [LLM Integration](docs/LLM_INTEGRATION.md) - OpenAI, Claude, Ollama examples
- [Testing Guide](docs/TESTING.md) - Test your LLM integrations
- [Experiments & Training](docs/EXPERIMENTS_AND_TRAINING.md) - Model registry and experiment tracking
- [Integration Patterns](docs/INTEGRATION_PATTERNS.md) - Real-world Laravel examples
- [Best Practices](docs/BEST_PRACTICES.md) - Performance and optimization tips
- [Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues and solutions

## Requirements

- PHP 8.4+
- Laravel 12+
- MLflow server 2.0+

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run quality checks
composer quality

# Fix code style
composer pint

# Static analysis
composer phpstan
```

## Quality

- **73 tests** with 356 assertions
- **PHPStan Level 9** - Strictest static analysis
- **100% Laravel Pint** - PSR-12 compliant
- **Comprehensive coverage** - Unit, integration, and feature tests

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Security

For security vulnerabilities, see [SECURITY.md](SECURITY.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Built with ❤️ for the Laravel and MLflow communities.
