import os
from typing import Optional
from fastapi import APIRouter, File, UploadFile, HTTPException, Request, status, Query
from app.schemas.predict import PredictionResponse, HealthResponse

router = APIRouter()

ALLOWED_MIME_TYPES = {"image/jpeg", "image/png", "image/jpg"}
ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png"}
MAX_FILE_SIZE_BYTES = int(os.getenv("MAX_UPLOAD_SIZE_MB", "10")) * 1024 * 1024


@router.get("/health", response_model=HealthResponse, tags=["Health"])
async def health_check(request: Request):
    inference_service = request.app.state.inference_service
    is_loaded = inference_service is not None and inference_service.is_ready
    return HealthResponse(
        status="ok" if is_loaded else "degraded",
        model_loaded=is_loaded
    )


@router.post("/predict", response_model=PredictionResponse, tags=["Inference"])
async def predict_image(
    request: Request,
    image: UploadFile = File(...),
    preprocess: Optional[bool] = Query(
        default=None,
        description="Override leaf isolation preprocessing (true/false). Omit to use server default.",
    ),
    gate: Optional[bool] = Query(
        default=None,
        description="Override confidence gating (true/false). Omit to use server default.",
    ),
):
    inference_service = request.app.state.inference_service
    if inference_service is None or not inference_service.is_ready:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Model service is initializing or unavailable."
        )

    # 1. Validate file existence and filename
    if not image.filename:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No filename provided for upload."
        )

    ext = os.path.splitext(image.filename)[1].lower()
    if ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"Unsupported file extension '{ext}'. Allowed extensions: {', '.join(sorted(ALLOWED_EXTENSIONS))}"
        )

    # 2. Validate MIME type
    if image.content_type and image.content_type.lower() not in ALLOWED_MIME_TYPES:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"Unsupported MIME type '{image.content_type}'. Allowed types: {', '.join(sorted(ALLOWED_MIME_TYPES))}"
        )

    # 3. Read image bytes and check size
    contents = await image.read()
    if len(contents) == 0:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Uploaded image file is empty."
        )

    if len(contents) > MAX_FILE_SIZE_BYTES:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"File size exceeds maximum allowed limit of {MAX_FILE_SIZE_BYTES // (1024 * 1024)} MB."
        )

    # 4. Run inference
    try:
        result = inference_service.predict(contents, preprocess=preprocess, gate=gate)
        return PredictionResponse(
            **result
        )
    except ValueError as e:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Invalid image file: {str(e)}"
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="An unexpected error occurred during image inference."
        )
