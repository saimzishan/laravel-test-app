<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MattersResource extends JsonResource
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
            "id" => $this->matter_id,
            "matter_name" => $this->matter_name,
            "activities" => $this->activities,
            "time_entries" => $this->time_entries,
            "invoices" => $this->invoices,
            "days_file_open" => $this->days_file_open,
            "created_date" => $this->created_date,
            "created_date_raw" => $this->created_date_raw,
        ];
    }
}
