<?php

namespace App\Actions;

use Illuminate\Support\Facades\Storage;

class AwsFileCheckerAction
{
    public function __invoke(string $path, $disk = "s3")
    {
        $is_exist = Storage::disk($disk)->exists($path);
        $s3_path = null;
        if ($is_exist) {
            $s3_path = $disk == "s3" ? Storage::disk($disk)->url($path) : null;
        }

        return [
            "path" => $is_exist ? $path : null,
            "s3_path" => $s3_path,
            "is_exist" => $is_exist,
        ];
    }
}
