<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageStorage
{
    /**
     * @var array<string, string>
     */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{mime: string, extension: string, width: int, height: int}
     */
    public function inspect(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false || ! is_file($path)) {
            throw new InvalidProductImage('Não foi possível ler a imagem.');
        }

        $maxBytes = (int) config('catalog.max_bytes');
        if ($file->getSize() === false || $file->getSize() > $maxBytes) {
            throw new InvalidProductImage('O arquivo é grande demais.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        $detected = is_array($info) ? (string) ($info['mime'] ?? '') : '';

        if (! is_string($mime) || $mime === '' || $mime !== $detected || ! isset(self::EXTENSIONS[$mime])) {
            throw new InvalidProductImage('Envie um arquivo JPEG, PNG ou WebP.');
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        $min = (int) config('catalog.min_edge');
        $max = (int) config('catalog.max_edge');

        if ($width < $min || $height < $min || $width > $max || $height > $max) {
            throw new InvalidProductImage("Cada lado da imagem precisa ter entre {$min} e {$max} pixels.");
        }

        if ($mime === 'image/webp') {
            $this->assertWebpContainer($path);
        }

        return [
            'mime' => $mime,
            'extension' => self::EXTENSIONS[$mime],
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{path: string, mime: string, width: int, height: int}
     */
    public function store(UploadedFile $file): array
    {
        $meta = $this->inspect($file);
        $source = (string) $file->getRealPath();

        if ($meta['mime'] === 'image/webp') {
            $bytes = file_get_contents($source);
        } else {
            [$bytes, $meta['width'], $meta['height']] = $this->reencode($source, $meta['mime']);
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new InvalidProductImage('Não foi possível processar a imagem.');
        }

        $relative = 'products/'.Str::uuid()->toString().'.'.$meta['extension'];
        $written = Storage::disk('catalog')->put($relative, $bytes);

        if ($written === false) {
            throw new InvalidProductImage('Não foi possível guardar a imagem.');
        }

        return [
            'path' => $relative,
            'mime' => $meta['mime'],
            'width' => $meta['width'],
            'height' => $meta['height'],
        ];
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return;
        }

        Storage::disk('catalog')->delete($path);
    }

    private function assertWebpContainer(string $path): void
    {
        $header = file_get_contents($path, false, null, 0, 12);

        if (! is_string($header) || ! str_starts_with($header, 'RIFF') || substr($header, 8, 4) !== 'WEBP') {
            throw new InvalidProductImage('Envie um arquivo JPEG, PNG ou WebP.');
        }
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function reencode(string $path, string $mime): array
    {
        $image = $mime === 'image/jpeg' ? @imagecreatefromjpeg($path) : @imagecreatefrompng($path);

        if ($image === false) {
            throw new InvalidProductImage('Não foi possível processar a imagem.');
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyJpegOrientation($image, $path);
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        ob_start();
        $saved = $mime === 'image/jpeg' ? imagejpeg($image, null, 88) : imagepng($image, null, 6);
        $bytes = ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        if ($saved !== true || ! is_string($bytes) || @getimagesizefromstring($bytes) === false) {
            throw new InvalidProductImage('Não foi possível processar a imagem.');
        }

        return [$bytes, $width, $height];
    }

    private function applyJpegOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated === false) {
            return $image;
        }

        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated;
    }
}
