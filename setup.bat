@echo off
setlocal EnableExtensions
cd /d "%~dp0"

echo.
echo ========================================
echo   ProjectWebsite - Windows Setup
echo ========================================
echo.

echo [1/7] Checking PHP...
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP was not found in PATH.
    echo Make sure XAMPP/PHP is installed and added to PATH.
    exit /b 1
)
php -v | findstr /r /c:"PHP [0-9]" 
echo.

echo [2/7] Checking required PHP extensions...
php -m | findstr /i /c:"openssl" /c:"pdo" /c:"mbstring" /c:"fileinfo" /c:"tokenizer" /c:"xml" /c:"ctype" >nul
if errorlevel 1 (
    echo WARNING: One or more common Laravel PHP extensions may be missing.
    echo If Composer or Laravel reports a missing extension, enable it in php.ini.
)
echo.

echo [3/7] Checking Composer...
call composer --version
if errorlevel 1 (
    echo ERROR: Composer was not found or could not start.
    echo Install Composer and make sure the 'composer' command works in a new terminal.
    exit /b 1
)
echo.

echo [4/7] Installing PHP dependencies...
call composer install --no-interaction --prefer-dist
if errorlevel 1 (
    echo ERROR: Composer dependency installation failed.
    echo Read the Composer error above, fix that issue, then run this file again.
    exit /b 1
)
if not exist "vendor\autoload.php" (
    echo ERROR: vendor\autoload.php was not created.
    echo Composer installation did not complete correctly.
    exit /b 1
)
echo.

echo [5/7] Preparing Laravel environment...
if not exist ".env" copy /y ".env.example" ".env" >nul
if not exist "database\database.sqlite" type nul > "database\database.sqlite"
php artisan key:generate --force
if errorlevel 1 exit /b 1
php artisan migrate --seed --force
if errorlevel 1 exit /b 1
rem Makes uploaded course thumbnails reachable at /storage/... in the browser.
if not exist "public\storage" (
    php artisan storage:link
    if errorlevel 1 (
        echo WARNING: Could not create the public\storage link.
        echo Uploaded course thumbnails stay invisible until you run:
        echo     php artisan storage:link
    )
)
echo.

echo [6/7] Installing frontend dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: npm install failed.
    exit /b 1
)
call npm run build
if errorlevel 1 (
    echo ERROR: frontend build failed.
    exit /b 1
)
echo.

echo [7/7] Setup complete.
echo.
echo Start the website with:
echo     php artisan serve
echo.
echo Then open:
echo     http://127.0.0.1:8000
echo.
pause
exit /b 0
