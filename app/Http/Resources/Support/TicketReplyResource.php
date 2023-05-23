<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
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
            "user_id" => $this->user_id,
            "user_type" => $this->user_type,
            "user" => $this->getUser(),
            "created_date" => $this->getCreatedTime(),
            "comment" => $this->comment,
            "attachment" => [
                "name" => $this->getAttachment()->getName(),
                "link" => $this->getAttachment()->getURL(),
            ],
        ];
    }
}
