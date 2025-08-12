# PowerShell script to check application status
param(
    [string]$VpsIp = "31.97.48.96",
    [string]$VpsUser = "root",
    [string]$VpsPass = "123123zz@"
)

$ErrorActionPreference = 'Stop'

Write-Host "[INFO] Checking application status..." -ForegroundColor Cyan

# Create credential
$sec = ConvertTo-SecureString $VpsPass -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($VpsUser, $sec)

# Import Posh-SSH module
Import-Module Posh-SSH

try {
    # Create SSH session
    $sess = New-SSHSession -ComputerName $VpsIp -Credential $cred -AcceptKey
    Write-Host "[OK] SSH session established" -ForegroundColor Green

    # Check PHP version
    Write-Host "[INFO] Checking PHP version..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "php -v"
    Write-Host "[INFO] PHP version: $($result.Output)" -ForegroundColor Cyan

    # Check PHP modules
    Write-Host "[INFO] Checking PHP modules..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "php -m | grep -E '(pdo|mysql|mbstring|openssl)'"
    Write-Host "[INFO] PHP modules: $($result.Output)" -ForegroundColor Cyan

    # Check file permissions
    Write-Host "[INFO] Checking file permissions..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "ls -la /home/specialprogram2025.online/public_html/"
    Write-Host "[INFO] File permissions: $($result.Output)" -ForegroundColor Cyan

    # Check .env file
    Write-Host "[INFO] Checking .env file..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "cat /home/specialprogram2025.online/public_html/.env | head -10"
    Write-Host "[INFO] .env content: $($result.Output)" -ForegroundColor Cyan

    # Check Apache error log
    Write-Host "[INFO] Checking Apache error log..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "tail -n 20 /var/log/httpd/error_log"
    Write-Host "[INFO] Apache errors: $($result.Output)" -ForegroundColor Cyan

    # Test PHP syntax
    Write-Host "[INFO] Testing PHP syntax..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "php -l /home/specialprogram2025.online/public_html/index.php"
    Write-Host "[INFO] PHP syntax check: $($result.Output)" -ForegroundColor Cyan

    # Test database connection
    Write-Host "[INFO] Testing database connection..." -ForegroundColor Yellow
    $result = Invoke-SSHCommand -SSHSession $sess -Command "php -r 'require_once \"/home/specialprogram2025.online/public_html/config/database.php\"; echo \"DB connection: OK\";'"
    Write-Host "[INFO] Database test: $($result.Output)" -ForegroundColor Cyan

    Write-Host "[DONE] Status check completed!" -ForegroundColor Green

} catch {
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
} finally {
    if ($sess) {
        Remove-SSHSession -SSHSession $sess
        Write-Host "[INFO] SSH session closed" -ForegroundColor Yellow
    }
}
