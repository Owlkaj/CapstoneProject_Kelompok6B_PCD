import os
from pathlib import Path
from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.services.inference import InferenceService
from app.services.leaf_preprocessing import LeafPreprocessor
from app.routes.predict import router as predict_router

# Base directories
BASE_DIR = Path(__file__).resolve().parent.parent

# Load .env if python-dotenv is installed (optional, never fatal)
try:
    from dotenv import load_dotenv

    load_dotenv(BASE_DIR / ".env")
    load_dotenv(BASE_DIR.parent / ".env")
except Exception:
    pass


def _env_bool(name: str, default: bool) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default
    return raw.strip().lower() in ("1", "true", "yes", "on")


# Read configurations
MODEL_PATH = os.getenv("MODEL_PATH", str(BASE_DIR / "models" / "final_model.pt"))
LABEL_MAPPING_PATH = os.getenv("LABEL_MAPPING_PATH", str(BASE_DIR / "models" / "label_mapping.json"))
PREPROCESSING_PATH = os.getenv("PREPROCESSING_CONFIG_PATH", str(BASE_DIR / "models" / "preprocessing.json"))
DEVICE = os.getenv("DEVICE", "cpu")

# Leaf isolation & normalisation configuration (Pilar 2 enhancement)
LEAF_PREPROCESSING_ENABLED = _env_bool("ENABLE_LEAF_PREPROCESSING", True)
LEAF_SEGMENTATION_METHOD = os.getenv("LEAF_SEGMENTATION_METHOD", "auto")
LEAF_TARGET_FILL = float(os.getenv("LEAF_TARGET_FILL", "0.75"))
LEAF_BACKGROUND_GRAY = int(os.getenv("LEAF_BACKGROUND_GRAY", "240"))
LEAF_COLOR_NORMALIZATION = _env_bool("LEAF_COLOR_NORMALIZATION", True)
LEAF_PREPROCESSING_POLICY = os.getenv("LEAF_PREPROCESSING_POLICY", "adaptive")
LEAF_DEBUG_DIR = os.getenv("LEAF_DEBUG_DIR") or None

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Load EfficientNetB0 model and configurations once (PRD Bab 18.6)
    print("=" * 60)
    print("STARTING FASTAPI TOMATO DISEASE INFERENCE SERVICE")
    print("=" * 60)
    print(f"Loading Model from: {MODEL_PATH}")

    preprocessor = LeafPreprocessor(
        enabled=LEAF_PREPROCESSING_ENABLED,
        method=LEAF_SEGMENTATION_METHOD,
        target_fill=LEAF_TARGET_FILL,
        background_gray=LEAF_BACKGROUND_GRAY,
        color_normalization=LEAF_COLOR_NORMALIZATION,
        debug_dir=LEAF_DEBUG_DIR,
        policy=LEAF_PREPROCESSING_POLICY,
    )
    print(
        f"Leaf preprocessing: enabled={LEAF_PREPROCESSING_ENABLED} "
        f"method={LEAF_SEGMENTATION_METHOD} policy={LEAF_PREPROCESSING_POLICY} "
        f"available={preprocessor.available_methods()}"
    )

    inference_service = InferenceService(
        model_path=MODEL_PATH,
        label_mapping_path=LABEL_MAPPING_PATH,
        preprocessing_path=PREPROCESSING_PATH,
        device_name=DEVICE,
        preprocessor=preprocessor,
    )
    inference_service.load()
    app.state.inference_service = inference_service
    app.state.leaf_preprocessor = preprocessor
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
cors_origins_env = os.getenv(
    "ALLOWED_ORIGINS",
    "http://localhost:8000,http://127.0.0.1:8000,http://localhost:3000,http://127.0.0.1:3000"
)
allowed_origins = [o.strip() for o in cors_origins_env.split(",") if o.strip()]
allow_credentials = "*" not in allowed_origins

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=allow_credentials,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(predict_router)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("app.main:app", host="127.0.0.1", port=8001, reload=False)
