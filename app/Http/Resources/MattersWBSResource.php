<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MattersWBSResource extends JsonResource
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
            "matter_type" => $this->matter_type,
            "lead_days" => $this->lead_days,
            "time_entries" => $this->time_entries,
            "total_cost" => $this->total_cost,
        ];
    }
}
