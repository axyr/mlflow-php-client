# Troubleshooting Guide

Common issues and solutions when working with the MLflow PHP Client in Laravel.

## Connection Issues

### Cannot Connect to MLflow Server

**Problem**: `NetworkException: Cannot connect to MLflow server`

**Solutions**:

1. **Verify server is running**:
   ```bash
   curl http://localhost:5000/health
   ```

2. **Check configuration**:
   ```php
   // config/mlflow.php or .env
   MLFLOW_TRACKING_URI=http://localhost:5000
   ```

3. **Test connection**:
   ```bash
   php artisan mlflow:test-connection
   ```

4. **Check firewall/network**:
   - Ensure port 5000 is accessible
   - Check if firewall is blocking connections
   - Verify DNS resolution if using hostname

### SSL/TLS Verification Failures

**Problem**: `SSL certificate problem: unable to get local issuer certificate`

**Solutions**:

1. **For development** (not recommended for production):
   ```php
   // config/mlflow.php
   'options' => [
       'verify' => false, // Only for local development
   ],
   ```

2. **For production** (recommended):
   ```php
   'options' => [
       'verify' => '/path/to/cacert.pem',
   ],
   ```

3. **Update CA certificates**:
   ```bash
   # Ubuntu/Debian
   sudo update-ca-certificates

   # macOS
   brew install ca-certificates
   ```

### Timeout Issues

**Problem**: Requests timing out

**Solutions**:

1. **Increase timeout**:
   ```php
   // config/mlflow.php
   'options' => [
       'timeout' => 60, // Increase from default 30
       'connect_timeout' => 10,
   ],
   ```

2. **Check server performance**:
   - MLflow server might be overloaded
   - Database might be slow
   - Network latency

## Authentication Issues

### 401 Unauthorized

**Problem**: Authentication failures

**Solutions**:

1. **Add authentication headers**:
   ```php
   // config/mlflow.php
   'options' => [
       'headers' => [
           'Authorization' => 'Bearer ' . env('MLFLOW_API_TOKEN'),
       ],
   ],
   ```

2. **Check environment variables**:
   ```env
   MLFLOW_API_TOKEN=your-token-here
   ```

3. **Verify token is valid**:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:5000/api/2.0/mlflow/experiments/list
   ```

## API Errors

### 404 Not Found

**Problem**: `NotFoundException: Experiment/Run not found`

**Common causes**:

1. **Experiment doesn't exist**:
   ```php
   // Bad: Assumes experiment exists
   $exp = MLflow::experiments()->getByName('my-exp');

   // Good: Create if doesn't exist
   try {
       $exp = MLflow::experiments()->getByName('my-exp');
   } catch (\MLflow\Exception\NotFoundException $e) {
       $exp = MLflow::experiments()->create('my-exp');
   }
   ```

2. **Using wrong ID**:
   ```php
   // Make sure you're using the correct ID
   $experimentId = $experiment->experimentId; // Not $experiment->id
   $runId = $run->info->runId; // Not $run->runId
   ```

### 409 Conflict

**Problem**: `ConflictException: Resource already exists`

**Solutions**:

1. **Experiment already exists**:
   ```php
   try {
       $exp = MLflow::experiments()->create('my-experiment');
   } catch (\MLflow\Exception\ConflictException $e) {
       // Get existing experiment instead
       $exp = MLflow::experiments()->getByName('my-experiment');
   }
   ```

2. **Model already registered**:
   ```php
   try {
       $model = MLflow::modelRegistry()->createRegisteredModel('my-model');
   } catch (\MLflow\Exception\ConflictException $e) {
       $model = MLflow::modelRegistry()->getRegisteredModel('my-model');
   }
   ```

### 400 Bad Request

**Problem**: Invalid request parameters

**Common causes**:

1. **Invalid parameter types**:
   ```php
   // Bad: Passing wrong types
   MLflow::runs()->logMetric($runId, 'accuracy', '0.95'); // String instead of float

   // Good: Correct types
   MLflow::runs()->logMetric($runId, 'accuracy', 0.95); // Float
   ```

2. **Invalid run status transition**:
   ```php
   // Bad: Invalid status
   MLflow::runs()->setTerminated($runId, \MLflow\Enum\RunStatus::RUNNING);

   // Good: Terminal status
   MLflow::runs()->setTerminated($runId, \MLflow\Enum\RunStatus::FINISHED);
   ```

## Laravel Integration Issues

### Facade Not Found

**Problem**: `Class 'MLflow' not found`

**Solutions**:

1. **Verify service provider is registered**:
   ```php
   // config/app.php
   'providers' => [
       // ...
       MLflow\Laravel\MLflowServiceProvider::class,
   ],
   ```

2. **Clear Laravel cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Run package discovery**:
   ```bash
   composer dump-autoload
   php artisan package:discover
   ```

### Configuration Not Loading

**Problem**: Config values not being used

**Solutions**:

1. **Publish configuration**:
   ```bash
   php artisan vendor:publish --tag=mlflow-config
   ```

2. **Clear config cache**:
   ```bash
   php artisan config:clear
   ```

3. **Verify .env values**:
   ```bash
   php artisan config:show mlflow
   ```

### Fake Not Working in Tests

**Problem**: Tests hitting real MLflow server

**Solutions**:

1. **Ensure fake is called before code execution**:
   ```php
   public function test_example()
   {
       MLflow::fake(); // Must be BEFORE using MLflow

       $this->service->train(); // This should use the fake

       MLflow::assertExperimentCreated('training');
   }
   ```

2. **Don't inject client directly**:
   ```php
   // Bad: Bypasses facade
   public function __construct(MLflowClient $client)
   {
       $this->client = $client;
   }

   // Good: Use facade
   use MLflow\Laravel\Facades\MLflow;

   public function train()
   {
       MLflow::experiments()->create('training');
   }
   ```

3. **Bind fake for dependency injection**:
   ```php
   $fake = \MLflow\Testing\Fakes\MLflowFake::create();
   $this->app->instance(\MLflow\MLflowClient::class, $fake);
   ```

## Performance Issues

### Slow API Calls

**Problem**: MLflow operations are slow

**Solutions**:

1. **Use batch operations**:
   ```php
   // Bad: Multiple API calls
   foreach ($metrics as $metric) {
       MLflow::runs()->logMetric($runId, $metric['key'], $metric['value']);
   }

   // Good: Single API call
   MLflow::runs()->logBatch($runId, metrics: $metrics);
   ```

2. **Optimize network**:
   - Use MLflow server in same datacenter
   - Enable HTTP/2 if possible
   - Check network latency

3. **Queue heavy operations**:
   ```php
   // Dispatch to queue for async processing
   LogMLflowMetrics::dispatch($runId, $metrics);
   ```

### Memory Issues

**Problem**: Out of memory when processing large datasets

**Solutions**:

1. **Log incrementally**:
   ```php
   // Bad: Build large array in memory
   $metrics = [];
   foreach ($data as $item) {
       $metrics[] = $this->process($item);
   }
   MLflow::runs()->logBatch($runId, metrics: $metrics);

   // Good: Log as you go
   foreach ($data as $index => $item) {
       $result = $this->process($item);
       if ($index % 100 === 0) { // Log every 100 items
           MLflow::runs()->logMetric($runId, 'processed', $index);
       }
   }
   ```

2. **Use chunking**:
   ```php
   collect($data)->chunk(1000)->each(function ($chunk) use ($runId) {
       $metrics = $chunk->map(fn($item) => $this->toMetric($item))->toArray();
       MLflow::runs()->logBatch($runId, metrics: $metrics);
   });
   ```

## Data Issues

### Unicode/Encoding Issues

**Problem**: Special characters not displaying correctly

**Solutions**:

1. **Ensure UTF-8 encoding**:
   ```php
   MLflow::runs()->logParameter($runId, 'description', mb_convert_encoding($text, 'UTF-8'));
   ```

2. **Check database encoding**:
   - MLflow backend database should be UTF-8

### Large Parameter Values

**Problem**: `ValidationException: Parameter value too large`

**Solutions**:

1. **Parameters have size limits** (~500 chars):
   ```php
   // Bad: Storing large text as parameter
   MLflow::runs()->logParameter($runId, 'model_code', $largeCode);

   // Good: Save as artifact instead
   $path = storage_path('temp/model_code.txt');
   file_put_contents($path, $largeCode);
   MLflow::artifacts()->logArtifact($runId, $path);
   ```

2. **Use tags for metadata, artifacts for data**:
   - Tags: Short metadata (environment, version, etc.)
   - Params: Hyperparameters (learning_rate, epochs, etc.)
   - Artifacts: Large files (models, datasets, code)

## PHPStan/Static Analysis Issues

### Property Type Errors

**Problem**: PHPStan complains about property types

**Solutions**:

1. **Use proper type hints**:
   ```php
   /** @var array<string, mixed> $response */
   $response = MLflow::experiments()->search();
   ```

2. **Add PHPDoc blocks**:
   ```php
   /**
    * @param array<string, string> $params
    * @return array{success: bool, run_id: string}
    */
   public function train(array $params): array
   {
       // ...
   }
   ```

## Debugging

### Enable Debug Logging

```php
// config/mlflow.php
'logging' => [
    'enabled' => true,
    'channel' => 'daily',
],

// Check logs
tail -f storage/logs/laravel.log | grep MLflow
```

### Inspect HTTP Requests

Use Guzzle middleware to log requests:

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

$stack = HandlerStack::create();

$stack->push(Middleware::log(
    app('log')->channel('daily'),
    new \GuzzleHttp\MessageFormatter('{method} {uri} - {code}')
));

// In your service provider
$this->app->extend(MLflowClient::class, function ($client) use ($stack) {
    $httpClient = new Client(['handler' => $stack]);
    $client->setHttpClient($httpClient);
    return $client;
});
```

### Test MLflow Server Directly

```bash
# List experiments
curl http://localhost:5000/api/2.0/mlflow/experiments/list

# Get experiment
curl http://localhost:5000/api/2.0/mlflow/experiments/get?experiment_id=1

# Search runs
curl -X POST http://localhost:5000/api/2.0/mlflow/runs/search \
  -H 'Content-Type: application/json' \
  -d '{"experiment_ids": ["1"]}'
```

## Getting Help

If you're still experiencing issues:

1. **Check the documentation**: Read the integration patterns and best practices docs
2. **Review examples**: Look at the tests for working examples
3. **Enable debug logging**: See what's being sent to MLflow
4. **Test the server**: Verify MLflow server is working correctly
5. **Check GitHub issues**: Someone might have reported the same issue
6. **Ask for help**: Open an issue with:
   - PHP version
   - Laravel version
   - MLflow server version
   - Full error message and stack trace
   - Minimal code to reproduce the issue
