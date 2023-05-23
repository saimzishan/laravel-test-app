<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            "user" => $this->relationFirmUser->getName(),
            "subject" => $this->getShortSubject(),
            "priority" => $this->getPriority(),
            "status" => $this->getStatus(),
        ];
    }
}
