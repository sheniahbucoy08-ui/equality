# Start Jenkins container only
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root
docker compose -f docker-compose.jenkins.yml up -d
& (Join-Path $Root "scripts\jenkins-wait-ready.ps1")
Write-Host "Jenkins: http://localhost:9090"
