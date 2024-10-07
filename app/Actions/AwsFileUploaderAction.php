<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AwsFileUploaderAction
{
    public function __invoke(UploadedFile $file, string $file_id, string $path, $disk = "s3")
    {
        $file_name = $file_id . "_" . uniqid() . "." . $file->getClientOriginalExtension();
        $path = Storage::disk($disk)->putFileAs($path, $file, $file_name);
        $s3_path = $disk == "s3" ? Storage::disk("s3")->url($path) :  Storage::disk($disk)->url($path);

        return [
            "file_name" => $file_name,
            "path" => $path,
            "s3_path" => $s3_path,
            "is_exist" => Storage::disk($disk)->exists($path)
        ];
    }
}
