@echo off
set VPS_IP=31.97.48.96
set VPS_USER=root
set VPS_PASS=123123zz@

if "%1"=="" (
    echo Usage: ssh-auto.bat "command"
    echo Example: ssh-auto.bat "systemctl status mysqld"
    exit /b 1
)

echo %VPS_PASS% | ssh -o StrictHostKeyChecking=no %VPS_USER%@%VPS_IP% "%1"
