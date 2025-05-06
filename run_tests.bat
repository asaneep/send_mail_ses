@echo off
REM Script to run PHPUnit tests on Windows

REM Check if composer dependencies are installed
if not exist "vendor" (
    echo Installing dependencies...
    call composer install --dev
)

REM Run all tests by default
if "%~1"=="" (
    echo Running all tests...
    call vendor\bin\phpunit
) else (
    REM Run specific test file if provided
    echo Running test: %1
    call vendor\bin\phpunit %1
)