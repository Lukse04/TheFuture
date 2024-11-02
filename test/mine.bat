@echo off
setlocal ENABLEDELAYEDEXPANSION
set "counter=0"
set "max_count=210000"

cd /d "C:\wamp64\www\test\"  REM Pakeiskite į savo projekto kelią

:loop
php mine.php >nul
set /a counter+=1
if !counter! lss %max_count% goto loop

echo Užbaigta %max_count% paleidimų.
pause
