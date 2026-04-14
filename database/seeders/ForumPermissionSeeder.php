<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ForumPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultUserId = '4d2689d8-6f72-4aa6-911d-2414c1a751af';
        $adminRoleId = 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4';
        $now = Carbon::now();

        // Find or Create Forums page
        $forumPage = DB::table('pages')->where('name', 'Forums')->first();
        if (!$forumPage) {
            $forumPageId = Str::uuid();
            DB::table('pages')->insert([
                'id' => $forumPageId,
                'name' => 'Forums',
                'order' => 13,
                'createdBy' => $defaultUserId,
                'modifiedBy' => $defaultUserId,
                'isDeleted' => 0,
                'createdDate' => $now,
                'modifiedDate' => $now
            ]);
            $forumPage = (object) ['id' => $forumPageId];
        } else {
            DB::table('pages')->where('id', $forumPage->id)->update([
                'order' => 13,
                'isDeleted' => 0,
                'modifiedBy' => $defaultUserId,
                'modifiedDate' => $now
            ]);
            $forumPage->id = $forumPage->id;
        }

        $forumActions = [
            ['code' => 'FORUM_VIEW_FORUMS', 'name' => 'View Forums', 'order' => 1],
            ['code' => 'FORUM_ADD_TOPIC', 'name' => 'Add Forum Topic', 'order' => 2],
            ['code' => 'FORUM_EDIT_TOPIC', 'name' => 'Edit Forum Topic', 'order' => 3],
            ['code' => 'FORUM_DELETE_TOPIC', 'name' => 'Delete Forum Topic', 'order' => 4],
            ['code' => 'FORUM_DELETE_COMMENT', 'name' => 'Delete Forum Comments', 'order' => 5],
            ['code' => 'FORUM_VIEW_CATEGORIES', 'name' => 'View Forum Categories', 'order' => 6],
            ['code' => 'FORUM_ADD_CATEGORY', 'name' => 'Add Forum Category', 'order' => 7],
            ['code' => 'FORUM_EDIT_CATEGORY', 'name' => 'Edit Forum Category', 'order' => 8],
            ['code' => 'FORUM_DELETE_CATEGORY', 'name' => 'Delete Forum Category', 'order' => 9],
        ];

        foreach ($forumActions as $actionData) {
            $existingAction = DB::table('actions')->where('code', $actionData['code'])->first();
            
            if (!$existingAction) {
                $actionId = Str::uuid();
                DB::table('actions')->insert([
                    'id' => $actionId,
                    'name' => $actionData['name'],
                    'order' => $actionData['order'],
                    'pageId' => $forumPage->id,
                    'code' => $actionData['code'],
                    'createdBy' => $defaultUserId,
                    'modifiedBy' => $defaultUserId,
                    'isDeleted' => 0,
                    'createdDate' => $now,
                    'modifiedDate' => $now
                ]);

                // Assign to Admin role
                DB::table('roleClaims')->insert([
                    'id' => Str::uuid(),
                    'actionId' => $actionId,
                    'roleId' => $adminRoleId,
                    'claimType' => $actionData['code'],
                    'claimValue' => null
                ]);
            } else {
                DB::table('actions')
                    ->where('id', $existingAction->id)
                    ->update([
                        'name' => $actionData['name'],
                        'order' => $actionData['order'],
                        'pageId' => $forumPage->id,
                        'modifiedBy' => $defaultUserId,
                        'modifiedDate' => $now,
                        'isDeleted' => 0
                    ]);

                $existingClaim = DB::table('roleClaims')
                    ->where('actionId', $existingAction->id)
                    ->where('roleId', $adminRoleId)
                    ->first();

                if (!$existingClaim) {
                    DB::table('roleClaims')->insert([
                        'id' => Str::uuid(),
                        'actionId' => $existingAction->id,
                        'roleId' => $adminRoleId,
                        'claimType' => $actionData['code'],
                        'claimValue' => null
                    ]);
                }
            }
        }
    }
}
