# Git Hooks

This directory contains Git hooks to ensure code quality before committing.

## Pre-commit Hook

The pre-commit hook runs automatically before each commit to validate:

1. **PHPStan** - Static analysis (level 9)
2. **Code Style** - PSR-12 compliance check
3. **Unit Tests** - All unit tests must pass

### Installation

```bash
# Copy the hook to your .git/hooks directory
cp hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### Usage

Once installed, the hook runs automatically on every `git commit`:

```bash
git commit -m "Your commit message"

# Output:
# 🔍 Running pre-commit checks...
#
# 📊 Running PHPStan (Level 9)...
# ✓ PHPStan passed
#
# 🎨 Running Code Style Check (PSR-12)...
# ✓ Code style passed
#
# 🧪 Running Unit Tests...
# ✓ Unit tests passed
#
# ✓ All pre-commit checks passed!
```

### Skipping the Hook

If you need to commit without running checks (not recommended):

```bash
git commit --no-verify -m "Your message"
```

### Troubleshooting

If the hook fails:

1. Fix the reported errors
2. Run individual checks manually:
   - `composer phpstan` - Static analysis
   - `composer cs-check` - Code style
   - `composer test` - Unit tests

3. Auto-fix code style issues:
   ```bash
   composer cs-fix
   ```

## CI Check Script

For a complete CI/CD check before pushing (including integration tests):

```bash
./bin/ci-check
```

This script runs all checks that GitHub Actions will run, including optional integration tests with Docker.

Skip integration tests:
```bash
SKIP_INTEGRATION=1 ./bin/ci-check
```
