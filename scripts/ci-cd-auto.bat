@echo off
REM EqualVoice full CI/CD (GitHub push + Jenkins job1/2/3)
powershell -ExecutionPolicy Bypass -File "%~dp0ci-cd-auto.ps1" %*
