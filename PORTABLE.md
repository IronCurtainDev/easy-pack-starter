# Easy Pack - Portable Installation Guide

This guide explains how to copy the Easy Pack folder to another machine (Ubuntu or Windows) and install Easy Pack projects without issues.

## Quick Start

### Ubuntu/Linux

#### On Your Current Machine (with everything working)

```bash
# Optional: Prepare offline cache for machines without internet
./install-local.sh --prepare-offline
```

#### On the New Ubuntu Machine

```bash
# 1. Copy the folder (USB, SCP, etc.)
scp -r /path/to/easy-pack-starter user@new-machine:/home/user/

# 2. Navigate to the folder
cd /home/user/easy-pack-starter

# 3. Check what dependencies are missing
./install-local.sh --check-deps

# 4. Install missing dependencies (requires sudo)
./install-local.sh --install-deps

# 5. Create your project
./install-local.sh my-project --quick
```

### Windows

#### On Your Current Machine (with everything working)

```powershell
# Optional: Prepare offline cache for machines without internet
.\install-local.ps1 -PrepareOffline
```

#### On the New Windows Machine

```powershell
# 1. Copy the folder (USB, network share, etc.)
# Example: Copy-Item -Recurse C:\source\easy-pack-starter D:\destination\

# 2. Navigate to the folder
cd D:\destination\easy-pack-starter

# 3. Check what dependencies are missing
.\install-local.ps1 -CheckDeps

# 4. Install missing dependencies (installs Chocolatey if needed)
.\install-local.ps1 -InstallDeps

# 5. Create your project
.\install-local.ps1 my-project -Quick
```

## Installation Options

### Online Installation (Internet Required)

**Ubuntu/Linux:**
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

**Windows:**
```powershell
# Standard installation
.\install-local.ps1 my-project

# Quick mode (skip prompts)
.\install-local.ps1 my-project -Quick

# Custom database settings
.\install-local.ps1 my-project `
    -DbName mydb `
    -DbUser myuser `
    -DbPassword secret `
    -Quick
```

### Offline Installation (No Internet)

First, prepare the cache on a machine with internet:

**Ubuntu/Linux:**
```bash
./install-local.sh --prepare-offline
```

**Windows:**
```powershell
.\install-local.ps1 -PrepareOffline
```

Then on the offline machine:

**Ubuntu/Linux:**
```bash
./install-local.sh my-project --offline --quick
```

**Windows:**
```powershell
.\install-local.ps1 my-project -Offline -Quick
```

### Database Options

**Ubuntu/Linux:**
```bash
# MySQL (default)
./install-local.sh my-project --db=mysql

# PostgreSQL
./install-local.sh my-project --db=pgsql

# SQLite (simplest, no server needed)
./install-local.sh my-project --db=sqlite --quick
```

**Windows:**
```powershell
# MySQL (default)
.\install-local.ps1 my-project -DbType mysql

# PostgreSQL
.\install-local.ps1 my-project -DbType pgsql

# SQLite (simplest, no server needed)
.\install-local.ps1 my-project -DbType sqlite -Quick
```

## Command Reference

| Ubuntu/Linux | Windows | Description |
|--------------|---------|-------------|
| `--check-deps` | `-CheckDeps` | Check system dependencies without installing |
| `--install-deps` | `-InstallDeps` | Auto-install missing dependencies |
| `--prepare-offline` | `-PrepareOffline` | Download and cache packages for offline use |
| `--offline` | `-Offline` | Use cached packages (no internet required) |
| `--quick` | `-Quick` | Skip interactive prompts, use defaults |
| `--db=TYPE` | `-DbType TYPE` | Database type: mysql, pgsql, sqlite |
| `--db-name=NAME` | `-DbName NAME` | Database name |
| `--db-user=USER` | `-DbUser USER` | Database username |
| `--db-password=PASS` | `-DbPassword PASS` | Database password |
| `--db-host=HOST` | `-DbHost HOST` | Database host (default: 127.0.0.1) |
| `--db-port=PORT` | `-DbPort PORT` | Database port (default: 3306) |
| `--with-docs` | `-WithDocs` | Generate API documentation |
| `--help` | `-Help` | Show help message |

## System Requirements

### Required

- **Operating System**: 
  - Ubuntu 20.04, 22.04, or 24.04 (or compatible Linux)
  - Windows 10/11 or Windows Server 2019+
- **PHP 8.2+** with extensions:
  - pdo, pdo_mysql (or pdo_pgsql/pdo_sqlite)
  - mbstring, xml, curl, zip
  - tokenizer, ctype, json, openssl
- **Composer** (latest version)

### Optional

- **MySQL 5.7+** or **PostgreSQL 9.6+** (not needed for SQLite)
- **jq** (JSON processor, has fallback) - Ubuntu only
- **git** (for version control)
- **Chocolatey** (Windows package manager) - recommended for Windows

## Manual Dependency Installation

### Ubuntu/Linux

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

### Windows

**Option 1: Using Chocolatey (Recommended)**

```powershell
# Install Chocolatey (if not already installed)
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Install PHP 8.2
choco install php -y --version=8.2.0

# Install Composer
choco install composer -y

# Install optional tools
choco install git -y

# Install MySQL (if using MySQL)
choco install mysql -y
```

**Option 2: Manual Download**

1. **PHP 8.2**: Download from https://windows.php.net/download/
   - Download "Thread Safe" ZIP for your architecture (x64/x86)
   - Extract to `C:\php`
   - Add `C:\php` to your system PATH
   - Copy `php.ini-development` to `php.ini`
   - Enable required extensions in `php.ini`:
     ```ini
     extension=pdo_mysql
     extension=mbstring
     extension=openssl
     extension=curl
     extension=fileinfo
     extension=zip
     ```

2. **Composer**: Download from https://getcomposer.org/download/
   - Run the Windows installer
   - Follow the setup wizard

3. **MySQL** (optional): Download from https://dev.mysql.com/downloads/installer/
   - Run the installer
   - Choose "Developer Default" or "Server only"
   - Follow the setup wizard

## Troubleshooting

### Ubuntu/Linux

#### "PHP not found"

```bash
./install-local.sh --install-deps
# Or manually:
sudo apt install php8.2 php8.2-cli
```

#### "Composer not found"

```bash
./install-local.sh --install-deps
# Or manually:
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

#### "Database connection failed"

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

#### "Permission denied"

```bash
chmod +x install-local.sh
```

#### "Offline cache not found"

You need to prepare the cache first on a machine with internet:
```bash
./install-local.sh --prepare-offline
```

Then copy the entire folder (including `.offline-cache/`) to the offline machine.

---

### Windows

#### "PHP not found"

```powershell
.\install-local.ps1 -InstallDeps
# Or manually: Download from https://windows.php.net/download/
```

#### "Composer not found"

```powershell
.\install-local.ps1 -InstallDeps
# Or manually: Download from https://getcomposer.org/download/
```

#### "Database connection failed"

For SQLite (easiest, no server needed):
```powershell
.\install-local.ps1 my-project -DbType sqlite -Quick
```

For MySQL:
```powershell
# Start MySQL service
Start-Service MySQL

# Create database (using MySQL client)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS my_project;"

# Then install
.\install-local.ps1 my-project -DbName my_project -Quick
```

#### "Execution policy" errors

If you get an error about script execution being disabled:
```powershell
# Run as Administrator
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

#### "Offline cache not found"

You need to prepare the cache first on a machine with internet:
```powershell
.\install-local.ps1 -PrepareOffline
```

Then copy the entire folder (including `.offline-cache\`) to the offline machine.

## Folder Structure

After copying, your folder should look like this:

```
easy-pack-starter/
├── install-local.sh          # Installer script (Ubuntu/Linux)
├── install-local.ps1         # Installer script (Windows)
├── PORTABLE.md               # This documentation
├── README.md                 # Package documentation
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
