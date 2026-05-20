# Deploy job1, job2, job3 into the running Jenkins container
# Usage: powershell -ExecutionPolicy Bypass -File scripts/jenkins-deploy-jobs.ps1 [-Restart]

param(
    [switch]$Restart
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

$Container = "equalvoice_jenkins"
$Jobs = @("job1", "job2", "job3")

function Wait-JenkinsContainerRunning {
    param([int]$MaxAttempts = 90)
    for ($i = 1; $i -le $MaxAttempts; $i++) {
        $status = docker inspect --format '{{.State.Status}}' $Container 2>$null
        if ($status -eq 'running') {
            try {
                docker exec $Container test -d /var/jenkins_home 2>$null | Out-Null
                if ($LASTEXITCODE -eq 0) { return }
            } catch { }
        }
        if ($status -eq 'restarting') {
            Write-Host "Jenkins container restarting ($i/$MaxAttempts)..."
        } else {
            Write-Host "Jenkins status: $status ($i/$MaxAttempts)..."
        }
        Start-Sleep -Seconds 3
    }
    $logs = docker logs $Container --tail 30 2>&1
    throw "Jenkins container did not become ready. Last logs:`n$logs"
}

if (-not (docker ps --format "{{.Names}}" | Select-String -Pattern "^${Container}$")) {
    Write-Host "Building and starting Jenkins..."
    docker compose -f docker-compose.jenkins.yml build
    docker compose -f docker-compose.jenkins.yml up -d
    $Restart = $true
}

Wait-JenkinsContainerRunning

foreach ($job in $Jobs) {
    $src = Join-Path $Root "jenkins\jobs\$job\config.xml"
    if (-not (Test-Path $src)) {
        throw "Missing $src"
    }
    docker exec -u root $Container mkdir -p "/var/jenkins_home/jobs/$job"
    if ($LASTEXITCODE -ne 0) {
        Wait-JenkinsContainerRunning
        docker exec -u root $Container mkdir -p "/var/jenkins_home/jobs/$job"
    }
    docker cp $src "${Container}:/var/jenkins_home/jobs/$job/config.xml"
    docker exec -u root $Container chown -R jenkins:jenkins "/var/jenkins_home/jobs/$job"
    Write-Host "Deployed $job"
}

if ($Restart) {
    Write-Host "Restarting Jenkins to load job configs..."
    docker restart $Container | Out-Null
    Wait-JenkinsContainerRunning
    & (Join-Path $Root "scripts\jenkins-wait-ready.ps1")
} else {
    Write-Host "Job configs copied (no restart). Use -Restart after config changes."
}

Write-Host ""
Write-Host "Jenkins jobs at http://localhost:9090/"
Write-Host "  job1 - build"
Write-Host "  job2 - test"
Write-Host "  job3 - deployment"
