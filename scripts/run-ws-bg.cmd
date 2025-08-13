@echo off
REM Start WebSocket server in background window
setlocal
cd /d "%~dp0..\realtime"
start "WS Server" cmd /c "php server.php"
echo WS server starting...
endlocal
