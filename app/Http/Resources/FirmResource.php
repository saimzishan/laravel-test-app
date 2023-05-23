<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FirmResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "logo_preview" => $this->logo,
            "address" => $this->address,
            "contact" => $this->contact,
            "desc" => $this->desc,
            "integration" => $this->integration,
            "package" => $this->package,
            "is_free" => $this->is_free,
        ];
    }
}
