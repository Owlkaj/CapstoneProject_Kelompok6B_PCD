# =============================================================================
#  Skema B (jalur YOLO-OBB) - konversi box -> crop -> dataset -> latih -> evaluasi
#  Jalankan dari folder ml/ :  powershell -ExecutionPolicy Bypass -File run_skema_b_yolo.ps1
# =============================================================================
$ErrorActionPreference = "Stop"
$PY = ".\.venv\Scripts\python.exe"
if (-not (Test-Path $PY)) { $PY = "..\ml\.venv\Scripts\python.exe" }
if (-not (Test-Path $PY)) { Write-Error "Python venv tidak ditemukan (ml\.venv)."; exit 1 }

$YOLO_ROOT = "..\dataset yolo\New folder"

Write-Host "=== [1/5] Konversi YOLO-OBB -> crop klasifikasi (buang Powdery Mildew) ===" -ForegroundColor Cyan
& $PY -m src.data.yolo_obb_to_classification `
    --root $YOLO_ROOT `
    --out "dataset\field_yolo" `
    --manifest "dataset\splits\manifest_yolo.csv" `
    --margin 0.12 --min-box-px 48 --max-boxes-per-image 20 --supersample 2

Write-Host "`n=== [2/5] Bersihkan kebocoran duplikat antar-split ===" -ForegroundColor Cyan
& $PY -m src.data.dedupe_manifest `
    --manifest "dataset\splits\manifest_yolo.csv" `
    --out "dataset\splits\manifest_yolo_clean.csv" --threshold 4

Write-Host "`n=== [3/5] Dataset berimbang 1.800/kelas lapangan + 20.000 PlantVillage ===" -ForegroundColor Cyan
& $PY -m src.data.build_balanced_dataset `
    --field-manifest "dataset\splits\manifest_yolo_clean.csv" `
    --pv-manifest      "dataset\splits\manifest.csv" `
    --out "dataset\splits" --per-class 1800 --augment-mode rows --val-cap 180 --test-cap 180

Write-Host "`n=== [4/5] Fine-tune CAMPUR (dari E02) ===" -ForegroundColor Cyan
& $PY -m src.training.train --stage field `
    --field-manifest "dataset\splits\manifest_field_only.csv" `
    --source-manifest "dataset\splits\manifest_pv.csv" `
    --mix-ratio 1.0 `
    --out-model "models\model_yolo_mixed.pt"

Write-Host "`n=== [5/5] Evaluasi pada dua domain ===" -ForegroundColor Cyan
& $PY -m src.evaluation.evaluate --compare-domains --model "models\model_yolo_mixed.pt"
& $PY -m src.evaluation.evaluate --model "models\model_yolo_mixed.pt" `
    --manifest "dataset\splits\manifest_yolo_clean.csv" --tag yolo_field

Write-Host "`nSELESAI. Model: models\model_yolo_mixed.pt" -ForegroundColor Green
