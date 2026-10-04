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

def run_api_tests():
    print("=" * 60)
    print("PHASE 6 - FASTAPI INTEGRATION & CONTRACT TESTING")
    print("=" * 60)

    # Use TestClient with lifespan context
    with TestClient(app) as client:
        # 1. Health check
        print("\n[1/6] Testing GET /health...")
        res = client.get("/health")
        assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
        data = res.json()
        assert data["status"] == "ok", f"Unexpected status: {data}"
        assert data["model_loaded"] is True, f"Model not loaded: {data}"
        print(f"  -> Response: {data} [PASS]")

        # 2. POST /predict with Synthetic JPEG
        print("\n[2/6] Testing POST /predict with Synthetic JPEG...")
        jpg_bytes = create_dummy_image(format="JPEG")
        res = client.post("/predict", files={"image": ("test_leaf.jpg", jpg_bytes, "image/jpeg")})
        assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
        pred = res.json()
        print(f"  -> Prediction Response: {pred}")
        for field in ["class", "confidence", "class_id", "inference_time_ms"]:
            assert field in pred, f"Missing required contract field '{field}' in response!"
        assert 0.0 <= pred["confidence"] <= 1.0, f"Invalid confidence range: {pred['confidence']}"
        assert pred["inference_time_ms"] > 0, f"Invalid inference time: {pred['inference_time_ms']}"
        print("  -> 4-field contract validated successfully [PASS]")

        # 3. POST /predict with Real Tomato Leaf from Dataset
        print("\n[3/6] Testing POST /predict with REAL Tomato Leaf Image...")
        manifest_file = project_root / "ml" / "dataset" / "splits" / "manifest.csv"
        if manifest_file.exists():
            df = pd.read_csv(manifest_file)
            test_row = df[df["split"] == "test"].iloc[0]
            real_img_path = Path(test_row["image_path"])
            true_class = test_row["class_name"]

            with open(real_img_path, "rb") as f:
                real_bytes = f.read()

            res = client.post("/predict", files={"image": (real_img_path.name, real_bytes, "image/jpeg")})
            assert res.status_code == 200, f"Failed on real image: {res.text}"
            real_pred = res.json()
            print(f"  -> Input Leaf File  : {real_img_path.name}")
            print(f"  -> True Class       : {true_class}")
            print(f"  -> Predicted Class  : {real_pred['class']}")
            print(f"  -> Confidence Score : {real_pred['confidence']*100:.2f}%")
            print(f"  -> Inference Time   : {real_pred['inference_time_ms']} ms")
            assert real_pred["class"] == true_class, f"Prediction mismatch! Expected {true_class}, got {real_pred['class']}"
            print("  -> Real Leaf Classification: ACCURATE [PASS]")
        else:
            print("  -> Manifest not found, skipping real image test.")

        # 4. POST /predict with PNG format
        print("\n[4/6] Testing POST /predict with PNG format...")
        png_bytes = create_dummy_image(format="PNG")
        res = client.post("/predict", files={"image": ("test_leaf.png", png_bytes, "image/png")})
        assert res.status_code == 200, f"Expected 200, got {res.status_code}: {res.text}"
        pred_png = res.json()
        print(f"  -> PNG Prediction: {pred_png['class']} ({pred_png['confidence']*100:.2f}%) [PASS]")

        # 5. Error testing: Empty file rejection (400)
        print("\n[5/6] Testing Empty File rejection (HTTP 400)...")
        res = client.post("/predict", files={"image": ("empty.jpg", b"", "image/jpeg")})
        assert res.status_code == 400, f"Expected 400 for empty file, got {res.status_code}"
        print(f"  -> Empty file properly rejected: {res.json()} [PASS]")

        # 6. Error testing: Unsupported format rejection (415)
        print("\n[6/6] Testing Unsupported format rejection (HTTP 415)...")
        res = client.post("/predict", files={"image": ("notes.txt", b"plain text data", "text/plain")})
        assert res.status_code == 415, f"Expected 415 for text file, got {res.status_code}"
        print(f"  -> Unsupported format rejected with 415: {res.json()} [PASS]")

    print("\n" + "=" * 60)
    print("PHASE 6 - FASTAPI TEST SUITE: ALL PASSED (PASS)")
    print("=" * 60)

if __name__ == "__main__":
    run_api_tests()
