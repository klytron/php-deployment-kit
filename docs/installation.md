# Installation Guide

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation Methods](#installation-methods)
- [System Requirements](#system-requirements)
- [Server Setup](#server-setup)
- [Configuration](#configuration)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)
- [Next Steps](#next-steps)

## 🔧 Prerequisites

Before installing Klytron Deployer, ensure you have the following prerequisites:

### Local Development Environment

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Git**: For repository access
- **SSH**: For server connectivity

### Server Requirements

- **Linux/Unix** server (Ubuntu, CentOS, Debian, etc.)
- **SSH access** with key-based authentication
- **Web server** (Apache, Nginx, etc.)
- **PHP** with required extensions
- **Database** (MySQL, PostgreSQL, SQLite)

### Required PHP Extensions

```bash
# Check PHP extensions
php -m | grep -E "(curl|json|openssl|zip|mbstring|xml)"
```

**Required extensions:**

- `curl` - For HTTP requests
- `json` - For JSON processing
- `openssl` - For SSH connections
- `zip` - For file compression
- `mbstring` - For string handling
- `xml` - For XML processing

## 📦 Installation Methods

### Method 1: Composer Installation (Recommended)

```bash
# Install via Composer
composer require klytron/php-deployment-kit

# Verify installation
vendor/bin/dep --version
```

### Method 2: Global Installation

```bash
# Install globally via Composer
composer global require klytron/php-deployment-kit

# Add to PATH (add to ~/.bashrc or ~/.zshrc)
export PATH="$HOME/.composer/vendor/bin:$PATH"

# Verify installation
dep --version
```

### Method 3: Manual Installation

```bash
# Clone the repository
git clone https://github.com/klytron/php-deployment-kit.git
cd deployment-kit

# Install dependencies
composer install

# Create symbolic link (optional)
sudo ln -s $(pwd)/deployment-kit.php /usr/local/bin/deployment-kit
```

## 🖥️ System Requirements

### Minimum Requirements

| Component    | Version      | Notes                        |
| ------------ | ------------ | ---------------------------- |
| **PHP**      | 8.1+         | Required for modern features |
| **Deployer** | 7.0+         | Base deployment framework    |
| **Git**      | 2.0+         | Repository access            |
| **SSH**      | OpenSSH 7.0+ | Server connectivity          |

### Recommended Requirements

| Component    | Version      | Notes                 |
| ------------ | ------------ | --------------------- |
| **PHP**      | 8.2+         | Latest stable version |
| **Deployer** | 7.4+         | Latest features       |
| **Git**      | 2.40+        | Latest features       |
| **SSH**      | OpenSSH 9.0+ | Enhanced security     |

### Server Requirements

#### Web Server

- **Apache** 2.4+ or **Nginx** 1.18+
- **PHP-FPM** (recommended)
- **SSL/TLS** support

#### Database

- **MySQL** 8.0+ or **MariaDB** 10.5+
- **PostgreSQL** 13+ (for API projects)
- **SQLite** 3.35+ (for simple projects)

#### File System

- **Ext4** or **XFS** filesystem
- **2GB+** available disk space
- **Proper permissions** for web server

## 🖥️ Server Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y  # Ubuntu/Debian
sudo yum update -y                      # CentOS/RHEL

# Install required packages
sudo apt install -y git curl wget unzip  # Ubuntu/Debian
sudo yum install -y git curl wget unzip  # CentOS/RHEL
```

### 2. PHP Installation

```bash
# Ubuntu/Debian
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-curl php8.2-json php8.2-mbstring php8.2-xml php8.2-zip

# CentOS/RHEL
sudo yum install -y php php-cli php-fpm php-mysqlnd php-curl php-json php-mbstring php-xml php-zip
```

### 3. Web Server Configuration

#### Apache Configuration

```apache
# /etc/apache2/sites-available/your-app.conf
<VirtualHost *:80>
    ServerName your-app.com
    DocumentRoot /var/www/html/current/public

    <Directory /var/www/html/current/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/your-app_error.log
    CustomLog ${APACHE_LOG_DIR}/your-app_access.log combined
</VirtualHost>
```

#### Nginx Configuration

```nginx
# /etc/nginx/sites-available/your-app
server {
    listen 80;
    server_name your-app.com;
    root /var/www/html/current/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Database Setup

#### MySQL Setup

```bash
# Install MySQL
sudo apt install -y mysql-server  # Ubuntu/Debian
sudo yum install -y mysql-server  # CentOS/RHEL

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE myapp;
CREATE USER 'myapp_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON myapp.* TO 'myapp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### PostgreSQL Setup

```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib  # Ubuntu/Debian
sudo yum install -y postgresql postgresql-server   # CentOS/RHEL

# Initialize database
sudo postgresql-setup initdb
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Create database and user
sudo -u postgres psql
```

```sql
CREATE DATABASE myapp;
CREATE USER myapp_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE myapp TO myapp_user;
\q
```

### 5. SSH Key Setup

```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "deploy@your-server.com"

# Copy public key to server
ssh-copy-id -i ~/.ssh/id_rsa.pub user@your-server.com

# Test SSH connection
ssh user@your-server.com
```

## ⚙️ Configuration

### 1. Create Deployment Configuration

```bash
# Copy appropriate template
cp vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template deploy.php

# Edit configuration
nano deploy.php
```

### 2. Basic Configuration Example

```php
<?php
namespace Deployer;

// Include the PHP Deployment Kit (framework-agnostic core)
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

// Include the Laravel Recipe (for Laravel projects)
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// Configure application
klytron_configure_app('my-app', 'git@github.com:user/my-app.git');

// Set paths
klytron_set_paths('/var/www', '/var/www/html');

// Configure project
klytron_configure_project([
    'type' => 'laravel',
    'database' => 'mysql',
    'supports_vite' => true,
]);

// Configure host
klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'http_user' => 'www-data',
]);
```

## Verification

### 1. Test Installation

```bash
# Test PHP Deployment Kit installation
vendor/bin/dep --version
```

### 2. Test Deployment

```bash
# Test deployment (dry run)
vendor/bin/dep deploy --dry-run

# Test with verbose output
vendor/bin/dep deploy -v
```

## Troubleshooting

### Common Installation Issues

#### Issue: Composer Installation Fails

```bash
# Clear Composer cache
composer clear-cache

# Update Composer
composer self-update

# Install with verbose output
composer require klytron/php-deployment-kit -v
```

#### Issue: SSH Connection Fails

```bash
# Check SSH key permissions
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub

# Test SSH connection manually
ssh -T user@your-server.com
```

#### Issue: PHP Extension Missing

```bash
# Install missing extensions
sudo apt install -y php8.3-curl php8.3-json php8.3-openssl php8.3-zip php8.3-mbstring php8.3-xml

# Restart web server
sudo systemctl restart apache2  # or nginx
sudo systemctl restart php8.3-fpm
```

#### Issue: Permission Denied

```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### Debug Commands

```bash
# Enable debug mode
vendor/bin/dep deploy --debug
```

## Next Steps

- [Quick Start Guide](quick-start.md) - Deploy your first application
- [Configuration Reference](configuration-reference.md) - Learn all configuration options
- [Framework Guides](frameworks/) - Framework-specific deployment guides

---

**Pro Tip**: Always test your deployment configuration in a staging environment before deploying to production.

**Need Help?**: Check the [Troubleshooting](troubleshooting.md) for common issues.
