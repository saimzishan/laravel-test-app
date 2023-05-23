<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpaController extends Controller
{
    public function pages() {
        $files = collect(Storage::disk("js")->allFiles("chunks/js"));
        return view('spa',compact("files"));
    }
}
