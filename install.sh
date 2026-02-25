#!/bin/bash

# PhpDeploymentKit Installation Script
# 
# This script installs PhpDeploymentKit in your PHP project
#
# Usage:
#   curl -sSL https://raw.githubusercontent.com/klytron/php-deployment-kit/main/install.sh | bash
#   or
#   ./install.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

check_php_project() {
    if [[ ! -f "composer.json" ]]; then
        error "This doesn't appear to be a PHP project root directory."
        error "Please run this script from your PHP project root."
        exit 1
    fi
    success "PHP project detected"
}

install_via_composer() {
    info "Installing PhpDeploymentKit via Composer..."
    
    if command -v composer &> /dev/null; then
        composer require klytron/php-deployment-kit --dev
        success "PhpDeploymentKit installed via Composer"
        
        if [[ ! -f "deploy.php" ]]; then
            if [[ -f "artisan" ]]; then
                cp vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template deploy.php
            else
                cp vendor/klytron/php-deployment-kit/templates/deploy.php.template deploy.php
            fi
            success "Created deploy.php from template"
        fi
    else
        error "Composer not found. Please install Composer first."
        exit 1
    fi
}

create_deploy_file() {
    if [[ -f "deploy.php" ]]; then
        warning "deploy.php already exists"
        read -p "Do you want to create a backup and use a template? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            mv deploy.php deploy.php.backup
            info "Existing deploy.php backed up as deploy.php.backup"
        else
            info "Keeping existing deploy.php"
            return
        fi
    fi
    
    info "Detecting project type..."
    
    if [[ -f "artisan" ]]; then
        info "Laravel project detected"
        cp vendor/klytron/php-deployment-kit/templates/laravel-deploy.php.template deploy.php
        success "Created deploy.php using Laravel template"
    elif [[ -f "yii" ]] || [[ -f "yii.bat" ]]; then
        info "Yii2 project detected"
        cp vendor/klytron/php-deployment-kit/templates/deploy.php.template deploy.php
        success "Created deploy.php using general template"
    elif [[ -f "public/index.php" ]] || [[ -f "index.php" ]]; then
        info "PHP project detected"
        cp vendor/klytron/php-deployment-kit/templates/simple-php.php.template deploy.php
        success "Created deploy.php using Simple PHP template"
    else
        info "Unknown project type, using general template"
        cp vendor/klytron/php-deployment-kit/templates/deploy.php.template deploy.php
        success "Created deploy.php using general template"
    fi
    
    info "Please customize deploy.php with your project-specific settings"
}

main() {
    info "🚀 PhpDeploymentKit Installation"
    info "================================"
    
    check_php_project
    
    install_via_composer
    
    create_deploy_file
    
    echo
    success "🎉 PhpDeploymentKit installation completed!"
    echo
    info "Next steps:"
    echo "1. Edit deploy.php with your project settings"
    echo "2. Configure your server details"
    echo "3. Run: vendor/bin/dep test"
    echo "4. Run: vendor/bin/dep deploy"
    echo
    info "Documentation: https://github.com/klytron/php-deployment-kit"
    echo
}

main "$@"
