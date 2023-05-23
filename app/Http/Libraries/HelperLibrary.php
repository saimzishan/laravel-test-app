<?php

namespace App\Http\Libraries;

use App\ActivityLog;
use App\Setting;
use App\SummaryAllTime;
use Illuminate\Support\Facades\Auth;
use mysql_xdevapi\Exception;

class HelperLibrary
{
    public static function getLoggedInUser() {
        if (Auth::guard("users")->check()) {
            return Auth::guard("users")->user();
        } elseif (Auth::guard("firm_users")->check()) {
            return Auth::guard("firm_users")->user();
        }
    }
    public static function isSuperAdmin() {
        if (Auth::guard("users")->check()) {
            if (Auth::guard("users")->user()->role_id == 1) {
                return true;
            } else {
                return false;
            }
        } elseif (Auth::guard("firm_users")->check()) {
            return false;
        }
    }
    public static function isAdmin() {
        if (Auth::guard("users")->check()) {
            return true;
        } elseif (Auth::guard("firm_users")->check()) {
            return false;
        } else {
            return false;
        }
    }
    public static function getUserRole() {
        if (Auth::guard("users")->check()) {
            return "super";
        } elseif (Auth::guard("firm_users")->check()) {
            return "firm_user";
        } else {
            return null;
        }
    }
    public static function hasFirm() {
        if (Auth::guard("users")->check()) {
            return false;
        } elseif (Auth::guard("firm_users")->check()) {
            return true;
        }
    }
    public static function getFirmID() {
        if (Auth::guard("users")->check()) {
            return false;
        } elseif (Auth::guard("firm_users")->check()) {
            return self::getLoggedInUser()->firm->id;
        }
    }
    public static function getFirm() {
        if (Auth::guard("users")->check()) {
            return false;
        } elseif (Auth::guard("firm_users")->check()) {
            return self::getLoggedInUser()->firm;
        }
    }
    public static function getFirmIntegration() {
        if (Auth::guard("users")->check()) {
            return false;
        } elseif (Auth::guard("firm_users")->check()) {
            return self::getLoggedInUser()->firm->integration;
        }
    }
    public static function getFirmPackage() {
        return self::getLoggedInUser()->firm->getCurrentPackage();
    }
    public static function getFirmPackageName($package=null) {
        if ($package==null) {
            $package = self::getFirmPackage();
        }
        $name = str_replace("_", " ", $package);
        return ucwords($name);
    }
    public static function getFirmAdminPermissions() {
        $data = [];
        $data[] = (object) [
            "type"=>"module",
            "slug"=>"financials",
        ];
        $data[] = (object) [
            "type"=>"module",
            "slug"=>"productivity",
        ];
        $data[] = (object) [
            "type"=>"module",
            "slug"=>"ar_aging",
        ];
        $data[] = (object) [
            "type"=>"module",
            "slug"=>"matter_tracker",
        ];
        $data[] = (object) [
            "type"=>"module",
            "slug"=>"quick_links",
        ];
        return collect($data);
    }
    public static function logActivity($text="") {
        if ($text != "") {
            ActivityLog::create([
                "firm_user_id" => self::getLoggedInUser()->id,
                "desc" => $text
            ]);
        } else {
            throw new Exception("Empty Description in Log Activity Function");
        }
    }
    public static function perPage($per_page = 10) {
        if (request()->filled("per_page")) {
            return request()->get("per_page");
        } else {
            return $per_page;
        }
    }
    public static function getSettings($key=null) {
        $ret = [];
        if ($key != null && !is_array($key)) {
            $settings = Setting::where("key", $key)->select("key", "value")->get();
        } else if (is_array($key)) {
            $settings = Setting::whereIn("key", $key)->select("key", "value")->get();
        } else {
            $settings = Setting::select("key", "value")->get();
        }
        foreach ($settings as $k=>$v) {
            $ret[$v->key] = $v->value;
        }
        return (object) $ret;
    }
    public static function getMonthsFromRange($year, $date=false) {
        $data = [];
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($date) {
                $data[] = $i->format("Y-m-01");
            } else {
                $data[] = $i->format("Y-m");
            }
        }
        return $data;
    }
    public static function getMonthsFromRangeModel($model, $column, $year, $date=false) {
        return $model->where(function($q) use ($column, $year, $date) {
            $begin = new \DateTime(substr($year->from, 0, 10));
            $end = new \DateTime(substr($year->to, 0, 10));
            $iterator = 0;
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($iterator == 0) {
                    if ($date) {
                        $q->where($column, $i->format("Y-m-01"));
                    } else {
                        $q->where($column, $i->format("Y-m"));
                    }
                } else {
                    if ($date) {
                        $q->orWhere($column, $i->format("Y-m-01"));
                    } else {
                        $q->orWhere($column, $i->format("Y-m"));
                    }
                }
                $iterator++;
            }
        });
    }

    public static function getTotalAROutstanding($firm_id) {
        $var = SummaryAllTime::where("firm_id", $firm_id)
            ->selectRaw("(ar_current + ar_late + ar_delinquent + ar_collection) as outstanding")->first();
        return  $var != null ? $var->outstanding : 0;
    }
}