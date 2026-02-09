<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResponseAuditTrailPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the Response Audit Trail page already exists
        $existingPage = DB::table('pages')
            ->where('id', 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c')
            ->first();

        if (!$existingPage) {
            // Insert Response Audit Trail page
            DB::table('pages')->insert([
                'id' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'name' => 'Response Audit Trail',
                'order' => 11,
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ]);
        }

        // Response Audit Trail Actions
        $actions = [
            [
                'id' => 'b2c4d5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e',
                'name' => 'View Response Audit Trail',
                'order' => 1,
                'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'code' => 'RESPONSE_AUDIT_TRAIL_VIEW_RESPONSE_AUDIT_TRAIL',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ],
            [
                'id' => 'f6a7b8c9-1d2e-3f4a-5b6c-7d8e9f0a1b2c',
                'name' => 'View Response Audit Logs',
                'order' => 2,
                'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'code' => 'RESPONSE_AUDIT_VIEW_RESPONSE_AUDIT_LOGS',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ],
            [
                'id' => 'c3d4e5f6-8a9b-0c1d-2e3f-4a5b6c7d8e9f',
                'name' => 'Create Response Audit Trail',
                'order' => 2,
                'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'code' => 'RESPONSE_AUDIT_TRAIL_CREATE_RESPONSE_AUDIT_TRAIL',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ],
            [
                'id' => 'd4e5f6a7-9b0c-1d2e-3f4a-5b6c7d8e9f0a',
                'name' => 'Update Response Audit Trail',
                'order' => 3,
                'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'code' => 'RESPONSE_AUDIT_TRAIL_UPDATE_RESPONSE_AUDIT_TRAIL',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ],
            [
                'id' => 'e5f6a7b8-0c1d-2e3f-4a5b-6c7d8e9f0a1b',
                'name' => 'Delete Response Audit Trail',
                'order' => 4,
                'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                'code' => 'RESPONSE_AUDIT_TRAIL_DELETE_RESPONSE_AUDIT_TRAIL',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0,
                'createdDate' => Carbon::now(),
                'modifiedDate' => Carbon::now()
            ]
        ];

        // Check if actions already exist before inserting
        foreach ($actions as $action) {
            $existingAction = DB::table('actions')
                ->where('id', $action['id'])
                ->first();

            if (!$existingAction) {
                DB::table('actions')->insert($action);
            }
        }

        // Add role claims for the Administrator role
        $roleClaims = [
            [
                'id' => Str::uuid(36),
                'actionId' => 'b2c4d5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_VIEW_RESPONSE_AUDIT_TRAIL'
            ],
            [
                'id' => Str::uuid(36),
                'actionId' => 'f6a7b8c9-1d2e-3f4a-5b6c-7d8e9f0a1b2c',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_VIEW_RESPONSE_AUDIT_LOGS'
            ],
            [
                'id' => Str::uuid(36),
                'actionId' => 'c3d4e5f6-8a9b-0c1d-2e3f-4a5b6c7d8e9f',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_CREATE_RESPONSE_AUDIT_TRAIL'
            ],
            [
                'id' => Str::uuid(36),
                'actionId' => 'd4e5f6a7-9b0c-1d2e-3f4a-5b6c7d8e9f0a',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_UPDATE_RESPONSE_AUDIT_TRAIL'
            ],
            [
                'id' => Str::uuid(36),
                'actionId' => 'e5f6a7b8-0c1d-2e3f-4a5b-6c7d8e9f0a1b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_DELETE_RESPONSE_AUDIT_TRAIL'
            ]
        ];

        // Check if role claims already exist before inserting
        foreach ($roleClaims as $claim) {
            $existingClaim = DB::table('roleClaims')
                ->where('actionId', $claim['actionId'])
                ->where('roleId', $claim['roleId'])
                ->where('claimType', $claim['claimType'])
                ->first();

            if (!$existingClaim) {
                DB::table('roleClaims')->insert($claim);
            }
        }

        $this->command->info('Response Audit Trail permissions seeded successfully!');
    }
}