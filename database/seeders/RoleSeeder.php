<?php

namespace Database\Seeders;

use App\Models\Roles;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Users;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->delete();
        $user = Users::first();

        $roles =
            [
                [
                    'id' => 'ff635a8f-4bb3-4d70-a3ed-c7749030696c',
                    'isDeleted' => 0,
                    'name' => 'Employee',
                    'createdBy' => "4d2689d8-6f72-4aa6-911d-2414c1a751af",
                    'modifiedBy' => "4d2689d8-6f72-4aa6-911d-2414c1a751af",
                    'createdDate' => Carbon::now(),
                    'modifiedDate' => Carbon::now()
                ],
                [
                    'id' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'isDeleted' => 0,
                    'name' => 'Super Admin',
                    'createdBy' => "4d2689d8-6f72-4aa6-911d-2414c1a751af",
                    'modifiedBy' => "4d2689d8-6f72-4aa6-911d-2414c1a751af",
                    'createdDate' => Carbon::now(),
                    'modifiedDate' => Carbon::now()
                ]
            ];

        Roles::insert($roles);
    }
}
