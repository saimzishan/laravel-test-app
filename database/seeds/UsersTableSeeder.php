<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'display_name' => "Super Administrator",
                'first_name' => "Super",
                'last_name' => "Administrator",
                'email' => 'admin@marvellawpc.com',
                'password' => Hash::make("marvel@10"),
                'role_id' => '1',
            ]
        ];
        DB::table('users')->insert($data);
    }
}
