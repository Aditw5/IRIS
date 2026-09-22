import io

from fastapi.testclient import TestClient
from PIL import Image

from app.main import Settings, app, optimize_image


def _image_bytes(fmt: str, size: tuple[int, int], mode: str = "RGB") -> bytes:
    color = (10, 80, 160, 128) if mode == "RGBA" else (10, 80, 160)
    image = Image.new(mode, size, color)
    output = io.BytesIO()
    image.save(output, format=fmt)
    return output.getvalue()


def test_main_is_webp_and_respects_bounds() -> None:
    config = Settings(max_width=1920, max_height=1920)
    result, width, height = optimize_image(_image_bytes("JPEG", (3000, 1500)), "main", config)

    assert result[0:4] == b"RIFF"
    assert result[8:12] == b"WEBP"
    assert (width, height) == (1920, 960)


def test_thumbnail_preserves_png_transparency() -> None:
    result, width, height = optimize_image(_image_bytes("PNG", (1200, 600), "RGBA"), "thumbnail")

    with Image.open(io.BytesIO(result)) as image:
        assert image.format == "WEBP"
        assert image.mode == "RGBA"
    assert (width, height) == (600, 300)


def test_exif_orientation_is_applied() -> None:
    image = Image.new("RGB", (40, 20), "red")
    exif = image.getexif()
    exif[274] = 6
    source = io.BytesIO()
    image.save(source, format="JPEG", exif=exif)

    _, width, height = optimize_image(source.getvalue(), "main")
    assert (width, height) == (20, 40)


def test_rejects_non_image() -> None:
    client = TestClient(app)
    response = client.post(
        "/v1/optimize",
        files={"image": ("not-image.jpg", b"not an image", "image/jpeg")},
        data={"profile": "main"},
    )

    assert response.status_code == 422


def test_health() -> None:
    assert TestClient(app).get("/health").json() == {"status": "ok"}
