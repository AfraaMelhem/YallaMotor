<?php

namespace App\Http\Resources;

use App\Traits\ImageUrlFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    use ImageUrlFormatter;

    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $data = [];

        $hidden = ['password', 'remember_token'];

        if (!is_null($this->id)) {
            $data['id'] = $this->id;
        }

        if (method_exists($this->resource, 'getFillable')) {
            foreach ($this->resource->getFillable() as $attribute) {
                if (!in_array($attribute, $hidden) && !is_null($this->{$attribute})) {

                    if (str_contains($attribute, 'image') || $attribute == 'logo') {
                        $data[$attribute] =url($this->{$attribute});
                    } else {
                        $data[$attribute] = $this->{$attribute};
                    }
                }
            }
        }

        if (property_exists($this->resource, 'translatable')) {
            foreach ($this->resource->translatable as $field) {
                if (!is_null($this->{$field})) {
                    $data[$field] = $this->getTranslation($field, app()->getLocale());
                }
            }
        }
        if ($this->relationLoaded('watchable') && $this->watchable) {
            $data['watchable'] = new BaseResource($this->watchable);
        }

        if ($this->relationLoaded('media')) {
            $data['media'] = $this->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'filename' => $media->filename,
                    'mime_type' => $media->mime_type,
                    'file_path' => $media->file_path,
                    'url' => $this->formatImageUrl($media->file_path),
                    'description' => $media->description,
                    'order' => $media->order,
                    'created_at' => $media->created_at,
                    'updated_at' => $media->updated_at,
                ];
            })->toArray();
        }

        return $data;
    }
}
