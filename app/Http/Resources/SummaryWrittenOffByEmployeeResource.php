<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SummaryWrittenOffByEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return
        [
            "id" => $this->id,
            "employee_name" => $this->employee_name,
            "total_hours" => $this->total_hours + $this->manually_written_off_hours,
            "pre_bill_hours" => $this->written_off_hours + $this->manually_written_off_hours,
            "billed_hours" => $this->billed_hours,
            "total_hours_amount" => $this->total_hours_amount,
            "pre_bill_amount" => $this->written_off_amount,
            "billed_amount" => $this->billed_amount,
            "post_billed_amount" => $this->post_billed_credits_amount,
            "differences" => $this->differences,

        ];
    }
}
