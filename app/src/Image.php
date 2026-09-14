<?php
declare(strict_types=1);

namespace App;

/**
 * Image intake: validates, strips metadata (EXIF/GPS) by re-encoding with GD,
 * resizes to a max edge, and writes a web-friendly JPEG/PNG.
 * Privacy-focused: uploaded photos never keep location data.
 */
final class Image
{
    public const ERR_NONE = 0;

    /**
     * @return array{path:string,width:int,height:int}|array{error:string}
     */
    /**
     * @param string $subdir  folder under the media root, e.g. "posts" or "avatars"
     */
    public static function process(array $file, string $subdir, int $maxEdge, ?int $square = null): array
    {
        $subdir = trim($subdir, '/');
        $destDir = rtrim((string) App::config('media_path'), '/') . '/' . $subdir;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload failed.'];
        }
        if (!is_uploaded_file($file['tmp_name']) && !is_file($file['tmp_name'])) {
            return ['error' => 'Invalid upload.'];
        }
        $maxBytes = (int) App::config('max_upload_bytes', 8 * 1024 * 1024);
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['error' => 'Image is larger than ' . round($maxBytes / 1048576) . ' MB.'];
        }
        if (!function_exists('gd_info')) {
            return ['error' => 'Server is missing the GD image library.'];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['error' => 'That file is not a valid image.'];
        }
        [$w, $h] = $info;
        $type = $info[2];

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_GIF  => @imagecreatefromgif($file['tmp_name']),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : null,
            default        => null,
        };
        if (!$src) {
            return ['error' => 'Unsupported image format (use JPEG, PNG, GIF or WebP).'];
        }

        if ($square !== null) {
            $side = min($w, $h);
            $crop = imagecreatetruecolor($side, $side);
            imagecopy($crop, $src, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $side, $side);
            imagedestroy($src);
            $src = $crop;
            $w = $h = $side;
            $maxEdge = $square;
        }

        $scale = min(1, $maxEdge / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $hasAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true);
        $ext = $hasAlpha ? 'png' : 'jpg';
        $name = $subdir . '/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $full = rtrim((string) App::config('media_path'), '/') . '/' . $name;
        @mkdir(dirname($full), 0775, true);

        $ok = $hasAlpha ? imagepng($dst, $full, 6) : imagejpeg($dst, $full, 82);
        imagedestroy($dst);

        if (!$ok) {
            return ['error' => 'Could not save the processed image.'];
        }
        return ['path' => $name, 'width' => $nw, 'height' => $nh];
    }
}
