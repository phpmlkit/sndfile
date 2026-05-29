# Contributing to SoundFile

Thanks for your interest in contributing! This document outlines the process for making changes.

## Getting Started

```bash
# Clone the repository
git clone https://github.com/phpmlkit/soundfile.git
cd sndfile

# Install PHP dependencies
composer install

# Install docs dependencies
cd docs && npm install && cd ..

# Run tests to verify everything works
composer test
```

## Development Workflow

1. **Create a branch** from `main` for your changes
2. **Make your changes** — follow the existing code style and conventions
3. **Run tests** — `composer test`
4. **Run static analysis** — `composer lint` (PHPStan level 8)
5. **Format code** — `composer cs:fix`
6. **Submit a pull request** with a clear description of what you changed and why

## Code Standards

- PHP 8.2+ with strict types in every file
- Backed enums for constants that map to C library values
- Readonly classes and constructor property promotion where appropriate
- PHPDoc on all public methods describing parameters, return values, and thrown exceptions
- No dead code or commented-out blocks

## Testing

- Tests use PHPUnit 10+
- Test fixtures are generated programmatically — no binary files checked in
- Write tests that exercise the library's logic, not libsndfile's or libsamplerate's internals
- Cover edge cases: empty files, partial reads, format validation failures, closed-handle operations

## Documentation

Documentation lives in `docs/` and is built with [Vitepress](https://vitepress.dev/).

```bash
cd docs
npm run docs:dev     # Start dev server
npm run docs:build   # Build for production
```

When adding or changing public API, update both the relevant guide page in `docs/guide/` and the API reference in `docs/api/`.

## Questions?

Open an issue on GitHub. Please include:
- What you're trying to do
- Code that reproduces the issue
- Your PHP version and operating system
