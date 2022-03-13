<?php

namespace javamapconverter\utils;

use GdImage;
use function array_map;
use function array_pop;
use function array_push;
use function bin2hex;
use function count;
use function hexdec;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecolorat;
use function imagecreatefrompng;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagesavealpha;
use function imagesetpixel;
use function imagesx;
use function imagesy;
use function pack;
use function str_split;

class SkinUtils {
    public static function readImage(string $filePath): string{
        $image = @imagecreatefrompng($filePath);
        if($image === false) return "";
        $fileContent = '';
        for($y = 0, $height = imagesy($image); $y < $height; $y++){
            for($x = 0, $width = imagesx($image); $x < $width; $x++){
                $color = imagecolorat($image, $x, $y);
                $fileContent .= pack("c", ($color >> 16) & 0xFF)
                    .pack("c", ($color >> 8) & 0xFF)
                    .pack("c", $color & 0xFF)
                    .pack("c", 255 - (($color & 0x7F000000) >> 23));
            }
        }
        imagedestroy($image);
        return $fileContent;
    }

    public static function fromString(string $skinData, $height = 64, $width = 64): GdImage|bool{
        $pixelarray = str_split(bin2hex($skinData), 8);
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);//do not touch
        imagesavealpha($image, true);
        $position = count($pixelarray) - 1;
        while (!empty($pixelarray)){
            $x = $position % $width;
            $y = ($position - $x) / $height;
            $walkable = str_split(array_pop($pixelarray), 2);
            $color = array_map(
                function ($val){
                    return hexdec($val);
                }, $walkable
            );
            $alpha = array_pop($color);
            $alpha = ((~((int)$alpha)) & 0xff) >> 1;
            array_push($color, $alpha);
            imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, ...$color));
            $position--;
        }
        return $image;
    }
}