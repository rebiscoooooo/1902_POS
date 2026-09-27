<?php
$src_path = 'C:\Users\Jay-ar\.gemini\antigravity-ide\brain\c4a287bb-0b19-44d2-a49d-9c085f5c670a\.user_uploaded\media_1789405662330.jpg';
$dst_path = 'c:\xampp\htdocs\1902_pos\assets\img\logo.png';

$src = imagecreatefromjpeg($src_path);
$w = imagesx($src);
$h = imagesy($src);
$dst = imagecreatetruecolor($w, $h);

imagealphablending($dst, false);
imagesavealpha($dst, true);
$trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $trans);

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        $lum = max($r, $g, $b);
        // Alpha is 0 (opaque) to 127 (transparent). 
        // If lum is 0 (black), alpha should be 127 (fully transparent)
        // If lum is 255 (white), alpha should be 0 (fully opaque)
        $alpha = 127 - (int)(127 * ($lum / 255));
        
        $col = imagecolorallocatealpha($dst, $r, $g, $b, $alpha);
        imagesetpixel($dst, $x, $y, $col);
    }
}

if (!is_dir(dirname($dst_path))) {
    mkdir(dirname($dst_path), 0777, true);
}
imagepng($dst, $dst_path);
echo "Image processed and saved to $dst_path\n";
