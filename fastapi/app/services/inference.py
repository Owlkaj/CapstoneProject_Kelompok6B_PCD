import os
import json
import time
import io
from pathlib import Path
from PIL import Image
import torch
import torchvision.transforms as T
from torchvision.models import efficientnet_b0

class InferenceService:
    def __init__(self, model_path: str, label_mapping_path: str, preprocessing_path: str, device_name: str = "cpu"):
        self.model_path = Path(model_path)
        self.label_mapping_path = Path(label_mapping_path)
        self.preprocessing_path = Path(preprocessing_path)
        self.device_name = device_name
        self.model = None
        self.idx_to_class = {}
        self.transform = None
        self.device = torch.device("cpu")
        self.is_ready = False

    def load(self):
        """Loads model weights, label mappings, and transforms."""
        # 1. Device selection
        if self.device_name.lower() == "auto":
            try:
                import torch_directml
                if torch_directml.device_count() > 0:
                    self.device = torch_directml.device(0)
            except:
                self.device = torch.device("cpu")
        elif self.device_name.lower().startswith("directml") or self.device_name.lower().startswith("dml"):
            import torch_directml
            self.device = torch_directml.device(0)
        else:
            self.device = torch.device("cpu")

        # 2. Label mapping
        with open(self.label_mapping_path, "r", encoding="utf-8") as f:
            data = json.load(f)
            self.idx_to_class = {int(k): v for k, v in data["idx_to_class"].items()}

        # 3. Preprocessing config
        with open(self.preprocessing_path, "r", encoding="utf-8") as f:
            prep = json.load(f)
            target_size = tuple(prep.get("target_size", [224, 224]))
            mean = prep.get("mean", [0.485, 0.456, 0.406])
            std = prep.get("std", [0.229, 0.224, 0.225])

        self.transform = T.Compose([
            T.Resize(target_size),
            T.ToTensor(),
            T.Normalize(mean=mean, std=std)
        ])

        # 4. Model Architecture & Weights
        model = efficientnet_b0(weights=None)
        in_features = model.classifier[1].in_features
        model.classifier[1] = torch.nn.Linear(in_features, len(self.idx_to_class))

        state_dict = torch.load(self.model_path, map_location="cpu", weights_only=True)
        model.load_state_dict(state_dict)
        model = model.to(self.device)
        model.eval()

        self.model = model
        self.is_ready = True
        print(f"[FastAPI InferenceService] Model loaded successfully on device: {self.device}")

    def predict(self, image_bytes: bytes) -> dict:
        """Processes image bytes and returns prediction dictionary."""
        if not self.is_ready or self.model is None:
            raise RuntimeError("Model is not initialized or ready for inference.")

        t_start = time.perf_counter()

        # Open and convert to RGB
        try:
            image = Image.open(io.BytesIO(image_bytes))
            image.verify()  # Verify integrity
            # Reopen because verify() closes the file pointer
            image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
        except Exception as e:
            raise ValueError(f"Corrupt or unreadable image file: {str(e)}")

        # Transform to tensor
        tensor = self.transform(image).unsqueeze(0).to(self.device)

        with torch.no_grad():
            logits = self.model(tensor)
            probs = torch.softmax(logits, dim=1)
            confidence, pred_idx = torch.max(probs, dim=1)

        t_elapsed_ms = (time.perf_counter() - t_start) * 1000.0

        idx = pred_idx.item()
        class_name = self.idx_to_class.get(idx, "Unknown")
        conf_score = float(confidence.item())

        return {
            "class": class_name,
            "confidence": round(conf_score, 4),
            "class_id": idx,
            "inference_time_ms": round(t_elapsed_ms, 2)
        }
