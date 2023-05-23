<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowupsResource extends JsonResource
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
            "type" => $this->getType(),
            "date" => $this->getDate(),
            "comment" => $this->comment,
            "reply_date" => $this->getReplyDate(),
            "reply_response" => $this->reply_response,
        ];
    }
}
