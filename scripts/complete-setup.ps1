# EqualVoice Complete CI/CD Setup Script (Windows PowerShell)
# Run: powershell -ExecutionPolicy Bypass -File scripts/complete-setup.ps1

$JENKINS_PORT = 9090
$APP_PORT = 8000
$PHPMYADMIN_PORT = 8081

function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Warning { Write-Host $args -ForegroundColor Yellow }
function Write-Error { Write-Host $args -ForegroundColor Red }
function Write-Info { Write-Host $args -ForegroundColor Blue }

$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

Write-Info ""
Write-Info "EqualVoice CI/CD Setup (Jenkins + Docker + GitHub)"
Write-Info ""

Write-Warning "Step 1: Checking prerequisites..."
$tools = @("docker", "docker-compose", "git", "curl")
$missing = 0
foreach ($cmd in $tools) {
    if (Get-Command $cmd -ErrorAction SilentlyContinue) {
        Write-Success "  $cmd OK"
    } else {
        Write-Error "  $cmd missing"
        $missing++
    }
}
if ($missing -gt 0) { exit 1 }

Write-Warning "Step 2: Environment file..."
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Success "  Created .env from .env.example"
    Write-Warning "  Edit .env: GITHUB_TOKEN, JENKINS_API_TOKEN, GITHUB_REPO"
}

Write-Warning "Step 3: Directories..."
@("scripts", "jenkins", "jenkins/jobs", "backups", "logs", "docker/jenkins") | ForEach-Object {
    if (-not (Test-Path $_)) { New-Item -ItemType Directory -Path $_ -Force | Out-Null }
}

Write-Warning "Step 4: Starting Jenkins..."
docker compose -f docker-compose.jenkins.yml up -d

Write-Warning "Step 5: Deploy Jenkins jobs..."
& (Join-Path $Root "scripts\jenkins-deploy-jobs.ps1") -Restart

Write-Warning "Step 6: Starting application stack..."
docker compose -f docker-compose.yml up -d

Write-Info "Waiting for Jenkins (http://localhost:$JENKINS_PORT)..."
$ready = $false
for ($i = 1; $i -le 60; $i++) {
    try {
        $r = Invoke-WebRequest -Uri "http://localhost:$JENKINS_PORT/login" -UseBasicParsing -TimeoutSec 5
        if ($r.StatusCode -eq 200) { $ready = $true; break }
    } catch { Start-Sleep -Seconds 2 }
}
if ($ready) { Write-Success "  Jenkins ready" } else { Write-Warning "  Jenkins may still be starting" }

Write-Success ""
Write-Success "Setup complete"
Write-Info "  Jenkins:     http://localhost:$JENKINS_PORT"
Write-Info "  App:         http://localhost:$APP_PORT"
Write-Info "  phpMyAdmin:  http://localhost:$PHPMYADMIN_PORT"
Write-Info ""
Write-Info "Next:"
Write-Info "  1. scripts/jenkins-create-api-token.ps1"
Write-Info "  2. Set GITHUB_TOKEN in .env"
Write-Info "  3. scripts/ci-cd-auto.ps1"
