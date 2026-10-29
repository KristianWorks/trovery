<?php
// Dynamic placeholder image generator — no external dependencies
$text = isset($_GET['text']) ? mb_substr(strip_tags($_GET['text']), 0, 30) : 'No Image';
$w    = max(50, min((int)($_GET['w'] ?? 400), 800));
$h    = max(50, min((int)($_GET['h'] ?? 400), 800));

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$im = imagecreatetruecolor($w, $h);
$bg = imagecolorallocate($im, 28, 58, 95);     // navy
$ov = imagecolorallocatealpha($im, 45, 84, 130, 100);
$ac = imagecolorallocate($im, 255, 107, 53);    // orange
$fg = imagecolorallocate($im, 255, 249, 245);   // warm white

imagefill($im, 0, 0, $bg);
// Subtle circle overlays
imagefilledellipse($im, (int)($w * 0.8), (int)($h * 0.2), (int)($w * 0.6), (int)($w * 0.6), $ov);
imagefilledellipse($im, (int)($w * 0.1), (int)($h * 0.8), (int)($w * 0.4), (int)($w * 0.4), $ov);

// Center icon area
$cx = (int)($w / 2); $cy = (int)($h / 2);
imagefilledellipse($im, $cx, $cy - 20, 60, 60, $ac);

// Text
$fontSize = 3;
$tw = imagefontwidth($fontSize) * strlen($text);
$th = imagefontheight($fontSize);
$tx = max(0, ($w - $tw) / 2);
$ty = $cy + 22;
imagestring($im, $fontSize, (int)$tx, (int)$ty, $text, $fg);

imagepng($im);
imagedestroy($im);
