@echo off
setlocal enabledelayedexpansion

REM Version bump script for VAPT Security plugin (Windows)

REM Check if version type is provided
if "%1"=="" (
    echo Usage: %0 ^<major^|minor^|patch^>
    echo Example: %0 patch
    exit /b 1
)

set VERSION_TYPE=%1

REM Get current version from plugin file
for /f "tokens=3" %%i in ('findstr "Version:" vapt-security.php') do (
    set CURRENT_VERSION=%%i
    goto :got_version
)

:got_version
echo Current version: %CURRENT_VERSION%

REM Parse version components
for /f "tokens=1,2,3 delims=." %%a in ("%CURRENT_VERSION%") do (
    set MAJOR=%%a
    set MINOR=%%b
    set PATCH=%%c
)

REM Increment version based on type
if "%VERSION_TYPE%"=="major" (
    set /a MAJOR+=1
    set MINOR=0
    set PATCH=0
) else if "%VERSION_TYPE%"=="minor" (
    set /a MINOR+=1
    set PATCH=0
) else if "%VERSION_TYPE%"=="patch" (
    set /a PATCH+=1
) else (
    echo Invalid version type. Use major, minor, or patch.
    exit /b 1
)

set NEW_VERSION=%MAJOR%.%MINOR%.%PATCH%

echo Bumping version from %CURRENT_VERSION% to %NEW_VERSION%

REM Update version in plugin file
powershell -Command "(gc vapt-security.php) -replace '\* Version:\s*[0-9]+\.[0-9]+\.[0-9]+', '* Version:     %NEW_VERSION%' | Out-File -encoding UTF8 vapt-security.php"

REM Update version in README.txt if it exists
if exist "README.txt" (
    powershell -Command "(gc README.txt) -replace 'Stable tag:\s*[0-9]+\.[0-9]+\.[0-9]+', 'Stable tag: %NEW_VERSION%' | Out-File -encoding UTF8 README.txt"
)

REM Add changes to git
git add vapt-security.php CHANGELOG.md

REM Commit with version bump message
git commit -m "Bump version to %NEW_VERSION%"

REM Create tag
git tag -a "v%NEW_VERSION%" -m "Version %NEW_VERSION%"

echo Version bumped to %NEW_VERSION%
echo Changes committed and tagged