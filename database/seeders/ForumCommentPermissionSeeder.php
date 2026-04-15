<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\Pages;
use App\Models\RoleClaims;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumCommentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Use raw SQL to avoid model boot methods that require JWT tokens
        $defaultUserId = '4d2689d8-6f72-4aa6-911d-2414c1a751af';
        $now = Carbon::now();

        // Find existing Forums page using raw query to avoid model boot
        $forumPage = DB::table('pages')->where('name', 'Forums')->first();
        
        if (!$forumPage) {
            // Create Forums page using raw SQL
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
        }
        


    }
}