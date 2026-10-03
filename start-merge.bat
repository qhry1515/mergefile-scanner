@echo off
REM MergeFile - jalankan tanpa XAMPP Control Panel (hanya butuh php.exe)
cd /d %~dp0
set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" (
  echo php.exe tidak ditemukan di %PHP_EXE%
  echo Edit file ini: ganti baris set PHP_EXE=... sesuai lokasi php.exe Anda.
  pause
  exit /b 1
)

netstat -ano | findstr ":8000" | findstr "LISTENING" >nul
if %errorlevel%==0 (
  echo Server MergeFile sudah berjalan di port 8000.
) else (
  echo Menjalankan server MergeFile di http://localhost:8000 ...
  start "MergeFile Server" /min "%PHP_EXE%" -S 0.0.0.0:8000 -t "%~dp0"
  ping -n 3 127.0.0.1 >nul
)

start "" http://localhost:8000/
exit /b 0
