<?php
session_start();

// Generate a random 5-character captcha code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_text = '';
for ($i = 0; $i < 5; $i++) {
    $captcha_text .= $chars[rand(0, strlen($chars) - 1)];
}
$_SESSION['admin_captcha'] = $captcha_text;

// Create the image
$width = 120;
$height = 38;
$image = imagecreatetruecolor($width, $height);
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 50, 50, 50);
$line_color = imagecolorallocate($image, 200, 200, 200);

imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add some noise lines
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add the text
$font_size = 20;
$font_file = __DIR__ . '/../assets/arial.ttf';
if (file_exists($font_file)) {
    imagettftext($image, $font_size, rand(-10, 10), 18, 30, $text_color, $font_file, $captcha_text);
} else {
    imagestring($image, 5, 28, 10, $captcha_text, $text_color);
}

// Output the image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
