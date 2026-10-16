"""
Leaf preprocessing pipeline for TomatCare inference.

Goal: reduce the "domain shift" between PlantVillage (training) images and
real-world field photos by making serving-time inputs look closer to the
training distribution:

    1. Segment the leaf and discard the background.
    2. Paste the leaf on a light-gray canvas at ~70-80% frame fill (PlantVillage style).
    3. Normalize color / illumination (gray-world white balance + CLAHE-ish contrast).
    4. Hand the result to the EfficientNet-B0 transform.

Segmentation is layered and auto-detected at runtime so the module never hard
fails when optional dependencies are missing:

    Strategy A : rembg (U^2-Net)          -- best quality, optional dependency
    Strategy B : OpenCV GrabCut            -- good, optional dependency
    Strategy C : NumPy/SciPy (ExG + Otsu)  -- always available fallback

All strategies are pure inference-time only; the model weights are untouched.
"""

from __future__ import annotations

import io
from pathlib import Path
from typing import Optional, Tuple

import numpy as np
from PIL import Image, ImageOps

try:  # optional, improves segmentation quality
    from scipy import ndimage  # type: ignore

    _HAS_SCIPY = True
except Exception:  # pragma: no cover - graceful degradation
    ndimage = None  # type: ignore
    _HAS_SCIPY = False


# ---------------------------------------------------------------------------
# Low level helpers
# ---------------------------------------------------------------------------

def _otsu_threshold(hist: np.ndarray) -> int:
    """Classic Otsu threshold from a 256-bin histogram."""
    hist = hist.astype(np.float64)
    total = hist.sum()
    if total <= 0:
        return 0
    sum_all = float(np.dot(np.arange(256), hist))
    sum_b = 0.0
    weight_b = 0.0
    best_var = -1.0
    threshold = 0
    for i in range(256):
        weight_b += hist[i]
        if weight_b <= 0:
            continue
        weight_f = total - weight_b
        if weight_f <= 0:
            break
        sum_b += i * hist[i]
        mean_b = sum_b / weight_b
        mean_f = (sum_all - sum_b) / weight_f
        var_between = weight_b * weight_f * (mean_b - mean_f) ** 2
        if var_between > best_var:
            best_var = var_between
            threshold = i
    return threshold


def _largest_component_and_cleanup(mask: np.ndarray) -> np.ndarray:
    """Keep the largest connected blob and clean its edges (needs SciPy)."""
    mask = mask.astype(bool)
    if not _HAS_SCIPY:
        return mask
    mask = ndimage.binary_closing(mask, iterations=3)
    mask = ndimage.binary_opening(mask, iterations=2)
    mask = ndimage.binary_fill_holes(mask)
    labeled, num = ndimage.label(mask)
    if num > 1:
        sizes = ndimage.sum(mask, labeled, index=np.arange(1, num + 1))
        biggest = int(np.argmax(sizes)) + 1
        mask = labeled == biggest
    return mask


# ---------------------------------------------------------------------------
# Segmentation strategies
# ---------------------------------------------------------------------------

def _segment_rembg(image: Image.Image, session) -> Optional[np.ndarray]:
    try:
        from rembg import remove  # type: ignore

        result = remove(image, session=session)
        alpha = np.asarray(result)[..., 3]
        return (alpha > 127).astype(np.uint8)
    except Exception:
        return None


def _segment_grabcut(image: Image.Image) -> Optional[np.ndarray]:
    try:
        import cv2  # type: ignore

        arr = cv2.cvtColor(np.asarray(image.convert("RGB")), cv2.COLOR_RGB2BGR)
        h, w = arr.shape[:2]
        if h < 10 or w < 10:
            return None
        mask = np.zeros((h, w), np.uint8)
        bgd = np.zeros((1, 65), np.float64)
        fgd = np.zeros((1, 65), np.float64)
        mx, my = int(w * 0.03), int(h * 0.03)
        rect = (mx, my, w - 2 * mx, h - 2 * my)
        cv2.grabCut(arr, mask, rect, bgd, fgd, 5, cv2.GC_INIT_WITH_RECT)
        fg = np.where((mask == cv2.GC_FGD) | (mask == cv2.GC_PR_FGD), 1, 0)
        return fg.astype(np.uint8)
    except Exception:
        return None


def _segment_numpy(image: Image.Image) -> Optional[np.ndarray]:
    """Dependency-light segmentation: Excess-Green + saturation + Otsu."""
    arr = np.asarray(image.convert("RGB"), dtype=np.float32) / 255.0
    r, g, b = arr[..., 0], arr[..., 1], arr[..., 2]

    mx = arr.max(axis=2)
    mn = arr.min(axis=2)
    sat = np.where(mx > 0, (mx - mn) / np.maximum(mx, 1e-6), 0.0)
    exg = 2.0 * g - r - b                     # excess green, range ~[-2, 2]
    exg_n = np.clip((exg + 1.0) / 2.0, 0.0, 1.0)

    # Leaves (even diseased/brown ones) keep some greenness + saturation,
    # whereas soil / sky / neutral background do not.
    score = 0.7 * exg_n + 0.3 * sat
    score_u8 = np.clip(score * 255.0, 0, 255).astype(np.uint8)

    hist, _ = np.histogram(score_u8, bins=256, range=(0, 256))
    thr = _otsu_threshold(hist)
    mask = (score_u8 > thr).astype(np.uint8)
    if mask.sum() == 0:
        return None
    mask = _largest_component_and_cleanup(mask)
    return mask.astype(np.uint8)


# ---------------------------------------------------------------------------
# Color / illumination normalization
# ---------------------------------------------------------------------------

def gray_world_balance(image: Image.Image, mask: Optional[np.ndarray] = None) -> Image.Image:
    """Simple gray-world white balance computed over leaf pixels (if any)."""
    arr = np.asarray(image.convert("RGB"), dtype=np.float32)
    if mask is not None and mask.sum() > 0:
        pixels = arr[mask > 0]
    else:
        pixels = arr.reshape(-1, 3)
    means = np.maximum(pixels.mean(axis=0), 1e-6)
    gray = float(means.mean())
    gains = np.clip(gray / means, 0.6, 1.6)
    out = arr * gains
    return Image.fromarray(np.clip(out, 0, 255).astype(np.uint8))


def apply_local_contrast(image: Image.Image, clip: float = 2.0, grid: int = 8) -> Image.Image:
    """CLAHE on the L channel when OpenCV is available, else a safe fallback."""
    try:
        import cv2  # type: ignore

        lab = cv2.cvtColor(np.asarray(image.convert("RGB")), cv2.COLOR_RGB2LAB)
        clahe = cv2.createCLAHE(clipLimit=clip, tileGridSize=(grid, grid))
        lab[..., 0] = clahe.apply(lab[..., 0])
        rgb = cv2.cvtColor(lab, cv2.COLOR_LAB2RGB)
        return Image.fromarray(rgb)
    except Exception:
        ycbcr = image.convert("YCbCr")
        y, cb, cr = ycbcr.split()
        y = ImageOps.autocontrast(y, cutoff=1)
        return Image.merge("YCbCr", (y, cb, cr)).convert("RGB")


# ---------------------------------------------------------------------------
# Public preprocessor
# ---------------------------------------------------------------------------

class LeafPreprocessor:
    """Configurable leaf isolation + normalization stage."""

    AVAILABLE_METHODS = ("rembg", "grabcut", "numpy")
    _MAX_SEGMENT_SIDE = 512  # segment on a downscaled copy for speed

    def __init__(
        self,
        enabled: bool = True,
        method: str = "auto",
        target_fill: float = 0.75,
        background_gray: int = 240,
        color_normalization: bool = True,
        canvas_size: Optional[int] = None,
        min_leaf_area_ratio: float = 0.02,
        debug_dir: Optional[str] = None,
        policy: str = "adaptive",
    ) -> None:
        self.enabled = enabled
        self.method = (method or "auto").lower()
        self.target_fill = float(np.clip(target_fill, 0.5, 0.95))
        self.background_gray = int(np.clip(background_gray, 0, 255))
        self.color_normalization = color_normalization
        self.canvas_size = canvas_size
        self.min_leaf_area_ratio = float(min_leaf_area_ratio)
        self.debug_dir = Path(debug_dir) if debug_dir else None
        self.policy = (policy or "adaptive").lower()
        self._rembg_session = None

    # -- introspection ------------------------------------------------------
    def available_methods(self) -> list[str]:
        methods = ["numpy"]
        try:
            import cv2  # noqa: F401
            methods.append("grabcut")
        except Exception:
            pass
        try:
            import rembg  # noqa: F401
            methods.append("rembg")
        except Exception:
            pass
        return methods

    def _get_rembg_session(self):
        if self._rembg_session is None:
            try:
                from rembg import new_session  # type: ignore

                self._rembg_session = new_session("u2net")
            except Exception:
                self._rembg_session = False
        return self._rembg_session or None

    # -- segmentation -------------------------------------------------------
    def segment(self, image: Image.Image) -> Tuple[Optional[np.ndarray], str]:
        """Return a full-size 0/1 mask and the strategy that produced it."""
        full_w, full_h = image.size
        scale = min(1.0, self._MAX_SEGMENT_SIDE / max(full_w, full_h))
        if scale < 1.0:
            work = image.resize((max(1, int(full_w * scale)), max(1, int(full_h * scale))), Image.BILINEAR)
        else:
            work = image

        order = {
            "auto": ["rembg", "grabcut", "numpy"],
            "rembg": ["rembg"],
            "grabcut": ["grabcut"],
            "numpy": ["numpy"],
        }.get(self.method, ["rembg", "grabcut", "numpy"])

        mask = None
        used = "none"
        for name in order:
            if name == "rembg":
                m = _segment_rembg(work, self._get_rembg_session())
            elif name == "grabcut":
                m = _segment_grabcut(work)
            else:
                m = _segment_numpy(work)
            if m is not None and m.sum() > 0:
                mask = m
                used = name
                break

        if mask is None:
            return None, "none"

        if mask.shape != (full_h, full_w):
            mask = np.asarray(
                Image.fromarray((mask * 255).astype(np.uint8)).resize((full_w, full_h), Image.NEAREST)
            )
            mask = (mask > 127).astype(np.uint8)

        if mask.sum() < self.min_leaf_area_ratio * full_w * full_h:
            return None, used
        return mask, used

    # -- compositing --------------------------------------------------------
    def _compose(self, image: Image.Image, mask: np.ndarray) -> Image.Image:
        ys, xs = np.where(mask > 0)
        x0, x1, y0, y1 = xs.min(), xs.max(), ys.min(), ys.max()
        crop = image.crop((x0, y0, x1 + 1, y1 + 1))
        crop_mask = Image.fromarray((mask[y0:y1 + 1, x0:x1 + 1] * 255).astype(np.uint8))

        w, h = crop.size
        canvas = self.canvas_size or max(256, int(max(w, h) * 1.6))
        target_leaf = max(1, int(canvas * self.target_fill))
        scale = target_leaf / max(w, h)
        new_size = (max(1, round(w * scale)), max(1, round(h * scale)))

        crop_r = crop.resize(new_size, Image.LANCZOS)
        mask_r = crop_mask.resize(new_size, Image.LANCZOS)

        bg = (self.background_gray,) * 3
        out = Image.new("RGB", (canvas, canvas), bg)
        offset = ((canvas - new_size[0]) // 2, (canvas - new_size[1]) // 2)
        out.paste(crop_r, offset, mask_r)
        return out

    # -- adaptive gate ------------------------------------------------------
    def _should_apply(self, image: Image.Image, mask: np.ndarray) -> bool:
        """Decide whether isolating the background is actually beneficial.

        Preprocessing must never make things worse, so this gate is deliberately
        conservative: it only fires when the segmentation is a plausible,
        well-isolated single leaf on a non-neutral background. In every other
        case (fragmented mask, leaf touching the frame, mask covering the whole
        photo, neutral studio backdrop) the image is passed through untouched.

        Measured rationale: on real field photos whose background contains other
        green foliage, an Excess-Green based mask tends to latch onto the
        *background* leaf, which then hurts accuracy (1/5 -> 0/5 on a 5-image
        sample). Skipping in those cases keeps the plain-image baseline.
        """
        total = float(mask.size)
        leaf_ratio = float(mask.sum()) / total

        # A plausible isolated leaf occupies a moderate part of the frame.
        if leaf_ratio < 0.08 or leaf_ratio > 0.75:
            return False

        # The leaf should be separated from the frame border (clean capture),
        # otherwise the mask is likely fragmented or bleeding into the scene.
        h, w = mask.shape
        ys, xs = np.where(mask > 0)
        margin = 0.02
        if (
            xs.min() < margin * w
            or ys.min() < margin * h
            or xs.max() > (1 - margin) * w
            or ys.max() > (1 - margin) * h
        ):
            return False

        arr = np.asarray(image.convert("RGB"), dtype=np.float32)
        bg = arr[mask == 0]
        if bg.shape[0] < 100:
            return False

        bg_std = float(bg.std(axis=0).mean())          # background texture
        mx = bg.max(axis=1)
        mn = bg.min(axis=1)
        bg_sat = float(np.mean(np.where(mx > 0, (mx - mn) / np.maximum(mx, 1e-6), 0.0)))

        # PlantVillage-style captures have a near-neutral, low-texture background
        # (studio backdrop / soil-free). In that case the image already matches
        # the training distribution, so re-framing it only hurts. Only act when
        # the background is colourful or textured, i.e. a real field photo.
        if bg_std < 55.0 and bg_sat < 0.20:
            return False
        return True

    # -- main entry ---------------------------------------------------------
    def process(
        self,
        image: Image.Image,
        save_debug_name: Optional[str] = None,
        enabled: Optional[bool] = None,
        policy: Optional[str] = None,
    ) -> Tuple[Image.Image, dict]:
        meta: dict = {
            "applied": False,
            "method": "none",
            "color_normalized": False,
            "leaf_area_ratio": 0.0,
        }
        if enabled is None:
            enabled = self.enabled
        if not enabled:
            return image, meta

        original = image.convert("RGB")
        mask, used = self.segment(original)
        if mask is None:
            # Could not isolate a leaf -> keep original to avoid degrading input.
            return original, meta

        meta["method"] = used
        meta["leaf_area_ratio"] = round(float(mask.sum()) / float(mask.size), 4)

        active_policy = (policy or self.policy or "adaptive").lower()
        if active_policy == "adaptive" and not self._should_apply(original, mask):
            meta["skipped"] = True
            return original, meta

        leaf = original
        if self.color_normalization:
            leaf = gray_world_balance(original, mask)

        composed = self._compose(leaf, mask)

        if self.color_normalization:
            composed = apply_local_contrast(composed)
            meta["color_normalized"] = True

        meta["applied"] = True

        if self.debug_dir is not None and save_debug_name:
            try:
                self.debug_dir.mkdir(parents=True, exist_ok=True)
                composed.save(self.debug_dir / f"{save_debug_name}_preprocessed.jpg", quality=90)
                original.save(self.debug_dir / f"{save_debug_name}_original.jpg", quality=90)
            except Exception:
                pass

        return composed, meta
