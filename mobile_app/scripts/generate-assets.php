<?php
$dir = __DIR__ . '/../assets';
@mkdir($dir, 0775, true);
$fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

function gradient($img, $top, $bottom) {
    $w = imagesx($img); $h = imagesy($img);
    for ($y = 0; $y < $h; $y++) {
        $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $y / $h);
        $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $y / $h);
        $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $y / $h);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }
}

function centerText($img, $font, $size, $text, $color, $y, $spacing = 0) {
    if ($spacing <= 0) {
        $box = imagettfbbox($size, 0, $font, $text);
        $w = $box[2] - $box[0];
        imagettftext($img, $size, 0, (int) ((imagesx($img) - $w) / 2), $y, $color, $font, $text);
        return;
    }

    $widths = [];
    $total = 0;
    for ($i = 0; $i < strlen($text); $i++) {
        $box = imagettfbbox($size, 0, $font, $text[$i]);
        $widths[$i] = $box[2] - $box[0];
        $total += $widths[$i];
    }
    $total += $spacing * (strlen($text) - 1);
    $x = (int) ((imagesx($img) - $total) / 2);
    for ($i = 0; $i < strlen($text); $i++) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text[$i]);
        $x += $widths[$i] + $spacing;
    }
}

function roundedRect($img, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

// App icon (full-bleed blue)
$icon = imagecreatetruecolor(1024, 1024);
gradient($icon, [29, 78, 216], [59, 130, 246]);
$soft = imagecolorallocatealpha($icon, 255, 255, 255, 18);
roundedRect($icon, 190, 190, 834, 834, 90, $soft);
$white = imagecolorallocate($icon, 255, 255, 255);
centerText($icon, $fontBold, 250, 'RST', $white, 590, 22);
centerText($icon, $fontBold, 66, 'INTERNAL', $white, 690, 10);
imagepng($icon, $dir . '/icon.png');

// Adaptive icon foreground (transparent background, safe zone)
$adaptive = imagecreatetruecolor(1024, 1024);
imagesavealpha($adaptive, true);
imagefill($adaptive, 0, 0, imagecolorallocatealpha($adaptive, 0, 0, 0, 127));
centerText($adaptive, $fontBold, 210, 'RST', $white, 585, 18);
centerText($adaptive, $fontBold, 58, 'INTERNAL', $white, 675, 8);
imagepng($adaptive, $dir . '/adaptive-icon.png');

// Splash logo (transparent)
$splash = imagecreatetruecolor(1024, 1024);
imagesavealpha($splash, true);
imagefill($splash, 0, 0, imagecolorallocatealpha($splash, 0, 0, 0, 127));
centerText($splash, $fontBold, 200, 'RST', $white, 540, 18);
centerText($splash, $fontBold, 54, 'INTERNAL APP', $white, 630, 8);
centerText($splash, $fontBold, 30, 'SISTEM ABSENSI KARYAWAN', $white, 700, 6);
imagepng($splash, $dir . '/splash-icon.png');

// Favicon
$favicon = imagecreatetruecolor(128, 128);
gradient($favicon, [29, 78, 216], [59, 130, 246]);
centerText($favicon, $fontBold, 58, 'RST', $white, 88, 2);
imagepng($favicon, $dir . '/favicon.png');

echo "assets generated\n";
