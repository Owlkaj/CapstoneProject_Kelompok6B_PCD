# =============================================================================
#  Skema B - Domain Fine-Tuning & Perbandingan Varian
#  Jalankan dari folder ml/ :  powershell -ExecutionPolicy Bypass -File run_skema_b.ps1
# =============================================================================
$ErrorActionPreference = "Stop"
$PY = "..\ml\.venv\Scripts\python.exe"
if (-not (Test-Path $PY)) { $PY = ".\.venv\Scripts\python.exe" }
if (-not (Test-Path $PY)) { Write-Error "Python venv tidak ditemukan (ml\.venv)."; exit 1 }

Write-Host "=== [1/6] Bangun manifest dataset baru (pisah sumber otomatis) ===" -ForegroundColor Cyan
& $PY -m src.data.build_source_manifest --root "..\dataset lapangan" --out "dataset\splits" --tag field2 --test-ratio 0.15 --seed 42

Write-Host "`n=== [2/6] Dataset berimbang: 300/kelas lapangan + 20.000 PlantVillage ===" -ForegroundColor Cyan
& $PY -m src.data.build_balanced_dataset `
    --field-manifest "dataset\splits\manifest_field2_field.csv" `
    --pv-manifest      "dataset\splits\manifest.csv" `
    --out "dataset\splits" --per-class 300 --augment-mode rows --val-cap 30 --test-cap 30

Write-Host "`n=== [3/6] Tandai manifest PlantVillage + bersihkan kebocoran duplikat ===" -ForegroundColor Cyan
& $PY -m src.data.tag_manifest_source `
    --manifest "dataset\splits\manifest.csv" --out "dataset\splits\manifest_pv.csv" --source plantvillage
& $PY -m src.data.dedupe_manifest `
    --manifest "dataset\splits\manifest_field_only.csv" `
    --out "dataset\splits\manifest_field_only_clean.csv" --threshold 4

Write-Host "`n=== [4/6] Varian A: fine-tune HANYA lapangan (dari E02) ===" -ForegroundColor Cyan
& $PY -m src.training.train --stage field `
    --field-manifest "dataset\splits\manifest_field_only.csv" `
    --out-model "models\model_field_only.pt"

Write-Host "`n=== [5/6] Varian B: fine-tune CAMPUR lapangan + PlantVillage 50/50 (dari E02) ===" -ForegroundColor Cyan
& $PY -m src.training.train --stage field `
    --field-manifest "dataset\splits\manifest_field_only.csv" `
    --source-manifest "dataset\splits\manifest_pv.csv" `
    --mix-ratio 1.0 `
    --out-model "models\model_field_mixed.pt"

Write-Host "`n=== [6/6] Bandingkan semua model pada dua domain ===" -ForegroundColor Cyan
Write-Host "--- BASELINE (model lama E02) ---"
& $PY -m src.evaluation.evaluate --compare-domains --model "ml\models\final_model.pt"
Write-Host "--- Varian A (hanya lapangan) ---"
& $PY -m src.evaluation.evaluate --compare-domains --model "models\model_field_only.pt"
Write-Host "--- Varian B (campur) ---"
& $PY -m src.evaluation.evaluate --compare-domains --model "models\model_field_mixed.pt"

Write-Host "`nSELESAI. Hasil: models\model_field_only.pt, models\model_field_mixed.pt" -ForegroundColor Green
Write-Host "Metrik: ml\results\evaluation_metrics_pv.json & evaluation_metrics_field.json" -ForegroundColor Green
