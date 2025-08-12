param(
    [Parameter(Mandatory=$true)]
    [string]$VpsIp,
    
    [Parameter(Mandatory=$true)]
    [string]$VpsUser,
    
    [Parameter(Mandatory=$true)]
    [string]$VpsPass,
    
    [Parameter(Mandatory=$true)]
    [string]$Command
)

# Function to execute SSH command using plink
function Invoke-SSHWithPlink {
    param(
        [string]$ServerHost,
        [string]$User,
        [string]$Password,
        [string]$Command
    )
    
    # Create a temporary batch file for plink
    $batchContent = @"
@echo off
echo y | plink -ssh -l $User -pw $Password $ServerHost "$Command"
"@
    
    $tempFile = [System.IO.Path]::GetTempFileName() + ".bat"
    $batchContent | Out-File -FilePath $tempFile -Encoding ASCII
    
    try {
        # Execute the batch file
        & cmd /c $tempFile
    }
    finally {
        # Clean up
        if (Test-Path $tempFile) {
            Remove-Item $tempFile -Force
        }
    }
}

# Execute the command
Write-Host "[INFO] Executing: $Command" -ForegroundColor Yellow
Invoke-SSHWithPlink -ServerHost $VpsIp -User $VpsUser -Password $VpsPass -Command $Command
