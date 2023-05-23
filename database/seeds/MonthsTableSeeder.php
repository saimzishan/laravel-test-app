<?php

use Illuminate\Database\Seeder;

class MonthsTableSeeder extends Seeder
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
                "number" => "01",
                "name" => "January",
            ],
            [
                "number" => "02",
                "name" => "February",
            ],
            [
                "number" => "03",
                "name" => "March",
            ],
            [
                "number" => "04",
                "name" => "April",
            ],
            [
                "number" => "05",
                "name" => "May",
            ],
            [
                "number" => "06",
                "name" => "June",
            ],
            [
                "number" => "07",
                "name" => "July",
            ],
            [
                "number" => "08",
                "name" => "August",
            ],
            [
                "number" => "09",
                "name" => "September",
            ],
            [
                "number" => "10",
                "name" => "October",
            ],
            [
                "number" => "11",
                "name" => "November",
            ],
            [
                "number" => "12",
                "name" => "December",
            ],
        ];
        DB::table("months")->insert($data);
    }
}
