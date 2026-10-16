from pydantic import BaseModel, Field

class PredictionResponse(BaseModel):
    # Contract specified in PRD Bab 18.4 & 31.1
    predicted_class: str = Field(..., alias="class", description="Predicted disease class name")
    confidence: float = Field(..., description="Softmax confidence score (0.0 to 1.0)")
    class_id: int = Field(..., ge=0, description="Integer class identifier matching label mapping (0 to 9)")
    inference_time_ms: float = Field(..., description="Inference time in milliseconds")
    probabilities: dict[str, float] | None = Field(default=None, description="Full dictionary of class probabilities")
    top_predictions: list[dict] | None = Field(default=None, description="Ranked top-k predictions with class and confidence")
    preprocessing_applied: bool | None = Field(default=None, description="Whether leaf isolation preprocessing was applied")
    preprocessing_method: str | None = Field(default=None, description="Segmentation strategy used (rembg/grabcut/numpy/none)")
    # --- confidence gating (added after calibration; see PERUBAHAN.md) --------
    accepted: bool | None = Field(
        default=None,
        description="True when the prediction is trustworthy enough to be shown as a diagnosis",
    )
    gate_action: str | None = Field(
        default=None,
        description="accept | review | reject — how the client should present the result",
    )
    gate_reason: str | None = Field(default=None, description="Primary reason the gate did not accept, if any")
    gate_message: str | None = Field(default=None, description="User-facing advice (Indonesian) for a retake")
    gate_threshold: float | None = Field(default=None, description="Confidence threshold used for this decision")
    quality_score: float | None = Field(default=None, description="Image sharpness (variance of Laplacian)")
    confidence_gate: dict | None = Field(default=None, description="Full gating payload (action, reasons, quality)")

    class Config:
        populate_by_name = True

class HealthResponse(BaseModel):
    # Contract specified in PRD Bab 28.3 & 32.2
    status: str
    model_loaded: bool
