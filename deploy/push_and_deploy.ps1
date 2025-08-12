# PowerShell deployment script for Windows
param(
  [string]$VpsIp = "31.97.48.96",
  [string]$VpsUser = "root",
  [string]$VpsPass = "",
  [string]$Domain = "specialprogram2025.online",
  [string]$DocRoot = "/home/specialprogram2025.online/public_html",
  [string]$DbName = "spec_specialprogram2025",
  [string]$DbUser = "spec_specialprogram",
  [string]$DbPass = "123123zz@",
  [switch]$SkipServerSetup
)

$ErrorActionPreference = 'Stop'

# If password auth is used, ensure PuTTY tools are available
if (-not [string]::IsNullOrEmpty($VpsPass)) {
  $hasPlink = $null -ne (Get-Command plink -ErrorAction SilentlyContinue)
  $hasPscp  = $null -ne (Get-Command pscp  -ErrorAction SilentlyContinue)
  if (-not ($hasPlink -and $hasPscp)) {
    Write-Error "Password authentication requires PuTTY tools (plink.exe and pscp.exe) in PATH. Install PuTTY or switch to SSH key auth (leave -VpsPass empty)."
    exit 1
  }
}

# Paths (derive repo root from script location to be robust)
$ScriptDir = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $ScriptDir "..")).Path
$SourceDir = $RepoRoot
# Create archive OUTSIDE the source tree to avoid self-inclusion locks
$Archive = Join-Path $env:TEMP "backend-php-release.zip"

Write-Host "[INFO] Creating release archive from $SourceDir" -ForegroundColor Cyan
if (Test-Path $Archive) { Remove-Item $Archive -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($SourceDir, $Archive)

Write-Host "[INFO] Uploading server setup script to VPS $VpsIp" -ForegroundColor Cyan

# Common SSH options to avoid interactive host key prompts
$SshOpts = @('-o','StrictHostKeyChecking=no','-o','UserKnownHostsFile=/dev/null')

function Use-Plink {
  param([string]$Cmd)
  if ([string]::IsNullOrEmpty($VpsPass)) {
    & ssh @SshOpts "$VpsUser@$VpsIp" $Cmd
  } else {
    if (Get-Command plink -ErrorAction SilentlyContinue) {
      & plink -batch -pw "$VpsPass" "$VpsUser@$VpsIp" $Cmd
    } else {
      & ssh @SshOpts "$VpsUser@$VpsIp" $Cmd
    }
  }
}

function Copy-File {
  param([string]$Local, [string]$Remote)
  if ([string]::IsNullOrEmpty($VpsPass)) {
    & scp @SshOpts $Local $Remote
  } else {
    if (Get-Command pscp -ErrorAction SilentlyContinue) {
      & pscp -batch -pw "$VpsPass" $Local $Remote
    } else {
      & scp @SshOpts $Local $Remote
    }
  }
}

# Ensure deploy directory on VPS
Use-Plink "mkdir -p /opt/deploy && mkdir -p $DocRoot/uploads"

if (-not $SkipServerSetup) {
  Copy-File (Join-Path $ScriptDir "server_setup.sh") "$VpsUser@${VpsIp}:/opt/deploy/server_setup.sh"
  Write-Host "[INFO] Running server setup (Apache/PHP/vhost/.env bootstrap)" -ForegroundColor Cyan
  $setup = "export DOMAIN=$Domain; export DOCROOT=$DocRoot; export DB_HOST=localhost; export DB_NAME=$DbName; export DB_USER=$DbUser; export DB_PASS='$DbPass'; export APP_URL=https://$Domain; bash /opt/deploy/server_setup.sh"
  Use-Plink $setup
}

Write-Host "[INFO] Uploading application release" -ForegroundColor Cyan
$dest1 = "$VpsUser@${VpsIp}:/opt/deploy/release.zip"
Copy-File "$Archive" $dest1

Write-Host "[INFO] Unpacking and syncing to docroot (root)" -ForegroundColor Cyan
$syncCmd = "unzip -o /opt/deploy/release.zip -d /opt/deploy/release && (rsync -a --delete /opt/deploy/release/ $DocRoot/ 2>/dev/null || cp -a /opt/deploy/release/. $DocRoot/) && rm -f /opt/deploy/release.zip && ls -la $DocRoot | head -n 50"
Use-Plink $syncCmd

# Ensure a root .htaccess routes traffic to index.php (and strip legacy /php-version prefix) and permissions are sane
Write-Host "[INFO] Ensuring root .htaccess routes to index.php and permissions are correct" -ForegroundColor Cyan
$htaccessRootCmd = @(
  "set -e",
  "if [ -f $DocRoot/.htaccess ]; then cp $DocRoot/.htaccess $DocRoot/.htaccess.bak-$(date +%s) || true; fi",
  "cat > $DocRoot/.htaccess <<'EOF'",
  "# Root rewrite rules",
  "RewriteEngine On",
  "# Backward compatibility: strip legacy /php-version prefix",
  "RewriteRule ^php-version/(.*)$ $1 [L]",
  "# Serve existing files/directories directly",
  "RewriteCond %{REQUEST_FILENAME} -f [OR]",
  "RewriteCond %{REQUEST_FILENAME} -d",
  "RewriteRule ^ - [L]",
  "# Route everything else to root index.php",
  "RewriteRule ^ index.php [L]",
  "EOF",
  "chmod 644 $DocRoot/.htaccess || true",
  # Normalize permissions: dirs 755, files 644; keep .env stricter
  "find $DocRoot -type d -exec chmod 755 {} \\; || true",
  "find $DocRoot -type f -exec chmod 644 {} \\; || true",
  "if [ -f $DocRoot/.env ]; then chmod 600 $DocRoot/.env || true; fi",
  # Fix ownership when the site user exists (CyberPanel user is the domain name)
  "if id -u $Domain >/dev/null 2>&1; then chown -R $Domain:$Domain $DocRoot || true; fi"
) -join " && "
Use-Plink $htaccessRootCmd

# Upload .env and set permissions
if (Test-Path (Join-Path $RepoRoot ".env")) {
  Copy-File (Join-Path $RepoRoot ".env") "$VpsUser@${VpsIp}:$DocRoot/.env"
  Use-Plink "chmod 600 $DocRoot/.env || true"
}

# Finalize (only if server setup was run)
if (-not $SkipServerSetup) {
  Write-Host "[INFO] Finalize server setup (.env import, DB schema import, Apache restart)" -ForegroundColor Cyan
  Use-Plink $setup
}

# Cleanup local temp archive
try { if (Test-Path $Archive) { Remove-Item $Archive -Force } } catch { }

Write-Host "[DONE] Deployment completed. Visit https://$Domain" -ForegroundColor Green