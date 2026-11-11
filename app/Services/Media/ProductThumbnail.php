<?php

namespace App\Services\Media;

use GdImage;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gambar sampul produk.
 *
 * Apa pun rasio dan ukuran file aslinya, gambar disimpan dengan rasio 16:10
 * (dipotong dari tengah) dan lebar maksimal 1600 px, supaya pas di kartu katalog
 * dan tetap ringan dimuat.
 */
class ProductThumbnail
{
    public const RATIO = 16 / 10;

    public const MAX_WIDTH = 1600;

    private const DIRECTORY = 'products/thumbnails';

    /**
     * Proses lalu simpan gambar ke disk public. Mengembalikan path-nya.
     * Menerima file unggahan (form admin) maupun file biasa di disk (gambar contoh milik seeder).
     */
    public function store(UploadedFile|File $file): string
    {
        // Server tanpa ekstensi GD: simpan apa adanya, tampilan katalog tetap memotongnya lewat CSS
        if (! function_exists('imagecreatefromstring')) {
            return Storage::disk('public')->putFile(self::DIRECTORY, $file);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages([
                'thumbnail' => 'Gambar sampul tidak bisa dibaca. Gunakan file JPG, PNG, atau WebP.',
            ]);
        }

        $source = $this->upright($source, $file);
        $width = imagesx($source);
        $height = imagesy($source);

        // Bagian tengah gambar dengan rasio 16:10
        if ($width / $height > self::RATIO) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * self::RATIO);
        } else {
            $cropWidth = $width;
            $cropHeight = max(1, (int) round($width / self::RATIO));
        }

        // Gambar kecil tidak diperbesar
        $targetWidth = min($cropWidth, self::MAX_WIDTH);
        $targetHeight = max(1, (int) round($targetWidth / self::RATIO));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled(
            $canvas, $source,
            0, 0, intdiv($width - $cropWidth, 2), intdiv($height - $cropHeight, 2),
            $targetWidth, $targetHeight, $cropWidth, $cropHeight,
        );

        $webp = function_exists('imagewebp');

        ob_start();
        $webp ? imagewebp($canvas, null, 82) : imagejpeg($canvas, null, 85);
        $bytes = (string) ob_get_clean();

        $path = self::DIRECTORY.'/'.Str::random(40).($webp ? '.webp' : '.jpg');
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /**
     * Foto dari HP sering disimpan miring dan hanya diberi tanda arah (EXIF). Tegakkan dulu.
     */
    private function upright(GdImage $image, UploadedFile|File $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $angle = match ((int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
