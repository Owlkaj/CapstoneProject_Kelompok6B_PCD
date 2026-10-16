# =============================================================================
#  Skema B (jalur YOLO-OBB, dataset 3 = 9 kelas) - crop -> dataset -> latih -> evaluasi
#  Jalankan dari folder ml/ :  powershell -ExecutionPolicy Bypass -File run_skema_b_yolo3.ps1
#
#  Dataset : dataset yolo/dataset3_full  ("leaf disease v1", 5.688 gambar, 9 kelas)
#  Target  : 1.500 crop/kelas lapangan (8 kelas; Leaf Miner dibuang)
# =============================================================================
$ErrorActionPreference = "Stop"
$PY = ".\.venv\Scripts\python.exe"
if (-not (Test-Path $PY)) { $PY = "..\ml\.venv\Scripts\python.exe" }
if (-not (Test-Path $PY)) { Write-Error "Python venv tidak ditemukan (ml\.venv)."; exit 1 }

$YOLO_ROOT  = "..\dataset yolo\dataset3_full"
$PER_CLASS  = 1500
$VAL_CAP    = 150
$TEST_CAP   = 150

Write-Host "=== [1/5] Konversi YOLO-OBB -> crop klasifikasi (8 kelas; Leaf Miner dibuang) ===" -ForegroundColor Cyan
& $PY -m src.data.yolo_obb_to_classification `
    --root $YOLO_ROOT `
    --out "dataset\field_yolo3" `
    --manifest "dataset\splits\manifest_yolo3.csv" `
    --margin 0.12 --min-box-px 48 --max-boxes-per-image 20 --supersample 2

Write-Host "`n=== [2/5] Bersihkan kebocoran duplikat (dataset ini punya augmentasi flip) ===" -ForegroundColor Cyan
& $PY -m src.data.dedupe_manifest `
    --manifest "dataset\splits\manifest_yolo3.csv" `
    --out "dataset\splits\manifest_yolo3_clean.csv" --threshold 4

Write-Host "`n=== [3/5] Dataset berimbang $PER_CLASS/kelas + 20.000 PlantVillage ===" -ForegroundColor Cyan
& $PY -m src.data.build_balanced_dataset `
    --field-manifest "dataset\splits\manifest_yolo3_clean.csv" `
    --pv-manifest      "dataset\splits\manifest.csv" `
    --out "dataset\splits" --per-class $PER_CLASS --augment-mode rows --val-cap $VAL_CAP --test-cap $TEST_CAP

Write-Host "`n=== [4/5] Fine-tune CAMPUR (dari E02) - 10 kelas tetap terwakili ===" -ForegroundColor Cyan
& $PY -m src.training.train --stage field `
    --field-manifest "dataset\splits\manifest_field_only.csv" `
    --source-manifest "dataset\splits\manifest_pv.csv" `
    --mix-ratio 1.0 `
    --out-model "models\model_yolo3_mixed.pt"

Write-Host "`n=== [5/5] Evaluasi ===" -ForegroundColor Cyan
Write-Host "--- PlantVillage + field (3 kelas terakhir) ---"
& $PY -m src.evaluation.evaluate --compare-domains --model "models\model_yolo3_mixed.pt"
Write-Host "--- field penuh (8 kelas, test identik & bersih) ---"
& $PY -m src.evaluation.evaluate --model "models\model_yolo3_mixed.pt" `
    --manifest "dataset\splits\manifest_yolo3_clean.csv" --tag yolo3_field
Write-Host "--- baseline (model lama) pada field 8 kelas, sebagai pembanding ---"
& $PY -m src.evaluation.evaluate --model "ml\models\final_model.pt" `
    --manifest "dataset\splits\manifest_yolo3_clean.csv" --tag baseline_yolo3_field

Write-Host "`nSELESAI. Model: models\model_yolo3_mixed.pt" -ForegroundColor Green
