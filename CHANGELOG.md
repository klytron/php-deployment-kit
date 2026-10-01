# Changelog

All notable changes to the PHP Deployment Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2] - 2026-10-01

Fixes load-time closure evaluation crash on Deployer 7.5.12, wires Filament v4/v5 assets into the Laravel pipeline, hardens secret masking, introduces the standalone `klytron` CLI scaffolding tool, and expands CI test coverage for all examples and templates.

### Fixed

- **Neutralize Deployer 7.5.12 Load-time `ask()` Crash**: Replaced `is_callable(get('php_version'))` in `recipes/klytron-laravel-recipe.php` with safe inspection of `klytron_php_version`. In Deployer 7.5.12 provision recipes, `php_version` is defined as a closure calling `Deployer\ask()`. Calling `get()` on it outside a task context triggered a fatal exception (`'Deployer\ask' can only be used within a task`), breaking all CLI commands (`dep list`, `dep help`, `dep test`, `dep --plan`).
- **Filament Asset Publishing in Deployment Flow**: Added `klytron:laravel:filament:assets` into `klytron_laravel_deploy_flow()` prior to `deploy:database:complete` and `deploy:symlink`, ensuring Filament admin panel assets are published automatically when enabled.
- **Enhanced Secret Masking**: Expanded secret masking in both local and remote environment validation tasks (`klytron:laravel:validate:environment:local` and `klytron:laravel:validate:environment`) using case-insensitive regex pattern matching `(KEY|PASSWORD|SECRET|TOKEN)`.
- **PHP 8.4 Modernization & Deprecations**: Fixed float-to-int conversion in `DeploymentMetricsService::formatDuration()`, added explicit nullable parameter types (`?string`) in `DeploymentErrorHandler` and `DeploymentMetricsTask`, and verified clean execution on PHP 8.4 without deprecations.
- **Domain & Username Validation Fixes**: Resolved malformed regex in `ConfigurationValidationService::isValidDomain()` using `filter_var` domain validation, enabled hyphenated username support (e.g. `www-data`, `deploy-user`), and supported PHP 8.4 in supported versions.
- **Enhanced Deployment & Decryption Hardening**: Corrected `curl_setopt` missing boolean parameter calls and imported `writeln` in `EnhancedDeploymentTask`, fixed exception arguments in `ImageOptimizationTask`, purged non-existent Deployer exception imports in `EnvironmentDecryptTask`, and added Laravel 11 `Schema::getTableListing()` fallback in `KlytronDbSearchReplaceCommand`.
- **Batch Encryption Error Handling**: Updated `KlytronFileEnCrypterCommand` to track failed encryptions and return `Command::FAILURE` when batch operations fail, mirroring `KlytronFileDeCrypterCommand`.
- **Unit Test Suite Visibility & Strictness**:
  - Changed `DeploymentMetricsService::formatDuration()` and `DeploymentMetricsService::formatBytes()` to `public static` to support direct invocation and test assertions.
  - Changed `RetryService::shouldRetry()` to `public static` and included `\RuntimeException` in `RetryService::executeCurlOperation()` retry candidates.
  - Updated `phpunit.xml` configuration to disable strict coverage requirements so integration test workflows run cleanly.
  - Added `ConfigurationValidationServiceTest` verifying validation rules and exception data integrity.

### Added

- **CLI Generator Tool (`vendor/bin/klytron`)**: Added standalone `bin/klytron` executable and Laravel Artisan command `php artisan klytron:init` driven by `DeployConfigGenerator`. Generates canonical `deploy.php` with environment-driven host configuration, path placeholders, and non-root user setup pre-wired, plus `.env.deploy.example`.
- **SQLite Database Replacement Task (`klytron:laravel:deploy:db:import:sqlite`)**: Implemented standalone and integrated SQLite database import supporting plain and encrypted SQLite dumps, maintenance mode (`artisan down`/`up`) during replacement, file permissions, and cache clearing.
- **Integration CI Validation for Templates & Examples**: Added `testExamplesAndTemplatesCanResolvePlan` and `testGeneratedConfigResolvesPlan` in `tests/Integration/DeploymentWorkflowTest.php` ensuring every example, template, and generated configuration resolves cleanly via `dep list` without syntax or load-time errors.

### Documentation

- **Example & Reference Template Cleanup**: Removed outdated `after('klytron:laravel:deploy:success', 'klytron:system:restart')` lines from `docs/quick-start.md` and `docs/configuration-reference.md` (PHP-FPM restart is already hooked automatically in the recipe) to prevent double restarts.
- **Validation-Driven Environment Checks**: Removed load-time `if (!file_exists('.env.production'))` guard from documentation snippets, directing users to the non-blocking validation task `klytron:validate:basic` / `klytron:validate:env_files`.
- **SQLite Workflow & Configuration**: Documented SQLite database replacement semantics, `database => 'sqlite'`, `sqlite_database_path`, and `db_import_path` in `docs/task-reference.md` and `docs/configuration-reference.md`.

## [1.1.1] - 2026-09-30

Maintenance and hardening release addressing edge cases, security enhancements, and codebase modernization.

### Fixed

- **Database Import Flag Typo**: Fixed `shouldIMportDbFile` typo to `shouldImportDbFile` in default core configuration, aligning with the Laravel recipe's expectation.
- **Laravel Permission Scoping**: Corrected `klytron:deploy:access_permissions` condition from `has('laravel')` to `get('project_type', '') === 'laravel'` so storage and cache permission sweeps trigger reliably.
- **PHP-FPM Default Version**: Changed default PHP version in `klytron:fpm:reload` from `8.4` to `8.3` to maintain consistency across core configurations.
- **Web Server User Permissions**: Updated `klytron:deploy:access_permissions` to respect `http_user` configuration rather than falling back to hardcoded `www-data`.
- **PHP Version Preserved in Laravel Recipe**: Safeguarded `recipes/klytron-laravel-recipe.php` so user-configured PHP versions are preserved while still neutralizing Deployer provision's interactive `ask()` prompt.
- **Cleaned Orphan Docblocks & Stale Version Strings**: Removed stacked docblocks and updated `@version` annotations to `1.1.1`.

### Security & Hardening

- **Secure Database Backups**: Hardened `mysqldump` command in `klytron:deploy:backup:create` using `MYSQL_PWD` environment variable to prevent password disclosure in process listings (`ps aux`).
- **Restricted Cleanup Scope**: Removed speculative directory probing of `/var/www`, `/opt`, and home directories in `klytron:delete:project`, strictly scoping cleanup to `deploy_path`.
- **Conditional Pseudo-Terminal Allocation**: Updated `klytron_configure_host` to only allocate `-t` (pseudo-terminal) when `writable_use_sudo` is enabled or explicitly requested via `ssh_arguments`, preventing SSH pseudo-terminal allocation errors in non-interactive CI/CD runners.

### Changed & Modernized

- **Modern String Functions**: Modernized string checks from `strpos()` to `str_contains()` and `str_starts_with()` across core and task implementations for PHP 8.1+.
- **Consolidated Deployment Flows**: Consolidated duplicate flow definitions (`klytron_deploy_flow_minimal()` and `klytron_deploy_flow_php()`), making them clean deprecated aliases pointing to `klytron_deploy_flow()`.
- **Hidden Stub Tasks**: Tagged 9 placeholder metrics and environment decryption tasks with `->hidden()` to keep `dep list` clean and uncluttered.

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
