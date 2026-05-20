# EqualVoice

PHP mentorship platform with automated **GitHub → Jenkins → Docker** delivery.

## Quick start

```powershell
cd c:\xampp\htdocs\equalvoice

# 1. One-time setup (Docker + Jenkins + app + job1/2/3)
powershell -ExecutionPolicy Bypass -File scripts\cicd.ps1 -Action setup

# 2. Copy and edit secrets (never commit .env)
copy .env.example .env
# Set: GITHUB_TOKEN, GITHUB_REPO, JENKINS_ADMIN_PASSWORD=admin123

# 3. Save Jenkins API token into .env
powershell -ExecutionPolicy Bypass -File scripts\cicd.ps1 -Action token -UseEnvPassword

# 4. Run full pipeline
powershell -ExecutionPolicy Bypass -File scripts\cicd.ps1
```

## URLs

| Service | URL |
|---------|-----|
| Application | http://localhost:8000 |
| Jenkins | http://localhost:9090 |
| phpMyAdmin | http://localhost:8081 |

Default Jenkins login: `admin` / value of `JENKINS_ADMIN_PASSWORD` in `.env` (default `admin123`).

## Single CI/CD script

All automation lives in **`scripts/cicd.ps1`**:

| Action | Command |
|--------|---------|
| Full pipeline (push + Jenkins) | `.\scripts\cicd.ps1` |
| First-time setup | `.\scripts\cicd.ps1 -Action setup` |
| Create Jenkins API token | `.\scripts\cicd.ps1 -Action token -UseEnvPassword` |
| Install/refresh job1–job3 | `.\scripts\cicd.ps1 -Action jobs` |
| GitHub push only | `.\scripts\cicd.ps1 -Action push` |
| Jenkins only (no GitHub) | `.\scripts\cicd.ps1 -SkipGitHub` |

## Jenkins pipeline (job1 → job2 → job3)

| Job | Purpose |
|-----|---------|
| **job1** | Checkout, PHP lint, `docker build` (`equalvoice-app`) |
| **job2** | MySQL health, connectivity test, web smoke test |
| **job3** | Deploy `web`, `mysql`, `phpmyadmin` (does not stop Jenkins) |

Job definitions: `jenkins/jobs/job1|job2|job3/config.xml`  
Inside Jenkins, shell steps call `scripts/jenkins-ci.sh` (`checkout` / `deploy`).

If Jenkins was recreated and jobs are missing:

```powershell
.\scripts\cicd.ps1 -Action jobs -Restart
```

## Local development

```powershell
# App stack (live code mount on port 8000)
docker compose up -d

# Sass watch (optional)
.\scripts\compile-sass.ps1
```

## Project layout

```
equalvoice/
├── scripts/
│   ├── cicd.ps1          # ← main automation (use this)
│   ├── jenkins-ci.sh     # used inside Jenkins container
│   └── compile-sass.ps1  # dev only
├── jenkins/jobs/         # job1, job2, job3
├── docker/               # Apache, PHP, Jenkins image
├── docker-compose.yml
├── docker-compose.jenkins.yml
├── docker-compose.ci.yml
├── Jenkinsfile           # orchestrates job1→job2→job3 on main
└── .env.example          # copy to .env (gitignored)
```

## Security

- **Never commit `.env`** — it holds tokens and passwords.
- Revoke any token that was ever committed; create a new `GITHUB_TOKEN`.
- Git push excludes `.env`, `.env.local`, `.jenkins-token` automatically.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Jenkins jobs 404 | `.\scripts\cicd.ps1 -Action jobs -Restart` |
| Push blocked (secret in commit) | Remove `.env` from git: `git rm --cached .env` |
| Jenkins 401 | `.\scripts\cicd.ps1 -Action token -UseEnvPassword` |
| Port 9090 busy | Stop other Jenkins: `docker stop jenkins` |
