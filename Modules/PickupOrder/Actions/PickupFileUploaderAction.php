<?php

namespace Modules\PickupOrder\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PickupFileUploaderAction
{
    public function __invoke(UploadedFile $file, string $file_id, string $path, $disk = "s3")
    {
        $path = $file->storePubliclyAs(
            $path,
            $file_id . "_" . uniqid() . "." . $file->getClientOriginalExtension(),
            's3'
        );
        return $path;
    }
}
