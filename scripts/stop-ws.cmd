@echo off
REM Attempt to stop WS Server window by title
taskkill /FI "WINDOWTITLE eq WS Server" /T /F >nul 2>nul
REM As fallback, kill php running server.php (use carefully)
for /f "tokens=2" %%a in ('tasklist ^| findstr /i "php.exe"') do (
	REM No reliable way to filter by arguments on Windows cmd; leave manual.
)
echo WS server stop attempted.
