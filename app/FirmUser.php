<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class FirmUser extends Authenticatable
{

    use Notifiable;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function firm() {
        return $this->belongsTo("App\Firm");
    }
    public function firmRole() {
        return $this->belongsTo("App\FirmRole", "firm_role_id");
    }
    public function activityLogs() {
        return $this->hasMany("App\ActivityLog")->orderBy("id", "desc");
    }
    public function getStatus() {
        return $this->is_active == 1 ? 'Active' : 'In-Active';
    }
    public function getRole() {
        return $this->firm_role_id == 0 ? 'Firm Admin' : $this->firmRole->name;
    }
    public function getType() {
        return "firm_user";
    }
    public function getCreatedDate() {
        return date("m/d/Y (h:i A)", strtotime($this->created_at));
    }
    public function getFirmName() {
        return $this->firm_id != null ? $this->firm->name : '-';
    }
    public function getPaginatedActivityLogs() {
        return $this->activityLogs()->paginate(HelperLibrary::perPage());
    }
    public function getName() {
        return $this->display_name;
    }
    public function getFullName() {
        return "{$this->first_name} {$this->last_name}";
    }
}
