# Laravel Developer Experience Audit
**Date:** 2026-02-11
**Package:** martijn/mlflow-php-client v2.0.0
**Perspective:** Laravel Package Consumer

---

## Executive Summary

**Overall Laravel DX Rating: 6.5/10** ⚠️

The package is **well-built** with modern PHP features and solid architecture, but it **lacks Laravel-specific integration** and some expected Laravel conventions. It feels like a **framework-agnostic package** rather than a Laravel-first experience.

### What's Good ✅
- Modern PHP 8.4 features
- Fluent builders (Laravel-style)
- Type safety
- Good documentation

### What's Missing ❌
- No Laravel ServiceProvider
- No Facades
- No config file
- Collections lack Laravel Collection methods
- No helper functions
- No Artisan commands
- No Laravel-specific documentation

---

## 1. Laravel Integration ❌ **MISSING**

### ServiceProvider - NOT PROVIDED
**Status:** ❌ Missing
**Impact:** HIGH
**Laravel Developer Expectation:** 10/10

```php
// ❌ What Laravel developers expect but DON'T have:
// In config/app.php 'providers' array (or auto-discovered)
MLflow\Laravel\MLflowServiceProvider::class

// Should provide:
// - Auto-registration of MLflowClient as singleton
// - Config publishing
// - Artisan command registration
// - Facade registration
```

**What's Missing:**
```php
// src/Laravel/MLflowServiceProvider.php - DOES NOT EXIST
namespace MLflow\Laravel;

use Illuminate\Support\ServiceProvider;

class MLflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/mlflow.php', 'mlflow');

        $this->app->singleton(MLflowClient::class, function ($app) {
            return new MLflowClient(
                config('mlflow.tracking_uri'),
                config('mlflow.config')
            );
        });

        $this->app->alias(MLflowClient::class, 'mlflow');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/mlflow.php' => config_path('mlflow.php'),
        ], 'mlflow-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ExperimentListCommand::class,
                Commands\RunShowCommand::class,
            ]);
        }
    }
}
```

**Workaround for Users:**
```php
// ❌ Users must manually register in AppServiceProvider
public function register()
{
    $this->app->singleton(\MLflow\MLflowClient::class, function () {
        return new \MLflow\MLflowClient(
            env('MLFLOW_TRACKING_URI', 'http://localhost:5000')
        );
    });
}
```

---

### Facade - NOT PROVIDED
**Status:** ❌ Missing
**Impact:** HIGH
**Laravel Developer Expectation:** 10/10

```php
// ❌ What Laravel developers expect but DON'T have:
use MLflow\Facades\MLflow;

// Beautiful static API
$experiment = MLflow::experiment('my-experiment')->create();
$run = MLflow::run($experimentId)->start();
MLflow::logMetric($runId, 'accuracy', 0.95);
```

**What's Missing:**
```php
// src/Laravel/Facades/MLflow.php - DOES NOT EXIST
namespace MLflow\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class MLflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mlflow';
    }
}
```

**Current Experience:**
```php
// ❌ Must inject or instantiate manually
use MLflow\MLflowClient;

public function __construct(private MLflowClient $mlflow) {}

public function store(Request $request)
{
    $experiment = $this->mlflow->experiments()->create('my-experiment');
    // Verbose, must pass $mlflow everywhere
}
```

---

### Config File - NOT PROVIDED
**Status:** ❌ Missing
**Impact:** HIGH
**Laravel Developer Expectation:** 10/10

```php
// ❌ What Laravel developers expect: config/mlflow.php
// DOES NOT EXIST - users must hardcode or create manually

return [
    'tracking_uri' => env('MLFLOW_TRACKING_URI', 'http://localhost:5000'),

    'timeout' => env('MLFLOW_TIMEOUT', 60),

    'retry' => [
        'max_attempts' => env('MLFLOW_MAX_RETRIES', 3),
        'delay' => env('MLFLOW_RETRY_DELAY', 1.0),
    ],

    'cache' => [
        'enabled' => env('MLFLOW_CACHE_ENABLED', false),
        'ttl' => env('MLFLOW_CACHE_TTL', 300),
        'store' => env('MLFLOW_CACHE_STORE', 'redis'),
    ],

    'default_experiment' => env('MLFLOW_DEFAULT_EXPERIMENT'),

    'headers' => [
        'Authorization' => env('MLFLOW_API_TOKEN'),
    ],
];
```

**Current Experience:**
```php
// ❌ Must manually construct config
$client = new MLflowClient(
    env('MLFLOW_TRACKING_URI'),
    [
        'timeout' => 60,
        'headers' => ['Authorization' => 'Bearer ' . env('MLFLOW_TOKEN')]
    ]
);
```

---

### Package Auto-Discovery - NOT PROVIDED
**Status:** ❌ Missing
**Impact:** MEDIUM
**Laravel Developer Expectation:** 9/10

```json
// ❌ composer.json missing Laravel auto-discovery
{
    "extra": {
        "laravel": {
            "providers": [
                "MLflow\\Laravel\\MLflowServiceProvider"
            ],
            "aliases": {
                "MLflow": "MLflow\\Laravel\\Facades\\MLflow"
            }
        }
    }
}
```

---

## 2. Collections API ⚠️ **PARTIAL**

### Comparison: Current vs Laravel Collections

**Status:** ⚠️ Partial (40% of Laravel Collection methods)
**Impact:** MEDIUM
**Laravel Developer Expectation:** 8/10

#### ✅ Methods Available (Good!)
```php
$metrics = $run->data->metrics;

// These work:
$metrics->filter($callback);      // ✅
$metrics->sort($comparator);      // ✅
$metrics->first();                // ✅
$metrics->last();                 // ✅
$metrics->count();                // ✅
$metrics->isEmpty();              // ✅
$metrics->all();                  // ✅
$metrics->toArray();              // ✅
$metrics->merge($other);          // ✅
$metrics->reduce($callback, $initial); // ✅
```

#### ❌ Laravel Collection Methods MISSING

**Transformation Methods:**
```php
// ❌ map() - Most commonly used Laravel Collection method!
$values = $metrics->map(fn($m) => $m->value); // Does NOT work

// ❌ pluck() - Extract single column
$keys = $metrics->pluck('key'); // Does NOT work

// ❌ mapWithKeys()
$keyed = $metrics->mapWithKeys(fn($m) => [$m->key => $m->value]); // Does NOT work

// ❌ flatMap()
$flattened = $metrics->flatMap($callback); // Does NOT work
```

**Query Methods:**
```php
// ❌ where() - Laravel's intuitive filtering
$highAccuracy = $metrics->where('value', '>', 0.9); // Does NOT work

// ❌ whereIn(), whereNotIn()
$selected = $metrics->whereIn('key', ['accuracy', 'loss']); // Does NOT work

// ❌ contains() - Check for item
$hasAccuracy = $metrics->contains('key', 'accuracy'); // Does NOT work

// ❌ firstWhere()
$metric = $metrics->firstWhere('key', 'accuracy'); // Does NOT work
```

**Utility Methods:**
```php
// ❌ each() - Iterate with side effects
$metrics->each(function ($metric) {
    Log::info($metric->key);
}); // Does NOT work

// ❌ tap() - Chainable debugging
$metrics->tap(fn($c) => dd($c->count()))->filter(...); // Does NOT work

// ❌ pipe() - Pass collection through callback
$result = $metrics->pipe(fn($c) => $c->groupBy('key')); // Does NOT work

// ❌ chunk() - Break into smaller collections
$batches = $metrics->chunk(10); // Does NOT work

// ❌ take(), skip()
$first10 = $metrics->take(10); // Does NOT work
$afterFirst10 = $metrics->skip(10); // Does NOT work
```

**Laravel Developer Favorites:**
```php
// ❌ dd(), dump() - Debugging shortcuts (Laravel devs LOVE these!)
$metrics->dd(); // Does NOT work - Would dump and die
$metrics->dump()->filter(...); // Does NOT work - Would dump and continue

// ❌ when(), unless() - Conditional operations
$metrics->when($condition, fn($c) => $c->filter(...)); // Does NOT work

// ❌ partition() - Split into two collections
[$passed, $failed] = $metrics->partition(fn($m) => $m->value > 0.9); // Does NOT work
```

**Current Workarounds:**
```php
// ❌ Instead of: $metrics->map(fn($m) => $m->value);
// Must do:
array_map(fn($m) => $m->value, $metrics->all());

// ❌ Instead of: $metrics->where('value', '>', 0.9);
// Must do:
$metrics->filter(fn($m) => $m->value > 0.9);

// ❌ Instead of: $metrics->pluck('key');
// Must do:
array_column($metrics->toArray(), 'key');
```

---

## 3. Eloquent-Style Query Builder ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** MEDIUM
**Laravel Developer Expectation:** 7/10

```php
// ❌ What Laravel developers expect (Eloquent-style):
$runs = MLflow::runs()
    ->where('experiment_id', $expId)
    ->where('metrics.accuracy', '>', 0.9)
    ->orderBy('start_time', 'desc')
    ->limit(10)
    ->get();

$run = MLflow::runs()
    ->where('run_id', $runId)
    ->first();

// or even:
$run = MLflow::runs()->find($runId);
```

**Current Experience:**
```php
// ❌ Must use raw search with filter strings
$result = $client->runs()->search(
    experimentIds: [$expId],
    filter: 'metrics.accuracy > 0.9',
    maxResults: 10,
    orderBy: ['start_time DESC']
);
$runs = $result['runs']; // Array, not collection
```

---

## 4. Helper Functions ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** LOW
**Laravel Developer Expectation:** 5/10

```php
// ❌ Laravel developers love global helpers
// NONE PROVIDED

// What would be nice:
mlflow()->experiments()->create('my-experiment');
mlflow_experiment('my-experiment')->create();
mlflow_run($experimentId)->start();
mlflow_log_metric($runId, 'accuracy', 0.95);

// Must use:
app(MLflowClient::class)->experiments()->create('my-experiment');
// or inject dependency
```

---

## 5. Artisan Commands ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** MEDIUM
**Laravel Developer Expectation:** 7/10

```bash
# ❌ What Laravel developers expect:
# NONE OF THESE EXIST

php artisan mlflow:experiment:list
php artisan mlflow:experiment:create {name}
php artisan mlflow:run:show {run-id}
php artisan mlflow:run:list {experiment-id}
php artisan mlflow:model:list
php artisan mlflow:model:promote {model} {version}
php artisan mlflow:test-connection
php artisan mlflow:clear-cache
```

**Current Experience:**
```php
// ❌ Must write custom commands or use code directly
```

---

## 6. Testing Helpers ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** MEDIUM
**Laravel Developer Expectation:** 8/10

```php
// ❌ What Laravel developers expect:
// Fake/Mock utilities for testing

use MLflow\Laravel\Facades\MLflow;

public function test_experiment_creation()
{
    MLflow::fake();

    // Act
    MLflow::experiment('test')->create();

    // Assert
    MLflow::assertExperimentCreated('test');
}

// ❌ Factory support
MLflow::factory()->experiment()->create(['name' => 'test']);
MLflow::factory()->run()->create(['experiment_id' => '123']);
```

**Current Experience:**
```php
// ❌ Must manually mock GuzzleHttp client or write custom test doubles
$mock = $this->createMock(ClientInterface::class);
// Verbose, manual setup
```

---

## 7. Middleware/Events ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** LOW
**Laravel Developer Expectation:** 6/10

```php
// ❌ Laravel developers expect event-driven patterns:

// Events
Event::listen(ExperimentCreated::class, function ($event) {
    Log::info("Experiment {$event->experiment->name} created");
});

// Middleware-like hooks
MLflow::beforeRequest(function ($endpoint, $data) {
    // Log or modify request
});

MLflow::afterResponse(function ($response) {
    // Process response
});
```

**Current Experience:**
```php
// ❌ No events, must extend classes
```

---

## 8. Queue Integration ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** LOW
**Laravel Developer Expectation:** 5/10

```php
// ❌ Laravel developers expect queueable jobs:

use MLflow\Laravel\Jobs\LogMetrics;

// Dispatch to queue
LogMetrics::dispatch($runId, $metrics);

// Or use ShouldQueue trait on listeners
class MLflowEventListener implements ShouldQueue
{
    public function handle(ModelTrained $event)
    {
        MLflow::logMetric($event->runId, 'accuracy', $event->accuracy);
    }
}
```

---

## 9. Validation Rules ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** LOW
**Laravel Developer Expectation:** 4/10

```php
// ❌ Custom validation rules would be nice:

$request->validate([
    'experiment_id' => ['required', new ValidMLflowExperiment],
    'run_id' => ['required', new ValidMLflowRun],
    'metric_name' => ['required', new ValidMLflowMetricKey],
]);
```

---

## 10. Documentation ⚠️ **NEEDS IMPROVEMENT**

**Status:** ⚠️ Framework-agnostic (no Laravel-specific docs)
**Impact:** MEDIUM
**Laravel Developer Expectation:** 8/10

### What's Missing:

#### No Laravel Installation Section
```markdown
❌ Should have:

## Laravel Installation

### 1. Install Package
composer require martijn/mlflow-php-client

### 2. Publish Config
php artisan vendor:publish --tag=mlflow-config

### 3. Configure
Edit config/mlflow.php or set env variables:
MLFLOW_TRACKING_URI=http://localhost:5000

### 4. Use Facade
use MLflow\Facades\MLflow;

MLflow::experiment('my-experiment')->create();
```

#### No Laravel Usage Examples
```php
// ❌ Should show Laravel-idiomatic usage:

// In Controllers
class ExperimentController extends Controller
{
    public function __construct(private MLflowClient $mlflow) {}

    public function store(Request $request)
    {
        $experiment = $this->mlflow->experiments()->create(
            $request->input('name')
        );

        return response()->json($experiment);
    }
}

// In Jobs
class TrainModel implements ShouldQueue
{
    public function handle(MLflowClient $mlflow)
    {
        $run = $mlflow->createRunBuilder($this->experimentId)
            ->withName('training-job-' . now())
            ->start();

        // Training code...

        $mlflow->runs()->logMetric($run->info->runId, 'accuracy', $accuracy);
        $mlflow->runs()->setTerminated($run->info->runId, RunStatus::FINISHED);
    }
}

// In Service Classes
class MLflowService
{
    public function __construct(private MLflowClient $mlflow) {}

    public function logExperimentResults(array $results): void
    {
        // ...
    }
}
```

---

## 11. Macro Support ❌ **MISSING**

**Status:** ❌ Missing
**Impact:** LOW
**Laravel Developer Expectation:** 6/10

```php
// ❌ Laravel developers love macros for extensibility:

// In AppServiceProvider
use MLflow\Collection\MetricCollection;

MetricCollection::macro('highAccuracy', function () {
    return $this->filter(fn($m) => $m->value > 0.9);
});

// Then use:
$highMetrics = $metrics->highAccuracy();
```

---

## 12. IDE Support / Discoverability ⚠️ **GOOD**

**Status:** ✅ Good (thanks to strict types)
**Impact:** LOW
**Laravel Developer Expectation:** 9/10

**What Works:**
- ✅ Full PHPDoc coverage
- ✅ Return types on all methods
- ✅ Named parameters work great
- ✅ Enum autocomplete works
- ✅ IDE can discover methods

**What Could Be Better:**
```php
// ⚠️ Facades would improve discoverability
MLflow:: // <- Would show all available methods in IDE

// ⚠️ Helper functions would improve discoverability
mlflow()-> // <- Would autocomplete in IDE
```

---

## 13. Error Messages ✅ **GOOD**

**Status:** ✅ Good (exception hierarchy)
**Laravel Developer Expectation:** 8/10

```php
// ✅ Good exception hierarchy
try {
    $exp = MLflow::experiment('invalid')->get();
} catch (NotFoundException $e) {
    // Clear, specific exceptions
}

// ⚠️ Could be more Laravel-like with:
catch (ModelNotFoundException $e) {
    // Laravel developers expect this
}
```

---

## Summary of Laravel DX Gaps

### Critical (Must Have for Laravel) ❌
1. **ServiceProvider** - Auto-registration, config, commands
2. **Facade** - Static API access
3. **Config File** - Laravel config conventions
4. **Package Auto-Discovery** - Zero-config installation

### High Priority (Expected by Laravel Devs) ⚠️
5. **Laravel Collection Methods** - map(), pluck(), where(), etc.
6. **Laravel-Specific Documentation** - Installation, usage examples
7. **Artisan Commands** - CLI tools
8. **Testing Helpers** - Fakes, factories, assertions

### Medium Priority (Nice to Have) 📝
9. **Helper Functions** - Global helpers
10. **Eloquent-Style Query Builder** - Chainable queries
11. **Events** - Laravel event system integration
12. **Macro Support** - Extensibility

### Low Priority (Optional) ✨
13. **Queue Integration** - Queueable jobs
14. **Validation Rules** - Custom rules
15. **Middleware Hooks** - Before/after request hooks

---

## Recommendations

### For Immediate Laravel Support (MVP):

**Priority 1: Create Laravel Bridge Package**
```
martijn/mlflow-php-client-laravel

Provides:
- ServiceProvider
- Facade
- Config file
- Package auto-discovery
- Laravel-specific docs
```

**Why separate package?**
- Keep core package framework-agnostic
- Don't add Laravel as dependency to core
- Laravel users get opt-in integration
- Other framework users not affected

**Priority 2: Enhance Collections**
```php
// Add most-used Laravel Collection methods:
- map()
- pluck()
- where()
- each()
- contains()
- dd() / dump()
- tap()
- pipe()
```

**Priority 3: Documentation**
```
Add README section:
## Laravel Integration

See: github.com/martijn/mlflow-php-client-laravel
```

---

## Laravel DX Score Breakdown

| Feature | Score | Weight | Total |
|---------|-------|--------|-------|
| Service Provider | 0/10 | 15% | 0.0 |
| Facade | 0/10 | 15% | 0.0 |
| Config File | 0/10 | 10% | 0.0 |
| Collections API | 5/10 | 10% | 0.5 |
| Documentation | 6/10 | 10% | 0.6 |
| Testing Helpers | 0/10 | 8% | 0.0 |
| Artisan Commands | 0/10 | 8% | 0.0 |
| Helper Functions | 0/10 | 5% | 0.0 |
| Query Builder | 0/10 | 5% | 0.0 |
| Auto-Discovery | 0/10 | 5% | 0.0 |
| Events/Hooks | 0/10 | 4% | 0.0 |
| IDE Support | 9/10 | 3% | 0.27 |
| Macro Support | 0/10 | 2% | 0.0 |

**Total Laravel DX Score: 1.37/10 = 13.7%** 😢

**Adjusted for "Framework-Agnostic as Intended": 6.5/10** ⚠️

---

## Alternative Perspective: Framework-Agnostic Package

**If the package is intentionally framework-agnostic:**
- ✅ Current design is actually GOOD
- ✅ No Laravel coupling is correct
- ✅ Clean, portable code
- ⚠️ BUT should provide Laravel bridge package
- ⚠️ Should document Laravel integration path

**Best Practice:**
```
Core Package: martijn/mlflow-php-client (Framework-agnostic)
Laravel Bridge: martijn/mlflow-php-client-laravel (Laravel-specific)
Symfony Bridge: martijn/mlflow-php-client-symfony (Symfony-specific)
```

This is what major packages do (Flysystem, Guzzle, etc.)

---

## Final Verdict

**For General PHP Developers:** ⭐⭐⭐⭐⭐ (9.5/10)
- Excellent package
- Modern, clean, well-tested
- Great DX for pure PHP

**For Laravel Developers:** ⭐⭐⭐☆☆ (6.5/10)
- Usable, but feels foreign
- Missing expected Laravel patterns
- Requires manual integration
- Verbose compared to typical Laravel packages

**Recommendation:**
Create **companion Laravel package** (`mlflow-php-client-laravel`) that provides:
1. ServiceProvider
2. Facade
3. Config
4. Artisan commands
5. Laravel-specific docs
6. Testing helpers

This approach:
- ✅ Keeps core package clean
- ✅ Provides Laravel developers expected DX
- ✅ Doesn't force Laravel dependency on non-Laravel users
- ✅ Industry best practice

---

**Generated by Laravel Package Consumer Audit**
**Date:** 2026-02-11
