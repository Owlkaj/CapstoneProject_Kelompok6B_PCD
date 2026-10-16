"""Unit tests for the leaf isolation preprocessing module.

These run without loading the CNN model, so they are fast and CI-friendly.
"""
import sys
import numpy as np
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

sys.path.insert(0, str(Path(__file__).resolve().parent))

from app.services.leaf_preprocessing import LeafPreprocessor, gray_world_balance, apply_local_contrast


def _make_field_photo(size=(420, 420)) -> Image.Image:
    """A green leaf on a colourful, textured (soil-like) background."""
    rng = np.random.default_rng(1)
    soil = rng.integers(60, 200, (size[1], size[0], 3), dtype=np.uint8)
    soil[..., 0] = np.clip(soil[..., 0].astype(int) + 70, 0, 255)  # brown/red soil
    soil[..., 2] = np.clip(soil[..., 2].astype(int) - 20, 0, 255)
    img = Image.fromarray(soil.astype(np.uint8))
    draw = ImageDraw.Draw(img)
    draw.ellipse((70, 50, 350, 380), fill=(50, 140, 50))
    return img.filter(ImageFilter.GaussianBlur(1.2))


def test_segment_finds_leaf_on_field_photo():
    pp = LeafPreprocessor(enabled=True, method="numpy")
    mask, used = pp.segment(_make_field_photo())
    assert mask is not None, "Expected a leaf mask on a field-like photo"
    assert used == "numpy"
    ratio = float(mask.sum()) / mask.size
    assert 0.05 < ratio < 0.9, f"Unexpected leaf area ratio: {ratio}"


def test_adaptive_skips_clean_neutral_background():
    """A leaf on a flat neutral background must be left untouched."""
    pp = LeafPreprocessor(enabled=True, method="numpy", policy="adaptive")
    img = Image.new("RGB", (400, 400), (235, 235, 235))
    ImageDraw.Draw(img).ellipse((60, 40, 340, 360), fill=(70, 150, 70))
    out, meta = pp.process(img)
    assert meta["applied"] is False, "Clean neutral background should be skipped"
    assert out.size == img.size


def test_always_policy_applies_and_recomposes():
    pp = LeafPreprocessor(
        enabled=True, method="numpy", policy="always",
        target_fill=0.75, background_gray=240, color_normalization=True,
    )
    out, meta = pp.process(_make_field_photo())
    assert meta["applied"] is True
    assert meta["method"] == "numpy"
    assert meta["color_normalized"] is True
    assert out.size[0] == out.size[1], "Output canvas should be square"


def test_disabled_preprocessor_is_passthrough():
    pp = LeafPreprocessor(enabled=False)
    img = _make_field_photo()
    out, meta = pp.process(img)
    assert meta["applied"] is False
    assert out.size == img.size


def test_color_helpers_return_valid_images():
    img = _make_field_photo()
    mask, _ = LeafPreprocessor(method="numpy").segment(img)
    balanced = gray_world_balance(img, mask)
    contrasted = apply_local_contrast(img)
    for out in (balanced, contrasted):
        assert out.mode == "RGB"
        assert out.size == img.size


def test_available_methods_always_contains_numpy():
    assert "numpy" in LeafPreprocessor().available_methods()


if __name__ == "__main__":
    tests = [v for k, v in sorted(globals().items()) if k.startswith("test_") and callable(v)]
    for fn in tests:
        fn()
        print(f"[PASS] {fn.__name__}")
    print(f"\nLeaf preprocessing unit tests: {len(tests)}/{len(tests)} PASSED")
