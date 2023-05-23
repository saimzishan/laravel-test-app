<?php

namespace App\Http\Resources;

use App\FirmIntegration;
use App\Http\Libraries\HelperLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class FirmIntegrationResource extends JsonResource
{
    private $app = null;

    public function __construct($resource, $app)
    {
        parent::__construct($resource);
        $this->app = (object) $app;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $temp = FirmIntegration::select("last_sync","status_message")->where("firm_id",HelperLibrary::getFirmID())->get();
        $date = substr($temp[0]->last_sync,0,10);
        $time = substr($temp[0]->last_sync,11);
        return [
            "client_id" => $this->app->client_id,
            "connected" => $this->isConnected(),
            "status" => $this->status,
            "percentage" => $this->percentage,
            "last_sync"=>$date." ".date('h:i:s a', strtotime($time)),
            "status_message"=>$temp[0]->status_message,

        ];
    }
}
