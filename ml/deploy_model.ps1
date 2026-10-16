# =============================================================================
#  Deploy a trained model into the FastAPI service (with automatic backup).
#  Usage (from repo root):
#     powershell -ExecutionPolicy Bypass -File ml\deploy_model.ps1 -Model ml\models\model_field_mixed.pt
# =============================================================================
param(
    [Parameter(Mandatory = $true)][string]$Model,
    [string]$Target = "fastapi\models\final_model.pt"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $Model)) { Write-Error "Model tidak ditemukan: $Model"; exit 1 }

$targetDir = Split-Path $Target -Parent
New-Item -ItemType Directory -Force -Path $targetDir | Out-Null

# Backup existing model
if (Test-Path $Target) {
    $stamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backup = "$Target.bak_$stamp"
    Copy-Item $Target $backup
    Write-Host "Backup model lama -> $backup" -ForegroundColor Yellow
}

Copy-Item $Model $Target -Force
Write-Host "Model di-deploy:" -ForegroundColor Green
Write-Host "  dari : $Model"
Write-Host "  ke   : $Target"
Write-Host "`nRestart FastAPI agar model baru dimuat (stop_public_demo.bat lalu start_public_demo.bat)." -ForegroundColor Cyan
