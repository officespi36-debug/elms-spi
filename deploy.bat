@echo off
chcp 65001 >nul
echo ========================================================
echo   Auto-Deploy: Pushing to GitHub & Render
echo   Repository: https://github.com/officespi36-debug/elms-spi
echo   Render Service: srv-da7qsm3bc2fs73bt2dng
echo   Production URL: https://elms-spi.onrender.com
echo ========================================================
echo.

echo [1/2] Checking & Pushing code to GitHub...
git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo  [SUCCESS] Code pushed to GitHub main branch!
    echo  Render Auto-Deploy is now triggered automatically.
    echo.
    echo  Live Website: https://elms-spi.onrender.com
    echo  Dashboard:    https://dashboard.render.com/web/srv-da7qsm3bc2fs73bt2dng
    echo ========================================================
) else (
    echo.
    echo [ERROR] Git push failed. Please check network connection or credentials.
)
echo.
pause
