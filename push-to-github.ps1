# Push this repo only as ppsudipta (does not change global GitHub login).
# Usage:
#   1. Create a token: https://github.com/settings/tokens (scope: repo)
#   2. Run:  $env:GITHUB_TOKEN = "ghp_your_token_here"
#   3. Run:  .\push-to-github.ps1

if (-not $env:GITHUB_TOKEN) {
    Write-Host "Set your ppsudipta GitHub token first:" -ForegroundColor Yellow
    Write-Host '  $env:GITHUB_TOKEN = "ghp_your_token_here"' -ForegroundColor Cyan
    Write-Host "Create one at: https://github.com/settings/tokens" -ForegroundColor Gray
    exit 1
}

$repoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $repoRoot

git push "https://ppsudipta:$($env:GITHUB_TOKEN)@github.com/ppsudipta/student.git" main
if ($LASTEXITCODE -eq 0) {
    Write-Host "Pushed to https://github.com/ppsudipta/student" -ForegroundColor Green
}
