# Changelog

All notable changes to the PHP Deployment Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-09-30

Major release introducing ultra-fast deployment optimizations (reducing release durations from 15+ minutes down to 30–60 seconds), native Filament v5 support, dynamic environment-driven host configuration, and CI dry-run validation.

### Added

**Performance & Acceleration**
- `klytron:cache:vendor` task: reuses `vendor/` from the previous release via hardlinks (`cp -al`), reducing Composer install time from minutes to ~2 seconds.
- `node_modules` hardlink caching in `klytron:laravel:node:vite:build`: compares `package-lock.json` and skips remote `npm install` when node dependencies have not changed.
- Batched directory permissions in `klytron:deploy:access_permissions`: replaces sequential `find ... -exec sudo chmod g+s {} \;` with batched `find ... -exec sudo chmod g+s,0755 {} +` and scopes permission sweeps strictly to the active release and shared storage.
- Non-destructive git repo handling: `klytron:deploy:fix_repo` preserves `.dep/repo` cache; added `klytron:deploy:clean_repo` for explicit repo cache purge.

**Filament v5 Support**
- `klytron:laravel:filament:assets` task: natively publishes Filament v5 panel assets into `public/`.
- Automatic detection for `filament/filament` and `filament/support` with `supports_filament` configuration flag.

**Dynamic Configuration & Zero-Secret Architecture**
- `klytron_configure_host_from_env('DEPLOY_HOST', $fallback, $config)`: dynamically reads host, user, branch, web user, and port from environment variables.
- `klytron_resolve_placeholders()` and `klytron_get_resolved_path()`: lazy path interpolation supporting `${APP_URL_DOMAIN}`, `${APP_NAME}`, `${STAGE}`, and `${PHP_VERSION}`.

**Validation, Safety & CI**
- `klytron:plan` task: CI smoke test command that validates configurations, resolves placeholders, and displays the plan without requiring SSH connections.
- `klytron:deploy:check_pushed` task: aborts deployment if local commits have not been pushed to the remote git branch.
- `klytron:validate:basic` composite task: validates local configuration, files, and path placeholders before connecting to remote servers.
- `klytron:deploy:health_check` task: verifies HTTP 200 response from the live site post-deployment.
- `klytron:fpm:reload` task: lightweight PHP-FPM service reload.
- SQLite database replacement support in `klytron:laravel:deploy:db:import`.

### Fixed

- Neutralized Deployer provision's interactive `ask()` prompt on `php_version`, preventing runtime fatal crashes during non-task evaluation and CI `--plan`.
- Replaced load-time `.env` crash guards with runtime validation in `klytron:validate:env_files`.
- Corrected batch failure exit code in `KlytronFileDeCrypterCommand` to return `Command::FAILURE` when any file fails decryption.
- Deduplicated `klytron:upload:env:production` to ensure environment files upload exactly once per deploy.
- Idempotent PHP-FPM restart logic in `klytron:system:restart`.

## [1.0.3] - 2026-04-02

### Fixed
- Removed hardcoded `"version"` field from `composer.json` to adhere to Packagist tag-based versioning best practices.

## [1.0.2] - 2026-03-31

### Added

- `klytron:deploy:end_timer` now displays completion datetime in addition to duration
- Documentation for timer tasks (`klytron:deploy:start_timer` and `klytron:deploy:end_timer`) in task-reference.md

### Changed

- Timer output now shows: `⏱️ Deployment completed at 2026-03-31 13:33:05` followed by duration

## [1.0.0] - 2026-02-25

Initial public release on Packagist.

### Added

**Multi-Framework Support**
- Laravel — full deployment flow with migrations, cache, Vite/Mix, Passport, storage
- Yii2 — Advanced Application Template with multi-app structure and maintenance mode
- Simple PHP — minimal deployment flow for non-framework projects
- Laravel API — API-specific tasks, Passport setup, rate limiting, endpoint validation

**Core Library Functions**
- `klytron_configure_app()` — application name, repo URL, and global options
- `klytron_set_paths()` — parent directory and dynamic `${APP_URL_DOMAIN}` public HTML path
- `klytron_set_domain()` — domain resolution for path templates
- `klytron_set_php_version()` — per-host PHP binary
- `klytron_configure_project()` — framework type, database, feature flags
- `klytron_configure_host()` — server SSH details, branch, web user
- `klytron_configure_shared_files()` / `klytron_configure_shared_dirs()`
- `klytron_configure_writable_dirs()`

**Deployment Tasks**
- `klytron:assets:map` — map Vite asset files for database URL compatibility
- `klytron:assets:cleanup` — remove problematic `.htaccess` files from build dirs
- `klytron:fonts:verify` / `klytron:fonts:debug` — verify webfont delivery
- `klytron:sitemap:generate` / `klytron:sitemap:verify` / `klytron:sitemap:check`
- `klytron:images:optimize` — optimise uploaded images post-deploy
- `klytron:deploy:start_timer` / `klytron:deploy:end_timer` — deployment timing

**Laravel-Specific Tasks**
- Interactive deployment configuration (auto or prompted)
- Automatic env file decryption (`LARAVEL_ENV_ENCRYPTION_KEY`)
- `enable_encryption` flag to skip decryption when not needed
- Database operations: migrations, fresh import, or both (conditional per run)
- Full cache clear and optimise (`config:cache`, `route:cache`, `view:cache`)
- Storage symlink creation
- Success notifications

**Security**
- Deployer function availability guard (prevents `composer update` errors)
- Input validation and path sanitisation for all configuration
- No hardcoded credentials — all secrets via environment variables

**Service Classes (`src/`)**
- `AssetMappingTask`, `SitemapTask`, `ImageOptimizationTask` — organised task classes
- `DeploymentMetricsService` — timing and metrics
- `RetryService` — retry logic for flaky operations
- `ConfigurationValidator` — pre-flight config validation

**Documentation**
- Full docs suite in `docs/` — installation, quick-start, configuration reference,
  function reference, task reference, dynamic configuration, features, error handling,
  best practices, troubleshooting, FAQ, framework-specific guides, and more
- Working examples for Laravel, Yii2, API, and simple PHP projects
- Starter templates for each project type

### Requirements

- PHP 8.1+
- [Deployer 7.x](https://deployer.org)
- Git + SSH access to deployment server

---

## Support

- **Issues**: https://github.com/klytron/php-deployment-kit/issues
- **Docs**: https://github.com/klytron/php-deployment-kit/tree/main/docs
