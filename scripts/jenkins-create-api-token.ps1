# Create a Jenkins API token and append it to .env
# Usage: powershell -ExecutionPolicy Bypass -File scripts/jenkins-create-api-token.ps1
#        powershell -ExecutionPolicy Bypass -File scripts/jenkins-create-api-token.ps1 -UseEnvPassword

param(
    [string]$JenkinsUrl = "http://localhost:9090",
    [string]$User = "",
    [switch]$UseEnvPassword
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$EnvFile = Join-Path $Root ".env"

function Load-DotEnv {
    if (-not (Test-Path $EnvFile)) { return }
    Get-Content $EnvFile | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
        $k, $v = $_ -split '=', 2
        $k = $k.Trim()
        $v = $v.Trim().Trim('"').Trim("'")
        if ($k) { Set-Item -Path "env:$k" -Value $v }
    }
}

Load-DotEnv

if (-not $User) {
    $User = if ($env:JENKINS_USER) { $env:JENKINS_USER } else { "admin" }
}

try {
    $null = Invoke-WebRequest -Uri "$JenkinsUrl/login" -UseBasicParsing -TimeoutSec 5
} catch {
    Write-Error @"
Cannot reach Jenkins at $JenkinsUrl

Start it first:
  docker compose -f docker-compose.jenkins.yml up -d
  powershell -ExecutionPolicy Bypass -File scripts/jenkins-wait-ready.ps1
"@
    exit 1
}

# If Jenkins is still open (no login), enable admin user via init groovy + restart
$initScript = Join-Path $Root "docker\jenkins\init.groovy.d\02-admin-user.groovy"
$needsSecurity = $false
if (Test-Path $initScript) {
    try {
        $secCheck = Invoke-RestMethod -Uri "$JenkinsUrl/api/json" -UseBasicParsing -TimeoutSec 5
        $needsSecurity = -not $secCheck.useSecurity
    } catch {
        # 403 on /api/json usually means security is already enabled
        $needsSecurity = $false
    }
    if ($needsSecurity) {
        docker exec equalvoice_jenkins mkdir -p /var/jenkins_home/init.groovy.d 2>$null | Out-Null
        docker cp $initScript equalvoice_jenkins:/var/jenkins_home/init.groovy.d/02-admin-user.groovy | Out-Null
        Write-Host "Enabling Jenkins security and creating admin user (one-time restart)..."
        docker restart equalvoice_jenkins | Out-Null
        & (Join-Path $Root "scripts\jenkins-wait-ready.ps1")
        Write-Host "Security enabled. User: $User (password from JENKINS_ADMIN_PASSWORD, default admin123)"
    }
}

if ($UseEnvPassword -or $env:JENKINS_ADMIN_PASSWORD) {
    $pass = $env:JENKINS_ADMIN_PASSWORD
    if (-not $pass) {
        Write-Error "Set JENKINS_ADMIN_PASSWORD in .env or omit -UseEnvPassword to type the password."
        exit 1
    }
    Write-Host "Using password from .env (JENKINS_ADMIN_PASSWORD)"
} else {
    Write-Host "Tip: set JENKINS_ADMIN_PASSWORD in .env (default: admin123) and use -UseEnvPassword"
    $sec = Read-Host "Jenkins password for user '$User'" -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
    $pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
}

$pair = "${User}:${pass}"
$b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

try {
    $crumb = Invoke-RestMethod `
        -Uri "$JenkinsUrl/crumbIssuer/api/json" `
        -Headers @{ Authorization = "Basic $b64" } `
        -WebSession $session `
        -Method Get
} catch {
    Write-Error "Login failed for user '$User'. Use the password from JENKINS_ADMIN_PASSWORD in .env (default: admin123)."
    exit 1
}

$headers = @{
    Authorization = "Basic $b64"
}
$headers[$crumb.crumbRequestField] = $crumb.crumb

$jsonPayload = '{"newTokenName":"ci-cd-auto"}'
$formBody = "json=" + [uri]::EscapeDataString($jsonPayload)
$tokenUri = "$JenkinsUrl/user/$User/descriptorByName/jenkins.security.ApiTokenProperty/generateNewToken"

$token = $null
try {
    $resp = Invoke-RestMethod `
        -Method Post `
        -Uri $tokenUri `
        -Headers $headers `
        -WebSession $session `
        -ContentType "application/x-www-form-urlencoded" `
        -Body $formBody
    $token = $resp.data.tokenValue
} catch {
    if (Get-Command curl.exe -ErrorAction SilentlyContinue) {
        $crumbJson = curl.exe -fsS -u $pair "$JenkinsUrl/crumbIssuer/api/json" | ConvertFrom-Json
        $crumbHeader = "$($crumbJson.crumbRequestField): $($crumbJson.crumb)"
        $raw = curl.exe -fsS -u $pair -H $crumbHeader -X POST `
            -H "Content-Type: application/x-www-form-urlencoded" `
            --data-raw "json={""newTokenName"":""ci-cd-auto""}" `
            $tokenUri
        $parsed = $raw | ConvertFrom-Json
        $token = $parsed.data.tokenValue
    } else {
        throw
    }
}

if (-not $token) {
    Write-Error "Jenkins did not return a token. Wrong password or user '$User' does not exist."
    exit 1
}

Write-Host "New API token created (save it now - shown once)"

$lines = @()
if (Test-Path $EnvFile) { $lines = Get-Content $EnvFile }
$updated = $false
$newLines = foreach ($line in $lines) {
    if ($line -match '^\s*JENKINS_API_TOKEN=') {
        $updated = $true
        "JENKINS_API_TOKEN=$token"
    } elseif ($line -match '^\s*JENKINS_URL=') {
        "JENKINS_URL=$JenkinsUrl"
    } else { $line }
}
if (-not $updated) { $newLines += "JENKINS_API_TOKEN=$token" }
if (-not ($newLines | Where-Object { $_ -match '^\s*JENKINS_USER=' })) {
    $newLines += "JENKINS_USER=$User"
}
if (-not ($newLines | Where-Object { $_ -match '^\s*JENKINS_URL=' })) {
    $newLines += "JENKINS_URL=$JenkinsUrl"
}
Set-Content -Path $EnvFile -Value $newLines -Encoding UTF8
Write-Host "Updated $EnvFile (JENKINS_URL=$JenkinsUrl)"
