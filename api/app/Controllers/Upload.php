<?php

namespace App\Controllers;

use App\Libraries\ImageResize;
use App\Libraries\AwsS3;

/**
 * Image upload API.
 * POST api/upload/image
 * - Multipart: file field "image"
 * - JSON/form: "image" or "photo" as base64 string (optional data:image/...;base64, prefix)
 * - Optional "type": "large" or "thumb" — which URL to return (default: large). Both sizes are always saved.
 * Resizes using config imageSizes; saves to separate folders (large/ and thumb/). Returns full URL for requested type.
 */
class Upload extends BaseController
{
    /** @var string[] Allowed image MIME types */
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @var string[] Allowed extensions */
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function image()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        if (! $this->AuthenticateToken()) {
            $this->setError($this->invalidToken, 401);
            return $this->response();
        }
        if (! $this->CheckUserTypePermissions('inspection:create')) {
            return $this->response();
        }

        $sourcePath = null;
        $imageData  = null;
        $extension  = 'jpg';
        $mimeType   = 'image/jpeg';

        // Prefer uploaded file
        $file = $this->request->getFile('image');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $mimeType   = $file->getClientMimeType();
            $extension  = $this->mimeToExt($mimeType);
            $sourcePath = $file->getTempName();
            if (! $sourcePath || ! is_file($sourcePath)) {
                $this->setError('Invalid uploaded file.', 400);
                return $this->response();
            }
            if (! in_array($mimeType, self::ALLOWED_MIMES, true)) {
                $this->setError('Allowed image types: JPEG, PNG, GIF, WebP.', 400);
                return $this->response();
            }
        } else {
            // Try base64 from body (image or photo)
            $base64 = $this->getPost('image', '') ?: $this->getPost('photo', '');
            if (is_string($base64) && $base64 !== '') {
                if (preg_match('#^data:image/(\w+);base64,(.+)$#i', $base64, $m)) {
                    $extension = strtolower($m[1]);
                    if ($extension === 'jpeg') {
                        $extension = 'jpg';
                    }
                    $base64 = $m[2];
                }
                $decoded = base64_decode($base64, true);
                $imageData = ($decoded !== false && strlen($decoded) > 0) ? $decoded : null;
                if ($imageData === null) {
                    $this->setError('Invalid base64 image data.', 400);
                    return $this->response();
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData);
                if (! in_array($mimeType, self::ALLOWED_MIMES, true)) {
                    $this->setError('Allowed image types: JPEG, PNG, GIF, WebP.', 400);
                    return $this->response();
                }
                $extension = $this->mimeToExt($mimeType);
            }
        }

        if ($sourcePath === null && $imageData === null) {
            $this->setError('Image required: send multipart file "image" or JSON/form "image"/"photo" as base64.', 400);
            return $this->response();
        }

        $returnType = strtolower(trim((string) $this->getPost('type', 'large')));
        if ($returnType !== 'thumb' && $returnType !== 'large') {
            $returnType = 'large';
        }

        $imageSizes = $this->AppConfig->imageSizes ?? [
            'large' => [1024, 768],
            'thumb' => [240, 240],
        ];
        $largeSize = $imageSizes['large'] ?? [1024, 768];
        $thumbSize = $imageSizes['thumb'] ?? [240, 240];

        $largePath = null;
        $tempFiles = [];

        try {
            if ($sourcePath !== null) {
                $resize = new ImageResize($sourcePath);
            } else {
                $resize = ImageResize::createFromString($imageData);
            }

            $uniqueId   = str_replace('.', '_', uniqid('img_', true));
            $datePath   = date('Y/m/d');
            $fileName   = $uniqueId . '.' . $extension;
            $largeName  = 'large/' . $datePath . '/' . $fileName;
            $thumbName  = 'thumb/' . $datePath . '/' . $fileName;

            $s3Enabled = ! empty($this->AppConfig->S3['enabled']);

            if ($s3Enabled) {
                $tempDir = sys_get_temp_dir() . '/upload_' . str_replace('.', '_', uniqid('', true));
                if (! is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                $tempLarge = $tempDir . '/large_' . $fileName;
                $tempThumb = $tempDir . '/thumb_' . $fileName;
                $tempFiles = [$tempLarge, $tempThumb, $tempDir];
            } else {
                $baseDirLarge = FCPATH . 'assets/upload/large/' . $datePath;
                $baseDirThumb = FCPATH . 'assets/upload/thumb/' . $datePath;
                if (! is_dir($baseDirLarge)) {
                    mkdir($baseDirLarge, 0755, true);
                }
                if (! is_dir($baseDirThumb)) {
                    mkdir($baseDirThumb, 0755, true);
                }
                $tempLarge = $baseDirLarge . '/' . $fileName;
                $tempThumb = $baseDirThumb . '/' . $fileName;
            }

            // Large: resize to best fit within dimensions
            $resize->resizeToBestFit((int) $largeSize[0], (int) $largeSize[1], false);
            $resize->save($tempLarge);

            // Thumb: reload source and crop to thumb size
            if ($sourcePath !== null) {
                $resizeThumb = new ImageResize($sourcePath);
            } else {
                $resizeThumb = ImageResize::createFromString($imageData);
            }
            $resizeThumb->crop((int) $thumbSize[0], (int) $thumbSize[1], false);
            $resizeThumb->save($tempThumb);

            if ($s3Enabled) {
                $s3 = new AwsS3();
                $s3KeyLarge = 'uploads/' . $largeName;
                $s3KeyThumb = 'uploads/' . $thumbName;
                $contentType = $this->extToMime($extension);
                $okLarge = $s3->upload([
                    'file' => $tempLarge,
                    'key'  => $s3KeyLarge,
                    'type' => $contentType,
                ]);
                $okThumb = $s3->upload([
                    'file' => $tempThumb,
                    'key'  => $s3KeyThumb,
                    'type' => $contentType,
                ]);
                foreach ($tempFiles as $f) {
                    if (is_file($f)) {
                        @unlink($f);
                    } elseif (is_dir($f)) {
                        @rmdir($f);
                    }
                }
                if (! $okLarge || ! $okThumb) {
                    $this->setError('S3 upload failed: ' . ($s3->error ?: 'Unknown error'), 500);
                    return $this->response();
                }
                $largeUrl = $s3->url($s3KeyLarge);
                $thumbUrl = $s3->url($s3KeyThumb);
            } else {
                $baseUrl = rtrim(config('App')->baseURL ?? $this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getHost() . '/', '/');
                $largeUrl = $baseUrl . '/' . 'assets/upload/' . $largeName;
                $thumbUrl = $baseUrl . '/' . 'assets/upload/' . $thumbName;
            }

            $returnPath = $returnType === 'thumb' ? $thumbUrl : $largeUrl;
            $this->setSuccess('Image uploaded successfully.');
            $this->setOutput(['path' => $returnPath]);
            return $this->response();
        } catch (\Throwable $e) {
            foreach ($tempFiles as $f) {
                if (is_file($f)) {
                    @unlink($f);
                } elseif (is_dir($f)) {
                    @rmdir($f);
                }
            }
            $code = $e instanceof \App\Libraries\ImageResizeException ? 400 : 500;
            $this->setError('Upload failed: ' . $e->getMessage(), $code);
            return $this->response();
        }
    }

    private function mimeToExt(string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        return $map[$mime] ?? 'jpg';
    }

    private function extToMime(string $ext): string
    {
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        return $map[$ext] ?? 'image/jpeg';
    }
}
