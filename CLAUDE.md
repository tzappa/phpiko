# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

phpiko is a PHP web application built using PSR standards and a custom micro-framework called "Clear". It follows a modular architecture with three main sections: App, Admin, and API.

## Architecture

### Namespace Structure
- `App\` - Main application logic
- `Admin\` - Administrative interface
- `API\` - RESTful API endpoints
- `Clear\` - Custom micro-framework providing core services
- `Web\` - Web-specific request handlers and middleware

### Core Components

**Clear Framework Components:**
- `Clear\Http\Router` - Request routing with middleware support
- `Clear\Container\Container` - PSR-11 dependency injection container
- `Clear\Database\PdoExt` - Extended PDO with event dispatching
- `Clear\Template\TwigTemplate` - Twig template engine integration
- `Clear\ACL\Service` - Access control layer
- `Clear\Events\Dispatcher` - PSR-14 event dispatcher
- `Clear\Session\SessionManager` - Session handling
- `Clear\Config\DotConfig` - Configuration management with dot notation

**Request Flow:**
1. `public/index.php` → `src/bootstrap.php`
2. Route detection based on URI prefix:
   - `/adm` → Admin module
   - `/api` → API module
   - Default → Web module
3. Each module has its own bootstrap file setting up container, routes, and middleware

### Database Layer
- Uses extended PDO with event dispatching for query logging/profiling
- Repository pattern for data access (e.g., `UserRepositoryPdo`)
- Event-driven architecture for database operations

### User Management
- Authentication via `LoginService`/`CheckLoginService`/`LogoutService`
- Password management with strength validation using zxcvbn-php
- Password reset functionality with email verification
- User signup with email verification workflow
- ACL-based authorization system

## Development Commands

### Testing
```bash
# Run all tests
composer test
# Or directly with PHPUnit
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/Clear/Config/DotConfigTest.php

# Generate coverage report (outputs to reports/ directory)
vendor/bin/phpunit --coverage-html reports
```

### Dependency Management
```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Validate composer files
composer validate --strict
```

## Standards and Conventions

This project strictly follows PSR standards:
- PSR-1: Basic coding standard
- PSR-4: Autoloading standard
- PSR-3: Logger interface
- PSR-11: Container interface
- PSR-15: Request handlers and middleware
- PSR-17: HTTP factories
- PDS/Skeleton: Package directory structure

### Code Style
- PHP 8.2+ required
- Strict typing enabled (`declare(strict_types=1)`)
- Constructor property promotion used where appropriate
- Grouped use statements by namespace origin
- Add newline at end of files

### PHPStan - PHP Static Analysis Tool
PHPStan is used for static analysis
```bash
# Run PHPStan analysis
composer analyse
```

### PHP-CS-Fixer - PHP Coding Standards Fixer
PHP-CS-Fixer is used to enforce coding standards
```bash
# Run PHP-CS-Fixer to check coding standards
composer lint

# Fix coding standards issues
composer lint:fix
```

## Key Files

- `src/bootstrap.php` - Main application bootstrap and routing logic
- `src/Web/bootstrap.php` - Web module container and route configuration
- `src/Admin/bootstrap.php` - Admin module container and route configuration
- `src/API/bootstrap.php` - API module container and route configuration
- `composer.json` - Dependencies and autoloading configuration
- `phpunit.xml` - PHPUnit configuration with coverage reporting
- `config/` - Application configuration files
