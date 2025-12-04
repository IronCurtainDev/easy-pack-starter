# Easy Pack - Portable Installation Guide

This guide explains how to copy the `new_oxygen` folder to another Ubuntu machine and install Easy Pack projects without issues.

## Quick Start

### On Your Current Machine (with everything working)

```bash
# Optional: Prepare offline cache for machines without internet
./install-local.sh --prepare-offline
```

### On the New Ubuntu Machine

```bash
# 1. Copy the folder (USB, SCP, etc.)
scp -r /path/to/new_oxygen user@new-machine:/home/user/

# 2. Navigate to the folder
cd /home/user/new_oxygen

# 3. Check what dependencies are missing
./install-local.sh --check-deps

# 4. Install missing dependencies (requires sudo)
./install-local.sh --install-deps

# 5. Create your project
./install-local.sh my-project --quick
```

## Installation Options

### Online Installation (Internet Required)

```bash
# Standard installation
./install-local.sh my-project

# Quick mode (skip prompts)
./install-local.sh my-project --quick

# Custom database settings
./install-local.sh my-project \
    --db-name=mydb \
    --db-user=myuser \
    --db-password=secret \
    --quick
```

### Offline Installation (No Internet)

First, prepare the cache on a machine with internet:

```bash
./install-local.sh --prepare-offline
```

Then on the offline machine:

```bash
./install-local.sh my-project --offline --quick
```

### Database Options

```bash
# MySQL (default)
./install-local.sh my-project --db=mysql

# PostgreSQL
./install-local.sh my-project --db=pgsql

# SQLite (simplest, no server needed)
./install-local.sh my-project --db=sqlite --quick
```

## Command Reference

| Option | Description |
|--------|-------------|
| `--check-deps` | Check system dependencies without installing |
| `--install-deps` | Auto-install missing dependencies (requires sudo) |
| `--prepare-offline` | Download and cache packages for offline use |
| `--offline` | Use cached packages (no internet required) |
| `--quick` | Skip interactive prompts, use defaults |
| `--db=TYPE` | Database type: mysql, pgsql, sqlite |
| `--db-name=NAME` | Database name |
| `--db-user=USER` | Database username |
| `--db-password=PASS` | Database password |
| `--db-host=HOST` | Database host (default: 127.0.0.1) |
| `--db-port=PORT` | Database port (default: 3306) |
| `--with-docs` | Generate API documentation |
| `--help` | Show help message |

## System Requirements

### Required

- **Ubuntu** 20.04, 22.04, or 24.04 (or compatible Linux)
- **PHP 8.2+** with extensions:
  - pdo, pdo_mysql (or pdo_pgsql/pdo_sqlite)
  - mbstring, xml, curl, zip
  - tokenizer, ctype, json, openssl
- **Composer** (latest version)

### Optional

- **MySQL 5.7+** or **PostgreSQL 9.6+** (not needed for SQLite)
- **jq** (JSON processor, has fallback)
- **git** (for version control)

## Manual Dependency Installation

If you prefer to install dependencies manually:

```bash
# Add PHP repository
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP 8.2 and extensions
sudo apt install -y \
    php8.2 php8.2-cli php8.2-common \
    php8.2-mysql php8.2-pgsql php8.2-sqlite3 \
    php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl

# Install Composer
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install optional tools
sudo apt install -y jq git unzip

# Install MySQL (if using MySQL)
sudo apt install -y mysql-server
sudo mysql -e "CREATE DATABASE IF NOT EXISTS my_project;"
```

## Troubleshooting

### "PHP not found"

```bash
./install-local.sh --install-deps
# Or manually:
sudo apt install php8.2 php8.2-cli
```

### "Composer not found"

```bash
./install-local.sh --install-deps
# Or manually:
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### "Database connection failed"

For SQLite (easiest, no server needed):
```bash
./install-local.sh my-project --db=sqlite --quick
```

For MySQL:
```bash
# Start MySQL
sudo systemctl start mysql

# Create database
sudo mysql -e "CREATE DATABASE IF NOT EXISTS my_project;"

# Then install
./install-local.sh my-project --db-name=my_project --quick
```

### "Permission denied"

```bash
chmod +x install-local.sh
```

### "Offline cache not found"

You need to prepare the cache first on a machine with internet:
```bash
./install-local.sh --prepare-offline
```

Then copy the entire folder (including `.offline-cache/`) to the offline machine.

## Folder Structure

After copying, your folder should look like this:

```
new_oxygen/
├── install-local.sh          # Main installer script
├── PORTABLE.md               # This documentation
├── easy-pack/                # The Easy Pack package
│   ├── composer.json
│   ├── src/
│   ├── config/
│   └── ...
└── .offline-cache/           # Optional: for offline installs
    ├── laravel-project/
    └── composer-cache/
```

## Tips for Different Scenarios

### Development Team Setup

1. Prepare offline cache once
2. Share the folder via network/USB
3. Each developer runs `--check-deps` and `--install-deps`
4. Create projects with `--offline --quick`

### CI/CD Pipeline

```bash
# In your CI script:
./install-local.sh test-project --db=sqlite --quick
cd test-project
php artisan test
```

### Fresh VM/Container

```bash
# All-in-one setup
./install-local.sh --install-deps && ./install-local.sh my-api --db=sqlite --quick
```
