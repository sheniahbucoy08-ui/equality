# EqualVoice - single CI/CD script (GitHub + Jenkins + Docker)
#
# Usage:
#   .\scripts\cicd.ps1              # full pipeline
#   .\scripts\cicd.ps1 -Action setup
#   .\scripts\cicd.ps1 -Action token
#   .\scripts\cicd.ps1 -Action jobs [-Restart]
#   .\scripts\cicd.ps1 -Action run -SkipGitHub
#   .\scripts\cicd.ps1 -Action push -Message "my commit"

param(
    [ValidateSet("run", "setup", "token", "jobs", "push")]
    [string]$Action = "run",
    [string]$Message = "",
    [string]$Branch = "",
    [switch]$SkipGitHub,
    [switch]$SkipJenkins,
    [switch]$Restart,
    [switch]$UseEnvPassword,
    [int]$BuildTimeoutMin = 60
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$EnvFile = Join-Path $Root ".env"
Set-Location $Root

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

function Save-DotEnvKey {
    param([string]$Key, [string]$Value)
    $lines = @()
    if (Test-Path $EnvFile) { $lines = Get-Content $EnvFile }
    $found = $false
    $newLines = foreach ($line in $lines) {
        if ($line -match "^\s*$([regex]::Escape($Key))=") {
            $found = $true
            "$Key=$Value"
        } else { $line }
    }
    if (-not $found) { $newLines += "$Key=$Value" }
    Set-Content -Path $EnvFile -Value $newLines -Encoding UTF8
}

Load-DotEnv

if (-not $Message) {
    $Message = "CI/CD automated push $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
}
if (-not $Branch) {
    $Branch = (git branch --show-current 2>$null)
    if (-not $Branch) { $Branch = "main" }
}
$env:GIT_BRANCH = if ($env:GIT_BRANCH) { $env:GIT_BRANCH } else { $Branch }

$JenkinsUrl   = if ($env:JENKINS_URL) { $env:JENKINS_URL } else { "http://localhost:9090" }
$JenkinsUser  = if ($env:JENKINS_USER) { $env:JENKINS_USER } else { "admin" }
$JenkinsToken = $env:JENKINS_API_TOKEN
$GithubRepo   = if ($env:GITHUB_REPO) { $env:GITHUB_REPO } else { "sheniahbucoy08-ui/equality" }
$GithubToken  = $env:GITHUB_TOKEN
$BuildTimeoutSec = $BuildTimeoutMin * 60
$Container = "equalvoice_jenkins"

# Jenkins jobs (create folders if missing)
function Ensure-JenkinsJobConfigs {
    foreach ($name in @("job1", "job2", "job3")) {
        $dir = Join-Path $Root "jenkins\jobs\$name"
        $cfg = Join-Path $dir "config.xml"
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-Host "Created $dir"
        }
        if (-not (Test-Path $cfg)) {
            throw "Missing jenkins/jobs/$name/config.xml - restore from repository."
        }
    }
}

function Wait-JenkinsHttp {
    param([int]$Max = 60)
    for ($i = 1; $i -le $Max; $i++) {
        try {
            $r = Invoke-WebRequest -Uri "$JenkinsUrl/login" -UseBasicParsing -TimeoutSec 5
            if ($r.StatusCode -eq 200) { return }
        } catch { }
        Write-Host "Waiting for Jenkins ($i/$Max)..."
        Start-Sleep -Seconds 3
    }
    throw "Jenkins not ready at $JenkinsUrl"
}

function Wait-JenkinsContainer {
    param([int]$Max = 90)
    for ($i = 1; $i -le $Max; $i++) {
        $status = docker inspect --format '{{.State.Status}}' $Container 2>$null
        if ($status -eq 'running') {
            docker exec $Container test -d /var/jenkins_home 2>$null | Out-Null
            if ($LASTEXITCODE -eq 0) { return }
        }
        Start-Sleep -Seconds 3
    }
    throw "Container $Container not ready"
}

function Invoke-ActionJobs {
    Ensure-JenkinsJobConfigs
    if (-not (docker ps --format "{{.Names}}" | Select-String -Pattern "^${Container}$")) {
        docker compose -f docker-compose.jenkins.yml build
        docker compose -f docker-compose.jenkins.yml up -d
        $Restart = $true
    }
    Wait-JenkinsContainer
    foreach ($job in @("job1", "job2", "job3")) {
        $src = Join-Path $Root "jenkins\jobs\$job\config.xml"
        docker exec -u root $Container mkdir -p "/var/jenkins_home/jobs/$job" 2>$null | Out-Null
        docker cp $src "${Container}:/var/jenkins_home/jobs/$job/config.xml"
        docker exec -u root $Container chown -R jenkins:jenkins "/var/jenkins_home/jobs/$job"
        Write-Host "Deployed $job"
    }
    if ($Restart) {
        docker restart $Container | Out-Null
        Wait-JenkinsContainer
        Wait-JenkinsHttp
    }
    Write-Host "Jenkins jobs: $JenkinsUrl (job1 build, job2 test, job3 deploy)"
}

function Invoke-ActionSetup {
    Write-Host "=== EqualVoice CI/CD setup ==="
    if (-not (Test-Path $EnvFile)) {
        Copy-Item ".env.example" $EnvFile
        Write-Host "Created .env from .env.example"
    }
    @("jenkins\jobs\job1", "jenkins\jobs\job2", "jenkins\jobs\job3", "backups", "logs") | ForEach-Object {
        if (-not (Test-Path $_)) { New-Item -ItemType Directory -Path $_ -Force | Out-Null }
    }
    Ensure-JenkinsJobConfigs
    docker compose -f docker-compose.jenkins.yml up -d --build
    docker compose -f docker-compose.yml up -d
    Wait-JenkinsContainer
    Invoke-ActionJobs
    Write-Host ""
    Write-Host "Next: .\scripts\cicd.ps1 -Action token -UseEnvPassword"
    Write-Host "Then: .\scripts\cicd.ps1"
    Write-Host "  Jenkins  http://localhost:9090"
    Write-Host "  App      http://localhost:8000"
    Write-Host "  phpMyAdmin http://localhost:8081"
}

function Invoke-ActionToken {
    Wait-JenkinsHttp
    $user = $JenkinsUser
    if ($UseEnvPassword -or $env:JENKINS_ADMIN_PASSWORD) {
        $pass = $env:JENKINS_ADMIN_PASSWORD
        if (-not $pass) { throw "Set JENKINS_ADMIN_PASSWORD in .env" }
    } else {
        $sec = Read-Host "Jenkins password for '$user'" -AsSecureString
        $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
        $pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
    $pair = "${user}:${pass}"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $crumb = Invoke-RestMethod -Uri "$JenkinsUrl/crumbIssuer/api/json" -Headers @{ Authorization = "Basic $b64" } -WebSession $session
    $headers = @{ Authorization = "Basic $b64" }
    $headers[$crumb.crumbRequestField] = $crumb.crumb
    $formBody = "json=" + [uri]::EscapeDataString('{"newTokenName":"ci-cd-auto"}')
    $resp = Invoke-RestMethod -Method Post -Uri "$JenkinsUrl/user/$user/descriptorByName/jenkins.security.ApiTokenProperty/generateNewToken" `
        -Headers $headers -WebSession $session -ContentType "application/x-www-form-urlencoded" -Body $formBody
    $token = $resp.data.tokenValue
    Save-DotEnvKey "JENKINS_API_TOKEN" $token
    Save-DotEnvKey "JENKINS_USER" $user
    Save-DotEnvKey "JENKINS_URL" $JenkinsUrl
    $script:JENKINS_API_TOKEN = $token
    $script:JenkinsToken = $token
    Write-Host "JENKINS_API_TOKEN saved to .env"
}

function Invoke-ActionPush {
    if (-not $GithubToken) { throw "Set GITHUB_TOKEN in .env" }
    $env:GITHUB_REPO = $GithubRepo
    $env:GITHUB_TOKEN = $GithubToken
    $remote = "https://x-access-token:${GithubToken}@github.com/${GithubRepo}.git"
    Write-Host "Pushing to ${GithubRepo} (branch ${Branch}) ..."
    git add -A
    git reset HEAD -- .env .env.local .jenkins-token 2>$null
    if (-not (git diff --cached --quiet)) {
        git commit -m $Message
    }
    git fetch $remote $Branch 2>$null
    if ($LASTEXITCODE -eq 0) { git rebase FETCH_HEAD 2>$null }
    git push $remote "HEAD:${Branch}"
    Write-Host "GitHub push OK"
}

function Get-JenkinsLastBuild {
    param([string]$Job)
    $pair = "${JenkinsUser}:${JenkinsToken}"
    try {
        $j = curl.exe -fsS -u $pair "$JenkinsUrl/job/$Job/lastBuild/api/json" | ConvertFrom-Json
        return [int]$j.number
    } catch { return 0 }
}

function Invoke-JenkinsBuild {
    param([string]$Job)
    $pair = "${JenkinsUser}:${JenkinsToken}"
    $before = Get-JenkinsLastBuild -Job $Job
    $uri = "$JenkinsUrl/job/$Job/build?delay=0sec"
    for ($a = 1; $a -le 5; $a++) {
        $crumb = curl.exe -fsS -u $pair "$JenkinsUrl/crumbIssuer/api/json" | ConvertFrom-Json
        $hdr = "$($crumb.crumbRequestField): $($crumb.crumb)"
        $code = curl.exe -X POST -u $pair -H $hdr -s -o NUL -w "%{http_code}" $uri
        if ($code -in @("201", "200", "302")) {
            $deadline = (Get-Date).AddSeconds(120)
            while ((Get-Date) -lt $deadline) {
                if ((Get-JenkinsLastBuild -Job $Job) -gt $before) { return (Get-JenkinsLastBuild -Job $Job) }
                Start-Sleep -Seconds 3
            }
            return ($before + 1)
        }
        Start-Sleep -Seconds 5
    }
    return $null
}

function Wait-JenkinsBuild {
    param([string]$Job, [int]$Num, [int]$TimeoutSec)
    $pair = "${JenkinsUser}:${JenkinsToken}"
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        $j = curl.exe -fsS -u $pair "$JenkinsUrl/job/$Job/$Num/api/json" | ConvertFrom-Json
        if ($j.building) { Write-Host "  $Job #$Num building..." }
        elseif ($j.result -eq "SUCCESS") { Write-Host "  $Job #$Num SUCCESS"; return $true }
        elseif ($j.result) { Write-Host "  $Job #$Num $($j.result)"; return $false }
        Start-Sleep -Seconds 15
    }
    throw "Timeout: $Job #$Num"
}

function Wait-Downstream {
    param([string]$Job, [int]$After, [int]$TimeoutSec)
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        if ((Get-JenkinsLastBuild -Job $Job) -gt $After) {
            return (Wait-JenkinsBuild -Job $Job -Num (Get-JenkinsLastBuild -Job $Job) -TimeoutSec $TimeoutSec)
        }
        Start-Sleep -Seconds 5
    }
    throw "Timeout waiting for $Job"
}

function Invoke-ActionRun {
    Write-Host "=== EqualVoice CI/CD ==="
    Ensure-JenkinsJobConfigs
    Invoke-ActionJobs
    if (-not $SkipGitHub) {
        if ($GithubToken) { Invoke-ActionPush } else { Write-Warning "GITHUB_TOKEN not set - skip push" }
    }
    if ($SkipJenkins) { return }
    if (-not $JenkinsToken) { throw "JENKINS_API_TOKEN missing - run: .\scripts\cicd.ps1 -Action token -UseEnvPassword" }
    Wait-JenkinsHttp
    $j1b = Get-JenkinsLastBuild -Job "job1"
    $j2b = Get-JenkinsLastBuild -Job "job2"
    $j3b = Get-JenkinsLastBuild -Job "job3"
    Write-Host "Triggering job1..."
    $n1 = Invoke-JenkinsBuild -Job "job1"
    if (-not $n1) { throw "Failed to queue job1" }
    if (-not (Wait-JenkinsBuild -Job "job1" -Num $n1 -TimeoutSec $BuildTimeoutSec)) { throw "job1 failed" }
    if (-not (Wait-Downstream -Job "job2" -After $j2b -TimeoutSec $BuildTimeoutSec)) { throw "job2 failed" }
    if (-not (Wait-Downstream -Job "job3" -After $j3b -TimeoutSec $BuildTimeoutSec)) { throw "job3 failed" }
    Write-Host ""
    Write-Host "=== CI/CD complete ==="
    Write-Host "Jenkins: $JenkinsUrl"
    Write-Host "App:     http://localhost:8000"
}

switch ($Action) {
    "setup" { Invoke-ActionSetup }
    "token" { Invoke-ActionToken }
    "jobs"  { Invoke-ActionJobs }
    "push"  { Invoke-ActionPush }
    "run"   { Invoke-ActionRun }
}
