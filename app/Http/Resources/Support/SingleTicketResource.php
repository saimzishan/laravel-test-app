<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Resources\Json\JsonResource;

class SingleTicketResource extends JsonResource
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
            "subject" => $this->subject,
            "priority" => $this->priority,
            "status" => [
                "number" => $this->status,
                "label" => $this->getStatus(),
            ],
            "description1" => $this->getPrimaryReply()->comment,
            "attachment" => [
                "state" => "existing",
                "id" => $this->getPrimaryAttachment()->id,
                "name" => $this->getPrimaryAttachment()->getName(),
                "link" => $this->getPrimaryAttachment()->getURL(),
            ],
            "new_attachment" => "",
        ];
    }
}
