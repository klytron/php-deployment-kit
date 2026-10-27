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
- ⚡ **Ultra-Fast Deployments** — Hardlink dependency caching for Composer & npm/Vite reduces deploys from 15+ minutes down to 30–60 seconds!
- 🎨 **Filament v5 Support** — Native publishing and cache-busting for Filament v5 assets
- 🔒 **Production Safe** — Pre-flight validation, unpushed commit guards, non-destructive git caching, and automated rollback
- 🔑 **Laravel Env Encryption** — Automatic `LARAVEL_ENV_ENCRYPTION_KEY` decryption before deploy
- 🌐 **Zero Server Literals** — Configure hosts dynamically from environment variables (`klytron_configure_host_from_env()`)
- 🧪 **CI Smoke Test Mode** — Test and preview deployment plans in CI without SSH access via `dep klytron:plan`
- 🗺️ **Asset Mapping** — Maps Vite/Mix assets for database URL compatibility
- 🖼️ **Image Optimisation** — Compress images post-deploy
- 🗂️ **Sitemap Generation** — Automatic sitemap creation and verification
- 🔤 **Font Verification** — Checks webfonts are accessible after deploy
- 🕒 **Deployment Timing** — Built-in timer and metrics with completion timestamps
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
# Using the built-in CLI tool:
vendor/bin/klytron init my-app --template=laravel

# Or via Artisan in Laravel projects:
php artisan klytron:init my-app

# Or via install script:
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
klytron_set_domain('production.example.com');
klytron_set_php_version('php8.3');

klytron_configure_project([
    'type'                  => 'laravel',
    'database'              => 'mysql',     // mysql | mariadb | postgresql | sqlite | none
    'supports_vite'         => true,
    'supports_filament'     => true,        // Publish Filament v5 assets
    'supports_storage_link' => true,
    'check_git_pushed'      => true,        // Prevent deploying unpushed commits
]);

// Host credentials read from DEPLOY_HOST env variable with fallback
// (or from .env.deploy / .env.production / .env files — see docs)
klytron_configure_host_from_env('DEPLOY_HOST', 'production.example.com', [
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
    'klytron:deploy:smart_unlock',             // Guarded unlock: only clears stale locks
    'klytron:deploy:check_pushed',
    'klytron:validate:basic',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'klytron:laravel:deploy:environment:complete',
    'deploy:vendors',
    'klytron:laravel:node:vite:build',
    'klytron:laravel:deploy:database:complete',
    'deploy:writable',
    'klytron:laravel:deploy:cache:complete',
    'klytron:laravel:filament:assets',
    'klytron:deploy:verify_staging',           // Stop clearly if a concurrent deploy took the staging symlink
    'deploy:symlink',
    'klytron:laravel:deploy:finalize:complete',
    'deploy:unlock',
    'deploy:cleanup',
    'klytron:deploy:health_check',
    'klytron:laravel:deploy:notify:complete',
    'klytron:deploy:end_timer',
])->desc('Deploy Laravel application');
```

## Concurrent Deploy Protection

Every kit deploy flow is protected against overlapping deployments. The whole
pipeline stages code through a single staging symlink (`{{deploy_path}}/release`)
that `deploy:release` creates and `deploy:symlink` atomically moves onto
`current` — so two deploys running at once corrupt each other's staging state
(the slower run fails with `mv: cannot stat …/release`) and can even leave
`current` pointing at the wrong release.

Three guard tasks keep Deployer's remote lock (`{{deploy_path}}/.dep/deploy.lock`)
honest:

| Task | Role |
|---|---|
| `klytron:deploy:smart_unlock` | Runs where flows used to run a raw `deploy:unlock`. It only auto-clears a **provably stale** lock (older than `klytron_stale_lock_minutes`, default `120`); a **live** lock aborts the run with an actionable message before anything is touched. |
| `klytron:deploy:verify_staging` | Runs immediately before `deploy:symlink`. If the staging symlink vanished (a concurrent deploy moved it), the run stops with a clear explanation — never the bare `mv` failure. |
| `klytron:deploy:failed_unlock_guard` | Failure hook that releases the lock **only when this run acquired it**, so an aborted pre-lock run can never erase a concurrent deploy's lock. |

Configuration:

```php
// Minutes a deploy.lock may sit untouched before a new deploy may clear it.
// Default: 120. Set 0 to never auto-clear (operator must run deploy:unlock).
set('klytron_stale_lock_minutes', 120);
```

If a run aborts because another deploy is live:

```bash
# 1. Wait for the other deploy to finish, or verify it is dead, then:
vendor/bin/dep deploy:unlock
# 2. Re-run the deploy.
```

> Set `klytron_stale_lock_minutes` to `0` on CI systems where a killed run
> cannot leave a live deploy behind, and manual unlocking must always be
> explicit.

### 2. Preview or Dry-Run Plan (CI/CD)

```bash
# Smoke test plan without SSH credentials:
vendor/bin/dep klytron:plan

# View Deployer task sequence:
vendor/bin/dep deploy --plan
```

### 3. Deploy

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
