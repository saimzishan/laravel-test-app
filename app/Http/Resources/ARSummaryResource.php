<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ARSummaryResource extends JsonResource
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
            "contact_id" => $this->id,
            "contact_name" => $this->contact_name,
            "total" => $this->total,
            "outstanding" => $this->outstanding,
            "percentage_to_sale" => $this->percentage_to_sale,
            "percentage_to_outstanding" => $this->percentage_to_outstanding,
        ];
    }
}
