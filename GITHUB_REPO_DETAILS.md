# GitHub Repository Details for PhpDeploymentKit

## Repository Information

| Property | Value |
|----------|-------|
| **Repository Name** | `php-deployment-kit` |
| **GitHub Owner** | `klytron` (your GitHub username) |
| **Full GitHub URL** | `https://github.com/klytron/php-deployment-kit` |
| **Git Clone URL** | `git@github.com:klytron/php-deployment-kit.git` |
| **Package Name** | `klytron/php-deployment-kit` |
| **Packagist URL** | `https://packagist.org/packages/klytron/php-deployment-kit` |

---

## Steps to Create GitHub Repository

### 1. Create the Repository on GitHub

Go to: https://github.com/new

Fill in:
- **Repository name**: `php-deployment-kit`
- **Description**: "A comprehensive deployment library for PHP applications built on Deployer. Supports Laravel, Yii2, API projects, and simple PHP applications."
- **Visibility**: Public
- **Initialize with**: Add a README (but we already have one, so select "None")

### 2. Push the Code

Run these commands in your local repository:

```bash
cd /path-to/klytron-dev-space/02-my-custom-packages/laravel-packages/php-deployment-kit

# Initialize git if not already done
git init
git add .
git commit -m "Initial commit: PhpDeploymentKit v1.0.0"

# Add remote (replace klytron with your actual GitHub username)
git remote add origin git@github.com:klytron/php-deployment-kit.git

# Push to GitHub
git push -u origin main
```

### 3. Create a Release Tag (Optional but Recommended)

```bash
git tag -a v1.0.0 -m "First stable release"
git push origin v1.0.0
```

### 4. Submit to Packagist

1. Go to: https://packagist.org/packages/submit
2. Enter the GitHub repository URL: `https://github.com/klytron/php-deployment-kit`
3. Click "Check"
4. Click "Submit"

### 5. Set Up Packagist Auto-Update (Optional)

1. Go to your GitHub repository: https://github.com/klytron/php-deployment-kit
2. Go to **Settings** → **Webhooks**
3. Add a webhook:
   - **Payload URL**: Get this from Packagist (in your package settings)
   - **Events**: Just the `push` event

---

## Installation Commands for Users

```bash
# Install via Composer
composer require klytron/php-deployment-kit --dev

# Or add to composer.json manually
# "require-dev": {
#     "klytron/php-deployment-kit": "^1.0"
# }
```

---

## Basic Usage

```php
// deploy.php
require 'vendor/klytron/php-deployment-kit/src/DeploymentKit.php';
require 'vendor/klytron/php-deployment-kit/recipes/laravel.php';

klytron_configure_app('my-app', 'git@github.com:user/my-app.git');
klytron_set_paths('/var/www', '/var/www/html');
klytron_set_domain('yourdomain.com');
klytron_set_php_version('php8.3');

klytron_configure_host('your-server.com', [
    'remote_user' => 'root',
    'http_user' => 'www-data',
]);
```

---

## Verification Commands

```bash
# Test deployment configuration
vendor/bin/dep test

# Deploy to production
vendor/bin/dep deploy

# View available tasks
vendor/bin/dep list
```

---

## Files Modified in This Update

### Security Fixes
- Removed hardcoded SSH key paths
- Fixed SQL injection in database search/replace command
- Added input validation
- Removed hardcoded PHP path

### Renamed/Refactored
- Package: `klytron/deployment-kit` → `klytron/php-deployment-kit`
- Namespace: `Klytron\Deployer` → `Klytron\PhpDeploymentKit`
- Main entry: `deployment-kit.php` → `src/DeploymentKit.php`

### Removed
- `working-deploy-scripts/` (project-specific)
- `00-klytron-dev-docs/` (internal docs)
- `tools/` (deprecated tools)
- `BackwardCompatibility/` (deprecated code)
- `src/Compatibility/` (deprecated compatibility)

### New Structure
```
php-deployment-kit/
├── src/
│   ├── Commands/
│   ├── DeploymentKit.php
│   ├── DeployerRecipe.php
│   └── Tasks.php
├── recipes/
│   ├── laravel.php (new)
│   └── yii2.php (new)
├── templates/
├── docs/
├── composer.json
└── README.md
```
