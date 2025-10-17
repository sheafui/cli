# Sheaf CLI

A powerful command-line tool that streamlines component installation, theme management, and project setup to accelerate your Laravel development workflow with beautiful, accessible UI components.

## Quick Start

Install the Sheaf CLI package in your Laravel project:

```bash
composer require sheaf/cli
```

Initialize Sheaf with all required dependencies:

```bash
php artisan sheaf:init
```

Start installing components:

```bash
php artisan sheaf:install button
```

## Requirements

- Laravel 10.0 or higher
- PHP 8.1 or higher
- Alpine.js (auto-installed if not present)
- Tailwindcss 4.0 or higher

## Features

### One-Command Setup
The `sheaf:init` command sets up your entire project with CSS theme system, dark mode support, JavaScript utilities, and proper file organization.

### Comprehensive Theme System
Built-in support for light/dark themes with CSS custom properties and Alpine.js integration for seamless theme switching.

### Smart Component Installation
Install components with automatic dependency resolution. Each component includes all required files and dependencies.

### Component Discovery
Browse and filter available components with `sheaf:list` to find exactly what you need for your project.

## Installation Options

### Interactive Setup
```bash
php artisan sheaf:init
```

### Quick Setup with All Features
```bash
php artisan sheaf:init --with-dark-mode --with-phosphor
```

### Custom Configuration
```bash
php artisan sheaf:init --css-file=custom.css --theme-file=my-theme --skip-prompts
```

## Component Management

### Install Components
```bash
# Install a single component
php artisan sheaf:install button

# Install with options
php artisan sheaf:install modal --force --no-deps
```

### Browse Components
```bash
# List all components
php artisan sheaf:list

# Filter by access level
php artisan sheaf:list
```

## File Structure

After initialization, Sheaf creates this organized structure:

```
resources/
├── css/
│   ├── app.css (updated with theme import)
│   └── theme.css (CSS custom properties)
├── js/
│   ├── utils.js (Alpine.js utilities)
│   ├── app.js (updated with imports)
│   └── globals/
│       └── theme.js (Dark mode system)
└── views/
    └── components/
        └── ui/ (installed components)
```

## Documentation

Visit [sheafui.dev](https://sheafui.dev) for:
- Complete component documentation
- Usage examples
- Theme customization guides
- Advanced configuration options

## License

Sheaf CLI is open-source software.
