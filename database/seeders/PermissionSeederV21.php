<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\RoleClaims;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Users;

class PermissionSeederV21 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $action = Actions::where([['name', '=', 'Edit Document'], ['pageId', '=', 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd']])->first();

        if ($action == null) {

            $user = Users::first();

            $actions =
                [
                    [
                        'id' => 'a737284a-e43b-481d-9fdd-07e1680ffe11',
                        'name' => 'Edit Document',
                        'order' => 2,
                        'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                        'code' => 'ASSIGNED_DOCUMENTS_EDIT_DOCUMENT',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ],
                    [
                        'id' => 'ac6d6fbc-6348-4149-9c0c-154ab79d1166',
                        'name' => 'Share Document',
                        'order' => 3,
                        'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                        'code' => 'ASSIGNED_DOCUMENTS_SHARE_DOCUMENT',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ],
                    [
                        'id' => '391c1739-1045-4dd4-9705-4a960479f0a0',
                        'name' => 'Upload New Version',
                        'order' => 4,
                        'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                        'code' => 'ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ],
                    [
                        'id' => 'c04a1094-f289-4de7-b788-9f21ee3fe32a',
                        'name' => 'Send Email',
                        'order' => 5,
                        'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                        'code' => 'ASSIGNED_DOCUMENTS_SEND_EMAIL',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ],
                    [
                        'id' => '92596605-e49a-4ab6-8a39-60116eba8abe',
                        'name' => 'Delete Document',
                        'order' => 6,
                        'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                        'code' => 'ASSIGNED_DOCUMENTS_DELETE_DOCUMENT',
                        'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                        'isDeleted' => 0
                    ]
                ];

            $roleClaims = [
                [
                    'id' => Str::uuid(36),
                    'actionId' => 'a737284a-e43b-481d-9fdd-07e1680ffe11',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'ASSIGNED_DOCUMENTS_EDIT_DOCUMENT',
                ],    [
                    'id' => Str::uuid(36),
                    'actionId' => 'ac6d6fbc-6348-4149-9c0c-154ab79d1166',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'ASSIGNED_DOCUMENTS_SHARE_DOCUMENT'
                ], [
                    'id' => Str::uuid(36),
                    'actionId' => '391c1739-1045-4dd4-9705-4a960479f0a0',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION'
                ], [
                    'id' => Str::uuid(36),
                    'actionId' => 'c04a1094-f289-4de7-b788-9f21ee3fe32a',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'ASSIGNED_DOCUMENTS_SEND_EMAIL'
                ], [
                    'id' => Str::uuid(36),
                    'actionId' => '92596605-e49a-4ab6-8a39-60116eba8abe',
                    'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                    'claimType' => 'ASSIGNED_DOCUMENTS_DELETE_DOCUMENT'
                ]
            ];

            $updatedActions =  collect($actions)->map(function ($item, $key) {
                $item['createdDate'] = Carbon::now();
                $item['modifiedDate'] = Carbon::now();
                return $item;
            });

            Actions::insert($updatedActions->toArray());
            RoleClaims::insert($roleClaims);
        }
    }
}
