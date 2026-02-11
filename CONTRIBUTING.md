# Contributing to MLflow PHP Client

Thank you for your interest in contributing! 🎉

## Code of Conduct

Please be respectful and constructive in all interactions. We're here to build great software together.

## Getting Started

### Prerequisites

- PHP 8.4+
- Composer
- Docker (for integration tests)
- Git

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/axyr/mlflow-php-client.git
cd mlflow-php-client

# Install dependencies
composer install

# Install pre-commit hook (recommended)
cp hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### Running Tests

```bash
# Unit tests
composer test

# Static analysis (PHPStan Level 9)
composer phpstan

# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Integration tests (requires Docker)
docker-compose -f docker-compose.test.yml up --abort-on-container-exit

# Run all checks locally (before pushing)
./bin/ci-check
```

## How to Contribute

### Reporting Bugs

1. Check if the bug is already reported in [GitHub Issues](https://github.com/axyr/mlflow-php-client/issues)
2. If not, create a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP version, MLflow version
   - Code sample (if applicable)

### Suggesting Enhancements

1. Check if the enhancement is already suggested
2. Create a new issue with:
   - Clear use case
   - Proposed API design
   - Example usage
   - Rationale and benefits

### Pull Requests

1. **Fork the repository** and create a feature branch
   ```bash
   git checkout -b feature/my-feature
   ```

2. **Make your changes** following our coding standards:
   - PSR-12 coding style
   - PHPStan Level 9 compliance
   - Full type hints
   - PHPDoc blocks on public APIs
   - Named parameters for clarity

3. **Write tests** for your changes:
   - Unit tests for new functionality
   - Integration tests for API changes
   - Maintain or improve coverage

4. **Update documentation**:
   - Update README.md if adding features
   - Update CHANGELOG.md (Unreleased section)
   - Add PHPDoc comments
   - Update examples if needed

5. **Run quality checks**:
   ```bash
   ./bin/ci-check
   ```

6. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Add feature: description"
   ```

   Follow conventional commit format:
   - `feat:` New feature
   - `fix:` Bug fix
   - `docs:` Documentation changes
   - `style:` Code style changes (no logic)
   - `refactor:` Code refactoring
   - `test:` Test additions/changes
   - `chore:` Build/tooling changes

7. **Push and create PR**:
   ```bash
   git push origin feature/my-feature
   ```

   Then create a Pull Request on GitHub with:
   - Clear title and description
   - Reference related issues
   - Screenshots (if UI-related)
   - Test results

### Code Review Process

1. **Automated checks** must pass (CI/CD)
2. **Maintainer review** - we'll review and provide feedback
3. **Revisions** - address feedback and update PR
4. **Approval** - once approved, we'll merge

## Development Guidelines

### Coding Standards

- **PSR-12**: Follow PSR-12 coding style
- **Type Safety**: Use strict types, full type hints
- **Immutability**: Prefer readonly classes
- **No Mixed Types**: Avoid `mixed`, use specific types
- **Named Parameters**: Use for clarity and self-documentation
- **Enums**: Use for fixed sets of values
- **Fluent APIs**: Use builders for complex objects

### Testing Standards

- **Coverage**: Maintain high test coverage
- **Unit Tests**: Test individual classes/methods
- **Integration Tests**: Test full API workflows
- **Mocking**: Use Guzzle MockHandler for HTTP
- **Assertions**: Use specific assertions (assertEquals, assertInstanceOf)

### Documentation Standards

- **PHPDoc**: All public APIs must have PHPDoc
- **Type Hints**: Use PHPDoc for complex types/generics
- **Examples**: Provide code examples for new features
- **README**: Update for user-facing changes

### Architecture Principles

- **Single Responsibility**: One class, one responsibility
- **Dependency Injection**: Inject dependencies via constructor
- **Interface Segregation**: Small, focused interfaces
- **Type Safety**: Leverage PHP 8.4 type system
- **Immutability**: Readonly classes where appropriate
- **Separation of Concerns**: Clear module boundaries

## Project Structure

```
src/
├── Api/              # API endpoint implementations
├── Builder/          # Fluent builders
├── Cache/            # Caching implementations
├── Collection/       # Typed collections
├── Config/           # Configuration value objects
├── Constants/        # Constants (HTTP, endpoints, fields)
├── Enum/             # Enums (status, stages)
├── Exception/        # Exception hierarchy
├── Interface/        # Interfaces
├── Model/            # Domain models (readonly)
├── Trait/            # Reusable traits
└── Util/             # Utility classes (security, validation)

tests/
├── Api/              # API unit tests
└── Integration/      # Integration tests
```

## Need Help?

- 💬 **Questions**: Open a GitHub Discussion
- 🐛 **Bugs**: Open a GitHub Issue
- 💡 **Ideas**: Open a GitHub Issue with enhancement label
- 📧 **Email**: info@axyrmedia.nl

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors page
- Release notes

Thank you for contributing! 🙏
