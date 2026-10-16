"""Unit tests for confidence gating (no CNN model needed)."""
import sys
import numpy as np
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

sys.path.insert(0, str(Path(__file__).resolve().parent))

from app.services.confidence_gating import (
    ConfidenceGate, image_quality, ACCEPT, REVIEW, REJECT,
)


def _sharp_leaf(size=(320, 320)) -> Image.Image:
    """A textured (non-blurry) image so the sharpness check passes."""
    rng = np.random.default_rng(0)
    arr = rng.integers(40, 215, (size[1], size[0], 3), dtype=np.uint8)
    img = Image.fromarray(arr)
    draw = ImageDraw.Draw(img)
    for r in range(40, 280, 35):
        draw.ellipse((r, r, r + 60, r + 60), outline=(20, 90, 20), width=6)
    return img


def test_confident_prediction_is_accepted():
    gate = ConfidenceGate(enabled=True, min_confidence=0.55, min_margin=0.10, min_sharpness=0.0)
    d = gate.decide(_sharp_leaf(), confidence=0.98, second_confidence=0.005)
    assert d.action == ACCEPT and d.passed is True
    assert d.reasons == []


def test_low_confidence_is_flagged():
    gate = ConfidenceGate(enabled=True, min_confidence=0.55, min_sharpness=0.0)
    d = gate.decide(_sharp_leaf(), confidence=0.40, second_confidence=0.30)
    assert d.passed is False
    assert "low_confidence" in d.reasons
    assert d.action == REVIEW
    assert d.message  # a user-facing retake hint is provided


def test_very_low_confidence_is_rejected():
    gate = ConfidenceGate(enabled=True, min_confidence=0.55, min_sharpness=0.0)
    d = gate.decide(_sharp_leaf(), confidence=0.15, second_confidence=0.12)
    assert d.action == REJECT and d.passed is False


def test_ambiguous_top2_is_flagged_even_when_confidence_ok():
    gate = ConfidenceGate(enabled=True, min_confidence=0.55, min_margin=0.20, min_sharpness=0.0)
    d = gate.decide(_sharp_leaf(), confidence=0.60, second_confidence=0.55)
    assert d.passed is False
    assert "ambiguous" in d.reasons


def test_blurry_image_is_flagged():
    smooth = Image.new("RGB", (320, 320), (120, 140, 110)).filter(ImageFilter.GaussianBlur(20))
    gate = ConfidenceGate(enabled=True, min_confidence=0.0, min_margin=0.0, min_sharpness=25.0)
    d = gate.decide(smooth, confidence=0.99, second_confidence=0.0)
    assert d.passed is False
    assert "blurry" in d.reasons


def test_dark_and_bright_images_are_flagged():
    gate = ConfidenceGate(enabled=True, min_confidence=0.0, min_margin=0.0, min_sharpness=0.0)
    dark = Image.eval(_sharp_leaf(), lambda v: int(v * 0.05))
    bright = Image.eval(_sharp_leaf(), lambda v: min(255, int(v * 0.5) + 180))
    assert "too_dark" in gate.decide(dark, 0.99, 0.0).reasons
    assert "too_bright" in gate.decide(bright, 0.99, 0.0).reasons


def test_tiny_image_is_flagged():
    gate = ConfidenceGate(enabled=True, min_confidence=0.0, min_margin=0.0, min_sharpness=0.0, min_side=96)
    d = gate.decide(_sharp_leaf((48, 48)), confidence=0.99, second_confidence=0.0)
    assert "too_small" in d.reasons


def test_disabled_gate_always_accepts():
    gate = ConfidenceGate(enabled=False)
    d = gate.decide(Image.new("RGB", (32, 32), (0, 0, 0)), confidence=0.01, second_confidence=0.0)
    assert d.passed is True and d.action == ACCEPT and d.reasons == []


def test_quality_metrics_present():
    q = image_quality(_sharp_leaf())
    for key in ("sharpness", "brightness", "min_side", "width", "height"):
        assert key in q
    assert q["sharpness"] > 0


if __name__ == "__main__":
    tests = [v for k, v in sorted(globals().items()) if k.startswith("test_") and callable(v)]
    for fn in tests:
        fn()
        print(f"[PASS] {fn.__name__}")
    print(f"\nConfidence gating unit tests: {len(tests)}/{len(tests)} PASSED")
