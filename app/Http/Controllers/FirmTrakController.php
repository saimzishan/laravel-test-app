<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Illuminate\Http\Request;

class FirmTrakController extends Controller
{
    public function update (Request $request) {
        $path = str_replace(" ", "\ ", base_path());
//        $commands = ["cd {$path}"];
//        if ($request->env=="development") {
//            $commands[] = "git pull origin dev";
//            $commands[] = "composer install --no-interaction --no-dev --prefer-dist";
//            $commands[] = "php artisan migrate --force";
//            $commands[] = "rm -rf ../../js";
//            $commands[] = "cp -r ../js ../../js";
//        } elseif ($request->env=="local") {
//            $commands[] = "npm run prod";
//            $commands[] = "rm -rf ../js";
//            $commands[] = "mv public/js ../js";
//            $commands[] = "mv public/mix-manifest.json ../mix-manifest.json";
//            $commands[] = "rm -rf public";
//        }
//        foreach ($commands as $k=>$command) {
//            if ($k > 0) {
//                dump(shell_exec($commands[0] . "; " . $command . "  2>&1"));
//            }
//        }
//        $output = "";
        $output = shell_exec("cd {$path}; ./deploy.sh 2>&1");
        return $output;

//        $process = new Process(implode('; ', $commands));
//        $process->run(function ($type, $buffer) {
//            echo $buffer;
//        });
    }
}
