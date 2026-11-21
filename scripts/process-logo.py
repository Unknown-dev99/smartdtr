"""Remove flat white background from INHS logo PNG."""

from collections import deque
from pathlib import Path

from PIL import Image

SRC = Path(__file__).resolve().parents[1] / "public" / "images" / "inhs-logo.png"
OUT = SRC


def is_background(r: int, g: int, b: int, a: int) -> bool:
    if a < 20:
        return True
    # Near-white square padding around the seal
    return r > 235 and g > 235 and b > 235


def flood_transparent(img: Image.Image) -> Image.Image:
    rgba = img.convert("RGBA")
    w, h = rgba.size
    pixels = rgba.load()
    visited = [[False] * w for _ in range(h)]
    queue: deque[tuple[int, int]] = deque()

    for x in range(w):
        queue.append((x, 0))
        queue.append((x, h - 1))
    for y in range(h):
        queue.append((0, y))
        queue.append((w - 1, y))

    while queue:
        x, y = queue.popleft()
        if x < 0 or y < 0 or x >= w or y >= h or visited[y][x]:
            continue
        visited[y][x] = True
        r, g, b, a = pixels[x, y]
        if not is_background(r, g, b, a):
            continue
        pixels[x, y] = (r, g, b, 0)
        queue.append((x + 1, y))
        queue.append((x - 1, y))
        queue.append((x, y + 1))
        queue.append((x, y - 1))

    return rgba


def main() -> None:
    img = Image.open(SRC)
    result = flood_transparent(img)
    result.save(OUT, "PNG")
    print(f"Processed logo saved to {OUT}")


if __name__ == "__main__":
    main()
