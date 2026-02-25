# Changelog

All notable changes to the PHP Deployment Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
