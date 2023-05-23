<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'display_name', 'first_name', 'last_name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function is_super()
    {
        return $this->role_id == 1 ? true : false;
    }
    public function getStatus() {
        return $this->is_active == 1 ? "Active" : "In-Active";
    }
    public function getType() {
        return $this->role_id == 1 ? "super" : "developer";
    }
    public function getName() {
        return $this->display_name;
    }
}
