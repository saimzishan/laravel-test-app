<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageRoleResource extends JsonResource
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
            "type" => $this->type,
            "slug" => $this->slug,
            "foundation" => $this->foundation ? true : false,
            "foundationplus" => $this->foundationplus ? true : false,
            "foundationplusaddon" => $this->foundationplusaddon ? true : false,
            "enhanced" => $this->enhanced ? true : false,
        ];
    }
}
