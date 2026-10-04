"""
TomatCare — Hugging Face Gradio Space Application
Provides both an interactive Web GUI and REST API endpoints.
100% Compatible with Hugging Face Free Tier (No Docker required).
"""

import json
import torch
import torch.nn as nn
from torchvision import models, transforms
from PIL import Image
import gradio as gr
from pathlib import Path

# Paths
BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "models" / "final_model.pt"
LABEL_PATH = BASE_DIR / "models" / "label_mapping.json"

# Load label mapping
with open(LABEL_PATH, "r", encoding="utf-8") as f:
    label_mapping = json.load(f)

# Sort labels by class index
num_classes = len(label_mapping)
idx_to_class = {v: k for k, v in label_mapping.items()}

# Indonesian Translation
ID_NAMES = {
    "Bacterial Spot": "Bercak Bakteri (Xanthomonas)",
    "Early Blight": "Bercak Kering (Alternaria solani)",
    "Healthy": "Daun Sehat (Kondisi Prima)",
    "Late Blight": "Hawar Daun (Phytophthora)",
    "Leaf Mold": "Kapang Daun (Passalora fulva)",
    "Tomato Mosaic Virus": "Virus Mosaik (ToMV)",
    "Septoria Leaf Spot": "Bercak Septoria (Septoria lycopersici)",
    "Spider Mites": "Tungau Laba-laba (Tetranychus)",
    "Target Spot": "Bercak Sasaran (Corynespora)",
    "Tomato Yellow Leaf Curl Virus (TYLCV)": "Virus Kuning Keriting (TYLCV)"
}

# Load Model
device = torch.device("cpu")
model = models.efficientnet_b0(weights=None)
model.classifier[1] = nn.Linear(model.classifier[1].in_features, num_classes)
checkpoint = torch.load(MODEL_PATH, map_location=device, weights_only=True)
model.load_state_dict(checkpoint)
model.eval()

# Image Preprocessing (ImageNet 224x224)
transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225])
])

def predict(image):
    if image is None:
        return {}
    
    # Ensure RGB
    if image.mode != "RGB":
        image = image.convert("RGB")
    
    # Transform and batch
    input_tensor = transform(image).unsqueeze(0).to(device)
    
    with torch.no_grad():
        outputs = model(input_tensor)
        probabilities = torch.softmax(outputs, dim=1)[0]
    
    # Return probabilities dictionary for Gradio Label component
    results = {}
    for idx in range(num_classes):
        tech_name = idx_to_class[idx]
        indonesia_name = ID_NAMES.get(tech_name, tech_name)
        display_label = f"{indonesia_name} [{tech_name}]"
        results[display_label] = float(probabilities[idx].item())
    
    return results

# Create Gradio UI
demo = gr.Interface(
    fn=predict,
    inputs=gr.Image(type="pil", label="Unggah / Ambil Foto Daun Tomat"),
    outputs=gr.Label(num_top_classes=3, label="Hasil Prediksi & Skor Kepercayaan (Confidence)"),
    title="🍃 TomatCare — Deteksi Penyakit Daun Tomat AI",
    description="Klasifikasi 10 kondisi daun tomat secara cerdas berbasis **EfficientNet-B0 (Test Acc: 96.60%)**. Sistem siap digunakan untuk diagnosa instan tanaman tomat.",
    theme="soft",
    allow_flagging="never"
)

if __name__ == "__main__":
    demo.launch(server_name="0.0.0.0", server_port=7860)
