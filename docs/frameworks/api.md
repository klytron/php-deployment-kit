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

// Configure API project
klytron_configure_project([
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_cors' => true,
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
    'type' => 'laravel-api',                // API project type
    'database' => 'postgresql',             // Database type
    'supports_passport' => true,            // Enable Passport OAuth
    'supports_rate_limiting' => true,       // Enable rate limiting
    'supports_cors' => true,                // Enable CORS support
    'supports_api_docs' => true,            // Enable API documentation
    'supports_oauth' => true,               // Enable OAuth support
    'api_version' => 'v1',                  // API version
    'api_prefix' => 'api',                  // API route prefix
]);
```

### Advanced API Configuration

```php
klytron_configure_project([
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'db_host' => 'localhost',
    'db_name' => 'myapi',
    'db_user' => 'postgres',
    'db_password' => 'secret',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_cors' => true,
    'supports_api_docs' => true,
    'api_version' => 'v1',
    'api_prefix' => 'api',
    'rate_limit_requests' => 60,
    'rate_limit_minutes' => 1,
    'cors_origins' => ['https://myapp.com', 'https://admin.myapp.com'],
    'security_headers' => true,
    'response_caching' => true,
    'api_monitoring' => true,
]);
```

## 🎯 API-Specific Tasks

### Available API Tasks

#### `deploy:api`

Main API deployment task that runs all API-specific operations.

```bash
vendor/bin/dep deploy:api
```

#### `deploy:api:passport`

Configure Laravel Passport for API authentication.

```bash
vendor/bin/dep deploy:api:passport
```

#### `deploy:api:rate_limiting`

Configure API rate limiting.

```bash
vendor/bin/dep deploy:api:rate_limiting
```

#### `deploy:api:cors`

Configure CORS for API endpoints.

```bash
vendor/bin/dep deploy:api:cors
```

#### `deploy:api:docs`

Generate API documentation.

```bash
vendor/bin/dep deploy:api:docs
```

#### `deploy:api:health`

Configure API health check endpoints.

```bash
vendor/bin/dep deploy:api:health
```

#### `deploy:api:security`

Configure API security headers and settings.

```bash
vendor/bin/dep deploy:api:security
```

## 🎯 API Authentication (Passport)

### Passport Configuration

```php
// Configure Passport support
klytron_configure_project([
    'supports_passport' => true,
    'passport_keys_path' => 'storage/oauth-*.key',
    'passport_personal_access' => true,
    'passport_password_grant' => true,
    'passport_client_credentials' => true,
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
klytron_add_task('deploy:api:passport_install', function () {
    run('php artisan passport:install');
    run('php artisan passport:keys');
}, [
    'description' => 'Install Laravel Passport',
]);

klytron_add_task('deploy:api:passport_clients', function () {
    // Create OAuth clients
    run('php artisan passport:client --name="Web App" --redirect_uri="https://myapp.com/callback"');
    run('php artisan passport:client --name="Mobile App" --redirect_uri="myapp://callback" --personal');
}, [
    'description' => 'Create Passport OAuth clients',
]);
```

## 🎯 API Rate Limiting

### Rate Limiting Configuration

```php
// Configure rate limiting
klytron_configure_project([
    'supports_rate_limiting' => true,
    'rate_limit_requests' => 60,
    'rate_limit_minutes' => 1,
    'rate_limit_headers' => true,
    'rate_limit_by_user' => true,
    'rate_limit_by_ip' => true,
]);
```

### Rate Limiting Tasks

```php
// Add rate limiting configuration task
klytron_add_task('deploy:api:configure_rate_limiting', function () {
    $requests = get('rate_limit_requests', 60);
    $minutes = get('rate_limit_minutes', 1);
    
    // Configure rate limiting in Laravel
    run("php artisan config:set throttle.requests={$requests}");
    run("php artisan config:set throttle.minutes={$minutes}");
    
    // Clear config cache
    run('php artisan config:clear');
}, [
    'description' => 'Configure API rate limiting',
]);
```

## 🎯 CORS Configuration

### CORS Setup

```php
// Configure CORS support
klytron_configure_project([
    'supports_cors' => true,
    'cors_origins' => ['https://myapp.com', 'https://admin.myapp.com'],
    'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'cors_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'cors_credentials' => true,
    'cors_max_age' => 86400,
]);
```

### CORS Configuration Task

```php
// Add CORS configuration task
klytron_add_task('deploy:api:configure_cors', function () {
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
}, [
    'description' => 'Configure CORS for API',
]);
```

## 🎯 API Documentation

### Documentation Configuration

```php
// Configure API documentation
klytron_configure_project([
    'supports_api_docs' => true,
    'api_docs_generator' => 'swagger', // or 'l5-swagger', 'scribe'
    'api_docs_path' => 'docs/api',
    'api_docs_url' => '/api/docs',
    'api_docs_auto_generate' => true,
]);
```

### Documentation Generation Tasks

```php
// Add API documentation tasks
klytron_add_task('deploy:api:generate_docs', function () {
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
}, [
    'description' => 'Generate API documentation',
]);

klytron_add_task('deploy:api:publish_docs', function () {
    $docsPath = get('api_docs_path', 'docs/api');
    $publicPath = 'public/api/docs';
    
    // Publish documentation to public directory
    run("cp -r {$docsPath}/* {$publicPath}/");
}, [
    'description' => 'Publish API documentation',
]);
```

## 🎯 API Health Checks

### Health Check Configuration

```php
// Configure API health checks
klytron_configure_project([
    'api_health_checks' => true,
    'health_check_endpoints' => [
        '/api/health',
        '/api/health/database',
        '/api/health/cache',
        '/api/health/queue',
    ],
    'health_check_timeout' => 30,
]);
```

### Health Check Tasks

```php
// Add API health check tasks
klytron_add_task('deploy:api:health_check', function () {
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
}, [
    'description' => 'Run API health checks',
]);

klytron_add_task('deploy:api:create_health_endpoints', function () {
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
}, [
    'description' => 'Create API health check endpoints',
]);
```

## 🎯 API Security

### Security Configuration

```php
// Configure API security
klytron_configure_project([
    'security_headers' => true,
    'api_security' => true,
    'security_headers_config' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
    ],
    'api_authentication' => true,
    'api_authorization' => true,
]);
```

### Security Tasks

```php
// Add API security tasks
klytron_add_task('deploy:api:configure_security', function () {
    $headers = get('security_headers_config', []);
    
    // Configure security headers
    foreach ($headers as $header => $value) {
        run("php artisan config:set security.headers.{$header}='{$value}'");
    }
    
    // Clear config cache
    run('php artisan config:clear');
}, [
    'description' => 'Configure API security headers',
]);

klytron_add_task('deploy:api:validate_security', function () {
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
}, [
    'description' => 'Validate API security configuration',
]);
```

## 🎯 API Response Caching

### Caching Configuration

```php
// Configure API response caching
klytron_configure_project([
    'response_caching' => true,
    'cache_driver' => 'redis',
    'api_cache_ttl' => 3600,
    'api_cache_tags' => ['api', 'responses'],
    'cache_warming' => true,
]);
```

### Caching Tasks

```php
// Add API caching tasks
klytron_add_task('deploy:api:configure_caching', function () {
    $ttl = get('api_cache_ttl', 3600);
    $driver = get('cache_driver', 'redis');
    
    // Configure caching
    run("php artisan config:set cache.default={$driver}");
    run("php artisan config:set cache.ttl={$ttl}");
    
    // Clear cache
    run('php artisan cache:clear');
}, [
    'description' => 'Configure API response caching',
]);

klytron_add_task('deploy:api:warm_cache', function () {
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
}, [
    'description' => 'Warm API response cache',
]);
```

## 🎯 API Monitoring

### Monitoring Configuration

```php
// Configure API monitoring
klytron_configure_project([
    'api_monitoring' => true,
    'monitoring_endpoints' => [
        '/api/metrics',
        '/api/status',
        '/api/performance',
    ],
    'monitoring_interval' => 60,
    'performance_tracking' => true,
]);
```

### Monitoring Tasks

```php
// Add API monitoring tasks
klytron_add_task('deploy:api:setup_monitoring', function () {
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
}, [
    'description' => 'Setup API monitoring endpoints',
]);

klytron_add_task('deploy:api:test_performance', function () {
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
}, [
    'description' => 'Test API performance',
]);
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
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_cors' => true,
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
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'db_host' => 'localhost',
    'db_name' => 'myapi',
    'db_user' => 'postgres',
    'db_password' => 'secret',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_cors' => true,
    'supports_api_docs' => true,
    'api_version' => 'v1',
    'api_prefix' => 'api',
    'rate_limit_requests' => 60,
    'rate_limit_minutes' => 1,
    'cors_origins' => ['https://myapp.com', 'https://admin.myapp.com'],
    'security_headers' => true,
    'response_caching' => true,
    'api_monitoring' => true,
    'cache_driver' => 'redis',
    'session_driver' => 'redis',
]);

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
    'type' => 'laravel-api',
    'database' => 'postgresql',
    'supports_passport' => true,
    'supports_rate_limiting' => true,
    'supports_cors' => true,
    'api_type' => 'graphql',
    'graphql_endpoint' => '/graphql',
    'graphql_playground' => true,
    'graphql_introspection' => true,
]);

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
