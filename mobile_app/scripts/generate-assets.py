#!/usr/bin/env python3
"""Generate Raja HR mobile icons and splash assets without external tools."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
ASSETS = ROOT / 'assets'
RES = ROOT / 'android' / 'app' / 'src' / 'main' / 'res'
FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'

TOP = (179, 60, 67)       # #B33C43
BOTTOM = (226, 117, 123)  # #E2757B
BRAND = '#D34B52'


def gradient(size):
    img = Image.new('RGB', (size, size), TOP)
    draw = ImageDraw.Draw(img)
    for y in range(size):
        ratio = y / max(size - 1, 1)
        color = tuple(round(TOP[i] + (BOTTOM[i] - TOP[i]) * ratio) for i in range(3))
        draw.line((0, y, size, y), fill=color)
    return img.convert('RGBA')


def rounded_rect(img, box, radius, fill):
    overlay = Image.new('RGBA', img.size, (0, 0, 0, 0))
    ImageDraw.Draw(overlay).rounded_rectangle(box, radius=radius, fill=fill)
    return Image.alpha_composite(img, overlay)


def text_width(font, value, spacing):
    widths = [font.getlength(char) for char in value]
    return sum(widths) + spacing * max(len(value) - 1, 0)


def draw_spaced_text(draw, center_x, y, value, size, spacing=0, fill='white', font_path=FONT_BOLD):
    font = ImageFont.truetype(font_path, size)
    width = text_width(font, value, spacing)
    x = center_x - width / 2
    for char in value:
        draw.text((x, y), char, font=font, fill=fill, anchor='lt')
        x += font.getlength(char) + spacing


def mark(size, with_background=True, rounded=True):
    if with_background:
        img = gradient(size)
        if rounded:
            inset = round(size * 0.185)
            img = rounded_rect(
                img,
                (inset, inset, size - inset, size - inset),
                round(size * 0.09),
                (255, 255, 255, 26),
            )
    else:
        img = Image.new('RGBA', (size, size), (0, 0, 0, 0))

    draw = ImageDraw.Draw(img)
    scale = size / 1024
    draw_spaced_text(draw, size / 2, round(390 * scale), 'RAJA', round(190 * scale), round(16 * scale))
    draw_spaced_text(draw, size / 2, round(595 * scale), 'HR', round(260 * scale), round(20 * scale))
    return img


def splash(size):
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    scale = size / 1024
    draw_spaced_text(draw, size / 2, round(390 * scale), 'RAJA HR', round(160 * scale), round(10 * scale))
    draw_spaced_text(
        draw,
        size / 2,
        round(610 * scale),
        'SISTEM MANAJEMEN SDM',
        round(34 * scale),
        round(5 * scale),
        fill=(255, 255, 255, 235),
    )
    return img


def circular(img):
    size = img.size[0]
    mask = Image.new('L', (size, size), 0)
    ImageDraw.Draw(mask).ellipse((0, 0, size, size), fill=255)
    result = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    result.paste(img, (0, 0), mask)
    return result


def save_png(img, path, size=None):
    path.parent.mkdir(parents=True, exist_ok=True)
    if size and img.size != (size, size):
        img = img.resize((size, size), Image.LANCZOS)
    if path.suffix == '.webp':
        img.save(path, 'WEBP', quality=95, method=6)
    else:
        img.save(path, 'PNG')
    print(f'{path.relative_to(ROOT)} {img.size[0]}x{img.size[1]}')


def main():
    ASSETS.mkdir(parents=True, exist_ok=True)

    icon = mark(1024)
    adaptive = mark(1024, with_background=False)
    splash_image = splash(1024)
    favicon = mark(128)

    save_png(icon, ASSETS / 'icon.png')
    save_png(adaptive, ASSETS / 'adaptive-icon.png')
    save_png(splash_image, ASSETS / 'splash-icon.png')
    save_png(favicon, ASSETS / 'favicon.png')

    splash_sizes = {
        'mdpi': 288,
        'hdpi': 432,
        'xhdpi': 576,
        'xxhdpi': 864,
        'xxxhdpi': 1152,
    }
    for density, size in splash_sizes.items():
        save_png(splash_image, RES / f'drawable-{density}' / 'splashscreen_logo.png', size)

    launcher_sizes = {
        'mdpi': 48,
        'hdpi': 72,
        'xhdpi': 96,
        'xxhdpi': 144,
        'xxxhdpi': 192,
    }
    foreground_sizes = {
        'mdpi': 108,
        'hdpi': 162,
        'xhdpi': 216,
        'xxhdpi': 324,
        'xxxhdpi': 432,
    }
    for density, size in launcher_sizes.items():
        folder = RES / f'mipmap-{density}'
        save_png(icon, folder / 'ic_launcher.webp', size)
        save_png(circular(icon), folder / 'ic_launcher_round.webp', size)
        save_png(adaptive, folder / 'ic_launcher_foreground.webp', foreground_sizes[density])

    print('Raja HR assets generated; splash background', BRAND)


if __name__ == '__main__':
    main()
