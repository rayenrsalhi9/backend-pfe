<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\Pages;
use App\Models\RoleClaims;
use App\Models\UserRoles;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Users;

class PermissionSeederV2 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $page = Pages::where('name', '=', 'Settings')->first();

        if ($page == null) {

            $user = Users::first();

            $pages =
                [
                    [
                        'id' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2',
                        'name' => 'Settings',
                        'order' => 9,
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ],
                ];

            $actions =
                [
                    [
                        'id' => '72ca5c91-b415-4997-a234-b4d71ba03253',
                        'name' => 'Manage Languages',
                        'order' => 1,
                        'pageId' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2',
                        'code' => 'SETTING_MANAGE_LANGUAGE',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ], [
                        'id' => 'a57b1ad5-8fbc-429b-b776-fbb468e5c6a4',
                        'name' => 'Manage Company Profile',
                        'order' => 2,
                        'pageId' => '8fbb83d6-9fde-4970-ac80-8e235cab1ff2',
                        'code' => 'SETTING_MANAGE_PROFILE',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ]
                ];

            $roleClaims = [
                [
                    'id' => Str::uuid(36),
                    'actionId' => '72ca5c91-b415-4997-a234-b4d71ba03253',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'SETTING_MANAGE_LANGUAGE',
                ],    [
                    'id' => Str::uuid(36),
                    'actionId' => 'a57b1ad5-8fbc-429b-b776-fbb468e5c6a4',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'SETTING_MANAGE_PROFILE'
                ]
            ];

            $updatedPages =  collect($pages)->map(function ($item, $key) {
                $item['createdDate'] = Carbon::now();
                $item['modifiedDate'] = Carbon::now();
                return $item;
            });

            $updatedActions =  collect($actions)->map(function ($item, $key) {
                $item['createdDate'] = Carbon::now();
                $item['modifiedDate'] = Carbon::now();
                return $item;
            });

            Pages::insert($updatedPages->toArray());
            Actions::insert($updatedActions->toArray());
            RoleClaims::insert($roleClaims);
        }
    }
}
