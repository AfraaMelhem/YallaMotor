<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ImageUrlFormatter
{
    public function formatImageUrl($filePath)
    {
        if (!$filePath) {
            return null;
        }

        return asset('storage/' . $filePath);

    }
}

