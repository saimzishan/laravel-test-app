<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FirmUsersResource extends JsonResource
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
            "display_name" => $this->display_name,
            "email" => $this->email,
            "firm_name" => $this->getFirmName(),
            "role_name" => $this->getRole(),
            "status" => $this->getStatus(),
        ];
    }
}
