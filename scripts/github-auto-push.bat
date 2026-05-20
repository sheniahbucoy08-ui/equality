@echo off
REM GitHub Auto-Push for EqualVoice CI/CD
REM Usage: github-auto-push.bat "commit message" [branch]

setlocal enabledelayedexpansion

set COMMIT_MESSAGE=%~1
if "!COMMIT_MESSAGE!"=="" set COMMIT_MESSAGE=Automated build %BUILD_NUMBER%

set TARGET_BRANCH=%~2
if "!TARGET_BRANCH!"=="" (
    for /f "delims=" %%b in ('git branch --show-current 2^>nul') do set TARGET_BRANCH=%%b
)
if "!TARGET_BRANCH!"=="" set TARGET_BRANCH=main

if "!GITHUB_REPO!"=="" set GITHUB_REPO=sheniahbucoy08-ui/equality

if "!GITHUB_TOKEN!"=="" (
    echo Error: GITHUB_TOKEN environment variable not set
    exit /b 1
)

set REMOTE_URL=https://x-access-token:!GITHUB_TOKEN!@github.com/!GITHUB_REPO!.git

echo.
echo === GitHub Auto-Push ===
echo Repository: !GITHUB_REPO!
echo Branch: !TARGET_BRANCH!
echo Message: !COMMIT_MESSAGE!
echo.

git --version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Error: Git is not installed
    exit /b 1
)

git config user.name >nul 2>&1
if %ERRORLEVEL% neq 0 (
    git config user.name "Jenkins CI"
    git config user.email "jenkins@equalvoice.local"
)

git diff-index --quiet HEAD -- >nul 2>&1
set HAS_CHANGES=%ERRORLEVEL%
if %HAS_CHANGES% neq 0 (
    git add -A
    git commit -m "!COMMIT_MESSAGE!"
)

echo Syncing with remote...
git fetch !REMOTE_URL! !TARGET_BRANCH! 2>nul
if %ERRORLEVEL% equ 0 (
    git rebase FETCH_HEAD 2>nul
    if !ERRORLEVEL! neq 0 (
        echo Rebase failed - resolve conflicts locally, then push again.
        exit /b 1
    )
) else (
    echo Remote branch new or not found - will create on push
)

echo Pushing to GitHub...
git push !REMOTE_URL! HEAD:!TARGET_BRANCH!
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to push to GitHub
    exit /b 1
)

echo.
echo === GitHub push OK ===
echo.

endlocal
exit /b 0
