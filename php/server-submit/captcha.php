<?php
/**
 * 图形验证码生成
 * 校验端读取 $_SESSION['captcha']（大写比对），详见 user/ajax.php 与 Adminq/Ajax.php
 * 访问路径：{站点根}/server-submit/captcha.php
 */
session_start();

// 禁止缓存，避免验证码被浏览器缓存复用
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$width  = 100;
$height = 40;
$length = 4;

// 去除易混淆字符 0/O/1/I/L
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$charCount = strlen($chars) - 1;

$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, $charCount)];
}
// 校验端使用 strtoupper 比对，此处直接存大写，保持统一
$_SESSION['captcha'] = $code;

$img = imagecreate($width, $height);

$bgColor    = imagecolorallocate($img, 245, 245, 245);
$noiseColor = imagecolorallocate($img, 180, 180, 180);

// 背景干扰点
for ($i = 0; $i < 220; $i++) {
    imagesetpixel($img, random_int(0, $width - 1), random_int(0, $height - 1), $noiseColor);
}

// 背景干扰线
for ($i = 0; $i < 3; $i++) {
    imageline(
        $img,
        random_int(0, $width), random_int(0, $height),
        random_int(0, $width), random_int(0, $height),
        $noiseColor
    );
}

// 逐字符绘制（使用内置字体，避免依赖外部字体文件）
$step = intval($width / $length);
for ($i = 0; $i < $length; $i++) {
    $x = $step * $i + intval(($step - 10) / 2);
    $y = random_int(2, 14);
    $c = imagecolorallocate($img, random_int(0, 120), random_int(0, 120), random_int(0, 120));
    imagechar($img, 5, $x, $y, $code[$i], $c);
}

imagepng($img);
imagedestroy($img);
exit;
