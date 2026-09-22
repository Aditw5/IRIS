from __future__ import annotations

import io
import os
from dataclasses import dataclass
from typing import Annotated, Literal

from fastapi import FastAPI, File, Form, HTTPException, UploadFile, status
from fastapi.responses import Response
from PIL import Image, ImageOps, UnidentifiedImageError


Profile = Literal["main", "thumbnail"]


def _positive_int(name: str, default: int) -> int:
    try:
        value = int(os.getenv(name, str(default)))
    except ValueError as exc:
        raise RuntimeError(f"{name} harus berupa bilangan bulat") from exc
    if value <= 0:
        raise RuntimeError(f"{name} harus lebih besar dari 0")
    return value


@dataclass(frozen=True)
class Settings:
    quality: int = _positive_int("IMAGE_WEBP_QUALITY", 80)
    max_width: int = _positive_int("IMAGE_MAX_WIDTH", 1920)
    max_height: int = _positive_int("IMAGE_MAX_HEIGHT", 1920)
    thumb_width: int = _positive_int("IMAGE_THUMB_WIDTH", 600)
    max_upload_bytes: int = _positive_int("IMAGE_MAX_UPLOAD_BYTES", 20 * 1024 * 1024)
    max_image_pixels: int = _positive_int("IMAGE_MAX_PIXELS", 40_000_000)

    def __post_init__(self) -> None:
        if not 1 <= self.quality <= 100:
            raise RuntimeError("IMAGE_WEBP_QUALITY harus berada di antara 1 dan 100")


settings = Settings()
Image.MAX_IMAGE_PIXELS = settings.max_image_pixels

app = FastAPI(
    title="U-LAB Image Optimizer",
    version="1.0.0",
    docs_url=None,
    redoc_url=None,
)


async def _read_limited(upload: UploadFile) -> bytes:
    chunks: list[bytes] = []
    total = 0
    while chunk := await upload.read(1024 * 1024):
        total += len(chunk)
        if total > settings.max_upload_bytes:
            raise HTTPException(
                status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
                detail="Ukuran gambar melebihi batas layanan",
            )
        chunks.append(chunk)
    return b"".join(chunks)


def optimize_image(source: bytes, profile: Profile, config: Settings = settings) -> tuple[bytes, int, int]:
    if not source:
        raise ValueError("File gambar kosong")

    try:
        with Image.open(io.BytesIO(source)) as probe:
            if probe.format not in {"JPEG", "PNG"}:
                raise ValueError("Format gambar harus JPEG atau PNG")
            if probe.width * probe.height > config.max_image_pixels:
                raise ValueError("Resolusi gambar melebihi batas layanan")
            probe.verify()

        with Image.open(io.BytesIO(source)) as opened:
            image = ImageOps.exif_transpose(opened)
            image.load()
    except (UnidentifiedImageError, OSError, SyntaxError) as exc:
        raise ValueError("File bukan gambar JPEG/PNG yang valid") from exc

    if profile == "main":
        bounds = (config.max_width, config.max_height)
    else:
        bounds = (config.thumb_width, config.thumb_width)
    image.thumbnail(bounds, Image.Resampling.LANCZOS)

    has_alpha = image.mode in {"RGBA", "LA"} or (
        image.mode == "P" and "transparency" in image.info
    )
    image = image.convert("RGBA" if has_alpha else "RGB")

    output = io.BytesIO()
    image.save(
        output,
        format="WEBP",
        quality=config.quality,
        method=6,
        optimize=True,
    )
    return output.getvalue(), image.width, image.height


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/v1/optimize", response_class=Response)
async def optimize(
    image: Annotated[UploadFile, File()],
    profile: Annotated[Profile, Form()] = "main",
) -> Response:
    source = await _read_limited(image)
    try:
        body, width, height = optimize_image(source, profile)
    except (ValueError, Image.DecompressionBombError) as exc:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_CONTENT,
            detail=str(exc),
        ) from exc
    finally:
        await image.close()

    return Response(
        content=body,
        media_type="image/webp",
        headers={
            "X-Image-Width": str(width),
            "X-Image-Height": str(height),
            "Cache-Control": "no-store",
        },
    )
