<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleTicketViewResource extends JsonResource
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
            "user_id" => $this->user_id,
            "user_type" => $this->user_type,
            "subject" => $this->subject,
            "user" => $this->getUser(),
            "created_time" => $this->getCreatedTime(),
            "priority" => $this->getPriority(),
            "status" => $this->getStatus(),
            "description" => $this->getPrimaryReply()->comment,
            "attachment" => [
                "name" => $this->getPrimaryAttachment()->getName(),
                "link" => $this->getPrimaryAttachment()->getURL(),
            ],
            "replies" => TicketReplyResource::collection($this->getReplies()),
        ];
    }
}
