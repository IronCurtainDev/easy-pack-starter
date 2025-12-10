# ============================================================================
# EXECUTION POLICY ERROR?
# ============================================================================
# If you see "running scripts is disabled on this system", run ONE of these:
#
# Option 1 - Bypass for current session only (recommended):
#   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#   .\install-local.ps1 my-api-project -Quick
#
# Option 2 - Run directly with bypass:
#   powershell -ExecutionPolicy Bypass -File .\install-local.ps1 my-api-project -Quick
#
# Option 3 - Change policy permanently for current user:
#   Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
# ============================================================================

<#
.SYNOPSIS
    Easy Pack - Local Development Installer for Windows

.DESCRIPTION
    This script automates the installation of a new Laravel project with Easy Pack
    when developing locally (before publishing to Packagist).

    PORTABLE: Copy the entire folder to any Windows machine and run this script.

    EXECUTION POLICY: If you get "running scripts is disabled" error, run:
        Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
    Then run this script again.

.PARAMETER ProjectName
    Name of the project to create

.PARAMETER DbName
    Database name (default: project name with underscores)

.PARAMETER DbUser
    Database username (default: root)

.PARAMETER DbPassword
    Database password (default: empty)

.PARAMETER DbHost
    Database host (default: 127.0.0.1)

.PARAMETER DbPort
    Database port (default: 3306)

.PARAMETER DbType
    Database type: mysql, pgsql, sqlite (default: mysql)

.PARAMETER Quick
    Skip interactive prompts, use defaults

.PARAMETER WithDocs
    Generate API documentation after install

.PARAMETER CheckDeps
    Only check dependencies, don't install

.PARAMETER InstallDeps
    Auto-install missing system dependencies

.PARAMETER Offline
    Use offline/cached packages (no internet required)

.PARAMETER PrepareOffline
    Download and cache packages for offline use

.PARAMETER Help
    Show this help message

.EXAMPLE
    .\install-local.ps1 my-api-project -DbName my_api -DbUser root -Quick

.EXAMPLE
    .\install-local.ps1 -CheckDeps

.EXAMPLE
    .\install-local.ps1 -PrepareOffline

.EXAMPLE
    .\install-local.ps1 my-project -Offline -Quick

.EXAMPLE
    # If execution policy blocks the script:
    Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass; .\install-local.ps1 my-api-project -Quick
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string]$ProjectName = "",

    [string]$DbName = "",
    [string]$DbUser = "root",
    [string]$DbPassword = "",
    [string]$DbHost = "127.0.0.1",
    [string]$DbPort = "3306",
    [ValidateSet("mysql", "pgsql", "sqlite")]
    [string]$DbType = "mysql",

    [switch]$Quick,
    [switch]$WithDocs,
    [switch]$CheckDeps,
    [switch]$InstallDeps,
    [switch]$Offline,
    [switch]$PrepareOffline,
    [switch]$Help
)

# Stop on errors
$ErrorActionPreference = "Stop"

# Minimum PHP version required
$MIN_PHP_VERSION = "8.2"

# Colors
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Write-Success($message) {
    Write-ColorOutput Green "✓ $message"
}

function Write-Error-Custom($message) {
    Write-ColorOutput Red "✗ $message"
}

function Write-Warning-Custom($message) {
    Write-ColorOutput Yellow "⚠ $message"
}

function Write-Info($message) {
    Write-ColorOutput Cyan $message
}

# Get script directory
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$EASYPACK_PATH = Join-Path $SCRIPT_DIR "easy-pack"
$OFFLINE_CACHE = Join-Path $SCRIPT_DIR ".offline-cache"
$LARAVEL_CACHE = Join-Path $OFFLINE_CACHE "laravel-project"

# ============================================================================
# DEPENDENCY CHECK FUNCTIONS
# ============================================================================

function Test-CommandExists($command) {
    try {
        if (Get-Command $command -ErrorAction SilentlyContinue) {
            return $true
        }
    }
    catch {
        return $false
    }
    return $false
}

function Get-PhpVersionNumber {
    try {
        $version = php -r "echo PHP_VERSION_ID;" 2>$null
        return [int]$version
    }
    catch {
        return 0
    }
}

function Test-PhpVersion {
    $versionId = Get-PhpVersionNumber
    $minVersionId = 80200  # 8.2.0

    return $versionId -ge $minVersionId
}

function Test-PhpExtension($extension) {
    try {
        $modules = php -m 2>$null | ForEach-Object { $_.Trim() }
        return $modules -contains $extension
    }
    catch {
        return $false
    }
}

function Test-MySQLConnection {
    $exists = Test-CommandExists "mysql"
    if (-not $exists) {
        return $false
    }

    try {
        $mysqlArgs = @("-h", $DbHost, "-P", $DbPort, "-u", $DbUser, "-e", "SELECT 1")
        if ($DbPassword) {
            $mysqlArgs += "-p$DbPassword"
        }

        & mysql $mysqlArgs 2>$null | Out-Null
        return $?
    }
    catch {
        return $false
    }
}

function Get-AvailableSpaceMB {
    $drive = (Get-Location).Drive.Name + ":"
    $disk = Get-PSDrive $drive.TrimEnd(':')
    return [math]::Round($disk.Free / 1MB)
}

function Write-Check($status, $name, $details) {
    switch ($status) {
        "ok" {
            Write-Host "  " -NoNewline
            Write-ColorOutput Green "✓ $name "
            Write-ColorOutput Cyan $details
        }
        "warn" {
            Write-Host "  " -NoNewline
            Write-ColorOutput Yellow "⚠ $name "
            Write-ColorOutput Yellow $details
        }
        "fail" {
            Write-Host "  " -NoNewline
            Write-ColorOutput Red "✗ $name "
            Write-ColorOutput Red $details
        }
    }
}

function Invoke-DependencyChecks {
    $hasErrors = $false
    $missingPackages = @()

    Write-Info ""
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-Host ""
    Write-ColorOutput Cyan "System Dependency Check"
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-Host ""

    # Check PHP
    Write-Host ""
    Write-Host "PHP:" -ForegroundColor White
    if (Test-CommandExists "php") {
        $phpVersion = php -v 2>$null | Select-Object -First 1
        $phpVersion = $phpVersion -replace "^PHP\s+(\S+).*", '$1'

        if (Test-PhpVersion) {
            Write-Check "ok" "PHP installed" "(v$phpVersion)"
        }
        else {
            Write-Check "fail" "PHP version" "(v$phpVersion - need $MIN_PHP_VERSION+)"
            $hasErrors = $true
            $missingPackages += "php"
        }
    }
    else {
        Write-Check "fail" "PHP not installed" ""
        $hasErrors = $true
        $missingPackages += "php"
    }

    # Check PHP Extensions
    Write-Host ""
    Write-Host "PHP Extensions:" -ForegroundColor White
    $requiredExtensions = @("pdo", "mbstring", "xml", "curl", "zip", "tokenizer", "ctype", "json", "openssl")

    switch ($DbType) {
        "mysql" { $requiredExtensions += "pdo_mysql" }
        "pgsql" { $requiredExtensions += "pdo_pgsql" }
        "sqlite" { $requiredExtensions += "pdo_sqlite" }
    }

    foreach ($ext in $requiredExtensions) {
        if (Test-PhpExtension $ext) {
            Write-Check "ok" $ext ""
        }
        else {
            Write-Check "fail" $ext "(missing)"
            $hasErrors = $true
            $missingPackages += "php-$ext"
        }
    }

    # Check Composer
    Write-Host ""
    Write-Host "Composer:" -ForegroundColor White
    if (Test-CommandExists "composer") {
        $composerVersion = composer --version 2>$null | Select-Object -First 1
        Write-Check "ok" "Composer installed" "($composerVersion)"
    }
    else {
        Write-Check "fail" "Composer not installed" ""
        $hasErrors = $true
        $missingPackages += "composer"
    }

    # Check Database
    Write-Host ""
    Write-Host "Database ($DbType):" -ForegroundColor White
    if ($DbType -eq "sqlite") {
        Write-Check "ok" "SQLite" "(no server required)"
    }
    elseif ($DbType -eq "mysql") {
        if (Test-CommandExists "mysql") {
            if (Test-MySQLConnection) {
                Write-Check "ok" "MySQL connection" "($DbUser`@${DbHost}:$DbPort)"
            }
            else {
                Write-Check "warn" "MySQL installed but cannot connect" "(will retry during install)"
            }
        }
        else {
            Write-Check "warn" "MySQL client not installed" "(optional for connection test)"
        }
    }
    elseif ($DbType -eq "pgsql") {
        if (Test-CommandExists "psql") {
            Write-Check "ok" "PostgreSQL client" "(installed)"
        }
        else {
            Write-Check "warn" "PostgreSQL client not installed" "(optional)"
        }
    }

    # Check other tools
    Write-Host ""
    Write-Host "Other Tools:" -ForegroundColor White
    if (Test-CommandExists "git") {
        Write-Check "ok" "git" "(optional - for version control)"
    }
    else {
        Write-Check "warn" "git not installed" "(optional)"
    }

    # Check system resources
    Write-Host ""
    Write-Host "System Resources:" -ForegroundColor White
    $availableSpace = Get-AvailableSpaceMB
    if ($availableSpace -gt 500) {
        Write-Check "ok" "Disk space" "(${availableSpace}MB available)"
    }
    else {
        Write-Check "warn" "Low disk space" "(${availableSpace}MB - recommend 500MB+)"
    }

    $currentPath = Get-Location
    if (Test-Path -Path $currentPath -PathType Container) {
        Write-Check "ok" "Directory writable" "($currentPath)"
    }
    else {
        Write-Check "fail" "Directory not writable" "($currentPath)"
        $hasErrors = $true
    }

    # Check offline cache
    Write-Host ""
    Write-Host "Offline Cache:" -ForegroundColor White
    if ((Test-Path $LARAVEL_CACHE) -and (Test-Path (Join-Path $LARAVEL_CACHE "composer.json"))) {
        Write-Check "ok" "Laravel cache available" "($LARAVEL_CACHE)"
    }
    else {
        if ($Offline) {
            Write-Check "fail" "Offline cache not found" "(run --PrepareOffline first)"
            $hasErrors = $true
        }
        else {
            Write-Check "warn" "Offline cache not prepared" "(use -PrepareOffline)"
        }
    }

    # Summary
    Write-Host ""
    Write-Info "════════════════════════════════════════════════════════════════"

    if ($hasErrors) {
        Write-Host ""
        Write-ColorOutput Red "Some dependencies are missing!"
        Write-Host ""

        if ($missingPackages.Count -gt 0) {
            Write-ColorOutput Yellow "To install missing dependencies:"
            Write-Host ""
            Write-ColorOutput Cyan "Option 1: Use Chocolatey (recommended)"
            Write-Host "  choco install php composer"
            Write-Host ""
            Write-ColorOutput Cyan "Option 2: Manual installation"
            Write-Host "  PHP: https://windows.php.net/download/"
            Write-Host "  Composer: https://getcomposer.org/download/"
            Write-Host ""
            Write-ColorOutput Yellow "Or run this script with -InstallDeps to auto-install:"
            Write-Host "  .\install-local.ps1 -InstallDeps"
        }

        return $false
    }
    else {
        Write-Host ""
        Write-ColorOutput Green "All dependencies satisfied!"
        return $true
    }
}

function Install-Dependencies {
    Write-Info ""
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-ColorOutput Cyan "Installing System Dependencies"
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-Host ""

    # Check if Chocolatey is installed
    if (-not (Test-CommandExists "choco")) {
        Write-ColorOutput Yellow "Chocolatey is not installed. Installing Chocolatey..."
        Write-Host ""

        try {
            Set-ExecutionPolicy Bypass -Scope Process -Force
            [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
            Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

            # Refresh environment variables
            $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")
        }
        catch {
            Write-ColorOutput Red "Failed to install Chocolatey. Please install manually from https://chocolatey.org/"
            exit 1
        }
    }

    Write-ColorOutput Yellow "This will install the following packages:"
    Write-Host "  - PHP 8.2"
    Write-Host "  - Composer"
    Write-Host ""

    if (-not $Quick) {
        Write-ColorOutput Yellow "Press Enter to continue or Ctrl+C to cancel..."
        Read-Host
    }

    # Install PHP
    Write-ColorOutput Cyan "Installing PHP..."
    choco install php -y --version=8.2.0

    # Install Composer
    if (-not (Test-CommandExists "composer")) {
        Write-ColorOutput Cyan "Installing Composer..."
        choco install composer -y
    }

    # Refresh environment variables
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path", "User")

    Write-Host ""
    Write-ColorOutput Green "Dependencies installed successfully!"
    Write-Host ""

    # Re-run checks
    Invoke-DependencyChecks
}

function Start-OfflineCachePreparation {
    Write-Info ""
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-ColorOutput Cyan "Preparing Offline Cache"
    Write-Info "════════════════════════════════════════════════════════════════"
    Write-Host ""

    # Check dependencies first
    if (-not (Test-CommandExists "composer")) {
        Write-ColorOutput Red "Error: Composer is required to prepare offline cache"
        exit 1
    }

    if (-not (Test-CommandExists "php")) {
        Write-ColorOutput Red "Error: PHP is required to prepare offline cache"
        exit 1
    }

    # Create cache directory
    New-Item -ItemType Directory -Force -Path $OFFLINE_CACHE | Out-Null

    # Step 1: Create a fresh Laravel project for caching
    Write-ColorOutput Cyan "Step 1: Downloading Laravel project..."
    if (Test-Path $LARAVEL_CACHE) {
        Write-ColorOutput Yellow "Removing existing cache..."
        Remove-Item -Recurse -Force $LARAVEL_CACHE
    }

    Push-Location $OFFLINE_CACHE
    composer create-project laravel/laravel laravel-project --no-interaction

    # Step 2: Add easy-pack to the cached project
    Write-ColorOutput Cyan "Step 2: Adding Easy Pack dependencies..."
    Push-Location $LARAVEL_CACHE

    # Add local repository to composer.json
    $composerJson = Get-Content "composer.json" -Raw | ConvertFrom-Json
    $composerJson | Add-Member -MemberType NoteProperty -Name "repositories" -Value @(@{
            type    = "path"
            url     = $EASYPACK_PATH
            options = @{
                symlink = $false
            }
        }) -Force
    $composerJson | ConvertTo-Json -Depth 10 | Set-Content "composer.json"

    # Install easy-pack (this downloads all dependencies)
    composer require easypack/starter:@dev --no-interaction

    # Step 3: Cache the vendor directory
    Write-ColorOutput Cyan "Step 3: Caching vendor packages..."

    # Create a packages cache from composer cache
    $composerCacheDir = composer config cache-dir 2>$null
    if (Test-Path $composerCacheDir) {
        Write-ColorOutput Cyan "Copying composer cache..."
        $targetCache = Join-Path $OFFLINE_CACHE "composer-cache"
        Copy-Item -Recurse -Force $composerCacheDir $targetCache
    }

    Pop-Location
    Pop-Location

    Write-Host ""
    Write-ColorOutput Green "Offline cache prepared successfully!"
    Write-Host ""
    Write-ColorOutput Cyan "Cache location: $OFFLINE_CACHE"

    $cacheSize = (Get-ChildItem $OFFLINE_CACHE -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-ColorOutput Cyan "Cache size: $([math]::Round($cacheSize, 2))MB"
    Write-Host ""
    Write-ColorOutput Yellow "You can now copy the entire folder to an offline machine and run:"
    Write-Host "  .\install-local.ps1 my-project -Offline -Quick"
    Write-Host ""
}

# ============================================================================
# MAIN SCRIPT
# ============================================================================

# Show help
if ($Help) {
    Get-Help $MyInvocation.MyCommand.Path -Detailed
    exit 0
}

# Header
Write-Host ""
Write-Info "╔══════════════════════════════════════════════════════════════╗"
Write-Info "║       Easy Pack - Portable Local Development Installer       ║"
Write-Info "╚══════════════════════════════════════════════════════════════╝"
Write-Host ""

# Handle special modes first
if ($CheckDeps) {
    $result = Invoke-DependencyChecks
    if ($result) {
        exit 0
    }
    else {
        exit 1
    }
}

if ($InstallDeps) {
    Install-Dependencies
    exit 0
}

if ($PrepareOffline) {
    Start-OfflineCachePreparation
    exit 0
}

# Verify easy-pack path exists
if (-not (Test-Path (Join-Path $EASYPACK_PATH "composer.json"))) {
    Write-ColorOutput Red "Error: Easy Pack not found at: $EASYPACK_PATH"
    Write-ColorOutput Yellow "Make sure this script is in the same directory as the easy-pack folder"
    exit 1
}

Write-Success "Easy Pack found at: $EASYPACK_PATH"
Write-Host ""

# Run pre-flight dependency check (non-blocking in quick mode)
Write-ColorOutput Cyan "Running pre-flight checks..."
Write-Host ""

$checkResult = Invoke-DependencyChecks

if (-not $checkResult) {
    Write-Host ""
    if ($Quick) {
        Write-ColorOutput Yellow "Warning: Some dependencies are missing. Installation may fail."
        Write-ColorOutput Yellow "Continuing in quick mode..."
        Write-Host ""
    }
    else {
        Write-ColorOutput Red "Please install missing dependencies before continuing."
        Write-ColorOutput Yellow "Use -InstallDeps to auto-install, or -Quick to skip this check."
        exit 1
    }
}

Write-Host ""

# Get project name if not provided
if (-not $ProjectName) {
    Write-ColorOutput Yellow "Enter project name:"
    $ProjectName = Read-Host "> "

    if (-not $ProjectName) {
        Write-ColorOutput Red "Project name is required"
        exit 1
    }
}

# Set default database name based on project name
if (-not $DbName) {
    $DbName = $ProjectName -replace '-', '_' | ForEach-Object { $_.ToLower() }
}

# Check if directory already exists
if (Test-Path $ProjectName) {
    Write-ColorOutput Red "Error: Directory '$ProjectName' already exists"
    exit 1
}

# Show configuration
Write-ColorOutput Cyan "Configuration:"
Write-Host "  Project Name: $ProjectName"
Write-Host "  Database: $DbType ($DbName`@${DbHost}:$DbPort)"
Write-Host "  DB User: $DbUser"
Write-Host "  Quick Mode: $Quick"
Write-Host "  Offline Mode: $Offline"
Write-Host "  With Docs: $WithDocs"
Write-Host ""

if (-not $Quick) {
    Write-ColorOutput Yellow "Press Enter to continue or Ctrl+C to cancel..."
    Read-Host
}

# Step 1: Create Laravel project
Write-ColorOutput Cyan "Step 1: Creating Laravel project..."

if ($Offline) {
    # Offline installation: copy from cache
    if (-not (Test-Path $LARAVEL_CACHE) -or -not (Test-Path (Join-Path $LARAVEL_CACHE "composer.json"))) {
        Write-ColorOutput Red "Error: Offline cache not found at: $LARAVEL_CACHE"
        Write-ColorOutput Yellow "Run -PrepareOffline first on a machine with internet access"
        exit 1
    }

    Write-ColorOutput Cyan "Copying from offline cache..."
    Copy-Item -Recurse $LARAVEL_CACHE $ProjectName
    Push-Location $ProjectName

    # Update the repository path to point to the new location
    $composerJson = Get-Content "composer.json" -Raw | ConvertFrom-Json
    $composerJson.repositories[0].url = $EASYPACK_PATH
    $composerJson.repositories[0].options.symlink = $true
    $composerJson | ConvertTo-Json -Depth 10 | Set-Content "composer.json"

    # Use cached composer packages
    $cachedComposerCache = Join-Path $OFFLINE_CACHE "composer-cache"
    if (Test-Path $cachedComposerCache) {
        $env:COMPOSER_CACHE_DIR = $cachedComposerCache
    }

    # Regenerate autoloader with symlink to local easy-pack
    composer dump-autoload

    Write-Success "Laravel project created from cache"
}
else {
    # Online installation: download fresh
    composer create-project laravel/laravel $ProjectName
    Push-Location $ProjectName
    Write-Success "Laravel project created"
}

# Step 2: Add local easy-pack repository
Write-ColorOutput Cyan "Step 2: Configuring local Easy Pack repository..."

if (-not $Offline) {
    # Only need to configure repository for online mode
    # (offline mode already has it configured from cache)

    # Read current composer.json
    $composerJson = Get-Content "composer.json" -Raw | ConvertFrom-Json

    # Add repositories section with local path
    $composerJson | Add-Member -MemberType NoteProperty -Name "repositories" -Value @(@{
            type    = "path"
            url     = $EASYPACK_PATH
            options = @{
                symlink = $true
            }
        }) -Force

    $composerJson | ConvertTo-Json -Depth 10 | Set-Content "composer.json"
}

Write-Success "Local repository configured"

# Step 3: Install easy-pack
Write-ColorOutput Cyan "Step 3: Installing Easy Pack..."

if ($Offline) {
    # In offline mode, easy-pack should already be in vendor from cache
    # Just need to ensure symlink is created
    $vendorEasypackPath = "vendor\easypack\starter"
    if (Test-Path $vendorEasypackPath) {
        Remove-Item -Recurse -Force $vendorEasypackPath
    }
    New-Item -ItemType Directory -Force -Path "vendor\easypack" | Out-Null

    # Create junction instead of symlink (works without admin rights)
    cmd /c mklink /J "$vendorEasypackPath" "$EASYPACK_PATH" | Out-Null

    composer dump-autoload
    Write-Success "Easy Pack linked from cache"
}
else {
    # Online mode: require the package
    composer require easypack/starter:@dev --no-interaction
    Write-Success "Easy Pack installed"
}

# Step 4: Run easypack:install
Write-ColorOutput Cyan "Step 4: Running Easy Pack installation..."

$installCmd = "php artisan easypack:install"
$installCmd += " --app-name=`"$ProjectName`""
$installCmd += " --db=$DbType"
$installCmd += " --db-name=$DbName"
$installCmd += " --db-user=$DbUser"
$installCmd += " --db-host=$DbHost"
$installCmd += " --db-port=$DbPort"

if ($DbPassword) {
    $installCmd += " --db-password=`"$DbPassword`""
}

if ($Quick) {
    $installCmd += " --quick"
    # Quick mode implies with-docs for better DX
    $installCmd += " --with-docs"
}

if ($WithDocs) {
    $installCmd += " --with-docs"
}

$installCmd += " --force"

Invoke-Expression $installCmd

Write-Host ""
Write-ColorOutput Green "╔══════════════════════════════════════════════════════════════╗"
Write-ColorOutput Green "║            Installation Complete! 🎉                          ║"
Write-ColorOutput Green "╚══════════════════════════════════════════════════════════════╝"
Write-Host ""
Write-ColorOutput Cyan "Project Location: $(Get-Location)"
Write-Host ""
Write-ColorOutput Cyan "Quick Start:"
Write-Host "  cd $ProjectName"
Write-Host "  php artisan serve"
Write-Host ""
Write-ColorOutput Cyan "Test API:"
Write-Host "  curl http://localhost:8000/api/v1/guests"
Write-Host ""
Write-ColorOutput Cyan "Login Credentials:"
Write-Host "  Email: admin@example.com"
Write-Host "  Password: password"
Write-Host ""
Write-ColorOutput Cyan "API Documentation:"
Write-Host "  http://localhost:8000/docs/swagger.html"
Write-Host ""
Write-ColorOutput Cyan "View Customization:"
Write-Host "  Page views published to: resources\views\vendor\easypack\"
Write-Host "  For full customization (users, roles, etc.):"
Write-Host "  cd $ProjectName"
Write-Host "  php artisan vendor:publish --tag=easypack-manage-views"
Write-Host "  See easy-pack\VIEW_CUSTOMIZATION.md for detailed guide"
Write-Host ""
Write-ColorOutput Cyan "Portability Tips:"
Write-Host "  To use on another machine, copy the entire parent folder and run:"
Write-Host "  .\install-local.ps1 -CheckDeps      # Check dependencies"
Write-Host "  .\install-local.ps1 -InstallDeps    # Install if needed"
Write-Host ""

Pop-Location
