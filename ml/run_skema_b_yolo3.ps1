# =============================================================================
#  Skema B (jalur YOLO-OBB, dataset 3 = 9 kelas) - crop -> dataset -> latih -> evaluasi
#  Jalankan dari folder ml/ :  powershell -ExecutionPolicy Bypass -File run_skema_b_yolo3.ps1
#
#  Dataset : dataset yolo/dataset3_full  ("leaf disease v1", 5.688 gambar, 9 kelas)
#  Target  : 1.500 crop/kelas lapangan (8 kelas; Leaf Miner dibuang)
#
#  PENTING (perbaikan 2026-11-08): split field dilakukan per group_id (gambar
#  asal), bukan per crop. Semua crop dari satu daun tetap di satu split supaya
#  test tidak bocor ke train.
# =============================================================================
$ErrorActionPreference = "Stop"
$PY = ".\.venv\Scripts\python.exe"
if (-not (Test-Path $PY)) { $PY = "..\ml\.venv\Scripts\python.exe" }
if (-not (Test-Path $PY)) { Write-Error "Python venv tidak ditemukan (ml\.venv)."; exit 1 }

$YOLO_ROOT  = "..\dataset yolo\dataset3_full"
$PER_CLASS  = 1500
$VAL_CAP    = 150
$TEST_CAP   = 150

Write-Host "=== [1/6] Konversi YOLO-OBB -> crop klasifikasi (8 kelas; Leaf Miner dibuang) ===" -ForegroundColor Cyan
& $PY -m src.data.yolo_obb_to_classification `
    --root $YOLO_ROOT `
    --out "dataset\field_yolo3" `
    --manifest "dataset\splits\manifest_yolo3.csv" `
    --margin 0.12 --min-box-px 48 --max-boxes-per-image 20 --supersample 2

Write-Host "`n=== [2/6] Buang crop identik/near-duplicate sebelum balancing ===" -ForegroundColor Cyan
& $PY -m src.data.dedupe_manifest `
    --manifest "dataset\splits\manifest_yolo3.csv" `
    --out "dataset\splits\manifest_yolo3_clean.csv" --threshold 4

Write-Host "`n=== [3/6] Dataset berimbang $PER_CLASS/kelas + 20.000 PlantVillage (group-aware split) ===" -ForegroundColor Cyan
& $PY -m src.data.build_balanced_dataset `
    --field-manifest "dataset\splits\manifest_yolo3_clean.csv" `
    --pv-manifest      "dataset\splits\manifest.csv" `
    --out "dataset\splits" --per-class $PER_CLASS --augment-mode rows --val-cap $VAL_CAP --test-cap $TEST_CAP

Write-Host "`n=== [4/6] Bersihkan near-duplicate pada split FINAL (setelah group split) ===" -ForegroundColor Cyan
& $PY -m src.data.dedupe_manifest `
    --manifest "dataset\splits\manifest_field_only.csv" `
    --out "dataset\splits\manifest_field_only_clean.csv" --threshold 4

Write-Host "`n=== [5/7] Fine-tune CAMPUR (dari E02) - 10 kelas tetap terwakili ===" -ForegroundColor Cyan
& $PY -m src.training.train --stage field `
    --field-manifest "dataset\splits\manifest_field_only_clean.csv" `
    --source-manifest "dataset\splits\manifest_pv.csv" `
    --mix-ratio 1.0 `
    --out-model "models\model_yolo3_grouped.pt"

Write-Host "`n=== [6/7] Kalibrasi confidence gating pada validation split (bukan test) ===" -ForegroundColor Cyan
& $PY -m src.evaluation.calibrate_gating `
    --model "models\model_yolo3_grouped.pt" `
    --manifest "dataset\splits\manifest_field_only_clean.csv" `
    --split val

Write-Host "`n=== [7/7] Evaluasi (test group-disjoint + cross-dataset) ===" -ForegroundColor Cyan
Write-Host "--- PlantVillage (2.000 citra) ---"
& $PY -m src.evaluation.evaluate --model "models\model_yolo3_grouped.pt" `
    --manifest "dataset\splits\manifest.csv" --tag final_yolo3_grouped_pv
Write-Host "--- Field 8 kelas, split final group-disjoint ---"
& $PY -m src.evaluation.evaluate --model "models\model_yolo3_grouped.pt" `
    --manifest "dataset\splits\manifest_field_only_clean.csv" --tag final_yolo3_grouped_field
Write-Host "--- Field 3 kelas lintas-dataset (tidak pernah dilatih) ---"
& $PY -m src.evaluation.evaluate --model "models\model_yolo3_grouped.pt" `
    --manifest "dataset\splits\manifest_yolo_clean.csv" --tag cross_yolo3_grouped_on_ds2
Write-Host "--- Baseline E02 pada field group-disjoint, sebagai pembanding ---"
& $PY -m src.evaluation.evaluate --model "ml\models\final_model.pt" `
    --manifest "dataset\splits\manifest_field_only_clean.csv" --tag baseline_grouped_field

Write-Host "`nSELESAI. Model: models\model_yolo3_grouped.pt" -ForegroundColor Green
