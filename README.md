# Fluxtor CLI

A powerful command-line tool that streamlines component installation, theme management, and project setup to accelerate your Laravel development workflow with beautiful, accessible UI components.

## Quick Start

Install the Fluxtor CLI package in your Laravel project:

```bash
composer require fluxtor/cli
```

Initialize Fluxtor with all required dependencies:

```bash
php artisan fluxtor:init
```

Start installing components:

```bash
php artisan fluxtor:install button
```

## Requirements

- Laravel 10.0 or higher
- PHP 8.1 or higher
- Alpine.js (auto-installed if not present)
- Tailwindcss 4.0 or higher

## Features

### One-Command Setup
The `fluxtor:init` command sets up your entire project with CSS theme system, dark mode support, JavaScript utilities, and proper file organization.

### Comprehensive Theme System
Built-in support for light/dark themes with CSS custom properties and Alpine.js integration for seamless theme switching.

### Smart Component Installation
Install components with automatic dependency resolution. Each component includes all required files and dependencies.

### Component Discovery
Browse and filter available components with `fluxtor:list` to find exactly what you need for your project.

### Premium Access
Authenticate with your Fluxtor account using `fluxtor:login` to access premium components and features.

## Installation Options

### Interactive Setup
```bash
php artisan fluxtor:init
```

### Quick Setup with All Features
```bash
php artisan fluxtor:init --with-dark-mode --with-phosphor
```

### Custom Configuration
```bash
php artisan fluxtor:init --css-file=custom.css --theme-file=my-theme --skip-prompts
```

## Component Management

### Install Components
```bash
# Install a single component
php artisan fluxtor:install button

# Install with options
php artisan fluxtor:install modal --force --no-deps
```

### Browse Components
```bash
# List all components
php artisan fluxtor:list

# Filter by access level
php artisan fluxtor:list --free
php artisan fluxtor:list --premium
```

## File Structure

After initialization, Fluxtor creates this organized structure:

```
resources/
├── css/
│   ├── app.css (updated with theme import)
│   └── theme.css (CSS custom properties)
├── js/
│   └── fluxtor/
│       ├── utils.js (Alpine.js utilities)
│       ├── app.js (updated with imports)
│       └── globals/
│           └── theme.js (Dark mode system)
└── views/
    └── components/
        └── ui/ (installed components)
```

## Documentation

Visit [fluxtor.dev](https://fluxtor.dev) for:
- Complete component documentation
- Usage examples
- Theme customization guides
- Advanced configuration options

## License

Fluxtor CLI is open-source software. Premium components require a valid license.
