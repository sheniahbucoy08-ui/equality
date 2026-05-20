# EqualVoice CI/CD (GitHub + Jenkins + Docker)

Same automation pattern as **hrms-main**: three Jenkins jobs chained **job1 → job2 → job3**, plus scripts to push to GitHub and run the full pipeline.

## Architecture

| Component | Purpose |
|-----------|---------|
| **job1** | Clone/checkout, PHP lint, `docker build` (`equalvoice-app`) |
| **job2** | Start MySQL, DB connectivity test, CI smoke test (`docker-compose.ci.yml`) |
| **job3** | Deploy app stack (`web`, `mysql`, `phpmyadmin`) — does **not** stop Jenkins |
| **ci-cd-auto** | Push to GitHub, then trigger job1 and wait for job2/job3 |
| **docker-compose.jenkins.yml** | Jenkins on port **9090** (separate from the app) |

## Quick start (Windows)

```powershell
# 1. One-time setup
powershell -ExecutionPolicy Bypass -File scripts/complete-setup.ps1

# 2. Create Jenkins API token → writes JENKINS_API_TOKEN to .env
powershell -ExecutionPolicy Bypass -File scripts/jenkins-create-api-token.ps1

# 3. Edit .env: GITHUB_TOKEN, GITHUB_REPO, DB passwords (never commit .env)

# 4. Run full pipeline (GitHub push + Jenkins job1/2/3)
 
```

## URLs

| Service | URL |
|---------|-----|
| Jenkins | http://localhost:9090 |
| App (dev override) | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |

## Useful commands

```powershell
# Jenkins only
.\scripts\start-jenkins.ps1

# Redeploy job configs after editing jenkins/jobs/*/config.xml
.\scripts\jenkins-deploy-jobs.ps1 -Restart

# GitHub push only
$env:GITHUB_TOKEN = "..."; $env:GITHUB_REPO = "owner/repo"
.\scripts\github-auto-push.bat "commit message" main

# Jenkins only (skip GitHub)
.\scripts\ci-cd-auto.ps1 -SkipGitHub

# GitHub only (skip Jenkins)
.\scripts\ci-cd-auto.ps1 -SkipJenkins
```

## Files (mirror of hrms-main)

- `docker-compose.jenkins.yml` — Jenkins container
- `docker-compose.ci.yml` — CI port overrides
- `docker/jenkins/` — Jenkins image + job seeding
- `jenkins/jobs/job1|job2|job3/config.xml`
- `scripts/ci-cd-auto.ps1`, `github-auto-push.sh`, `jenkins-*.sh/ps1`
- `Jenkinsfile` — orchestrates job1 → job2 → job3 on `main`

The older monolithic `Jenkinsfile` (single pipeline with Docker Hub push) is replaced by this multi-job layout. Registry push can still be added to job3 if needed.
