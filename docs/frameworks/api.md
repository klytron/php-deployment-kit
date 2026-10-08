# 🚀 API Project Deployment Guide

Complete guide for deploying API projects with Klytron Deployer. This guide covers API-specific features, best practices, and configuration options for Laravel API, REST APIs, and GraphQL APIs.

## 🎯 API Project Features

Klytron Deployer provides comprehensive support for API projects:

- **Authentication** - Laravel Passport OAuth configuration
- **Rate Limiting** - API rate limiting setup and configuration
- **CORS Support** - Cross-Origin Resource Sharing configuration
- **API Documentation** - Automatic API documentation generation
- **Health Checks** - API endpoint health monitoring
- **Security Headers** - Security header configuration
- **Response Caching** - API response caching strategies
- **Monitoring** - API performance monitoring
- **Load Balancing** - API load balancing support

## 🎯 Quick Start for API Projects

### 1. Install Klytron Deployer

```bash
composer require klytron/php-deployment-kit
```

### 2. Copy API Template

```bash
cp vendor/klytron/php-deployment-kit/templates/api-project.php.template deploy.php
```

### 3. Configure Your API Project

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure API application
klytron_configure_app('my-api', 'git@github.com:user/my-api.git');

// Set deployment paths
klytron_set_paths('/var/www', '/var/www/html');

// Configure API project (Laravel recipe handles APIs — type stays 'laravel')
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'supports_passport' => true,
    'supports_vite' => false,
]);

// Configure host
klytron_configure_host('api.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);
```

### 4. Deploy

```bash
vendor/bin/dep deploy
```

## 🎯 API Configuration Options

### Basic API Configuration

```php
klytron_configure_project([
    'type' => 'laravel',                // Laravel recipe also serves APIs
    'database' => 'postgresql',         // Database type
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,        // Enable Passport OAuth
    'supports_vite' => false,           // APIs usually skip frontend builds
]);
// Rate limiting, CORS, versioning and docs are application config
// (Laravel middleware / packages), not kit flags — configure them in your
// codebase and .env file.
```

### Advanced API Configuration

```php
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => false,
]);
// Database credentials are plain Deployer config (note `db_pass`), not
// project flags:
set('db_host', 'localhost');
set('db_name', 'myapi');
set('db_user', 'postgres');
set('db_pass', 'secret');
// Rate limiting, CORS, docs and monitoring are application config —
// configure them in your codebase and .env file, not in kit config.
```

## 🎯 API-Specific Tasks

### Available API Tasks

APIs deploy with the standard Laravel pipeline (see quick-start.md) — there
are no `deploy:api*` tasks in this package. The kit tasks that matter most
for APIs are:

#### `klytron:laravel:deploy:passport:install`

Configure Laravel Passport for API authentication.

```bash
vendor/bin/dep klytron:laravel:deploy:passport:install
```

#### `klytron:laravel:deploy:database:complete`

Run migrations and/or database imports.

```bash
vendor/bin/dep klytron:laravel:deploy:database:complete
```

#### `klytron:laravel:deploy:cache:complete`

Clear and rebuild caches (`cache:clear`, `config:cache`, `optimize`).

```bash
vendor/bin/dep klytron:laravel:deploy:cache:complete
```

#### `klytron:deploy:health_check`

Verify the API responds over HTTP after deploy.

```bash
vendor/bin/dep klytron:deploy:health_check
```

Rate limiting, CORS, docs, security headers and monitoring are application
concerns (middleware / packages in your codebase), not deployment tasks —
the custom `task('deploy:api:*', ...)` examples further below are
per-project starting points you own.

## 🎯 API Authentication (Passport)

### Passport Configuration

```php
// Configure Passport support (real kit flag)
klytron_configure_project([
    'supports_passport' => true,
]);

// Configure shared files for Passport keys
klytron_configure_shared_files([
    '.env',
    'storage/oauth-private.key',
    'storage/oauth-public.key',
]);
```

### Passport Installation Tasks

```php
// Add Passport installation tasks
task('deploy:api:passport_install', function () {
    run('php artisan passport:install');
    run('php artisan passport:keys');
})->desc('Install Laravel Passport');

task('deploy:api:passport_clients', function () {
    // Create OAuth clients
    run('php artisan passport:client --name="Web App" --redirect_uri="https://myapp.com/callback"');
    run('php artisan passport:client --name="Mobile App" --redirect_uri="myapp://callback" --personal');
})->desc('Create Passport OAuth clients');
```

## 🎯 API Rate Limiting

### Rate Limiting Configuration

```php
// Rate-limit values are read by the custom task below via get() — store
// them with set() (klytron_configure_project() would drop unknown keys):
set('rate_limit_requests', 60);
set('rate_limit_minutes', 1);
```

### Rate Limiting Tasks

```php
// Add rate limiting configuration task
task('deploy:api:configure_rate_limiting', function () {
    $requests = get('rate_limit_requests', 60);
    $minutes = get('rate_limit_minutes', 1);
    
    // Configure rate limiting in Laravel
    run("php artisan config:set throttle.requests={$requests}");
    run("php artisan config:set throttle.minutes={$minutes}");
    
    // Clear config cache
    run('php artisan config:clear');
})->desc('Configure API rate limiting');
```

## 🎯 CORS Configuration

### CORS Setup

```php
// CORS values are read by the custom task below via get() — store them
// with set():
set('cors_origins', ['https://myapp.com', 'https://admin.myapp.com']);
```

### CORS Configuration Task

```php
// Add CORS configuration task
task('deploy:api:configure_cors', function () {
    $origins = get('cors_origins', ['*']);
    $methods = get('cors_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    $headers = get('cors_headers', ['Content-Type', 'Authorization']);
    
    // Configure CORS in Laravel
    $corsConfig = [
        'paths' => ['api/*'],
        'allowed_methods' => $methods,
        'allowed_origins' => $origins,
        'allowed_origins_patterns' => [],
        'allowed_headers' => $headers,
        'exposed_headers' => [],
        'max_age' => get('cors_max_age', 86400),
        'supports_credentials' => get('cors_credentials', false),
    ];
    
    // Write CORS configuration
    $configPath = 'config/cors.php';
    // Implementation to write CORS config
})->desc('Configure CORS for API');
```

## 🎯 API Documentation

### Documentation Configuration

```php
// Doc-generator values are read by the custom tasks below via get() —
// store them with set():
set('api_docs_generator', 'swagger'); // or 'l5-swagger', 'scribe'
set('api_docs_path', 'docs/api');
```

### Documentation Generation Tasks

```php
// Add API documentation tasks
task('deploy:api:generate_docs', function () {
    $generator = get('api_docs_generator', 'swagger');
    
    switch ($generator) {
        case 'swagger':
            run('php artisan l5-swagger:generate');
            break;
        case 'scribe':
            run('php artisan scribe:generate');
            break;
        default:
            run('php artisan api:docs');
    }
})->desc('Generate API documentation');

task('deploy:api:publish_docs', function () {
    $docsPath = get('api_docs_path', 'docs/api');
    $publicPath = 'public/api/docs';
    
    // Publish documentation to public directory
    run("cp -r {$docsPath}/* {$publicPath}/");
})->desc('Publish API documentation');
```

## 🎯 API Health Checks

### Health Check Configuration

```php
// Configure API health checks
// Endpoint lists for the custom health-check tasks below are your own
// values — store them with set():
set('health_check_endpoints', [
    '/api/health',
    '/api/health/database',
    '/api/health/cache',
    '/api/health/queue',
]);
set('health_check_timeout', 30);
```

### Health Check Tasks

```php
// Add API health check tasks
task('deploy:api:health_check', function () {
    $endpoints = get('health_check_endpoints', ['/api/health']);
    $timeout = get('health_check_timeout', 30);
    
    foreach ($endpoints as $endpoint) {
        try {
            writeln("<info>Checking API health: {$endpoint}</info>");
            run("curl -f --max-time {$timeout} http://localhost{$endpoint} || exit 1");
            writeln("<info>✓ {$endpoint} is healthy</info>");
        } catch (Exception $e) {
            writeln("<error>✗ {$endpoint} health check failed</error>");
            throw $e;
        }
    }
})->desc('Run API health checks');

task('deploy:api:create_health_endpoints', function () {
    // Create health check routes
    $healthRoutes = "
    Route::get('/health', function () {
        return response()->json(['status' => 'healthy']);
    });
    
    Route::get('/health/database', function () {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'healthy']);
        } catch (Exception \$e) {
            return response()->json(['status' => 'unhealthy'], 500);
        }
    });
    ";
    
    // Implementation to add health routes
})->desc('Create API health check endpoints');
```

## 🎯 API Security

### Security Configuration

```php
// Header maps for the custom security tasks below are your own values —
// store them with set():
set('security_headers_config', [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'",
]);
```

### Security Tasks

```php
// Add API security tasks
task('deploy:api:configure_security', function () {
    $headers = get('security_headers_config', []);
    
    // Configure security headers
    foreach ($headers as $header => $value) {
        run("php artisan config:set security.headers.{$header}='{$value}'");
    }
    
    // Clear config cache
    run('php artisan config:clear');
})->desc('Configure API security headers');

task('deploy:api:validate_security', function () {
    // Validate security configuration
    $securityChecks = [
        'HTTPS enabled' => 'curl -I https://localhost/api/health',
        'Security headers present' => 'curl -I https://localhost/api/health | grep -E "(X-Frame-Options|X-Content-Type-Options)"',
        'Authentication required' => 'curl -I https://localhost/api/protected-endpoint | grep "401"',
    ];
    
    foreach ($securityChecks as $check => $command) {
        try {
            writeln("<info>Validating: {$check}</info>");
            run($command);
            writeln("<info>✓ {$check} passed</info>");
        } catch (Exception $e) {
            writeln("<error>✗ {$check} failed</error>");
        }
    }
})->desc('Validate API security configuration');
```

## 🎯 API Response Caching

### Caching Configuration

```php
// Cache values are read by the custom tasks below via get() — store them
// with set(). The driver itself is application config (CACHE_DRIVER in .env).
set('api_cache_ttl', 3600);
```

### Caching Tasks

```php
// Add API caching tasks
task('deploy:api:configure_caching', function () {
    $ttl = get('api_cache_ttl', 3600);
    $driver = get('cache_driver', 'redis');
    
    // Configure caching
    run("php artisan config:set cache.default={$driver}");
    run("php artisan config:set cache.ttl={$ttl}");
    
    // Clear cache
    run('php artisan cache:clear');
})->desc('Configure API response caching');

task('deploy:api:warm_cache', function () {
    if (get('cache_warming', false)) {
        writeln('<info>Warming API cache...</info>');
        
        // Warm cache for common API endpoints
        $endpoints = [
            '/api/users',
            '/api/posts',
            '/api/categories',
        ];
        
        foreach ($endpoints as $endpoint) {
            run("curl -s http://localhost{$endpoint} > /dev/null");
        }
        
        writeln('<info>Cache warming completed</info>');
    }
})->desc('Warm API response cache');
```

## 🎯 API Monitoring

### Monitoring Configuration

```php
// Endpoint lists for the custom monitoring tasks below are your own values —
// store them with set():
set('monitoring_endpoints', [
    '/api/metrics',
    '/api/status',
    '/api/performance',
]);
```

### Monitoring Tasks

```php
// Add API monitoring tasks
task('deploy:api:setup_monitoring', function () {
    // Setup monitoring endpoints
    $monitoringRoutes = "
    Route::get('/metrics', function () {
        return response()->json([
            'requests_per_minute' => Cache::get('api_requests_per_minute', 0),
            'average_response_time' => Cache::get('api_avg_response_time', 0),
            'error_rate' => Cache::get('api_error_rate', 0),
        ]);
    });
    ";
    
    // Implementation to add monitoring routes
})->desc('Setup API monitoring endpoints');

task('deploy:api:test_performance', function () {
    if (get('performance_tracking', false)) {
        writeln('<info>Testing API performance...</info>');
        
        // Performance test endpoints
        $endpoints = ['/api/users', '/api/posts'];
        
        foreach ($endpoints as $endpoint) {
            $start = microtime(true);
            run("curl -s http://localhost{$endpoint} > /dev/null");
            $end = microtime(true);
            $time = round(($end - $start) * 1000, 2);
            
            writeln("<info>✓ {$endpoint}: {$time}ms</info>");
        }
    }
})->desc('Test API performance');
```

## 🎯 API Deployment Examples

### Basic API Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Basic API configuration
klytron_configure_app('my-api', 'git@github.com:user/my-api.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('api.myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => false,
]);

klytron_configure_host('api.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

### Advanced API Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Advanced API configuration
klytron_configure_app('my-advanced-api', 'git@github.com:user/my-api.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('api.myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => false,
]);
// Database credentials are plain Deployer config (note `db_pass`), not
// project flags:
set('db_host', 'localhost');
set('db_name', 'myapi');
set('db_user', 'postgres');
set('db_pass', 'secret');

klytron_configure_host('api.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
]);

// Configure shared files and directories
klytron_configure_shared_files([
    '.env',
    'storage/oauth-private.key',
    'storage/oauth-public.key',
]);

klytron_configure_shared_dirs([
    'storage',
    'bootstrap/cache',
    'docs/api',
]);

klytron_configure_writable_dirs([
    'storage',
    'bootstrap/cache',
]);
```

### GraphQL API Deployment

```php
<?php
require 'vendor/klytron/php-deployment-kit/deployment-kit.php';
require 'vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// GraphQL API configuration
klytron_configure_app('my-graphql-api', 'git@github.com:user/my-graphql-api.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('graphql.myapp.com');

klytron_configure_project([
    'type' => 'laravel',
    'database' => 'postgresql',
    'env_file_local' => '.env.production',
    'env_file_remote' => '.env',
    'supports_passport' => true,
    'supports_vite' => false,
]);
// GraphQL endpoint/playground settings are application config — configure
// them in your codebase and .env file, not in kit config.

klytron_configure_host('graphql.myapp.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

## 🎯 API Best Practices

### Security Best Practices

1. **Authentication**: Always use proper authentication (OAuth, JWT, etc.)
2. **Rate Limiting**: Implement rate limiting to prevent abuse
3. **CORS**: Configure CORS properly for cross-origin requests
4. **HTTPS**: Always use HTTPS in production
5. **Input Validation**: Validate all API inputs
6. **SQL Injection**: Use parameterized queries
7. **XSS Protection**: Implement XSS protection headers

### Performance Best Practices

1. **Caching**: Implement response caching for frequently accessed data
2. **Database Optimization**: Use database indexing and query optimization
3. **Response Compression**: Enable gzip compression
4. **CDN Usage**: Use CDN for static assets
5. **Load Balancing**: Implement load balancing for high traffic
6. **Monitoring**: Monitor API performance and errors

### API Design Best Practices

1. **RESTful Design**: Follow REST principles
2. **Versioning**: Use proper API versioning
3. **Documentation**: Maintain comprehensive API documentation
4. **Error Handling**: Implement proper error handling and status codes
5. **Pagination**: Use pagination for large datasets
6. **Filtering**: Implement filtering and sorting options

## 🎯 API Troubleshooting

### Common API Issues

1. **CORS Errors**: Check CORS configuration and allowed origins
2. **Authentication Issues**: Verify OAuth configuration and tokens
3. **Rate Limiting**: Check rate limiting configuration
4. **Performance Issues**: Monitor response times and optimize queries
5. **Documentation Issues**: Ensure API documentation is up to date

### Debugging API Deployments

```bash
# Check API health
vendor/bin/dep run "curl -f http://localhost/api/health"

# Check API documentation
vendor/bin/dep run "curl -f http://localhost/api/docs"

# Check rate limiting
vendor/bin/dep run "curl -I http://localhost/api/users"

# Check CORS headers
vendor/bin/dep run "curl -H 'Origin: https://myapp.com' -H 'Access-Control-Request-Method: GET' -H 'Access-Control-Request-Headers: Content-Type' -X OPTIONS http://localhost/api/users"

# Check authentication
vendor/bin/dep run "curl -H 'Authorization: Bearer YOUR_TOKEN' http://localhost/api/protected-endpoint"
```

## 🎯 Next Steps

- **Read the [Configuration Reference](../configuration-reference.md)** - Complete configuration options
- **Explore [Examples](../examples/)** - Real-world API deployment examples
- **Check [Best Practices](../best-practices.md)** - API deployment best practices
- **Review [Task Reference](../task-reference.md)** - Available API tasks
