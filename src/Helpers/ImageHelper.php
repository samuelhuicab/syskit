<?php
namespace SysKit\Helpers;

class ImageHelper
{
    public static function optimize(string $path, int $quality = 80): bool
    {
        if (!extension_loaded('gd')) {
            throw new \Exception("La extensión GD no está habilitada.");
        }

        if (!file_exists($path)) {
            throw new \Exception("Archivo no encontrado: $path");
        }

        [$width, $height, $type] = getimagesize($path);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                imagejpeg($image, $path, $quality);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                imagepng($image, $path, 9 - (int) round($quality / 10));
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($path);
                imagewebp($image, $path, $quality);
                break;
            default:
                throw new \Exception("Formato de imagen no soportado.");
        }

        imagedestroy($image);
        return true;
    }
}
