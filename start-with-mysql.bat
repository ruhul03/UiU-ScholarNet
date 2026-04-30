@echo off
echo Starting MySQL (XAMPP)...
start "" "C:\xampp\xampp-control.exe"
timeout /t 3 >nul
echo.
echo Starting PHP Development Server...
echo Server will be available at: http://localhost:8000
echo.
C:\xampp\php\php.exe -S localhost:8000
echo.
echo Server stopped.
pause
