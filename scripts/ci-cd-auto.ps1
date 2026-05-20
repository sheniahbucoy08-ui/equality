# Full CI/CD: push to GitHub, then trigger Jenkins job1 -> job2 -> job3
param(
    [string]$Message = "CI/CD automated push $(Get-Date -Format 'yyyy-MM-dd HH:mm')",
    [string]$Branch = "",
    [switch]$SkipGitHub,
    [switch]$SkipJenkins,
    [switch]$DeployOnly,
    [switch]$UpdateJobs,
    [int]$BuildTimeoutMin = 60
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Load-DotEnv {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return }
    Get-Content $Path | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
        $k, $v = $_ -split '=', 2
        $k = $k.Trim()
        $v = $v.Trim().Trim('"').Trim("'")
        if ($k) { Set-Item -Path "env:$k" -Value $v }
    }
}

Load-DotEnv (Join-Path $Root ".env")

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

function Get-JenkinsHeaders {
    $pair = "${JenkinsUser}:${JenkinsToken}"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    return @{ Authorization = "Basic $b64" }
}

function Invoke-JenkinsApi {
    param(
        [string]$Uri,
        [string]$Method = "Get",
        [hashtable]$ExtraHeaders = @{}
    )
    $headers = Get-JenkinsHeaders
    foreach ($k in $ExtraHeaders.Keys) { $headers[$k] = $ExtraHeaders[$k] }
    return Invoke-RestMethod -Uri $Uri -Headers $headers -Method $Method -TimeoutSec 120
}

function Wait-JenkinsReady {
    param([int]$TimeoutSec = 300)
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        try {
            $null = Invoke-JenkinsApi -Uri "$JenkinsUrl/api/json"
            return $true
        } catch {
            Start-Sleep -Seconds 5
        }
    }
    return $false
}

function Get-JenkinsLastBuildNumber {
    param([string]$JobName)
    try {
        $info = Invoke-JenkinsApi -Uri "$JenkinsUrl/job/$JobName/lastBuild/api/json"
        return [int]$info.number
    } catch {
        return 0
    }
}

function Invoke-JenkinsBuild {
    param([string]$JobName)

    Write-Host "Triggering Jenkins: $JobName ..."
    $headers = Get-JenkinsHeaders
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    $crumbResp = Invoke-RestMethod `
        -Uri "$JenkinsUrl/crumbIssuer/api/json" `
        -Headers $headers `
        -WebSession $session `
        -Method Get `
        -TimeoutSec 30
    $headers[$crumbResp.crumbRequestField] = $crumbResp.crumb

    $before = Get-JenkinsLastBuildNumber -JobName $JobName

    $resp = Invoke-WebRequest `
        -Uri "$JenkinsUrl/job/$JobName/build" `
        -Method Post `
        -Headers $headers `
        -WebSession $session `
        -UseBasicParsing `
        -TimeoutSec 60

    if ($resp.StatusCode -lt 200 -or $resp.StatusCode -ge 400) {
        return $null
    }

    Write-Host "  Queued $JobName"
    $deadline = (Get-Date).AddSeconds(300)
    while ((Get-Date) -lt $deadline) {
        $current = Get-JenkinsLastBuildNumber -JobName $JobName
        if ($current -gt $before) { return $current }
        Start-Sleep -Seconds 3
    }
    return ($before + 1)
}

function Wait-JenkinsBuildNumber {
    param(
        [string]$JobName,
        [int]$BuildNumber,
        [int]$TimeoutSec = 3600
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    $uri = "$JenkinsUrl/job/$JobName/$BuildNumber/api/json"
    $errors = 0

    while ((Get-Date) -lt $deadline) {
        try {
            $info = Invoke-JenkinsApi -Uri $uri
            $errors = 0
            if ($info.building) {
                Write-Host "  $JobName #$BuildNumber building..."
            } elseif ($info.result -eq "SUCCESS") {
                Write-Host "  $JobName #$BuildNumber SUCCESS"
                return $true
            } elseif ($null -eq $info.result) {
                Write-Host "  $JobName #$BuildNumber pending..."
            } else {
                Write-Host "  $JobName #$BuildNumber $($info.result)"
                return $false
            }
        } catch {
            $errors++
            Write-Host "  Jenkins API retry ($errors)..."
            if ($errors -ge 30) {
                throw "Jenkins not reachable while waiting for $JobName #$BuildNumber"
            }
            Start-Sleep -Seconds 5
        }
        Start-Sleep -Seconds 15
    }
    throw "Timeout waiting for $JobName #$BuildNumber"
}

function Wait-DownstreamBuild {
    param(
        [string]$JobName,
        [int]$AfterNumber,
        [int]$TimeoutSec = 3600
    )

    Write-Host "Waiting for $JobName (after build #$AfterNumber)..."
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    $targetBuild = $null

    while ((Get-Date) -lt $deadline) {
        $current = Get-JenkinsLastBuildNumber -JobName $JobName
        if ($current -gt $AfterNumber) {
            $targetBuild = $current
            break
        }
        Start-Sleep -Seconds 5
    }

    if (-not $targetBuild) {
        throw "Timeout: $JobName was not triggered"
    }

    return (Wait-JenkinsBuildNumber -JobName $JobName -BuildNumber $targetBuild -TimeoutSec $TimeoutSec)
}

Write-Host ""
Write-Host "=== EqualVoice CI/CD Auto (GitHub + Jenkins) ==="
Write-Host ""

if ($UpdateJobs -and -not $SkipJenkins) {
    & (Join-Path $Root "scripts\jenkins-deploy-jobs.ps1") -Restart
}

if (-not $SkipJenkins) {
    if (-not (Wait-JenkinsReady)) {
        throw "Jenkins is not running at $JenkinsUrl"
    }
}

if (-not $SkipGitHub -and -not $DeployOnly) {
    if (-not $GithubToken) {
        Write-Warning "GITHUB_TOKEN not set in .env - skipping GitHub push"
    } else {
        Write-Host "Pushing to GitHub ($GithubRepo @ $Branch)..."
        $env:GITHUB_REPO = $GithubRepo
        $env:GITHUB_TOKEN = $GithubToken
        & (Join-Path $Root "scripts\github-auto-push.bat") $Message $Branch
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "GitHub push failed - continuing with Jenkins"
        }
    }
}

if (-not $SkipJenkins) {
    if (-not $JenkinsToken) {
        Write-Warning "JENKINS_API_TOKEN not set in .env"
        exit 1
    }

    $job1Before = Get-JenkinsLastBuildNumber -JobName "job1"
    $job2Before = Get-JenkinsLastBuildNumber -JobName "job2"
    $job3Before = Get-JenkinsLastBuildNumber -JobName "job3"

    $job1Build = Invoke-JenkinsBuild -JobName "job1"
    if (-not $job1Build) { throw "Failed to queue job1" }

    if (-not (Wait-JenkinsBuildNumber -JobName "job1" -BuildNumber $job1Build -TimeoutSec $BuildTimeoutSec)) {
        throw "job1 failed - see $JenkinsUrl/job/job1/$job1Build/console"
    }

    if (-not (Wait-DownstreamBuild -JobName "job2" -AfterNumber $job2Before -TimeoutSec $BuildTimeoutSec)) {
        throw "job2 failed - see $JenkinsUrl/job/job2/lastBuild/console"
    }

    if (-not (Wait-DownstreamBuild -JobName "job3" -AfterNumber $job3Before -TimeoutSec $BuildTimeoutSec)) {
        throw "job3 failed - see $JenkinsUrl/job/job3/lastBuild/console"
    }
}

Write-Host ""
Write-Host "=== CI/CD complete ==="
Write-Host "Jenkins: $JenkinsUrl"
Write-Host "App:     http://localhost:8000"
