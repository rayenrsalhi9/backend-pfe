<?php

namespace Database\Seeders;

use App\Models\Users;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();
        $users = [
            [
                'id' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'matricule' => '123456',
                'direction'=> 'IT',
                'firstName' => 'super',
                'lastName' => 'admin',
                'isDeleted' => '0',
                'userName' => 'superadmin@gmail.com',
                'normalizedUserName' => NULL,
                'email' => 'superadmin@gmail.com',
                'normalizedEmail' => NULL,
                'emailConfirmed' => '0',
                'password' => Hash::make("123456Sa"),
                'securityStamp' => NULL,
                'concurrencyStamp' => NULL,
                'phoneNumber' => '00000000',
                'phoneNumberConfirmed' => '0',
                'twoFactorEnabled' => '0',
                'lockoutEnd' => NULL,
                'lockoutEnabled' => '0',
                'accessFailedCount' => '0',
                'matricule' => '123456', 
                'direction' => 'IT'       
            ]
        ];

        Users::insert($users);
    }
}
