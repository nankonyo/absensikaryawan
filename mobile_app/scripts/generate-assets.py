import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ASSETS = ROOT / 'assets'
ASSETS.mkdir(parents=True, exist_ok=True)
TMP = Path('/tmp/rst-mobile-assets')
TMP.mkdir(parents=True, exist_ok=True)

FONT = "Helvetica, Arial, sans-serif"
GRAD = '''<defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#B33C43"/><stop offset="1" stop-color="#E2757B"/></linearGradient></defs>'''

def svg(body, size=1024, background='url(#g)'):
    bg = f'<rect width="1024" height="1024" fill="{background}"/>'
    return f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 {size} {size}">{GRAD}{bg}{body}</svg>'

def text(x, y, size, value, weight=700, spacing=0, opacity=1, fill="#ffffff"):
    ls = f' letter-spacing="{spacing}"' if spacing else ''
    return f'<text x="{x}" y="{y}" text-anchor="middle" font-family="{FONT}" font-weight="{weight}" font-size="{size}" fill="{fill}" fill-opacity="{opacity}"{ls}>{value}</text>'

logs = {
    'icon.svg': svg(
        '<rect x="190" y="190" width="644" height="644" rx="90" fill="#ffffff" fill-opacity="0.10"/>'
        + text(512, 610, 250, 'RST', spacing=22)
        + text(512, 700, 62, 'INTERNAL', spacing=8)
    ),
    'adaptive-icon.svg': svg(
        text(512, 585, 210, 'RST', spacing=18)
        + text(512, 675, 58, 'INTERNAL', spacing=8)
    , background='#D34B52'),
    'splash-icon.svg': svg(
        text(512, 510, 200, 'RST', spacing=18)
        + text(512, 605, 54, 'INTERNAL APP', spacing=8)
        + text(512, 685, 28, 'SISTEM ABSENSI KARYAWAN', weight=600, spacing=6)
    , background='#D34B52'),
    'favicon.svg': svg(
        text(64, 84, 58, 'RST', spacing=2)
    , size=128),
}

for name, content in logs.items():
    src = TMP / name
    src.write_text(content)
    subprocess.run(['qlmanage', '-t', '-s', str(128 if name == 'favicon.svg' else 1024), '-o', str(TMP), str(src)], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    generated = TMP / f'{name}.png'
    if not generated.exists():
        raise SystemExit(f'failed to generate {name}')
    target = ASSETS / name.replace('.svg', '.png')
    generated.replace(target)
    print(target.name, target.stat().st_size)

print('assets generated')
