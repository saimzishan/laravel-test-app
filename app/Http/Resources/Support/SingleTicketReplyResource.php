<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleTicketReplyResource extends JsonResource
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
            "support_ticket_id" => $this->support_ticket_id,
            "comment" => $this->comment,
            "attachment" => [
                "state" => "existing",
                "id" => $this->getAttachment()->id,
                "name" => $this->getAttachment()->getName(),
                "link" => $this->getAttachment()->getURL(),
            ],
            "new_attachment" => ""
        ];
    }
}
