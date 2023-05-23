<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Carbon\Carbon;

class Firm extends Model
{

    use Billable;

    public function integrationRelation() {
        return $this->hasOne("App\FirmIntegration");
    }
    public function users() {
        return $this->hasMany("App\FirmUser");
    }
    public function ppUsers() {
        return $this->hasMany("App\PPUser");
    }
    public function clUsers() {
        return $this->hasMany("App\CLUser");
    }
    public function getStatus() {
        return $this->is_active == 1 ? 'Active' : 'In-Active';
    }
    public function getIntegration() {
        if ($this->integration == "practice_panther") {
            return 'Practice Panther';
        } elseif ($this->integration == "clio") {
            return 'Clio';
        } else {
            return "-";
        }
    }
    public function isIntegrated() {
        if (!empty($this->integrationRelation->code) && !empty($this->integrationRelation->refresh_token) && !empty($this->integrationRelation->access_token) && $this->status !="Disconnected") {
            return true;
        } else {
            return false;
        }
    }
    public function isSynced() {
        if ($this->integrationRelation->status == 'Synced') {
            return true;
        } else {
            return false;
        }
    }
    public function unlockDashoard() {

        if($this->is_free == 1)
        {
            return true;
        }
        else
        {
            if($this->isIntegrated() and $this->isSubscribed())
            {
                return true;
            }else
            {
                return false;
            }
        }
    }
    public function createIntegration() {
        $row = new FirmIntegration();
        $row->firm_id = $this->id;
        $row->status = "Disconnected";
        return $row->save();
    }
    public function makeDefaultDefinitions() {
        $entries = [
            [
                "category" => "general",
                "type" => "ar",
                "label" => "current_from",
                "value" => "0",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "current_to",
                "value" => "30",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "late_from",
                "value" => "31",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "late_to",
                "value" => "60",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "delinquent_from",
                "value" => "61",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "delinquent_to",
                "value" => "90",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "collection_from",
                "value" => "91",
            ],
            [
                "category" => "general",
                "type" => "ar",
                "label" => "collection_to",
                "value" => "99999999999",
            ],
            [
                "category" => "general",
                "type" => "financial_year",
                "label" => "starting_month",
                "value" => "1",
            ],
        ];
        foreach ($entries as $v) {
            $v = (object) $v;
            $row = new Definition();
            $row->firm_id = $this->id;
            $row->category = $v->category;
            $row->type = $v->type;
            $row->label = $v->label;
            $row->value = $v->value;
            $row->save();
        }
    }
    public function getCurrentPackage()
    {
        $package = $this->package != "" ? $this->package : "trial";
        if ($this->is_free == 0) {
            if ($this->subscribed("main", $package)) {
                return $this->getStripeSubscriptionKey();
            } else {
                return $package;
            }
        } else {
            return $package;
        }
    }
    public function getCurrentPlan()
    {
        $plan = $this->getPaymentUsers();
        if($plan != null) {
            if($plan >=1 and $plan < 4) {
                return "1-3 Employees";
            } else if ($plan > 3 and $plan < 10) {
                return "4-9 Employees";
            } else if ($plan > 9 and $plan < 20) {
                return "10-19 Employees";
            } else if($plan > 19){
                return "Enterprise";
            } else {
                return null;
            }
        } else {
            return null;
        }


    }
    public function getCurrentPackageName()
    {
        $package = $this->package != "" ? $this->package : "Trial";
        if ($this->is_free == 0) {
            if ($this->subscribed("main", $package)) {
                return $this->getStripeSubscriptionName();
            } else {
                return ucwords(str_replace("_", " ", $package));
            }
        } else {
            return ucwords(str_replace("_", " ", $package));
        }
    }
    public function getIntegrationUsers()
    {
        if ($this->integration == "practice_panther") {
            return $this->ppUsers();
        } else {
            return $this->clUsers();
        }
    }
    public function getPaymentUsers()
    {
        if ($this->integration == "practice_panther") {
            return $this->ppUsers()->count();
        } else {
            return $this->clUsers()->where("enabled",1)->count();
        }
    }
    public function isStripeConnected()
    {
        if (!empty($this->stripe_id)) {
            return true;
        } else {
            return false;
        }
    }
    public function getStripeSubscriptionName()
    {
        if ($this->subscribedToPlan('foundation', 'main')) {
            return "Foundation";
        } elseif ($this->subscribedToPlan('foundation_plus', 'main')) {
            return "Foundation Plus";
        } elseif ($this->subscribedToPlan('enhanced', 'main')) {
            return "Enhanced";
        } else {
            return "-";
        }
    }
    public function getStripeSubscriptionKey()
    {
        if ($this->subscribedToPlan('foundation', 'main')) {
            return "foundation";
        } elseif ($this->subscribedToPlan('foundation_plus', 'main')) {
            return "foundation_plus";
        } elseif ($this->subscribedToPlan('enhanced', 'main')) {
            return "enhanced";
        } else {
            return "-";
        }
    }
    public function getStripeSubscriptionExpiry()
    {
        if ($this->subscribed('main') && !$this->is_free) {
            if ($this->subscriptions()->where("ends_at", "")->count() == 1) {
                $ends = $this->subscription("main")->asStripeSubscription()->current_period_end;
                return Carbon::createFromTimeStamp($ends)->toFormattedDateString();
            } else {
                return "-";
            }
        } else {
            return "-";
        }
    }
    public function getPaymentFromPackage()
    {
        if ($this->package != "") {
            if ($this->is_free == 0) {
                $plan = HelperLibrary::getSettings([$this->package]);
                return (int) $plan->{$this->package};
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    public function getTotalPaymentFromPackage()
    {
        return $this->getPaymentFromPackage() * $this->getIntegrationUsers()->count();
    }
    public function isTrial()
    {
        if ($this->trial_ends_at != "") {
            if ($this->is_free) {
                return false;
            } elseif (optional($this->subscription("main"))->cancelled()) {
                return false;
            } elseif ($this->subscriptions()->where("ends_at", "")->count() == 1) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    public function isTrialExpired()
    {
        if ($this->trial_ends_at != "") {
            if ($this->is_free) {
                return false;
            } else {
                $check = Carbon::parse($this->trial_ends_at)->diffInSeconds(Carbon::now(), false);
                return $check > 0 ? true : false;
            }
        } else {
            return false;
        }
    }
    public function getTrialDaysLeft()
    {
        if ($this->trial_ends_at != "") {
            $check = Carbon::now()->diffInDays(Carbon::parse($this->trial_ends_at), false);
            return $check > 0 ? $check : 0;
        } else {
            return 0;
        }
    }
    public function isSubscriptionCancelled()
    {
        return $this->is_free ? false : optional($this->subscription("main"))->cancelled();
    }
    public function getFirmAdmins() {
        return $this->users->where("firm_role_id", "0");
    }
    public static function doDeleteSummary($firm_id) {
        SummaryAllTime::where("firm_id",$firm_id)->delete();
        SummaryAOP::where("firm_id",$firm_id)->delete();
        SummaryAR::where("firm_id",$firm_id)->delete();
        SummaryClient::where("firm_id",$firm_id)->delete();
        SummaryMatter::where("firm_id",$firm_id)->delete();
        SummaryMatterTracker::where("firm_id",$firm_id)->delete();
        SummaryMonth::where("firm_id",$firm_id)->delete();
        SummaryUser::where("firm_id",$firm_id)->delete();
        SummaryWrittenOffByClient::where("firm_id",$firm_id)->delete();
        SummaryWrittenOffByEmployee::where("firm_id",$firm_id)->delete();
        return true;
    }
    public function getSubscribeUserName() {
        return $this->firm_id != null ? $this->firm->subscribe_user_name : '-';
    }
    public function isSubscribed() {
        $check = false;
        $checkPromotion = Promotion::get();
        if($this->subscribedToPlan('ftt_monthly_all', 'main'))
        {
            $check = true;
        } elseif ($this->subscribedToPlan('ftt_monthly', 'main')) {
            $check = true;
        }elseif ($checkPromotion !=null) {
            foreach ($checkPromotion as $c) {
                if($this->subscribedToPlan($c->stripe_plan_id, 'main')) {
                    $check = true;
                }
            }


        }
        return $check;
    }
    public function getSubscriptionType() {
        $type = "-";
        if($this->is_free) {
            $type =  "Free";
        } else if($this->isSubscribed()) {
            $checkPromotion = Promotion::notDeleted()->first();
            if ($checkPromotion !=null) {
                if($this->subscribedToPlan($checkPromotion->stripe_plan_id, 'main')) {
                    $type = "Promotion";
                } else {
                    $type = "Monthly";
                }
            }
        } else {
            $checkPromotion = Promotion::notDeleted()
                ->where("start_date", "<=", (new Carbon($this->created_at))->format("Y-m-d"))
                ->where("end_date", ">=", (new Carbon($this->created_at))->format("Y-m-d"))
                ->first();
            if($checkPromotion != null) {
                $type = "Promotion";
            } else {
                $type = "Monthly";
            }
        }
        return $type;
    }
}
