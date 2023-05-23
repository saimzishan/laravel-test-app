<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowupResource extends JsonResource
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
            "date" => $this->date,
            "comment" => $this->comment,
            "reply_date" => $this->reply_date,
            "reply_response" => $this->reply_response,
        ];
    }
}
