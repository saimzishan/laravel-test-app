<?php

namespace App\Http\Resources;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class ARResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $total = HelperLibrary::getTotalAROutstanding($this->firm_id);
        $percentage_to_sale = $this->getInvoicesTotal() == 0 ? 0 :$this->getInvoicesTotalOutstanding() / $this->getInvoicesTotal();
        $percentage_to_outstanding = $total == 0 ? 0 :$this->getInvoicesTotalOutstanding() / $total;
        return [
            "contact_id" => $this->id,
            "contact_name" => $this->getDisplayName(),
            "total" => $this->getInvoicesTotal(),
            "outstanding" => $this->getInvoicesTotalOutstanding(),
            "percentage_to_sale" => round(($percentage_to_sale) * 100, 0),
            "percentage_to_outstanding" => $total == 0 ? 0 : round(($percentage_to_outstanding) * 100, 0),
        ];
    }
}
