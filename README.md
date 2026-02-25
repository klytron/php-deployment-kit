# PHP Deployment Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/klytron/php-deployment-kit.svg?style=flat-square)](https://packagist.org/packages/klytron/php-deployment-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/klytron/php-deployment-kit.svg?style=flat-square)](https://packagist.org/packages/klytron/php-deployment-kit)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg?style=flat-square)](https://php.net)
[![Deployer](https://img.shields.io/badge/Deployer-7.x-green.svg?style=flat-square)](https://deployer.org)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](LICENSE)

> A comprehensive deployment library for PHP applications built on [Deployer](https://deployer.org). Supports **Laravel**, **Yii2**, **API projects**, and **simple PHP** applications with zero boilerplate.

---

## Features

- 🚀 **Multi-Framework** — Laravel, Yii2, API, and simple PHP deployment recipes
- ⚡ **Zero-Config Defaults** — Smart detection with easy customisation
- 🔒 **Production Safe** — Multiple confirmation prompts, validation, and rollback
- 🔑 **Laravel Env Encryption** — Automatic `LARAVEL_ENV_ENCRYPTION_KEY` decryption before deploy
- 🗺️ **Asset Mapping** — Maps Vite/Mix assets for database URL compatibility
- 🖼️ **Image Optimisation** — Compress images post-deploy
- 🗂️ **Sitemap Generation** — Automatic sitemap creation and verification
- 🔤 **Font Verification** — Checks webfonts are accessible after deploy
- 🕒 **Deployment Timing** — Built-in timer and metrics
- 🎛️ **Interactive or Unattended** — Prompted workflow or fully automated via `auto_*` flags

---

## Requirements

- PHP **8.1+**
- [Deployer](https://deployer.org) **7.x** (installed automatically as a dependency)
- Git
- SSH access to your deployment server

---

## Installation

Install via [Composer](https://getcomposer.org):

```bash
composer require klytron/php-deployment-kit --dev
```

Verify the installation:

```bash
vendor/bin/dep --version
```

### Optional: Scaffold a deploy.php automatically

```bash
# Auto-detect your project type (Laravel / Yii2 / simple PHP) and create deploy.php
curl -sSL https://raw.githubusercontent.com/klytron/php-deployment-kit/main/install.sh | bash
```

---

## Quick Start

### 1. Create `deploy.php` in your project root

```php
<?php
namespace Deployer;

// Core kit
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

// Laravel recipe (omit for simple PHP or use klytron-php-recipe.php)
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// ── Configure ────────────────────────────────────────────────────────────────

klytron_configure_app('my-app', 'git@github.com:your-username/my-app.git');
klytron_set_paths('/var/www', '/var/www/${APP_URL_DOMAIN}/public_html');
klytron_set_domain('yourdomain.com');
klytron_set_php_version('php8.3');

klytron_configure_project([
    'type'                  => 'laravel',
    'database'              => 'mysql',     // mysql | mariadb | postgresql | sqlite | none
    'supports_vite'         => true,
    'supports_storage_link' => true,
]);

klytron_configure_host('yourdomain.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
]);

klytron_configure_shared_files(['.env']);
klytron_configure_shared_dirs(['storage', 'bootstrap/cache']);
klytron_configure_writable_dirs([
    'bootstrap/cache',
    'storage',
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
]);

// ── Deployment Flow ───────────────────────────────────────────────────────────

task('deploy', [
    'klytron:deploy:start_timer',
    'klytron:laravel:deploy:display:info',
    'klytron:laravel:deploy:configure:interactive',
    'deploy:unlock',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'klytron:laravel:deploy:environment:complete',
    'deploy:env',
    'deploy:vendors',
    'klytron:laravel:node:vite:build',
    'klytron:laravel:deploy:database:complete',
    'deploy:writable',
    'klytron:laravel:deploy:cache:complete',
    'deploy:symlink',
    'klytron:laravel:deploy:finalize:complete',
    'klytron:assets:map',
    'klytron:assets:cleanup',
    'klytron:sitemap:generate',
    'klytron:fonts:verify',
    'klytron:images:optimize',
    'deploy:unlock',
    'deploy:cleanup',
    'klytron:laravel:deploy:notify:complete',
    'klytron:deploy:end_timer',
])->desc('Deploy Laravel application');
```

### 2. Deploy

```bash
vendor/bin/dep deploy
```

---

## Documentation

| Guide | Description |
|---|---|
| [Installation](docs/installation.md) | Prerequisites, install methods, server setup |
| [Quick Start](docs/quick-start.md) | Step-by-step first deployment |
| [Configuration Reference](docs/configuration-reference.md) | All `klytron_*` functions and options |
| [Function Reference](docs/function-reference.md) | API reference |
| [Task Reference](docs/task-reference.md) | All available deployment tasks |
| [Laravel Guide](docs/frameworks/laravel.md) | Laravel-specific configuration |
| [Yii2 Guide](docs/frameworks/yii2.md) | Yii2-specific configuration |
| [Simple PHP Guide](docs/frameworks/simple-php.md) | Non-framework PHP projects |
| [Dynamic Configuration](docs/DYNAMIC_CONFIGURATION.md) | Path templates and placeholders |
| [Troubleshooting](docs/troubleshooting.md) | Common issues and fixes |

---

## Project Structure

```
php-deployment-kit/
├── deployment-kit.php          # Main entry point — include this in deploy.php
├── deployment-kit-core.php     # Core helper functions
├── klytron-tasks.php           # Framework-agnostic deployment tasks
├── recipes/
│   ├── klytron-laravel-recipe.php    # Laravel deployment tasks
│   ├── klytron-yii2-recipe.php       # Yii2 deployment tasks
│   ├── klytron-php-recipe.php        # Simple PHP tasks
│   └── klytron-server-recipe.php     # Server provisioning tasks
├── src/
│   ├── Commands/               # Laravel Artisan commands
│   ├── Exceptions/             # Custom exception classes
│   ├── Services/               # Metrics, retry, validation services
│   ├── Tasks/                  # Task classes (AssetMappingTask, SitemapTask, etc.)
│   ├── Validators/             # Configuration validators
│   └── Providers/              # Laravel service provider
├── templates/                  # Starter deploy.php templates
├── examples/                   # Real-world deployment examples
└── docs/                       # Full documentation
```

---

## Supported Frameworks

| Framework | Recipe |
|---|---|
| Laravel | `recipes/klytron-laravel-recipe.php` |
| Yii2 | `recipes/klytron-yii2-recipe.php` |
| API (Laravel) | `recipes/klytron-laravel-recipe.php` |
| Simple PHP | `recipes/klytron-php-recipe.php` |

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

## Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md) for how to report it responsibly.

## License

MIT — see [LICENSE](LICENSE).

---

**[📦 klytron/php-deployment-kit on Packagist](https://packagist.org/packages/klytron/php-deployment-kit)**  
**[⭐ GitHub](https://github.com/klytron/php-deployment-kit)**
