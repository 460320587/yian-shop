<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadController extends BaseController
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE_KB = 5120; // 5MB
    private const MAX_FILES = 9;

    public function reviewImages(Request $request): JsonResponse
    {
        if (! $request->hasFile('images')) {
            return $this->error(ErrorCode::VALIDATION_ERROR, '请上传图片');
        }

        /** @var UploadedFile[] $files */
        $files = $request->file('images');
        if (! is_array($files)) {
            $files = [$files];
        }

        if (count($files) > self::MAX_FILES) {
            throw ValidationException::withMessages([
                'images' => ['一次最多上传 ' . self::MAX_FILES . ' 张图片'],
            ]);
        }

        $urls = [];
        $disk = Storage::disk('public');
        $pathPrefix = 'reviews/' . now()->format('Y/m');

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    'images' => ['存在无效的上传文件'],
                ]);
            }

            if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
                throw ValidationException::withMessages([
                    'images' => ['仅支持 jpeg、png、gif、webp 格式'],
                ]);
            }

            if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
                throw ValidationException::withMessages([
                    'images' => ['单张图片大小不能超过 5MB'],
                ]);
            }

            $filename = uniqid('review_', true) . '.' . $file->extension();
            $path = $disk->putFileAs($pathPrefix, $file, $filename);

            if ($path === false) {
                throw ValidationException::withMessages([
                    'images' => ['图片保存失败'],
                ]);
            }

            $urls[] = $disk->url($path);
        }

        return $this->success(['urls' => $urls], '上传成功', 201);
    }
}
