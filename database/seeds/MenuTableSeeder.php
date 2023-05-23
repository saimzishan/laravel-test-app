<?php

use Illuminate\Database\Seeder;

class MenuTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        
        \DB::table('menu')->insert(array (
            0 => 
            array (
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Firms Management',
                'slug' => 'FirmsManagement',
                'icon' => 'fa-building',
                'order' => 1,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 0,
                'updated_by' => 2,
                'created_at' => '2019-02-28 23:12:37',
                'updated_at' => '2019-03-01 04:41:14',
            ),
            3 => 
            array (
                'id' => 5,
                'parent_id' => 1,
                'name' => 'Firms',
                'slug' => 'Firms',
                'icon' => 'fa-building',
                'order' => 1,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 04:41:27',
                'updated_at' => '2019-03-01 04:41:27',
            ),
            4 => 
            array (
                'id' => 6,
                'parent_id' => 1,
                'name' => 'Users',
                'slug' => 'FirmUsers',
                'icon' => 'fa-users',
                'order' => 2,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 04:41:49',
                'updated_at' => '2019-03-01 04:41:49',
            ),
            6 => 
            array (
                'id' => 8,
                'parent_id' => 7,
                'name' => 'Users',
                'slug' => 'Users',
                'icon' => 'fa-users',
                'order' => 1,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 18:57:24',
                'updated_at' => '2019-03-01 18:58:20',
            ),
            7 => 
            array (
                'id' => 9,
                'parent_id' => 7,
                'name' => 'Matters',
                'slug' => 'Matters',
                'icon' => 'fa-map',
                'order' => 2,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 18:58:52',
                'updated_at' => '2019-03-01 18:58:52',
            ),
            9 => 
            array (
                'id' => 11,
                'parent_id' => 7,
                'name' => 'Matters WBS',
                'slug' => 'MattersWBS',
                'icon' => 'fa-calendar-check',
                'order' => 4,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 19:02:15',
                'updated_at' => '2019-03-01 19:02:15',
            ),
            11 => 
            array (
                'id' => 13,
                'parent_id' => 1,
                'name' => 'Firm Integration',
                'slug' => 'FirmIntegration',
                'icon' => 'fa-cog',
                'order' => 3,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-03-01 19:36:48',
                'updated_at' => '2019-03-01 19:36:48',
            ),
            15 => 
            array (
                'id' => 17,
                'parent_id' => 19,
                'name' => 'Firm',
                'slug' => 'Firm',
                'icon' => 'fa-building',
                'order' => 7,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-04-07 19:50:58',
                'updated_at' => '2019-05-09 23:11:48',
            ),
            17 => 
            array (
                'id' => 19,
                'parent_id' => 0,
                'name' => 'Firm Management',
                'slug' => 'FirmManagement',
                'icon' => 'fa-building',
                'order' => 9,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-05-09 23:11:32',
                'updated_at' => '2019-05-09 23:11:32',
            ),
            18 => 
            array (
                'id' => 20,
                'parent_id' => 19,
                'name' => 'Firm Integration',
                'slug' => 'FirmIntegration',
                'icon' => 'fa-cog',
                'order' => 8,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-05-09 23:12:13',
                'updated_at' => '2019-05-09 23:12:13',
            ),
            19 => 
            array (
                'id' => 21,
                'parent_id' => 19,
                'name' => 'Firm Users',
                'slug' => 'FirmUsers',
                'icon' => 'fa-users',
                'order' => 9,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-05-09 23:13:26',
                'updated_at' => '2019-05-09 23:13:50',
            ),
            20 => 
            array (
                'id' => 22,
                'parent_id' => 19,
                'name' => 'Firm Packages',
                'slug' => 'FirmPackages',
                'icon' => 'fa-box-open',
                'order' => 10,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-05-09 23:19:01',
                'updated_at' => '2019-05-09 23:19:01',
            ),
            21 => 
            array (
                'id' => 23,
                'parent_id' => 19,
                'name' => 'Firm Roles',
                'slug' => 'FirmRoles',
                'icon' => 'fa-circle',
                'order' => 11,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-05-12 19:46:02',
                'updated_at' => '2019-05-12 19:46:02',
            ),
            24 => 
            array (
                'id' => 26,
                'parent_id' => 0,
                'name' => 'Settings',
                'slug' => 'Settings',
                'icon' => 'fa-cog',
                'order' => 10,
                'is_active' => 1,
                'is_delete' => 0,
                'created_by' => 2,
                'updated_by' => 2,
                'created_at' => '2019-07-15 13:29:24',
                'updated_at' => '2019-07-15 13:29:24',
            ),
        ));
        
        
    }
}