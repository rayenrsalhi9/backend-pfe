<?php

namespace Database\Seeders;

use App\Models\Actions;
use App\Models\Pages;
use App\Models\RoleClaims;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Users;

class PermissionSeederV23 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'id' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'name' => 'Chat',
                'order' => 11,
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'name' => 'Articles',
                'order' => 12,
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
        ];


        $actions = [
            [
                'id' => '657fb11b-e2ba-496c-8c2f-8fcb71eac677',
                'name' => 'View Chat',
                'order' => 1,
                'pageId' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'code' => 'CHAT_VIEW_CHATS',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => 'a89a853b-095d-4104-b226-ca84b197fadd',
                'name' => 'Create Chat',
                'order' => 2,
                'pageId' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'code' => 'CHAT_CREATE_CHAT',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '6309ca1c-5dcc-4cd6-a39d-be158ee28638',
                'name' => 'Edit Chat',
                'order' =>  3,
                'pageId' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'code' => 'CHAT_EDIT_CHAT',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '73e5fbd0-f243-492f-beab-c26b98905b4e',
                'name' => 'Delete Chat',
                'order' => 4,
                'pageId' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'code' => 'CHAT_DELETE_CHAT',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '3468f958-c1c8-40ca-97cd-656e4c986bd5',
                'name' => 'Add Group Chat',
                'order' => 5,
                'pageId' => 'a5597558-30ad-4717-abce-1da887b61b0c',
                'code' => 'CHAT_ADD_GROUP_CHAT',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],

            [
                'id' => '6790406b-c1a1-41ab-bdc0-942e5139345f',
                'name' => 'View Articles',
                'order' => 1,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_VIEW_ARTICLES',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '60f7ec00-5449-4dd5-bf60-e76762fa2d9b',
                'name' => 'Add Article',
                'order' => 2,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_ADD_ARTICLE',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '48a14a30-aa66-486d-aa1b-394234ef24c1',
                'name' => 'Edit Article',
                'order' => 3,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_EDIT_ARTICLE',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => 'f12ce6ee-4c58-44e6-8fac-51f5a7e1617e',
                'name' => 'Delete Article',
                'order' => 4,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_DELETE_ARTICLE',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],

            [
                'id' => '4b02cfcf-18f7-48f5-88d7-65b82ba0f319',
                'name' => 'View Article Categories',
                'order' => 5,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_VIEW_CATEGORIES',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '0ecdbba9-23f4-4db3-9981-0a7e9ced558c',
                'name' => 'Add Article Category',
                'order' => 6,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_ADD_CATEGORY',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => 'bd573fb0-5064-45e0-8bbb-aec65395cee3',
                'name' => 'Edit Article Category',
                'order' => 7,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_EDIT_CATEGORY',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
            ],
            [
                'id' => '50527611-8da9-40af-92be-32e6009d93c0',
                'name' => 'Delete Article Category',
                'order' => 8,
                'pageId' => '3b9d99e1-d17b-4ab9-92f1-ff1089182da6',
                'code' => 'ARTICLE_DELETE_CATEGORY',
                'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                'isDeleted' => 0
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
    }
}
