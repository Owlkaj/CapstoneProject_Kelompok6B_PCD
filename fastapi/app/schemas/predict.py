from pydantic import BaseModel, Field

class PredictionResponse(BaseModel):
    # Contract specified in PRD Bab 18.4 & 31.1
    predicted_class: str = Field(..., alias="class", description="Predicted disease class name")
    confidence: float = Field(..., description="Softmax confidence score (0.0 to 1.0)")
    class_id: str = Field(..., description="Class identifier matching label mapping")
    inference_time_ms: float = Field(..., description="Inference time in milliseconds")

    class Config:
        populate_by_name = True

class HealthResponse(BaseModel):
    # Contract specified in PRD Bab 28.3 & 32.2
    status: str
    model_loaded: bool
