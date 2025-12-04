#!/bin/bash
#
# Oxygen Starter - Local Development Installer
# =============================================
#
# This script automates the installation of a new Laravel project with Oxygen Starter
# when developing locally (before publishing to Packagist).
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
#   --quick              Skip interactive prompts, use defaults
#   --with-docs          Generate API documentation after install
#   --help               Show this help message
#
# Example:
#   ./install-local.sh my-api-project --db-name=my_api --db-user=root --quick
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Default values
PROJECT_NAME=""
DB_NAME=""
DB_USER="root"
DB_PASSWORD=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
QUICK_MODE=false
WITH_DOCS=false

# Get the directory where this script is located (oxygen-starter package)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OXYGEN_PATH="$SCRIPT_DIR"

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
        --quick)
            QUICK_MODE=true
            shift
            ;;
        --with-docs)
            WITH_DOCS=true
            shift
            ;;
        --help)
            head -35 "$0" | tail -30
            exit 0
            ;;
        -*)
            echo -e "${RED}Unknown option: $1${NC}"
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

# Header
echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         Oxygen Starter - Local Development Installer         ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

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

# Verify oxygen-starter path exists
if [ ! -f "$OXYGEN_PATH/composer.json" ]; then
    echo -e "${RED}Error: Oxygen Starter not found at: $OXYGEN_PATH${NC}"
    echo -e "${YELLOW}Make sure this script is in the oxygen-starter directory${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Oxygen Starter found at: $OXYGEN_PATH"
echo ""

# Show configuration
echo -e "${CYAN}Configuration:${NC}"
echo "  Project Name: $PROJECT_NAME"
echo "  Database: MySQL ($DB_NAME@$DB_HOST:$DB_PORT)"
echo "  DB User: $DB_USER"
echo "  Quick Mode: $QUICK_MODE"
echo "  With Docs: $WITH_DOCS"
echo ""

if [ "$QUICK_MODE" = false ]; then
    echo -e "${YELLOW}Press Enter to continue or Ctrl+C to cancel...${NC}"
    read
fi

# Step 1: Create Laravel project
echo -e "${CYAN}Step 1: Creating Laravel project...${NC}"
composer create-project laravel/laravel "$PROJECT_NAME"
cd "$PROJECT_NAME"

echo -e "${GREEN}✓${NC} Laravel project created"

# Step 2: Add local oxygen-starter repository
echo -e "${CYAN}Step 2: Configuring local Oxygen Starter repository...${NC}"

# Read current composer.json
COMPOSER_JSON=$(cat composer.json)

# Add repositories section with local path
# Using jq if available, otherwise use sed
if command -v jq &> /dev/null; then
    echo "$COMPOSER_JSON" | jq --arg path "$OXYGEN_PATH" '.repositories = [{"type": "path", "url": $path, "options": {"symlink": true}}]' > composer.json.tmp
    mv composer.json.tmp composer.json
else
    # Fallback: Add repositories after the first {
    sed -i '2i\    "repositories": [{"type": "path", "url": "'"$OXYGEN_PATH"'", "options": {"symlink": true}}],' composer.json
fi

echo -e "${GREEN}✓${NC} Local repository configured"

# Step 3: Install oxygen-starter
echo -e "${CYAN}Step 3: Installing Oxygen Starter...${NC}"
composer require oxygen/starter:@dev --no-interaction

echo -e "${GREEN}✓${NC} Oxygen Starter installed"

# Step 4: Run oxygen:install
echo -e "${CYAN}Step 4: Running Oxygen installation...${NC}"

INSTALL_CMD="php artisan oxygen:install"
INSTALL_CMD="$INSTALL_CMD --app-name=\"$PROJECT_NAME\""
INSTALL_CMD="$INSTALL_CMD --db=mysql"
INSTALL_CMD="$INSTALL_CMD --db-name=$DB_NAME"
INSTALL_CMD="$INSTALL_CMD --db-user=$DB_USER"
INSTALL_CMD="$INSTALL_CMD --db-host=$DB_HOST"
INSTALL_CMD="$INSTALL_CMD --db-port=$DB_PORT"

if [ -n "$DB_PASSWORD" ]; then
    INSTALL_CMD="$INSTALL_CMD --db-password=\"$DB_PASSWORD\""
fi

if [ "$QUICK_MODE" = true ]; then
    INSTALL_CMD="$INSTALL_CMD --quick"
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
