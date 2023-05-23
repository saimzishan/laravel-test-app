<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;

class Definition extends Model
{
    public static function getInvoicesDefinitions($firm_id=null) {
        if ($firm_id==null) {
            $firm_id = HelperLibrary::getFirmID();
        }
        $arr = self::where("firm_id", $firm_id)
            ->where("category", "general")
            ->where("type", "ar")->get();
        $ret = [];
        foreach ($arr as $a) {
            $ret[$a->label] = $a->value;
        }
        return (object) $ret;
    }
    public static function get_MOM_month($month)
    {

        return date('Y-m', strtotime($month));

    }
    public static function getFinancialMonth($y = "this") {
        if ($y=="this") {
            $from = date("Y-m-01 00:00:00");
            $to = date("Y-m-t 23:59:59");
        } elseif ($y=="last") {
            $from = date("Y-m-01 00:00:00", strtotime("-1 month"));
            $to = date("Y-m-t 23:59:59", strtotime("-1 month"));
        }
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getFinancialYear($y = "this", $firm_id=null) {
        if ($firm_id==null) {
            $firm_id = HelperLibrary::getFirmID();
        }
        $start = self::where("firm_id", $firm_id)
            ->where("category", "general")
            ->where("type", "financial_year")
            ->where("label", "starting_month")->first()->value;
        if (date("m") >= $start) {
            // this financial year
            $start = $start >= 10 ? $start : "0" . $start;
            if ($y == "this") {
                $from = date("Y-{$start}-01 00:00:00");
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            } elseif ($y == "last") {
                $from = date("Y-{$start}-01 00:00:00", strtotime("-1 year"));
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            } elseif ($y == "before-last") {
                $from = date("Y-{$start}-01 00:00:00", strtotime("-2 year"));
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            }
        } else {
            // previous financial year
            $start = $start >= 10 ? $start : "0" . $start;
            if ($y == "this") {
                $from = date("Y-{$start}-01 00:00:00", strtotime("-1 year"));
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            } elseif ($y == "last") {
                $from = date("Y-{$start}-01 00:00:00", strtotime("-2 year"));
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            } elseif ($y == "before-last") {
                $from = date("Y-{$start}-01 00:00:00", strtotime("-3 year"));
                $to = date("Y-m-t 23:59:59", strtotime($from . " + 1 year - 1 month"));
            }
        }
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getFinancialYearLabels($y = "this", $firm_id=null) {
        if ($firm_id==null) {
            $firm_id = HelperLibrary::getFirmID();
        }
        $labels = [];
        $fy = self::getFinancialYear($y, $firm_id);
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function getYearTrail() {

        $to = date("Y-m-t 23:59:59", strtotime("-1 month"));
        $from = date("Y-m-01 00:00:00", strtotime("-12 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getYearTrailCustom($date)
    {
        $to = date("Y-m-t",strtotime($date)-1);
        $from = date("Y-m-01", strtotime('-11 months', strtotime($to)));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }

    public static function getYearTrailLabels() {
        $labels = [];
        $range = self::getYearTrail();
        $begin = new \DateTime($range->from);
        $end = new \DateTime($range->to);
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }

    public static function getHalfYearTrail() {
        $to = date("Y-m-t 23:59:59");
        $from = date("Y-m-01 00:00:00", strtotime("-5 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    //Function that returns financial months trial without last month
    public static function getHalfYearTrailAverage() {
        $to = date("Y-m-t 23:59:59",strtotime('-1 month'));
        $from = date("Y-m-01 00:00:00", strtotime("-6 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getHalfYearTrailAverageCustom($date)
    {
        $to = date("Y-m-t",strtotime($date)-1);
        $from = date("Y-m-01 00:00:00", strtotime('-5 months', strtotime($to)));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getHalfYearTrailAverageLabels() {
        $labels = [];
        $fy = self::getHalfYearTrailAverage();
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function getThreeMonthsTrail() {
        $to = date("Y-m-t 23:59:59",strtotime('-1 month'));
        $from = date("Y-m-01 00:00:00", strtotime("-3 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getThreeMonthsTrailLabels() {
        $labels = [];
        $fy = self::getThreeMonthsTrail();
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function getNineMonthsTrail() {
        $to = date("Y-m-t 23:59:59",strtotime('-1 month'));
        $from = date("Y-m-01 00:00:00", strtotime("-9 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getNineMonthsTrailLabels() {
        $labels = [];
        $fy = self::getNineMonthsTrail();
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function getHalfYearTrailLabels() {
        $labels = [];
        $fy = self::getHalfYearTrail();
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function getHalfYearTrailPredictive() {
        $to = date("Y-m-t 23:59:59", strtotime("+2 month"));
        $from = date("Y-m-01 00:00:00", strtotime("-3 month"));
        return (object) [
            "from" => $from,
            "to" => $to,
        ];
    }
    public static function getHalfYearTrailPredictiveLabels() {
        $labels = [];
        $fy = self::getHalfYearTrailPredictive();
        $begin = new \DateTime(substr($fy->from, 0, 10));
        $end = new \DateTime(substr($fy->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $labels[] = $i->format("M-y");
        }
        return $labels;
    }
    public static function checkTiers($numberOfUsers)
    {
        $value = 0;
        $tier = "";
        if($numberOfUsers>0 && $numberOfUsers<=3)
        {
            $value = 49;
            $tier = "1-3 Users";
        }
        if($numberOfUsers>3 && $numberOfUsers<=9)
        {
            $value = 129;
            $tier = "4-9 Users";
        }
        if($numberOfUsers>9 && $numberOfUsers<=19)
        {
            $value = 299;
            $tier = "10-19 Users";
        }
        if($numberOfUsers>19 && $numberOfUsers<=29)
        {
            $value = 599;
            $tier = "20-29 Users";
        }
        if($numberOfUsers>29 && $numberOfUsers<=39)
        {
            $value = 849;
            $tier = "30-39 Users";
        }
        if($numberOfUsers>39 && $numberOfUsers<=49)
        {
            $value = 1099;
            $tier = "40-49 Users";
        }
        if($numberOfUsers>49 && $numberOfUsers<=59)
        {
            $value = 1599;
            $tier = "50-59 Users";
        }
        if($numberOfUsers>59 && $numberOfUsers<=69)
        {
            $value = 1849;
            $tier = "60-69 Users";
        }
        if($numberOfUsers>79 && $numberOfUsers<=89)
        {
            $value = 2099;
            $tier = "80-89 Users";
        }
        if($numberOfUsers>99 && $numberOfUsers<=149)
        {
            $value = 3049;
            $tier = "100-149 Users";
        }
        if($numberOfUsers>149 && $numberOfUsers<=199)
        {
            $value = 4249;
            $tier = "149-199 Users";
        }
        if($numberOfUsers>199 && $numberOfUsers<=249)
        {
            $value = 5499;
            $tier = "150-249 Users";
        }
        if($numberOfUsers>249 && $numberOfUsers<=299)
        {
            $value = 6699;
            $tier = "250-299 Users";
        }
        if($numberOfUsers>299 && $numberOfUsers<=349)
        {
            $value = 7899;
            $tier = "300-349 Users";
        }
        if($numberOfUsers>349 && $numberOfUsers<=399)
        {
            $value = 9049;
            $tier = "350-399 Users";
        }
        if($numberOfUsers>399 && $numberOfUsers<=449)
        {
            $value = 10299;
            $tier = "400-449 Users";
        }
        if($numberOfUsers>449 && $numberOfUsers<=499)
        {
            $value = 11499;
            $tier = "450-499 Users";
        }
        if($numberOfUsers>500 && $numberOfUsers<=549)
        {
            $value = 12699;
            $tier = "500-549 Users";
        }
        if($numberOfUsers>550 && $numberOfUsers<=599)
        {
            $value = 13899;
            $tier = "550-599 Users";
        }
        if($numberOfUsers>600 && $numberOfUsers<=649)
        {
            $value = 15049;
            $tier = "600-649 Users";
        }
        if($numberOfUsers>650 && $numberOfUsers<=699)
        {
            $value = 18699;
            $tier = "650-799 Users";
        }
        if($numberOfUsers>700 && $numberOfUsers<=749)
        {
            $value = 17499;
            $tier = "700-749 Users";
        }
        if($numberOfUsers>750 && $numberOfUsers<=799)
        {
            $value = 18649;
            $tier = "750-799 Users";
        }
        if($numberOfUsers>800 && $numberOfUsers<=849)
        {
            $value = 19849;
            $tier = "800-849 Users";
        }
        if($numberOfUsers>850 && $numberOfUsers<=999)
        {
            $value = 23499;
            $tier = "850-999 Users";
        }
        if($numberOfUsers>1000 && $numberOfUsers<=1049)
        {
            $value = 24649;
            $tier = "1000-1049 Users";
        }
        if($numberOfUsers>1050 && $numberOfUsers<=1099)
        {
            $value = 25849;
            $tier = "1050-1099 Users";
        }
        if($numberOfUsers>1100 && $numberOfUsers<=1149)
        {
            $value = 27049;
            $tier = "1100-1149 Users";
        }
        if($numberOfUsers>1150 && $numberOfUsers<=1199)
        {
            $value = 28249;
            $tier = "1150-1199 Users";
        }
        if($numberOfUsers>1200 && $numberOfUsers<=1249)
        {
            $value = 29449;
            $tier = "1200-1249 Users";
        }
        if($numberOfUsers>1250 && $numberOfUsers<=1299)
        {
            $value = 30649;
            $tier = "1250-1299 Users";
        }
        if($numberOfUsers>1300 && $numberOfUsers<=1349)
        {
            $value = 31849;
            $tier = "1300-1349 Users";
        }
        if($numberOfUsers>1350 && $numberOfUsers<=1399)
        {
            $value = 33049;
            $tier = "1350-1399 Users";
        }
        if($numberOfUsers>1400 && $numberOfUsers<=1449)
        {
            $value = 34249;
            $tier = "1400-1449 Users";
        }
        if($numberOfUsers>1450 && $numberOfUsers<=1499)
        {
            $value = 35499;
            $tier = "1450-1499 Users";
        }
        if($numberOfUsers>1500 && $numberOfUsers<=1549)
        {
            $value = 36649;
            $tier = "1500-1549 Users";
        }
        if($numberOfUsers>1550 && $numberOfUsers<=1599)
        {
            $value = 37849;
            $tier = "1550-1599 Users";
        }
        if($numberOfUsers>1600 && $numberOfUsers<=1649)
        {
            $value = 39049;
            $tier = "1600-1649 Users";
        }
        if($numberOfUsers>1650 && $numberOfUsers<=1699)
        {
            $value = 40249;
            $tier = "1650-1699 Users";
        }
        if($numberOfUsers>1700 && $numberOfUsers<=1749)
        {
            $value = 41449;
            $tier = "1700-1749 Users";
        }
        if($numberOfUsers>1750 && $numberOfUsers<=1799)
        {
            $value = 42649;
            $tier = "1750-1799 Users";
        }
        if($numberOfUsers>1800 && $numberOfUsers<=1849)
        {
            $value = 43849;
            $tier = "1800-1849 Users";
        }
        if($numberOfUsers>1850 && $numberOfUsers<=1899)
        {
            $value = 45049;
            $tier = "1850-1899 Users";
        }
        if($numberOfUsers>1900 && $numberOfUsers<=1949)
        {
            $value = 46249;
            $tier = "1900-1949 Users";
        }
        if($numberOfUsers>1950 && $numberOfUsers<=2000)
        {
            $value = 47249;
            $tier = "1950-2000 Users";
        }



        return array("Tier"=>$tier,"Price"=>$value);

    }
    public static function getIntegrationCode($integration)
    {
        $value =null;
        if($integration=="practice_panther")
        {
            $value = "PP";
        }
        else
        {
            $value = "Clio";
        }
        return $value;

    }
}
