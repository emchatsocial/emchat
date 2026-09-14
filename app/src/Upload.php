<?php
declare(strict_types=1);

namespace App;

/**
 * Intake for message attachments: images (re-encoded, metadata stripped),
 * videos, audio, PDFs and a small allowlist of document types.
 * No external dependencies.
 */
final class Upload
{
    private const IMAGE = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    private const VIDEO = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
    private const AUDIO = ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/webm' => 'weba'];
    private const DOC = [
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/zip' => 'zip',
        'text/csv' => 'csv',
    ];

    /**
     * @return array{kind:string,path:string,name:string,mime:string,size:int,width:int,height:int}|array{error:string}
     */
    public static function messageFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_INI_SIZE) {
            return ['error' => 'That file is larger than the server allows.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_file($file['tmp_name'])) {
            return ['error' => 'Upload failed. Try again.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        $origName = self::safeName((string) ($file['name'] ?? 'file'));
        $size = (int) ($file['size'] ?? filesize($file['tmp_name']));

        // Images -> route through the EXIF-stripping processor.
        if (isset(self::IMAGE[$mime])) {
            $res = Image::process($file, 'messages', (int) App::config('image_max_edge', 1600));
            if (isset($res['error'])) {
                return $res;
            }
            return [
                'kind' => 'image', 'path' => $res['path'], 'name' => $origName,
                'mime' => 'image/' . pathinfo($res['path'], PATHINFO_EXTENSION),
                'size' => $size, 'width' => $res['width'], 'height' => $res['height'],
            ];
        }

        [$kind, $allow, $limitKey] = match (true) {
            isset(self::VIDEO[$mime]) => ['video', self::VIDEO, 'max_video_bytes'],
            isset(self::AUDIO[$mime]) => ['audio', self::AUDIO, 'max_file_bytes'],
            isset(self::DOC[$mime])   => ['file',  self::DOC,   'max_file_bytes'],
            default                   => [null, [], null],
        };
        if ($kind === null) {
            return ['error' => 'That file type isn\'t supported.'];
        }
        $limit = (int) App::config($limitKey, 20 * 1024 * 1024);
        if ($size > $limit) {
            return ['error' => ucfirst($kind) . ' is larger than ' . round($limit / 1048576) . ' MB.'];
        }

        $ext = $allow[$mime];
        $rel = 'messages/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $full = rtrim((string) App::config('media_path'), '/') . '/' . $rel;
        @mkdir(dirname($full), 0775, true);
        if (!move_uploaded_file($file['tmp_name'], $full) && !rename($file['tmp_name'], $full)) {
            return ['error' => 'Could not store the file.'];
        }

        $w = $h = 0;
        if ($kind === 'video' && function_exists('getimagesize')) {
            // best-effort dimensions unavailable without ffprobe; leave 0.
        }

        return compact('kind') + [
            'path' => $rel, 'name' => $origName, 'mime' => $mime, 'size' => $size, 'width' => $w, 'height' => $h,
        ];
    }

    private static function safeName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1f\/\\\\]+/', '', $name) ?? 'file';
        $name = trim($name) ?: 'file';
        return mb_substr($name, 0, 120);
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
