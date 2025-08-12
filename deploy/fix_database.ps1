# PowerShell script to fix database connection issues
param(
    [string]$VpsIp = "31.97.48.96",
    [string]$VpsUser = "root",
    [string]$VpsPass = "123123zz@"
)

$ErrorActionPreference = 'Stop'

Write-Host "[INFO] Connecting to VPS and fixing database..." -ForegroundColor Cyan

# Create credential
$sec = ConvertTo-SecureString $VpsPass -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($VpsUser, $sec)

# Import Posh-SSH module
Import-Module Posh-SSH

try {
    # Create SSH session
    $sess = New-SSHSession -ComputerName $VpsIp -Credential $cred -AcceptKey
    Write-Host "[OK] SSH session established" -ForegroundColor Green

    # Step 1: Create database
    Write-Host "[INFO] Creating database..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS spec_specialprogram2025 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
    Write-Host "[OK] Database creation result: $($result.ExitStatus)" -ForegroundColor Green

    # Step 2: Create user with simple command
    Write-Host "[INFO] Creating user..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "mysql -uroot -e 'CREATE USER IF NOT EXISTS spec_specialprogram@localhost IDENTIFIED BY 123123zz@;'"
    Write-Host "[OK] User creation result: $($result.ExitStatus)" -ForegroundColor Green

    # Step 3: Grant privileges
    Write-Host "[INFO] Granting privileges..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "mysql -uroot -e 'GRANT ALL PRIVILEGES ON spec_specialprogram2025.* TO spec_specialprogram@localhost; FLUSH PRIVILEGES;'"
    Write-Host "[OK] Privilege grant result: $($result.ExitStatus)" -ForegroundColor Green

    # Step 4: Test connection
    Write-Host "[INFO] Testing database connection..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "export MYSQL_PWD=123123zz@; mysql -u spec_specialprogram -e 'SHOW DATABASES LIKE spec_specialprogram2025;'"
    Write-Host "[OK] Connection test result: $($result.ExitStatus)" -ForegroundColor Green
    Write-Host "[INFO] Database output: $($result.Output)" -ForegroundColor Cyan

    # Step 5: Import schema
    Write-Host "[INFO] Importing database schema..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "export MYSQL_PWD=123123zz@; mysql -u spec_specialprogram spec_specialprogram2025 < /home/specialprogram2025.online/public_html/setup-database.sql"
    Write-Host "[OK] Schema import result: $($result.ExitStatus)" -ForegroundColor Green

    # Step 6: Check HTTP response
    Write-Host "[INFO] Testing HTTP response..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "curl -I http://localhost/ 2>/dev/null | head -5"
    Write-Host "[INFO] HTTP response: $($result.Output)" -ForegroundColor Cyan

    # Step 7: Check Apache error log
    Write-Host "[INFO] Checking Apache error log..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "tail -n 20 /var/log/httpd/error_log"
    Write-Host "[INFO] Apache errors: $($result.Output)" -ForegroundColor Cyan

    Write-Host "[DONE] Database fix completed!" -ForegroundColor Green

} catch {
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
} finally {
    if ($sess) {
        Remove-SSHSession -SSHSession $sess
        Write-Host "[INFO] SSH session closed" -ForegroundColor Yellow
    }
}
