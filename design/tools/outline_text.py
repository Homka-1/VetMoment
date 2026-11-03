"""Переводит строку в SVG-контур (для логотипа: в <img> веб-шрифты не грузятся).

Использование: python3 outline_text.py <font.woff2> "<текст>" <size> [tracking_em]
Печатает JSON {"d": path, "width": ширина, "ascent": ..., "capHeight": ...}.
Нужны пакеты: fonttools, brotli (pip install fonttools brotli).
"""
import json
import re
import sys

from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont

font_path, text, size = sys.argv[1], sys.argv[2], float(sys.argv[3])
tracking = float(sys.argv[4]) if len(sys.argv) > 4 else 0.0

font = TTFont(font_path)
cmap = font.getBestCmap()
gs = font.getGlyphSet()
upm = font["head"].unitsPerEm
scale = size / upm
hmtx = font["hmtx"]
kern = {}
pen = SVGPathPen(gs)
x = 0.0
for ch in text:
    name = cmap[ord(ch)]
    tp = TransformPen(pen, (scale, 0, 0, -scale, x, 0))
    gs[name].draw(tp)
    x += hmtx[name][0] * scale + tracking * size
x -= tracking * size
os2 = font["OS/2"]
print(json.dumps({
    "d": re.sub(r"-?\d+\.\d+", lambda m: f"{float(m.group()):.2f}".rstrip("0").rstrip("."), pen.getCommands()),
    "width": round(x, 2),
    "ascent": round(os2.sTypoAscender * scale, 2),
    "capHeight": round(getattr(os2, "sCapHeight", 0) * scale, 2),
}))
