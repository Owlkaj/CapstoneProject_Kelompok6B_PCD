import os
from pathlib import Path
from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.services.inference import InferenceService
from app.routes.predict import router as predict_router

# Base directories
BASE_DIR = Path(__file__).resolve().parent.parent

# Read configurations
MODEL_PATH = os.getenv("MODEL_PATH", str(BASE_DIR / "models" / "final_model.pt"))
LABEL_MAPPING_PATH = os.getenv("LABEL_MAPPING_PATH", str(BASE_DIR / "models" / "label_mapping.json"))
PREPROCESSING_PATH = os.getenv("PREPROCESSING_CONFIG_PATH", str(BASE_DIR / "models" / "preprocessing.json"))
DEVICE = os.getenv("DEVICE", "cpu")

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Load EfficientNetB0 model and configurations once (PRD Bab 18.6)
    print("=" * 60)
    print("STARTING FASTAPI TOMATO DISEASE INFERENCE SERVICE")
    print("=" * 60)
    print(f"Loading Model from: {MODEL_PATH}")
    
    inference_service = InferenceService(
        model_path=MODEL_PATH,
        label_mapping_path=LABEL_MAPPING_PATH,
        preprocessing_path=PREPROCESSING_PATH,
        device_name=DEVICE
    )
    inference_service.load()
    app.state.inference_service = inference_service
    print("Service initialized and ready to receive requests.")
    yield
    # Shutdown
    print("Shutting down FastAPI inference service.")

app = FastAPI(
    title="Tomato Leaf Disease Inference API",
    description="REST API for Tomato Leaf Disease Classification using PyTorch EfficientNetB0",
    version="3.0",
    lifespan=lifespan
)

# CORS middleware for Laravel or browser interactions
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(predict_router)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("app.main:app", host="127.0.0.1", port=8001, reload=False)
