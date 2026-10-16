import os
import sys
import io
import pandas as pd
from pathlib import Path
from PIL import Image

current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent
sys.path.append(str(current_dir))

from fastapi.testclient import TestClient
from app.main import app

def create_dummy_image(format="JPEG", size=(256, 256), color=(100, 150, 200)):
    buf = io.BytesIO()
    img = Image.new("RGB", size, color=color)
    img.save(buf, format=format)
    buf.seek(0)
    return buf.read()

def test_health_check(client: TestClient):
    print("\n[1/7] Testing GET /health...")
    res = client.get("/health")
    assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
    data = res.json()
    assert data["status"] == "ok", f"Unexpected status: {data}"
    assert data["model_loaded"] is True, f"Model not loaded: {data}"
    print(f"  -> Health Response: {data} [PASS]")

def test_predict_synthetic_contract(client: TestClient):
    print("\n[2/7] Testing POST /predict contract with Synthetic JPEG...")
    jpg_bytes = create_dummy_image(format="JPEG")
    res = client.post("/predict", files={"image": ("test_leaf.jpg", jpg_bytes, "image/jpeg")})
    assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
    pred = res.json()
    print(f"  -> Prediction Response: {pred}")

    # Verify 4-field contract and exact data types
    for field in ["class", "confidence", "class_id", "inference_time_ms"]:
        assert field in pred, f"Missing required contract field '{field}' in response!"

    assert isinstance(pred["class"], str) and len(pred["class"]) > 0, "Field 'class' must be non-empty string"
    assert isinstance(pred["class_id"], int), f"Field 'class_id' must be integer, got {type(pred['class_id'])}"
    assert 0 <= pred["class_id"] <= 9, f"class_id out of range 0-9: {pred['class_id']}"
    assert isinstance(pred["confidence"], (int, float)) and 0.0 <= pred["confidence"] <= 1.0, f"Invalid confidence range: {pred['confidence']}"
    assert isinstance(pred["inference_time_ms"], (int, float)) and pred["inference_time_ms"] > 0, f"Invalid inference time: {pred['inference_time_ms']}"
    print("  -> 4-field contract (with integer class_id) validated successfully [PASS]")

def test_predict_real_dataset_sampling(client: TestClient):
    print("\n[3/7] Testing POST /predict accuracy on REAL Tomato Leaf Images (Multi-Class Sampling)...")
    manifest_file = project_root / "ml" / "dataset" / "splits" / "manifest.csv"
    assert manifest_file.exists(), f"Critical: Test manifest file not found at {manifest_file}"

    df = pd.read_csv(manifest_file)
    test_df = df[df["split"] == "test"]
    assert len(test_df) > 0, "No test rows found in manifest.csv"

    classes = test_df["class_name"].unique()
    assert len(classes) == 10, f"Expected 10 classes in test split, found {len(classes)}"

    # Check if raw dataset images are present on disk (they are excluded from git repository)
    has_local_images = False
    for _, row in test_df.head(20).iterrows():
        if Path(row["image_path"]).exists():
            has_local_images = True
            break

    if not has_local_images:
        print("  -> [INFO/SKIP] Raw dataset images not present on disk (CI environment).")
        print("                 Synthetic contracts and API test suite validated successfully.")
        return

    correct = 0
    total = 0

    # Sample 1 image per class from test split to rigorously verify classification
    for class_name in sorted(classes):
        class_rows = test_df[test_df["class_name"] == class_name]
        sampled_row = None
        for _, row in class_rows.iterrows():
            img_path = Path(row["image_path"])
            if img_path.exists():
                sampled_row = row
                break

        if sampled_row is None:
            continue

        real_img_path = Path(sampled_row["image_path"])
        with open(real_img_path, "rb") as f:
            real_bytes = f.read()

        res = client.post("/predict", files={"image": (real_img_path.name, real_bytes, "image/jpeg")})
        assert res.status_code == 200, f"Inference request failed for {real_img_path.name}: {res.text}"
        pred = res.json()

        is_match = (pred["class"] == class_name)
        if is_match:
            correct += 1
        total += 1

        print(f"  [{total:02d}/10] Ground Truth: {class_name:<36} | Pred: {pred['class']:<36} (id={pred['class_id']}, conf={pred['confidence']*100:.1f}%) -> {'CORRECT' if is_match else 'MISMATCH'}")

    accuracy = (correct / total) * 100.0
    print(f"\n  -> Sampling Evaluation Result: {correct}/{total} correct ({accuracy:.1f}%)")
    # Strict validation: accuracy on clean samples should be at least 80% (model test accuracy is 96.6%)
    assert accuracy >= 80.0, f"Sampling accuracy below required threshold (80.0%): got {accuracy:.1f}%"
    print("  -> Multi-Class Real Leaf Accuracy Verification: [PASS]")

def test_predict_png_format(client: TestClient):
    print("\n[4/7] Testing POST /predict with PNG format...")
    png_bytes = create_dummy_image(format="PNG")
    res = client.post("/predict", files={"image": ("test_leaf.png", png_bytes, "image/png")})
    assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
    pred_png = res.json()
    assert "class" in pred_png and "class_id" in pred_png
    assert isinstance(pred_png["class_id"], int)
    print(f"  -> PNG Prediction: {pred_png['class']} ({pred_png['confidence']*100:.2f}%) [PASS]")

def test_empty_file_rejected(client: TestClient):
    print("\n[5/7] Testing Empty File rejection (HTTP 400)...")
    res = client.post("/predict", files={"image": ("empty.jpg", b"", "image/jpeg")})
    assert res.status_code == 400, f"Expected 400 for empty file, got {res.status_code}"
    print(f"  -> Empty file properly rejected: {res.json()} [PASS]")

def test_unsupported_format_rejected(client: TestClient):
    print("\n[6/7] Testing Unsupported format rejection (HTTP 415)...")
    res = client.post("/predict", files={"image": ("notes.txt", b"plain text data", "text/plain")})
    assert res.status_code == 415, f"Expected 415 for text file, got {res.status_code}"
    print(f"  -> Unsupported format rejected with 415: {res.json()} [PASS]")

def test_leaf_preprocessing_ab_override(client: TestClient):
    print("\n[7/7] Testing leaf-isolation preprocessing A/B override...")
    manifest_file = project_root / "ml" / "dataset" / "splits" / "manifest.csv"

    payload = None
    sample_label = "synthetic image"
    if manifest_file.exists():
        df = pd.read_csv(manifest_file)
        for _, row in df[df["split"] == "test"].iterrows():
            candidate = Path(row["image_path"])
            if candidate.exists():
                payload = candidate.read_bytes()
                sample_label = row["class_name"]
                break

    if payload is None:
        payload = create_dummy_image(format="JPEG")

    results = {}
    for mode in (True, False):
        res = client.post(
            "/predict",
            files={"image": ("ab_leaf.jpg", payload, "image/jpeg")},
            params={"preprocess": str(mode).lower()},
        )
        assert res.status_code == 200, f"A/B request failed (preprocess={mode}): {res.text}"
        body = res.json()
        for field in ["class", "confidence", "class_id", "inference_time_ms"]:
            assert field in body, f"Missing contract field '{field}' when preprocess={mode}"
        assert body.get("preprocessing_applied") is mode, (
            f"preprocessing_applied mismatch: expected {mode}, got {body.get('preprocessing_applied')}"
        )
        results[mode] = body

    print(f"  -> Sample          : {sample_label}")
    print(
        f"  -> preprocess=true : {results[True]['class']} "
        f"({results[True]['confidence'] * 100:.1f}%, method={results[True].get('preprocessing_method')})"
    )
    print(f"  -> preprocess=false: {results[False]['class']} ({results[False]['confidence'] * 100:.1f}%)")
    print("  -> Both modes return a valid contract and correct flag [PASS]")

def run_api_tests():
    print("=" * 70)
    print("TOMATCARE FASTAPI TEST SUITE (CONTRACT, ACCURACY SAMPLING & ERRORS)")
    print("=" * 70)

    with TestClient(app) as client:
        test_health_check(client)
        test_predict_synthetic_contract(client)
        test_predict_real_dataset_sampling(client)
        test_predict_png_format(client)
        test_empty_file_rejected(client)
        test_unsupported_format_rejected(client)
        test_leaf_preprocessing_ab_override(client)

    print("\n" + "=" * 70)
    print("FASTAPI TEST SUITE: ALL TESTS COMPLETED & VALIDATED [PASS]")
    print("=" * 70)

if __name__ == "__main__":
    run_api_tests()
