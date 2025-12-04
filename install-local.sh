#!/bin/bash
#
# Easy Pack - Local Development Installer
# =============================================
#
# This script automates the installation of a new Laravel project with Easy Pack
# when developing locally (before publishing to Packagist).
#
# PORTABLE: Copy the entire folder to any Ubuntu machine and run this script.
#
# Usage:
#   ./install-local.sh [project-name] [options]
#
# Options:
#   --db-name=NAME       Database name (default: project name with underscores)
#   --db-user=USER       Database username (default: root)
#   --db-password=PASS   Database password (default: empty)
#   --db-host=HOST       Database host (default: 127.0.0.1)
#   --db-port=PORT       Database port (default: 3306)
#   --db=TYPE            Database type: mysql, pgsql, sqlite (default: mysql)
#   --quick              Skip interactive prompts, use defaults
#   --with-docs          Generate API documentation after install
#   --check-deps         Only check dependencies, don't install
#   --install-deps       Auto-install missing system dependencies (requires sudo)
#   --offline            Use offline/cached packages (no internet required)
#   --prepare-offline    Download and cache packages for offline use
#   --help               Show this help message
#
# Example:
#   ./install-local.sh my-api-project --db-name=my_api --db-user=root --quick
#
# Portable Installation (on a new Ubuntu machine):
#   ./install-local.sh --check-deps              # Check what's missing
#   ./install-local.sh --install-deps            # Install missing dependencies
#   ./install-local.sh my-project --quick        # Create project
#
# Offline Installation (prepare on machine with internet):
#   ./install-local.sh --prepare-offline         # Cache packages
#   # Copy folder to offline machine, then:
#   ./install-local.sh my-project --offline --quick
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Default values
PROJECT_NAME=""
DB_NAME=""
DB_USER="root"
DB_PASSWORD=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_TYPE="mysql"
QUICK_MODE=false
WITH_DOCS=false
CHECK_DEPS_ONLY=false
INSTALL_DEPS=false
OFFLINE_MODE=false
PREPARE_OFFLINE=false

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Look for easy-pack in the same directory as this script
EASYPACK_PATH="$SCRIPT_DIR/easy-pack"
# Offline cache directory
OFFLINE_CACHE="$SCRIPT_DIR/.offline-cache"
LARAVEL_CACHE="$OFFLINE_CACHE/laravel-project"

# Minimum PHP version required
MIN_PHP_VERSION="8.2"

# ============================================================================
# DEPENDENCY CHECK FUNCTIONS
# ============================================================================

# Check if a command exists
command_exists() {
    command -v "$1" &> /dev/null
}

# Get PHP version as comparable number (e.g., 8.2.1 -> 80201)
get_php_version_number() {
    php -r "echo PHP_VERSION_ID;" 2>/dev/null || echo "0"
}

# Check PHP version meets minimum requirement
check_php_version() {
    local version_id=$(get_php_version_number)
    local min_version_id=80200  # 8.2.0
    
    if [ "$version_id" -ge "$min_version_id" ]; then
        return 0
    else
        return 1
    fi
}

# Check if a PHP extension is loaded
php_extension_loaded() {
    php -m 2>/dev/null | grep -qi "^$1$"
}

# Check MySQL connectivity
check_mysql_connection() {
    if command_exists mysql; then
        if [ -n "$DB_PASSWORD" ]; then
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" &>/dev/null
        else
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e "SELECT 1" &>/dev/null
        fi
        return $?
    fi
    return 1
}

# Check if directory is writable
check_writable() {
    [ -w "${1:-.}" ]
}

# Check available disk space (in MB)
get_available_space_mb() {
    df -m . | awk 'NR==2 {print $4}'
}

# Print check result
print_check() {
    local status=$1
    local name=$2
    local details=$3
    
    if [ "$status" = "ok" ]; then
        echo -e "  ${GREEN}✓${NC} $name ${CYAN}$details${NC}"
    elif [ "$status" = "warn" ]; then
        echo -e "  ${YELLOW}⚠${NC} $name ${YELLOW}$details${NC}"
    else
        echo -e "  ${RED}✗${NC} $name ${RED}$details${NC}"
    fi
}

# Run all dependency checks
run_dependency_checks() {
    local has_errors=false
    local missing_packages=""
    
    echo -e "${CYAN}${BOLD}System Dependency Check${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Check PHP
    echo -e "${BOLD}PHP:${NC}"
    if command_exists php; then
        local php_version=$(php -v | head -n1 | cut -d' ' -f2)
        if check_php_version; then
            print_check "ok" "PHP installed" "(v$php_version)"
        else
            print_check "fail" "PHP version" "(v$php_version - need $MIN_PHP_VERSION+)"
            has_errors=true
            missing_packages="$missing_packages php8.2 php8.2-cli"
        fi
    else
        print_check "fail" "PHP not installed" ""
        has_errors=true
        missing_packages="$missing_packages php8.2 php8.2-cli"
    fi
    
    # Check PHP Extensions
    echo ""
    echo -e "${BOLD}PHP Extensions:${NC}"
    local required_extensions=("pdo" "mbstring" "xml" "curl" "zip" "tokenizer" "ctype" "json" "openssl")
    local db_extension="pdo_mysql"
    
    if [ "$DB_TYPE" = "pgsql" ]; then
        db_extension="pdo_pgsql"
    elif [ "$DB_TYPE" = "sqlite" ]; then
        db_extension="pdo_sqlite"
    fi
    required_extensions+=("$db_extension")
    
    for ext in "${required_extensions[@]}"; do
        if php_extension_loaded "$ext"; then
            print_check "ok" "$ext" ""
        else
            print_check "fail" "$ext" "(missing)"
            has_errors=true
            # Map extension to Ubuntu package name
            case "$ext" in
                pdo_mysql) missing_packages="$missing_packages php8.2-mysql" ;;
                pdo_pgsql) missing_packages="$missing_packages php8.2-pgsql" ;;
                pdo_sqlite) missing_packages="$missing_packages php8.2-sqlite3" ;;
                mbstring) missing_packages="$missing_packages php8.2-mbstring" ;;
                xml) missing_packages="$missing_packages php8.2-xml" ;;
                curl) missing_packages="$missing_packages php8.2-curl" ;;
                zip) missing_packages="$missing_packages php8.2-zip" ;;
            esac
        fi
    done
    
    # Check Composer
    echo ""
    echo -e "${BOLD}Composer:${NC}"
    if command_exists composer; then
        local composer_version=$(composer --version 2>/dev/null | head -n1)
        print_check "ok" "Composer installed" "($composer_version)"
    else
        print_check "fail" "Composer not installed" ""
        has_errors=true
        missing_packages="$missing_packages composer"
    fi
    
    # Check Database (optional)
    echo ""
    echo -e "${BOLD}Database ($DB_TYPE):${NC}"
    if [ "$DB_TYPE" = "sqlite" ]; then
        print_check "ok" "SQLite" "(no server required)"
    elif [ "$DB_TYPE" = "mysql" ]; then
        if command_exists mysql; then
            if check_mysql_connection; then
                print_check "ok" "MySQL connection" "($DB_USER@$DB_HOST:$DB_PORT)"
            else
                print_check "warn" "MySQL installed but cannot connect" "(will retry during install)"
            fi
        else
            print_check "warn" "MySQL client not installed" "(optional for connection test)"
        fi
    elif [ "$DB_TYPE" = "pgsql" ]; then
        if command_exists psql; then
            print_check "ok" "PostgreSQL client" "(installed)"
        else
            print_check "warn" "PostgreSQL client not installed" "(optional)"
        fi
    fi
    
    # Check other tools
    echo ""
    echo -e "${BOLD}Other Tools:${NC}"
    if command_exists jq; then
        print_check "ok" "jq" "(JSON processor - optional)"
    else
        print_check "warn" "jq not installed" "(will use sed fallback)"
    fi
    
    if command_exists git; then
        print_check "ok" "git" "(optional - for version control)"
    else
        print_check "warn" "git not installed" "(optional)"
    fi
    
    # Check system resources
    echo ""
    echo -e "${BOLD}System Resources:${NC}"
    local available_space=$(get_available_space_mb)
    if [ "$available_space" -gt 500 ]; then
        print_check "ok" "Disk space" "(${available_space}MB available)"
    else
        print_check "warn" "Low disk space" "(${available_space}MB - recommend 500MB+)"
    fi
    
    if check_writable "."; then
        print_check "ok" "Directory writable" "($(pwd))"
    else
        print_check "fail" "Directory not writable" "($(pwd))"
        has_errors=true
    fi
    
    # Check offline cache
    echo ""
    echo -e "${BOLD}Offline Cache:${NC}"
    if [ -d "$LARAVEL_CACHE" ] && [ -f "$LARAVEL_CACHE/composer.json" ]; then
        print_check "ok" "Laravel cache available" "($LARAVEL_CACHE)"
    else
        if [ "$OFFLINE_MODE" = true ]; then
            print_check "fail" "Offline cache not found" "(run --prepare-offline first)"
            has_errors=true
        else
            print_check "warn" "Offline cache not prepared" "(use --prepare-offline)"
        fi
    fi
    
    # Summary
    echo ""
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    
    if [ "$has_errors" = true ]; then
        echo -e "${RED}${BOLD}Some dependencies are missing!${NC}"
        echo ""
        
        # Remove duplicates from missing packages
        missing_packages=$(echo "$missing_packages" | tr ' ' '\n' | sort -u | tr '\n' ' ')
        
        if [ -n "$missing_packages" ]; then
            echo -e "${YELLOW}To install missing dependencies, run:${NC}"
            echo ""
            echo -e "  ${CYAN}# Add PHP repository (if needed)${NC}"
            echo -e "  sudo add-apt-repository -y ppa:ondrej/php"
            echo -e "  sudo apt update"
            echo ""
            echo -e "  ${CYAN}# Install missing packages${NC}"
            echo -e "  sudo apt install -y$missing_packages"
            echo ""
            echo -e "${YELLOW}Or run this script with --install-deps to auto-install:${NC}"
            echo -e "  $0 --install-deps"
        fi
        
        return 1
    else
        echo -e "${GREEN}${BOLD}All dependencies satisfied!${NC}"
        return 0
    fi
}

# Install missing dependencies automatically
install_dependencies() {
    echo -e "${CYAN}${BOLD}Installing System Dependencies${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Check if running as root or with sudo
    if [ "$EUID" -ne 0 ]; then
        if ! command_exists sudo; then
            echo -e "${RED}Error: This script needs sudo to install packages${NC}"
            exit 1
        fi
        SUDO="sudo"
    else
        SUDO=""
    fi
    
    echo -e "${YELLOW}This will install the following packages:${NC}"
    echo "  - PHP 8.2 and common extensions"
    echo "  - Composer"
    echo "  - jq (JSON processor)"
    echo ""
    
    if [ "$QUICK_MODE" = false ]; then
        echo -e "${YELLOW}Press Enter to continue or Ctrl+C to cancel...${NC}"
        read
    fi
    
    # Add PHP repository
    echo -e "${CYAN}Adding PHP repository...${NC}"
    $SUDO apt-get update -qq
    $SUDO apt-get install -y software-properties-common
    $SUDO add-apt-repository -y ppa:ondrej/php
    $SUDO apt-get update -qq
    
    # Install PHP and extensions
    echo -e "${CYAN}Installing PHP 8.2...${NC}"
    $SUDO apt-get install -y \
        php8.2 \
        php8.2-cli \
        php8.2-common \
        php8.2-mysql \
        php8.2-pgsql \
        php8.2-sqlite3 \
        php8.2-mbstring \
        php8.2-xml \
        php8.2-curl \
        php8.2-zip \
        php8.2-bcmath \
        php8.2-gd \
        php8.2-intl
    
    # Install Composer
    if ! command_exists composer; then
        echo -e "${CYAN}Installing Composer...${NC}"
        cd /tmp
        curl -sS https://getcomposer.org/installer | php
        $SUDO mv composer.phar /usr/local/bin/composer
        $SUDO chmod +x /usr/local/bin/composer
        cd - > /dev/null
    fi
    
    # Install optional tools
    echo -e "${CYAN}Installing optional tools...${NC}"
    $SUDO apt-get install -y jq git unzip
    
    echo ""
    echo -e "${GREEN}${BOLD}Dependencies installed successfully!${NC}"
    echo ""
    
    # Re-run checks
    run_dependency_checks
}

# Prepare offline cache
prepare_offline_cache() {
    echo -e "${CYAN}${BOLD}Preparing Offline Cache${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    # Check dependencies first
    if ! command_exists composer; then
        echo -e "${RED}Error: Composer is required to prepare offline cache${NC}"
        exit 1
    fi
    
    if ! command_exists php; then
        echo -e "${RED}Error: PHP is required to prepare offline cache${NC}"
        exit 1
    fi
    
    # Create cache directory
    mkdir -p "$OFFLINE_CACHE"
    
    # Step 1: Create a fresh Laravel project for caching
    echo -e "${CYAN}Step 1: Downloading Laravel project...${NC}"
    if [ -d "$LARAVEL_CACHE" ]; then
        echo -e "${YELLOW}Removing existing cache...${NC}"
        rm -rf "$LARAVEL_CACHE"
    fi
    
    cd "$OFFLINE_CACHE"
    composer create-project laravel/laravel laravel-project --no-interaction
    
    # Step 2: Add easy-pack to the cached project
    echo -e "${CYAN}Step 2: Adding Easy Pack dependencies...${NC}"
    cd "$LARAVEL_CACHE"
    
    # Add local repository
    if command_exists jq; then
        jq --arg path "$EASYPACK_PATH" '.repositories = [{"type": "path", "url": $path, "options": {"symlink": false}}]' composer.json > composer.json.tmp
        mv composer.json.tmp composer.json
    else
        sed -i '2i\    "repositories": [{"type": "path", "url": "'"$EASYPACK_PATH"'", "options": {"symlink": false}}],' composer.json
    fi
    
    # Install easy-pack (this downloads all dependencies)
    composer require easypack/starter:@dev --no-interaction
    
    # Step 3: Cache the vendor directory
    echo -e "${CYAN}Step 3: Caching vendor packages...${NC}"
    
    # Create a packages cache from composer cache
    COMPOSER_CACHE_DIR=$(composer config cache-dir 2>/dev/null || echo "$HOME/.cache/composer")
    if [ -d "$COMPOSER_CACHE_DIR" ]; then
        echo -e "${CYAN}Copying composer cache...${NC}"
        cp -r "$COMPOSER_CACHE_DIR" "$OFFLINE_CACHE/composer-cache"
    fi
    
    cd "$SCRIPT_DIR"
    
    echo ""
    echo -e "${GREEN}${BOLD}Offline cache prepared successfully!${NC}"
    echo ""
    echo -e "${CYAN}Cache location:${NC} $OFFLINE_CACHE"
    echo -e "${CYAN}Cache size:${NC} $(du -sh "$OFFLINE_CACHE" | cut -f1)"
    echo ""
    echo -e "${YELLOW}You can now copy the entire folder to an offline machine and run:${NC}"
    echo -e "  ./install-local.sh my-project --offline --quick"
    echo ""
}

# ============================================================================
# ARGUMENT PARSING
# ============================================================================

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --db-name=*)
            DB_NAME="${1#*=}"
            shift
            ;;
        --db-user=*)
            DB_USER="${1#*=}"
            shift
            ;;
        --db-password=*)
            DB_PASSWORD="${1#*=}"
            shift
            ;;
        --db-host=*)
            DB_HOST="${1#*=}"
            shift
            ;;
        --db-port=*)
            DB_PORT="${1#*=}"
            shift
            ;;
        --db=*)
            DB_TYPE="${1#*=}"
            shift
            ;;
        --quick)
            QUICK_MODE=true
            shift
            ;;
        --with-docs)
            WITH_DOCS=true
            shift
            ;;
        --check-deps)
            CHECK_DEPS_ONLY=true
            shift
            ;;
        --install-deps)
            INSTALL_DEPS=true
            shift
            ;;
        --offline)
            OFFLINE_MODE=true
            shift
            ;;
        --prepare-offline)
            PREPARE_OFFLINE=true
            shift
            ;;
        --help)
            head -45 "$0" | tail -40
            exit 0
            ;;
        -*)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
        *)
            if [ -z "$PROJECT_NAME" ]; then
                PROJECT_NAME="$1"
            fi
            shift
            ;;
    esac
done

# ============================================================================
# MAIN SCRIPT
# ============================================================================

# Header
echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║       Easy Pack - Portable Local Development Installer       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Handle special modes first
if [ "$CHECK_DEPS_ONLY" = true ]; then
    run_dependency_checks
    exit $?
fi

if [ "$INSTALL_DEPS" = true ]; then
    install_dependencies
    exit $?
fi

if [ "$PREPARE_OFFLINE" = true ]; then
    prepare_offline_cache
    exit $?
fi

# Verify easy-pack path exists
if [ ! -f "$EASYPACK_PATH/composer.json" ]; then
    echo -e "${RED}Error: Easy Pack not found at: $EASYPACK_PATH${NC}"
    echo -e "${YELLOW}Make sure this script is in the same directory as the easy-pack folder${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Easy Pack found at: $EASYPACK_PATH"
echo ""

# Run pre-flight dependency check (non-blocking in quick mode)
echo -e "${CYAN}Running pre-flight checks...${NC}"
echo ""

if ! run_dependency_checks; then
    echo ""
    if [ "$QUICK_MODE" = true ]; then
        echo -e "${YELLOW}Warning: Some dependencies are missing. Installation may fail.${NC}"
        echo -e "${YELLOW}Continuing in quick mode...${NC}"
        echo ""
    else
        echo -e "${RED}Please install missing dependencies before continuing.${NC}"
        echo -e "${YELLOW}Use --install-deps to auto-install, or --quick to skip this check.${NC}"
        exit 1
    fi
fi

echo ""

# Get project name if not provided
if [ -z "$PROJECT_NAME" ]; then
    echo -e "${YELLOW}Enter project name:${NC}"
    read -p "> " PROJECT_NAME
    
    if [ -z "$PROJECT_NAME" ]; then
        echo -e "${RED}Project name is required${NC}"
        exit 1
    fi
fi

# Set default database name based on project name
if [ -z "$DB_NAME" ]; then
    DB_NAME=$(echo "$PROJECT_NAME" | tr '-' '_' | tr '[:upper:]' '[:lower:]')
fi

# Check if directory already exists
if [ -d "$PROJECT_NAME" ]; then
    echo -e "${RED}Error: Directory '$PROJECT_NAME' already exists${NC}"
    exit 1
fi

# Show configuration
echo -e "${CYAN}Configuration:${NC}"
echo "  Project Name: $PROJECT_NAME"
echo "  Database: $DB_TYPE ($DB_NAME@$DB_HOST:$DB_PORT)"
echo "  DB User: $DB_USER"
echo "  Quick Mode: $QUICK_MODE"
echo "  Offline Mode: $OFFLINE_MODE"
echo "  With Docs: $WITH_DOCS"
echo ""

if [ "$QUICK_MODE" = false ]; then
    echo -e "${YELLOW}Press Enter to continue or Ctrl+C to cancel...${NC}"
    read
fi

# Step 1: Create Laravel project
echo -e "${CYAN}Step 1: Creating Laravel project...${NC}"

if [ "$OFFLINE_MODE" = true ]; then
    # Offline installation: copy from cache
    if [ ! -d "$LARAVEL_CACHE" ] || [ ! -f "$LARAVEL_CACHE/composer.json" ]; then
        echo -e "${RED}Error: Offline cache not found at: $LARAVEL_CACHE${NC}"
        echo -e "${YELLOW}Run --prepare-offline first on a machine with internet access${NC}"
        exit 1
    fi
    
    echo -e "${CYAN}Copying from offline cache...${NC}"
    cp -r "$LARAVEL_CACHE" "$PROJECT_NAME"
    cd "$PROJECT_NAME"
    
    # Update the repository path to point to the new location
    if command_exists jq; then
        jq --arg path "$EASYPACK_PATH" '.repositories = [{"type": "path", "url": $path, "options": {"symlink": true}}]' composer.json > composer.json.tmp
        mv composer.json.tmp composer.json
    else
        # Remove old repository and add new one
        sed -i 's|"url": "[^"]*easy-pack"|"url": "'"$EASYPACK_PATH"'"|g' composer.json
    fi
    
    # Use cached composer packages
    if [ -d "$OFFLINE_CACHE/composer-cache" ]; then
        export COMPOSER_CACHE_DIR="$OFFLINE_CACHE/composer-cache"
    fi
    
    # Regenerate autoloader with symlink to local easy-pack
    composer dump-autoload
    
    echo -e "${GREEN}✓${NC} Laravel project created from cache"
else
    # Online installation: download fresh
    composer create-project laravel/laravel "$PROJECT_NAME"
    cd "$PROJECT_NAME"
    echo -e "${GREEN}✓${NC} Laravel project created"
fi

# Step 2: Add local easy-pack repository
echo -e "${CYAN}Step 2: Configuring local Easy Pack repository...${NC}"

if [ "$OFFLINE_MODE" = false ]; then
    # Only need to configure repository for online mode
    # (offline mode already has it configured from cache)
    
    # Read current composer.json
    COMPOSER_JSON=$(cat composer.json)

    # Add repositories section with local path
    # Using jq if available, otherwise use sed
    if command_exists jq; then
        echo "$COMPOSER_JSON" | jq --arg path "$EASYPACK_PATH" '.repositories = [{"type": "path", "url": $path, "options": {"symlink": true}}]' > composer.json.tmp
        mv composer.json.tmp composer.json
    else
        # Fallback: Add repositories after the first {
        sed -i '2i\    "repositories": [{"type": "path", "url": "'"$EASYPACK_PATH"'", "options": {"symlink": true}}],' composer.json
    fi
fi

echo -e "${GREEN}✓${NC} Local repository configured"

# Step 3: Install easy-pack
echo -e "${CYAN}Step 3: Installing Easy Pack...${NC}"

if [ "$OFFLINE_MODE" = true ]; then
    # In offline mode, easy-pack should already be in vendor from cache
    # Just need to ensure symlink is created
    if [ -d "vendor/easypack/starter" ]; then
        rm -rf vendor/easypack/starter
    fi
    mkdir -p vendor/easypack
    ln -sf "$EASYPACK_PATH" vendor/easypack/starter
    composer dump-autoload
    echo -e "${GREEN}✓${NC} Easy Pack linked from cache"
else
    # Online mode: require the package
    composer require easypack/starter:@dev --no-interaction
    echo -e "${GREEN}✓${NC} Easy Pack installed"
fi

# Step 4: Run easypack:install
echo -e "${CYAN}Step 4: Running Easy Pack installation...${NC}"

INSTALL_CMD="php artisan easypack:install"
INSTALL_CMD="$INSTALL_CMD --app-name=\"$PROJECT_NAME\""
INSTALL_CMD="$INSTALL_CMD --db=$DB_TYPE"
INSTALL_CMD="$INSTALL_CMD --db-name=$DB_NAME"
INSTALL_CMD="$INSTALL_CMD --db-user=$DB_USER"
INSTALL_CMD="$INSTALL_CMD --db-host=$DB_HOST"
INSTALL_CMD="$INSTALL_CMD --db-port=$DB_PORT"

if [ -n "$DB_PASSWORD" ]; then
    INSTALL_CMD="$INSTALL_CMD --db-password=\"$DB_PASSWORD\""
fi

if [ "$QUICK_MODE" = true ]; then
    INSTALL_CMD="$INSTALL_CMD --quick"
    # Quick mode implies with-docs for better DX
    INSTALL_CMD="$INSTALL_CMD --with-docs"
fi

if [ "$WITH_DOCS" = true ]; then
    INSTALL_CMD="$INSTALL_CMD --with-docs"
fi

INSTALL_CMD="$INSTALL_CMD --force"

eval $INSTALL_CMD

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║            Installation Complete! 🎉                          ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${CYAN}Project Location:${NC} $(pwd)"
echo ""
echo -e "${CYAN}Quick Start:${NC}"
echo "  cd $PROJECT_NAME"
echo "  php artisan serve"
echo ""
echo -e "${CYAN}Test API:${NC}"
echo "  curl http://localhost:8000/api/v1/guests"
echo ""
echo -e "${CYAN}Login Credentials:${NC}"
echo "  Email: admin@example.com"
echo "  Password: password"
echo ""
echo -e "${CYAN}API Documentation:${NC}"
echo "  http://localhost:8000/docs/swagger.html"
echo ""
echo -e "${CYAN}Portability Tips:${NC}"
echo "  To use on another machine, copy the entire parent folder and run:"
echo "  ./install-local.sh --check-deps    # Check dependencies"
echo "  ./install-local.sh --install-deps  # Install if needed"
echo ""
