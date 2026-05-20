# Trigger a Jenkins build from PowerShell.
# Reads the token from .jenkins-token (which is gitignored).
# For full CI/CD (GitHub + job1/2/3), use scripts/ci-cd-auto.ps1 instead.
# Usage: .\scripts\trigger-build.ps1 [-Job job1] [-JenkinsUrl http://localhost:9090] [-User admin]

param(
    [string]$Job = "job1",
    [string]$JenkinsUrl = "http://localhost:9090",
    [string]$User = "admin",
    [string]$TokenFile = ".jenkins-token"
)

if (-not (Test-Path $TokenFile)) {
    Write-Error "Token file '$TokenFile' not found. Generate one via Jenkins UI or the docker init.groovy.d trick and save it there."
    exit 1
}

$token  = (Get-Content $TokenFile -Raw).Trim()
$creds  = "${User}:$token"

$crumbJson  = curl.exe -s -u $creds "$JenkinsUrl/crumbIssuer/api/json"
$parsed     = $crumbJson | ConvertFrom-Json
$crumbHdr   = "$($parsed.crumbRequestField): $($parsed.crumb)"

$status = curl.exe -X POST -u $creds -H $crumbHdr -s -o NUL -w "%{http_code}" "$JenkinsUrl/job/$Job/build?delay=0sec"

if ($status -eq "201") {
    Start-Sleep -Seconds 2
    $last = curl.exe -s -u $creds "$JenkinsUrl/job/$Job/lastBuild/api/json" | ConvertFrom-Json
    Write-Host "Triggered build #$($last.number) -> $($last.url)"
} else {
    Write-Error "Failed to trigger build. HTTP $status"
    exit 1
}
