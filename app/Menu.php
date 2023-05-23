<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = "menu";

    public function parent() {
        return $this->belongsTo('App\Menu', 'parent_id')->orderBy("order", "asc")->where("is_delete", 0);
    }
    public function children() {
        return $this->hasMany('App\Menu', 'parent_id')->orderBy("order", "asc")->where("is_delete", 0);
    }
    public function getParent() {
        if ($this->parent_id==0) {
            return '-';
        } else {
            return optional($this->parent)->name;
        }
    }
    public function getStatus() {
        return $this->is_active == 1 ? 'Active' : 'In-Active';
    }
    public function getNewOrder($parent_id) {
        $count = Menu::where("is_delete", 0)->where("parent_id", $parent_id)->orderBy("order",  "desc")->get();
        if ($count->count() == 0) {
            return 1;
        } else {
            return $count->first()->order + 1;
        }
    }

}
