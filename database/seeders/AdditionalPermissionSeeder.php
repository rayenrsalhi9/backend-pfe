<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdditionalPermissionSeeder extends Seeder
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

        $pagesData = [
            ['name' => 'Surveys', 'order' => 14],
            ['name' => 'News', 'order' => 15],
            ['name' => 'Blogs', 'order' => 16]
        ];

        $pageIds = [];

        foreach ($pagesData as $pd) {
            $page = DB::table('pages')->where('name', $pd['name'])->first();
            if (!$page) {
                $pageId = Str::uuid();
                DB::table('pages')->insert([
                    'id' => $pageId,
                    'name' => $pd['name'],
                    'order' => $pd['order'],
                    'createdBy' => $defaultUserId,
                    'modifiedBy' => $defaultUserId,
                    'isDeleted' => 0,
                    'createdDate' => $now,
                    'modifiedDate' => $now
                ]);
                $pageIds[$pd['name']] = $pageId;
            } else {
                $pageIds[$pd['name']] = $page->id;
            }
        }

        $allActions = [
            // Surveys
            ['code' => 'SURVEY_VIEW_SURVEYS', 'name' => 'View Surveys', 'order' => 1, 'page' => 'Surveys'],
            ['code' => 'SURVEY_ADD_SURVEY', 'name' => 'Add Survey', 'order' => 2, 'page' => 'Surveys'],
            ['code' => 'SURVEY_EDIT_SURVEY', 'name' => 'Edit Survey', 'order' => 3, 'page' => 'Surveys'],
            ['code' => 'SURVEY_DELETE_SURVEY', 'name' => 'Delete Survey', 'order' => 4, 'page' => 'Surveys'],
            ['code' => 'SURVEY_ANSWER_SURVEY', 'name' => 'Answer Survey', 'order' => 5, 'page' => 'Surveys'],
            
            // News
            ['code' => 'NEWS_VIEW_NEWS', 'name' => 'View News', 'order' => 1, 'page' => 'News'],
            ['code' => 'NEWS_ADD_NEWS', 'name' => 'Add News', 'order' => 2, 'page' => 'News'],
            ['code' => 'NEWS_EDIT_NEWS', 'name' => 'Edit News', 'order' => 3, 'page' => 'News'],
            ['code' => 'NEWS_DELETE_NEWS', 'name' => 'Delete News', 'order' => 4, 'page' => 'News'],
            ['code' => 'NEWS_VIEW_CATEGORIES', 'name' => 'View News Categories', 'order' => 5, 'page' => 'News'],
            ['code' => 'NEWS_ADD_CATEGORY', 'name' => 'Add News Category', 'order' => 6, 'page' => 'News'],
            ['code' => 'NEWS_EDIT_CATEGORY', 'name' => 'Edit News Category', 'order' => 7, 'page' => 'News'],
            ['code' => 'NEWS_DELETE_CATEGORY', 'name' => 'Delete News Category', 'order' => 8, 'page' => 'News'],

            // Blogs
            ['code' => 'BLOG_VIEW_BLOGS', 'name' => 'View Blogs', 'order' => 1, 'page' => 'Blogs'],
            ['code' => 'BLOG_ADD_BLOG', 'name' => 'Add Blog', 'order' => 2, 'page' => 'Blogs'],
            ['code' => 'BLOG_EDIT_BLOG', 'name' => 'Edit Blog', 'order' => 3, 'page' => 'Blogs'],
            ['code' => 'BLOG_DELETE_BLOG', 'name' => 'Delete Blog', 'order' => 4, 'page' => 'Blogs'],
            ['code' => 'BLOG_VIEW_CATEGORIES', 'name' => 'View Blog Categories', 'order' => 5, 'page' => 'Blogs'],
            ['code' => 'BLOG_ADD_CATEGORY', 'name' => 'Add Blog Category', 'order' => 6, 'page' => 'Blogs'],
            ['code' => 'BLOG_EDIT_CATEGORY', 'name' => 'Edit Blog Category', 'order' => 7, 'page' => 'Blogs'],
            ['code' => 'BLOG_DELETE_CATEGORY', 'name' => 'Delete Blog Category', 'order' => 8, 'page' => 'Blogs'],
            ['code' => 'BLOG_DELETE_COMMENT', 'name' => 'Delete Blog Comments', 'order' => 9, 'page' => 'Blogs']
        ];

        foreach ($allActions as $actionData) {
            $existingAction = DB::table('actions')->where('code', $actionData['code'])->first();
            
            if (!$existingAction) {
                $actionId = Str::uuid();
                DB::table('actions')->insert([
                    'id' => $actionId,
                    'name' => $actionData['name'],
                    'order' => $actionData['order'],
                    'pageId' => $pageIds[$actionData['page']],
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
                    ->update(['name' => $actionData['name']]);
                
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
