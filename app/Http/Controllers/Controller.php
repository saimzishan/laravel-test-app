<?php

namespace App\Http\Controllers;

use App\Setting;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function getRandomColor($count = 1) {
        $colors = ["#de4d44", "#9e3744", "#ff842a", "#fc766a", "#c83e74", "#8d9440", "#fed65e", "#2e5d9f", "#755841", "#daa03d", "#616247", "#e7b7cf"];
        if ($count == 1) {
            return $colors[array_rand($colors)];
        } else {
            $ret = [];
            for ($i = 1; $i <= $count; $i++) {
                $ret[] = $colors[array_rand($colors)];
            }
            return $ret;
        }
    }

}
