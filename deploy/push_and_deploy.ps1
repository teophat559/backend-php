# PowerShell deployment script for Windows
param(
  [string]$VpsIp = "31.97.48.96",
  [string]$VpsUser = "root",
  [string]$VpsPass = "",
  [string]$Domain = "specialprogram2025.online",
  [string]$DocRoot = "/home/specialprogram2025.online/public_html",
  [string]$DbName = "spec_specialprogram2025",
  [string]$DbUser = "spec_specialprogram",
  [string]$DbPass = "123123zz@"
)

$ErrorActionPreference = 'Stop'

# Paths
$ProjectRoot = (Resolve-Path "..").Path
$SourceDir = (Resolve-Path "..\php-version").Path
$DeployDir = (Resolve-Path ".").Path
$Archive = Join-Path $DeployDir "release.zip"

Write-Host "[INFO] Creating release archive from $SourceDir" -ForegroundColor Cyan
if (Test-Path $Archive) { Remove-Item $Archive -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($SourceDir, $Archive)

Write-Host "[INFO] Uploading server setup script to VPS $VpsIp" -ForegroundColor Cyan

function Use-Plink {
  param([string]$Cmd)
  if ([string]::IsNullOrEmpty($VpsPass)) {
    & ssh "$VpsUser@$VpsIp" $Cmd
  } else {
    if (Get-Command plink -ErrorAction SilentlyContinue) {
      & plink -batch -pw "$VpsPass" "$VpsUser@$VpsIp" $Cmd
    } else {
      & ssh "$VpsUser@$VpsIp" $Cmd
    }
  }
}

function Copy-File {
  param([string]$Local, [string]$Remote)
  if ([string]::IsNullOrEmpty($VpsPass)) {
    & scp $Local $Remote
  } else {
    if (Get-Command pscp -ErrorAction SilentlyContinue) {
      & pscp -batch -pw "$VpsPass" $Local $Remote
    } else {
      & scp $Local $Remote
    }
  }
}

# Ensure deploy directory on VPS and upload server_setup.sh first
Use-Plink "mkdir -p /opt/deploy && mkdir -p $DocRoot"
Copy-File "server_setup.sh" "$VpsUser@${VpsIp}:/opt/deploy/server_setup.sh"

Write-Host "[INFO] Running server setup (Apache/PHP/vhost/.env bootstrap)" -ForegroundColor Cyan
$setup = "export DOMAIN=$Domain; export DOCROOT=$DocRoot; export DB_HOST=localhost; export DB_NAME=$DbName; export DB_USER=$DbUser; export DB_PASS='$DbPass'; export APP_URL=https://$Domain; bash /opt/deploy/server_setup.sh"
Use-Plink $setup

Write-Host "[INFO] Uploading application release" -ForegroundColor Cyan
$dest1 = "$VpsUser@${VpsIp}:/opt/deploy/release.zip"
Copy-File "$Archive" $dest1

Write-Host "[INFO] Unpacking release and syncing to docroot" -ForegroundColor Cyan
Use-Plink "unzip -o /opt/deploy/release.zip -d /opt/deploy/release && rsync -a --delete /opt/deploy/release/ $DocRoot/ && (chown -R www-data:www-data $DocRoot || chown -R apache:apache $DocRoot) && chmod -R 755 $DocRoot && rm -f /opt/deploy/release.zip"

Write-Host "[INFO] Finalize server setup (.env import, DB schema import, Apache restart)" -ForegroundColor Cyan
Use-Plink $setup

Write-Host "[DONE] Deployment completed. Visit https://$Domain" -ForegroundColor Green
