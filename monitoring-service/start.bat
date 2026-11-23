@echo off
chcp 65001 >nul
title Kelompok 7 - Monitoring Service (Port 8107)
cls
echo ===================================================================
echo   MINI-LMS TERDISTRIBUSI - KELOMPOK 7: MONITORING SERVICE
echo   Layanan Pemeriksaan Kesehatan dan Status Seluruh Service
echo   Port: 8107
echo ===================================================================
echo.

REM --- Konfigurasi Alamat Service LAN yang Dipantau ---
if "%AUTH_SERVICE_URL%"=="" set AUTH_SERVICE_URL=http://127.0.0.1:8102
if "%COURSE_SERVICE_URL%"=="" set COURSE_SERVICE_URL=http://127.0.0.1:8103
if "%ASSIGNMENT_SERVICE_URL%"=="" set ASSIGNMENT_SERVICE_URL=http://127.0.0.1:8104
if "%SUBMISSION_SERVICE_URL%"=="" set SUBMISSION_SERVICE_URL=http://127.0.0.1:8105
if "%NOTIFICATION_SERVICE_URL%"=="" set NOTIFICATION_SERVICE_URL=http://127.0.0.1:8106

echo Target Monitoring:
echo   - Auth (K2)        : %AUTH_SERVICE_URL%
echo   - Course (K3)      : %COURSE_SERVICE_URL%
echo   - Assignment (K4)  : %ASSIGNMENT_SERVICE_URL%
echo   - Submission (K5)  : %SUBMISSION_SERVICE_URL%
echo   - Notification (K6): %NOTIFICATION_SERVICE_URL%
echo.

REM --- Deteksi PHP ---
set PHP_CMD=php
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_CMD=C:\xampp\php\php.exe
    ) else (
        echo [ERROR] PHP tidak ditemukan di PATH maupun di C:\xampp\php\php.exe!
        echo Pastikan PHP atau XAMPP sudah terinstal.
        pause
        exit /b 1
    )
)

echo [INFO] Menjalankan Monitoring Service di http://0.0.0.0:8107 ...
echo [INFO] Tekan CTRL+C untuk menghentikan service.
echo -------------------------------------------------------------------
%PHP_CMD% -S 0.0.0.0:8107 router.php
pause
