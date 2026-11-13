@echo off
chcp 65001 >nul
echo ========================================================
echo   Auto-Deploy: Pushing 7 Commits to GitHub & Render
echo   Repository: https://github.com/officespi36-debug/elms-spi
echo   Render Service: srv-da7qsm3bc2fs73bt2dng
echo ========================================================
echo.

git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo  [SUCCESS] Code pushed to GitHub main branch!
    echo  Render Auto-Deploy is now triggered automatically.
    echo  Monitor your deployment at:
    echo  https://dashboard.render.com/web/srv-da7qsm3bc2fs73bt2dng
    echo ========================================================
) else (
    echo.
    echo [NOTICE] If GitHub requested authentication, please log in or provide your GitHub Personal Access Token.
)
echo.
pause
