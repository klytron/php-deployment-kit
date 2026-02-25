# Server Configuration Deployment

This guide explains how to use the server configuration deployment feature in the Klytron Deployment Kit.

## Overview

The `klytron:server:deploy:configs` task allows you to deploy various server configuration files (like `.htaccess`, nginx configs, etc.) from a central `server` directory to their appropriate locations during deployment. This is particularly useful for maintaining environment-specific configurations in version control.

## Basic Usage

1. Create a `server` directory in your project root (if it doesn't exist already):
   ```bash
   mkdir -p server
   ```

2. Add your server configuration files to this directory. For example:
   ```bash
   # Production .htaccess
   cp public/.htaccess server/.htaccess.production
   
   # Nginx configuration
   touch server/nginx-site.conf
   
   # PHP configuration
   touch server/php.ini
   ```

3. Configure the deployment in your `deploy.php` file:

```php
// Configure server files to deploy
set('server_config_files', [
    [
        'source' => 'server/.htaccess.production',
        'target' => 'public/.htaccess',
        'symlink' => false,  // Set to true to create a symlink instead of copying
        'mode' => 0644      // Optional: set file permissions
    ],
    [
        'source' => 'server/nginx-site.conf',
        'target' => 'shared/nginx/site.conf',
        'symlink' => true   // Create a symlink
    ],
    [
        'source' => 'server/php.ini',
        'target' => 'shared/php.ini',
        'mode' => 0644
    ]
]);

// The server configuration task is automatically added to the deployment flow
after('deploy:shared', 'klytron:server:deploy:configs');
```

## Configuration Options

Each file configuration can have the following options:

- `source`: (string, required) Path to the source file relative to the project root
- `target`: (string, required) Path where the file should be deployed, relative to the release path
- `symlink`: (bool, optional) If true, creates a symlink instead of copying the file (default: false)
- `mode`: (int, optional) File permissions in octal format (e.g., 0644, 0755)

## Example: Laravel Project

For a typical Laravel project, you might use this configuration:

```php
set('server_config_files', [
    [
        'source' => 'server/.htaccess.production',
        'target' => 'public/.htaccess',
        'mode' => 0644
    ],
    [
        'source' => 'server/nginx-site.conf',
        'target' => 'shared/nginx/site.conf',
        'symlink' => true
    ]
]);

// In your deployment flow
after('deploy:shared', 'klytron:server:deploy:configs');
```

## Backward Compatibility

The old `klytron:laravel:deploy:htaccess` task is still available but marked as deprecated. It now simply calls the new `klytron:server:deploy:configs` task internally.

## Best Practices

1. **Version Control**: Add your production configuration files to `.gitignore` to prevent accidentally committing sensitive information.
2. **Environment Variables**: Use environment variables in your configuration files when possible.
3. **Documentation**: Document your server configuration requirements in your project's README.
4. **Testing**: Test your configuration changes in a staging environment before deploying to production.

## Troubleshooting

If files aren't being deployed as expected:

1. Check that the source file exists in the expected location
2. Verify file permissions on the source file
3. Check the deployment logs for any error messages
4. Ensure the target directory exists and is writable
