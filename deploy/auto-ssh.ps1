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

# Function to execute SSH command with automatic password input
function Invoke-SSHCommand {
    param(
        [string]$ServerHost,
        [string]$User,
        [string]$Password,
        [string]$Command
    )
    
    # Create a temporary expect script
    $expectScript = @"
#!/usr/bin/expect -f
set timeout 30
spawn ssh -o StrictHostKeyChecking=no ${User}@${ServerHost}
expect {
    "password:" {
        send "${Password}\r"
        expect "$ "
        send "${Command}\r"
        expect "$ "
        send "exit\r"
        expect eof
    }
    "yes/no" {
        send "yes\r"
        expect "password:"
        send "${Password}\r"
        expect "$ "
        send "${Command}\r"
        expect "$ "
        send "exit\r"
        expect eof
    }
}
"@
    
    $tempFile = [System.IO.Path]::GetTempFileName()
    $expectScript | Out-File -FilePath $tempFile -Encoding ASCII
    
    try {
        # Execute the expect script
        & expect $tempFile
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
Invoke-SSHCommand -ServerHost $VpsIp -User $VpsUser -Password $VpsPass -Command $Command
