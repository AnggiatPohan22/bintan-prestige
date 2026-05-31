<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    public function upload(
        UploadedFile $file,
        string $folder = 'products'
    ): string {

        // filename
        $filename =
            Str::uuid() . '.webp';

        // directory
        $directory =
            storage_path(
                'app/public/' . $folder
            );

        // create folder
        if (! file_exists($directory)) {
            mkdir(
                $directory,
                0777,
                true
            );
        }

        $path =
            $directory .
            '/' .
            $filename;

        // detect mime type
        $mime =
            $file->getMimeType();

        // create source image
        switch ($mime) {

            case 'image/jpeg':
            case 'image/jpg':
                $source =
                    imagecreatefromjpeg(
                        $file->getRealPath()
                    );
                break;

            case 'image/png':
                $source =
                    imagecreatefrompng(
                        $file->getRealPath()
                    );
                break;

            case 'image/webp':
                $source =
                    imagecreatefromwebp(
                        $file->getRealPath()
                    );
                break;

            default:
                throw new \Exception(
                    'Unsupported image type.'
                );
        }

        // get dimensions
        $width =
            imagesx($source);

        $height =
            imagesy($source);

        // max width
        $newWidth = 1400;

        // keep ratio
        if ($width > $newWidth) {

            $newHeight =
                intval(
                    ($height / $width)
                    * $newWidth
                );

        } else {

            $newWidth = $width;
            $newHeight = $height;
        }

        // create canvas
        $optimized =
            imagecreatetruecolor(
                $newWidth,
                $newHeight
            );

        // resize
        imagecopyresampled(
            $optimized,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        // convert to webp
        imagewebp(
            $optimized,
            $path,
            80
        );

        // cleanup memory
        imagedestroy($source);
        imagedestroy($optimized);

        return
            $folder .
            '/' .
            $filename;
    }
}