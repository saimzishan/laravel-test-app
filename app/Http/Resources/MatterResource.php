<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $timeline = $this->getTimeline();
        return [
            "id" => $this->id,
            "display_name" => $this->getName(),
            "status" => [
                "activities" => "",
                "time_entries" => "",
            ],
            "count" => [
                "activities" => $this->tasks->count(),
                "time_entries" => $this->timeEntries->count(),
                "invoices" => $this->invoices->count(),
            ],
            "timeline" => $timeline,
            "timeline_count" => count($timeline),
        ];
    }
}
