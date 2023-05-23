<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            "name" => $this->name,
            "stripe_plan_id" => $this->stripe_plan_id,
            "validity" => $this->validity,
            "start_date" => $this->start_date,
            "start_date_formatted" => (new Carbon($this->start_date))->format("m/d/Y"),
            "end_date" => $this->end_date,
            "end_date_formatted" => (new Carbon($this->end_date))->format("m/d/Y"),
        ];
    }
}
