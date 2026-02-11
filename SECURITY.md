# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| < 2.0   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to:

📧 **info@axyrmedia.nl**

Include the following in your report:

- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact
- Suggested fix (if available)

### What to Expect

1. **Acknowledgment**: We'll acknowledge your email within 48 hours
2. **Assessment**: We'll assess the vulnerability and determine severity
3. **Fix**: We'll work on a fix and keep you updated on progress
4. **Disclosure**: Once fixed, we'll coordinate disclosure timing with you
5. **Credit**: We'll credit you in the security advisory (unless you prefer to remain anonymous)

### Security Best Practices

When using this library:

1. **Keep Dependencies Updated**: Regularly update to the latest version
2. **Secure Credentials**: Never hardcode API keys or tokens
3. **Use Environment Variables**: Store sensitive configuration in environment variables
4. **Enable SSL Verification**: Always use `verify: true` in production
5. **Validate Input**: Use provided validation helpers for user input
6. **Log Securely**: Sensitive headers are automatically masked in logs
7. **Path Traversal Protection**: Use `SecurityHelper::validatePath()` for file operations

### Security Features

This library includes built-in security features:

- ✅ **Input Validation**: `SecurityHelper` and `ValidationHelper` utilities
- ✅ **Sensitive Data Masking**: Automatic masking of Authorization, API-Key, Token headers in logs
- ✅ **Path Traversal Protection**: `SecurityHelper::validatePath()`
- ✅ **Type Safety**: PHPStan Level 9 static analysis
- ✅ **No Dangerous Functions**: No eval(), exec(), system() calls
- ✅ **Immutable Models**: Readonly classes prevent tampering

### Disclosure Policy

- Security vulnerabilities will be disclosed publicly after a fix is available
- We aim to fix critical vulnerabilities within 7 days
- We follow coordinated disclosure practices

### Security Updates

Subscribe to GitHub releases to receive notifications about security updates.

Thank you for helping keep MLflow PHP Client secure! 🔒
