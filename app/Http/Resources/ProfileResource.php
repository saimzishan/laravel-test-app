<?php

namespace App\Http\Resources;

use App\FirmIntegration;
use App\Setting;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use function Complex\theta;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $temp=null;
        $return = [
            "id" => $this->id,
            "display_name" => $this->display_name,
            "first_name" => $this->first_name,
            "middle_name" => $this->middle_name,
            "last_name" => $this->last_name,
            "email" => $this->email,
            "address" => $this->address,
            "contact" => $this->contact,
            "type" => $this->getType(),
        ];
        if (Auth::guard("firm_users")->check()) {
            $temp=FirmIntegration::select('last_sync')->where("firm_id",$this->firm->id)->get();
            $first_login=false;
            $location="";
            $first_login = $this->firstLogin($temp[0]->last_sync);
            unset($temp);
            $temp=Setting::select("value")->where("key","location")->get();
            $location=$temp[0]->value;
            $return["firm_id"] = $this->firm->id;
            $return["firm_type"] = $this->firm->integration;
            $return["firm_package"] = $this->firm->getCurrentPackage();
            $return["firm_package_name"] = $this->firm->getCurrentPackageName();
            $return["firm_plan"] = $this->firm->getCurrentPlan();
            $return["is_free"] = $this->firm->is_free;
            $return["is_trial"] = $this->firm->isTrial();
            $return["is_trial_expired"] = $this->firm->isTrialExpired();
            $return["trial_days_left"] = $this->firm->getTrialDaysLeft();
            $return["is_subscription_cancelled"] = $this->firm->isSubscriptionCancelled();
            $return["firm_role_id"] = $this->firm_role_id;
            $return["is_integrated"] = $this->firm->isIntegrated();
            $return["unlock_dashboard"] = $this->firm->unlockDashoard();
            $return["first_login"]=$first_login;
            $return["location"]=$location;
        }
        return $return;
    }
    public function firstLogin($var)
    {
        if(is_null($var))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}