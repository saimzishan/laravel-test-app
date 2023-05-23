<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;

class FirmRole extends Model
{
    public function permissions() {
        return $this->hasMany("App\FirmRolePermission", "firm_role_id");
    }

    public function generateDefaultPermissions() {
        $rows = [];
        $permissions = collect(HelperLibrary::getFirmAdminPermissions());
        foreach ($permissions as $per) {
            $row = new FirmRolePermission();
            $row->firm_id = $this->firm_id;
            $row->type = $per->type;
            $row->slug = $per->slug;
            $row->is_allowed = 0;
            $row->created_by = 0;
            $row->updated_by = 0;
            $rows[] = $row;
        }
        $this->permissions()->saveMany($rows);
    }
}
