"""
Confidence gating for TomatCare inference.

The classifier is a *closed-set* 10-class model: it always returns one of the 10
labels, however unfamiliar the input is. On out-of-distribution photos (blurry
phone shots, cluttered field scenes, non-tomato images) it can therefore answer
**confidently and wrongly**.

This module decides whether a prediction is trustworthy enough to be shown as a
diagnosis, or whether the app should instead ask the user to retake the photo.

Signals used (all measured on the same image that was fed to the model):

    confidence  softmax top-1 probability
    margin      top1 - top2 probability          (separates ambiguous cases)
    sharpness   variance of the Laplacian        (blur detector)
    brightness  mean luminance                   (too dark / blown out)
    resolution  shortest side in pixels          (too small to judge)

Thresholds are calibrated from labelled test data
(`ml/src/evaluation/calibrate_gating.py`) and can be overridden via environment
variables.
"""

from __future__ import annotations

import os
from dataclasses import dataclass, field
from typing import Optional

import numpy as np
from PIL import Image

try:
    from scipy import ndimage

    _HAS_SCIPY = True
except Exception:  # pragma: no cover
    ndimage = None  # type: ignore
    _HAS_SCIPY = False


# --- default thresholds (overridable via env) --------------------------------
DEFAULT_MIN_CONFIDENCE = 0.55     # below this the diagnosis is not shown
DEFAULT_MIN_MARGIN = 0.10         # top1 - top2; below this the model is torn
DEFAULT_MIN_SHARPNESS = 25.0      # variance of Laplacian
DEFAULT_MIN_BRIGHTNESS = 30.0     # 0..255
DEFAULT_MAX_BRIGHTNESS = 226.0    # 0..255
DEFAULT_MIN_SIDE = 96             # pixels

ACCEPT = "accept"     # show the diagnosis
REVIEW = "review"     # show it as a low-confidence suggestion + ask to retake
REJECT = "reject"     # do not show a diagnosis at all


def _env_float(name: str, default: float) -> float:
    try:
        return float(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


def _env_bool(name: str, default: bool) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default
    return raw.strip().lower() in ("1", "true", "yes", "on")


def image_quality(image: Image.Image) -> dict:
    """Cheap, dependency-light image quality metrics."""
    gray = np.asarray(image.convert("L"))
    if _HAS_SCIPY:
        sharpness = float(ndimage.laplace(gray.astype(np.float32)).var())
    else:
        gx = np.diff(gray.astype(np.float32), axis=1)
        gy = np.diff(gray.astype(np.float32), axis=0)
        sharpness = float((gx ** 2).mean() + (gy ** 2).mean())
    return {
        "sharpness": sharpness,
        "brightness": float(gray.mean()),
        "min_side": int(min(image.size)),
        "width": int(image.size[0]),
        "height": int(image.size[1]),
    }


@dataclass
class GateDecision:
    passed: bool
    action: str
    threshold: float
    margin: float
    quality: dict
    reasons: list[str] = field(default_factory=list)
    message: str = ""

    def to_dict(self, include_quality: bool = True) -> dict:
        out = {
            "passed": self.passed,
            "action": self.action,
            "threshold": round(self.threshold, 4),
            "margin": round(self.margin, 4),
            "reasons": self.reasons,
            "message": self.message,
        }
        if include_quality:
            out["quality"] = {
                k: (round(v, 3) if isinstance(v, float) else v)
                for k, v in self.quality.items()
            }
        return out


class ConfidenceGate:
    """Decides accept / review / reject from model confidence + image quality."""

    MESSAGES = {
        "accept": "",
        "low_confidence": "Model kurang yakin dengan hasil ini. Coba ambil foto ulang "
                          "dengan pencahayaan lebih baik dan daun memenuhi bingkai.",
        "ambiguous": "Beberapa penyakit memiliki gejala yang mirip pada foto ini. "
                     "Sertakan foto daun lain atau konsultasikan ke penyuluh.",
        "blurry": "Foto terlihat buram. Pegang kamera dengan stabil dan pastikan daun fokus.",
        "too_dark": "Foto terlalu gelap. Ambil foto di tempat yang lebih terang.",
        "too_bright": "Foto terlalu terang / silau. Hindari cahaya matahari langsung.",
        "too_small": "Resolusi foto terlalu kecil untuk dianalisis. Gunakan foto yang lebih besar.",
    }

    def __init__(
        self,
        enabled: Optional[bool] = None,
        min_confidence: Optional[float] = None,
        min_margin: Optional[float] = None,
        min_sharpness: Optional[float] = None,
        min_brightness: Optional[float] = None,
        max_brightness: Optional[float] = None,
        min_side: Optional[int] = None,
    ):
        self.enabled = _env_bool("ENABLE_CONFIDENCE_GATING", True) if enabled is None else bool(enabled)
        self.min_confidence = _env_float("GATE_MIN_CONFIDENCE", DEFAULT_MIN_CONFIDENCE) if min_confidence is None else min_confidence
        self.min_margin = _env_float("GATE_MIN_MARGIN", DEFAULT_MIN_MARGIN) if min_margin is None else min_margin
        self.min_sharpness = _env_float("GATE_MIN_SHARPNESS", DEFAULT_MIN_SHARPNESS) if min_sharpness is None else min_sharpness
        self.min_brightness = _env_float("GATE_MIN_BRIGHTNESS", DEFAULT_MIN_BRIGHTNESS) if min_brightness is None else min_brightness
        self.max_brightness = _env_float("GATE_MAX_BRIGHTNESS", DEFAULT_MAX_BRIGHTNESS) if max_brightness is None else max_brightness
        self.min_side = int(_env_float("GATE_MIN_RESOLUTION", DEFAULT_MIN_SIDE)) if min_side is None else int(min_side)

    def decide(self, image: Image.Image, confidence: float, second_confidence: float = 0.0,
               enabled: Optional[bool] = None) -> GateDecision:
        quality = image_quality(image)
        margin = float(confidence) - float(second_confidence)

        active = self.enabled if enabled is None else bool(enabled)
        if not active:
            return GateDecision(True, ACCEPT, self.min_confidence, margin, quality, [], "")

        reasons: list[str] = []

        # --- image quality problems: ask for a retake, but still show the guess ---
        if quality["sharpness"] < self.min_sharpness:
            reasons.append("blurry")
        if quality["brightness"] < self.min_brightness:
            reasons.append("too_dark")
        if quality["brightness"] > self.max_brightness:
            reasons.append("too_bright")
        if quality["min_side"] < self.min_side:
            reasons.append("too_small")

        # --- model uncertainty -----------------------------------------------------
        low_conf = confidence < self.min_confidence
        low_margin = margin < self.min_margin
        if low_conf:
            reasons.append("low_confidence")
        elif low_margin:
            reasons.append("ambiguous")

        # A very low confidence is a hard reject; anything else is a soft review.
        if confidence < self.min_confidence * 0.6:
            action, passed = REJECT, False
        elif reasons:
            action, passed = REVIEW, False
        else:
            action, passed = ACCEPT, True

        message = ""
        for r in reasons:
            if r in self.MESSAGES:
                message = self.MESSAGES[r]
                break

        return GateDecision(passed, action, self.min_confidence, margin, quality, reasons, message)
