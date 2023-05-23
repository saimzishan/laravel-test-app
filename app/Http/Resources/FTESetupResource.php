<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FTESetupResource extends JsonResource
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
            "name" => $this->getName(),
            "type" => $this->type,
            "date_of_joining" => $this->date_of_joining,
            "date_of_joining_formatted" => $this->getDateOfJoining(),
            "hours_per_week" => $this->hours_per_week,
            "rate_per_hour" => $this->rate_per_hour,
            "cost_per_hour" => $this->cost_per_hour,
            "fte_hours_per_month" => 167,
            "fte_equivalence" => $this->fte_equivalence,
            "monthly_billable_target" => $this->monthly_billable_target,
            "can_be_calculated" => $this->can_be_calculated,
        ];
    }
}
