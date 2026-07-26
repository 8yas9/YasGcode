# Contributing to Magic Generator

Thank you for your interest in contributing! This guide will help you get started.

## Development Setup

```bash
# Clone the repository
git clone https://github.com/yasksalim/magic-generator.git
cd magic-generator

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Run static analysis
./vendor/bin/phpstan analyse

# Fix code style
./vendor/bin/php-cs-fixer fix
```

## Project Structure

```
src/
  Http/Livewire/GeneratorDashboard.php   # Livewire component
  Services/GeneratorEngine.php            # Core generation engine
  MagicGeneratorServiceProvider.php        # Laravel service provider
config/
  magic-generator.php                     # Default configuration
stubs/
  *.stub                                  # Code templates
resources/
  views/                                  # Blade views
  lang/ar/                                # Arabic translations
tests/                                    # PHPUnit tests
```

## Writing Tests

Tests are located in `tests/`. Run them with:

```bash
./vendor/bin/phpunit
```

Each test should focus on a single behavior. Use descriptive test names.

## Code Style

This project uses PHP-CS-Fixer for code style enforcement. Run before committing:

```bash
./vendor/bin/php-cs-fixer fix
```

## Submitting a Pull Request

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request with a clear description of the changes

## Reporting Bugs

Please include:
- Laravel version
- PHP version
- Steps to reproduce
- Expected behavior
- Actual behavior (with error messages if any)

## Suggesting Features

Open an issue with the `enhancement` label describing the feature you'd like to see.

## Code of Conduct

Be respectful and constructive in all interactions.
