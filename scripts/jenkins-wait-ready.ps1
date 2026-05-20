# Wait until Jenkins HTTP API responds
param(
    [string]$JenkinsUrl = "http://localhost:9090",
    [int]$MaxAttempts = 60
)

for ($i = 1; $i -le $MaxAttempts; $i++) {
    try {
        $r = Invoke-WebRequest -Uri "$JenkinsUrl/api/json" -UseBasicParsing -TimeoutSec 5
        if ($r.StatusCode -eq 200) {
            Write-Host "Jenkins is ready"
            return
        }
    } catch {
        Write-Host "Waiting for Jenkins ($i/$MaxAttempts)..."
        Start-Sleep -Seconds 3
    }
}
throw "Jenkins did not become ready at $JenkinsUrl"
