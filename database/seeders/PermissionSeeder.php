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

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('pages')->delete();
        DB::table('actions')->delete();
        DB::table('roleClaims')->delete();
        DB::table('userRoles')->delete();

        $user = Users::first();

        $pages =
            [
                [
                    'id' => '42e44f15-8e33-423a-ad7f-17edc23d6dd3',
                    'name' => 'Dashboard',
                    'order' => 1,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'name' => 'All Documents',
                    'order' => 2,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                    'name' => 'Assigned Documents',
                    'order' => 3,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '5a5f7cf8-21a6-434a-9330-db91b17d867c',
                    'name' => 'Document Category',
                    'order' => 4,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '2396f81c-f8b5-49ac-88d1-94ed57333f49',
                    'name' => 'Document Audit Trail',
                    'order' => 5,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],       [
                    'id' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'name' => 'User',
                    'order' => 6,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '090ea443-01c7-4638-a194-ad3416a5ea7a',
                    'name' => 'Role',
                    'order' => 7,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '2e3c07a4-fcac-4303-ae47-0d0f796403c9',
                    'name' => 'Email',
                    'order' => 8,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],
                [
                    'id' => '97ff6eb0-39b3-4ddd-acf1-43205d5a9bb3',
                    'name' => 'Reminder',
                    'order' => 9,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],
                [
                    'id' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287',
                    'name' => 'Login Audit',
                    'order' => 10,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],
                [
                    'id' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                    'name' => 'Response Audit Trail',
                    'order' => 11,
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],
            ];


        $actions =
            [
                [
                    'id' => '7ba630ca-a9d3-42ee-99c8-766e2231fec1',
                    'name' => 'View Dashboard',
                    'order' => 1,
                    'pageId' => '42e44f15-8e33-423a-ad7f-17edc23d6dd3',
                    'code' => 'DASHBOARD_VIEW_DASHBOARD',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '63ed1277-1db5-4cf7-8404-3e3426cb4bc5',
                    'name' => 'View Documents',
                    'order' => 1,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_VIEW_DOCUMENTS',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '239035d5-cd44-475f-bbc5-9ef51768d389',
                    'name' => 'Create Document',
                    'order' => 2,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_CREATE_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'a8dd972d-e758-4571-8d39-c6fec74b361b',
                    'name' => 'Edit Document',
                    'order' => 3,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_EDIT_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '229ad778-c7d3-4f5f-ab52-24b537c39514',
                    'name' => 'Delete Document',
                    'order' => 4,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_DELETE_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '3da78b4d-d263-4b13-8e81-7aa164a3688c',
                    'name' => 'Add Reminder',
                    'order' => 5,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_ADD_REMINDER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '2ea6ba08-eb36-4e34-92d9-f1984c908b31',
                    'name' => 'Share Document',
                    'order' => 6,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_SHARE_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '6719a065-8a4a-4350-8582-bfc41ce283fb',
                    'name' => 'Download Document',
                    'order' => 7,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_DOWNLOAD_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '595a769d-f7ef-45f3-9f9e-60c58c5e1542',
                    'name' => 'Send Email',
                    'order' => 8,
                    'pageId' => 'eddf9e8e-0c70-4cde-b5f9-117a879747d6',
                    'code' => 'ALL_DOCUMENTS_SEND_EMAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'fa91ffd9-61ee-4bb1-bf86-6a593cdc7be9',
                    'name' => 'Create Document',
                    'order' => 1,
                    'pageId' => 'fc97dc8f-b4da-46b1-a179-ab206d8b7efd',
                    'code' => 'ASSIGNED_DOCUMENTS_CREATE_DOCUMENT',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '1c7d3e31-08ad-43cf-9cf7-4ffafdda9029',
                    'name' => 'View Document Audit Trail',
                    'order' => 1,
                    'pageId' => '2396f81c-f8b5-49ac-88d1-94ed57333f49',
                    'code' => 'DOCUMENT_AUDIT_TRAIL_VIEW_DOCUMENT_AUDIT_TRAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '31cb6438-7d4a-4385-8a34-b4e8f6096a48',
                    'name' => 'View Users',
                    'order' => 1,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_VIEW_USERS',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'b2c4d5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e',
                    'name' => 'View Response Audit Trail',
                    'order' => 1,
                    'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                    'code' => 'RESPONSE_AUDIT_TRAIL_VIEW_RESPONSE_AUDIT_TRAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'c3d4e5f6-8a9b-0c1d-2e3f-4a5b6c7d8e9f',
                    'name' => 'Create Response Audit Trail',
                    'order' => 2,
                    'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                    'code' => 'RESPONSE_AUDIT_TRAIL_CREATE_RESPONSE_AUDIT_TRAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'd4e5f6a7-9b0c-1d2e-3f4a-5b6c7d8e9f0a',
                    'name' => 'Update Response Audit Trail',
                    'order' => 3,
                    'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                    'code' => 'RESPONSE_AUDIT_TRAIL_UPDATE_RESPONSE_AUDIT_TRAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'e5f6a7b8-0c1d-2e3f-4a5b-6c7d8e9f0a1b',
                    'name' => 'Delete Response Audit Trail',
                    'order' => 4,
                    'pageId' => 'a8f5c3e2-7b4d-4e91-9f2c-1d8e9f7a5b2c',
                    'code' => 'RESPONSE_AUDIT_TRAIL_DELETE_RESPONSE_AUDIT_TRAIL',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '86ce1382-a2b1-48ed-ae81-c9908d00cf3b',
                    'name' => 'Create User',
                    'order' => 2,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_CREATE_USER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '0a2e19fc-d9f2-446c-8ca3-e6b8b73b5f9b',
                    'name' => 'Edit User',
                    'order' => 3,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_EDIT_USER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '374d74aa-a580-4928-848d-f7553db39914',
                    'name' => 'Delete User',
                    'order' => 4,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_DELETE_USER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'd4d724fc-fd38-49c4-85bc-73937b219e20',
                    'name' => 'Reset Password',
                    'order' => 5,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_RESET_PASSWORD',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'fbe77c07-3058-4dbe-9d56-8c75dc879460',
                    'name' => 'Assign User Role',
                    'order' => 6,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_ASSIGN_USER_ROLE',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'e506ec48-b99a-45b4-9ec9-6451bc67477b',
                    'name' => 'Assign Permission',
                    'order' => 7,
                    'pageId' => '324bdc51-d71f-4f80-9f28-a30e8aae4009',
                    'code' => 'USER_ASSIGN_PERMISSION',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '57216dcd-1a1c-4f94-a33d-83a5af2d7a46',
                    'name' => 'View Roles',
                    'order' => 1,
                    'pageId' => '090ea443-01c7-4638-a194-ad3416a5ea7a',
                    'code' => 'ROLE_VIEW_ROLES',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'db8825b1-ee4e-49f6-9a08-b0210ed53fd4',
                    'name' => 'Create Role',
                    'order' => 2,
                    'pageId' => '090ea443-01c7-4638-a194-ad3416a5ea7a',
                    'code' => 'ROLE_CREATE_ROLE',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'c288b5d3-419d-4dc0-9e5a-083194016d2c',
                    'name' => 'Edit Role',
                    'order' =>  3,
                    'pageId' => '090ea443-01c7-4638-a194-ad3416a5ea7a',
                    'code' => 'ROLE_EDIT_ROLE',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '18a5a8f6-7cb6-4178-857d-b6a981ea3d4f',
                    'name' => 'Delete Role',
                    'order' => 4,
                    'pageId' => '090ea443-01c7-4638-a194-ad3416a5ea7a',
                    'code' => 'ROLE_DELETE_ROLE',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'cd46a3a4-ede5-4941-a49b-3df7eaa46428',
                    'name' => 'Manage Document Category',
                    'order' => 1,
                    'pageId' => '5a5f7cf8-21a6-434a-9330-db91b17d867c',
                    'code' => 'DOCUMENT_CATEGORY_MANAGE_DOCUMENT_CATEGORY',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '41f65d07-9023-4cfb-9c7c-0e3247a012e0',
                    'name' => 'Manage SMTP Settings',
                    'order' => 1,
                    'pageId' => '2e3c07a4-fcac-4303-ae47-0d0f796403c9',
                    'code' => 'EMAIL_MANAGE_SMTP_SETTINGS',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '6f2717fc-edef-4537-916d-2d527251a5c1',
                    'name' => 'View Reminders',
                    'order' => 1,
                    'pageId' => '97ff6eb0-39b3-4ddd-acf1-43205d5a9bb3',
                    'code' => 'REMINDER_VIEW_REMINDERS',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '6bc0458e-22f5-4975-b387-4d6a4fb35201',
                    'name' => 'Create Reminder',
                    'order' => 2,
                    'pageId' => '97ff6eb0-39b3-4ddd-acf1-43205d5a9bb3',
                    'code' => 'REMINDER_CREATE_REMINDER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '3ccaf408-8864-4815-a3e0-50632d90bcb6',
                    'name' => 'Edit Reminder',
                    'order' => 3,
                    'pageId' => '97ff6eb0-39b3-4ddd-acf1-43205d5a9bb3',
                    'code' => 'REMINDER_EDIT_REMINDER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => '5ea48d56-2ed3-4239-bb90-dd4d70a1b0b2',
                    'name' => 'Delete Reminder',
                    'order' => 4,
                    'pageId' => '97ff6eb0-39b3-4ddd-acf1-43205d5a9bb3',
                    'code' => 'REMINDER_DELETE_REMINDER',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ], [
                    'id' => 'ff4b3b73-c29f-462a-afa4-94a40e6b2c4a',
                    'name' => 'View Login Audit Logs',
                    'order' => 1,
                    'pageId' => 'f042bbee-d15f-40fb-b79a-8368f2c2e287',
                    'code' => 'LOGIN_AUDIT_VIEW_LOGIN_AUDIT_LOGS',
                    'createdBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'modifiedBy' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
                    'isDeleted' => 0
                ],
            ];

        $roleClaims = [
            [
                'id' => Str::uuid(36),
                'actionId' => '0a2e19fc-d9f2-446c-8ca3-e6b8b73b5f9b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_EDIT_USER',
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '18a5a8f6-7cb6-4178-857d-b6a981ea3d4f',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ROLE_DELETE_ROLE'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '1c7d3e31-08ad-43cf-9cf7-4ffafdda9029',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'DOCUMENT_AUDIT_TRAIL_VIEW_DOCUMENT_AUDIT_TRAIL'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'b2c4d5e6-7f8a-9b0c-1d2e-3f4a5b6c7d8e',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_VIEW_RESPONSE_AUDIT_TRAIL'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'c3d4e5f6-8a9b-0c1d-2e3f-4a5b6c7d8e9f',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_CREATE_RESPONSE_AUDIT_TRAIL'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'd4e5f6a7-9b0c-1d2e-3f4a-5b6c7d8e9f0a',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_UPDATE_RESPONSE_AUDIT_TRAIL'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'e5f6a7b8-0c1d-2e3f-4a5b-6c7d8e9f0a1b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'RESPONSE_AUDIT_TRAIL_DELETE_RESPONSE_AUDIT_TRAIL'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '229ad778-c7d3-4f5f-ab52-24b537c39514',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_DELETE_DOCUMENT'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '239035d5-cd44-475f-bbc5-9ef51768d389',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_CREATE_DOCUMENT'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '2ea6ba08-eb36-4e34-92d9-f1984c908b31',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_SHARE_DOCUMENT'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '31cb6438-7d4a-4385-8a34-b4e8f6096a48',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_VIEW_USERS'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '374d74aa-a580-4928-848d-f7553db39914',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_DELETE_USER'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '3ccaf408-8864-4815-a3e0-50632d90bcb6',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'REMINDER_EDIT_REMINDER'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '3da78b4d-d263-4b13-8e81-7aa164a3688c',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_ADD_REMINDER'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '41f65d07-9023-4cfb-9c7c-0e3247a012e0',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'EMAIL_MANAGE_SMTP_SETTINGS'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '57216dcd-1a1c-4f94-a33d-83a5af2d7a46',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ROLE_VIEW_ROLES'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '595a769d-f7ef-45f3-9f9e-60c58c5e1542',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_SEND_EMAIL'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '5ea48d56-2ed3-4239-bb90-dd4d70a1b0b2',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'REMINDER_DELETE_REMINDER'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '63ed1277-1db5-4cf7-8404-3e3426cb4bc5',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_VIEW_DOCUMENTS'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '6719a065-8a4a-4350-8582-bfc41ce283fb',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_DOWNLOAD_DOCUMENT'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '6bc0458e-22f5-4975-b387-4d6a4fb35201',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'REMINDER_CREATE_REMINDER'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '6f2717fc-edef-4537-916d-2d527251a5c1',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'REMINDER_VIEW_REMINDERS'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '7ba630ca-a9d3-42ee-99c8-766e2231fec1',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'DASHBOARD_VIEW_DASHBOARD'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => '86ce1382-a2b1-48ed-ae81-c9908d00cf3b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_CREATE_USER'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'a8dd972d-e758-4571-8d39-c6fec74b361b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ALL_DOCUMENTS_EDIT_DOCUMENT'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => 'c288b5d3-419d-4dc0-9e5a-083194016d2c',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ROLE_EDIT_ROLE'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'cd46a3a4-ede5-4941-a49b-3df7eaa46428',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'DOCUMENT_CATEGORY_MANAGE_DOCUMENT_CATEGORY'
            ],    [
                'id' => Str::uuid(36),
                'actionId' => 'd4d724fc-fd38-49c4-85bc-73937b219e20',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_RESET_PASSWORD'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'db8825b1-ee4e-49f6-9a08-b0210ed53fd4',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ROLE_CREATE_ROLE'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'fa91ffd9-61ee-4bb1-bf86-6a593cdc7be9',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'ASSIGNED_DOCUMENTS_CREATE_DOCUMENT'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'e506ec48-b99a-45b4-9ec9-6451bc67477b',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_ASSIGN_PERMISSION'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'fbe77c07-3058-4dbe-9d56-8c75dc879460',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'USER_ASSIGN_USER_ROLE'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'ff4b3b73-c29f-462a-afa4-94a40e6b2c4a',
                'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4',
                'claimType' => 'LOGIN_AUDIT_VIEW_LOGIN_AUDIT_LOGS'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'fa91ffd9-61ee-4bb1-bf86-6a593cdc7be9',
                'roleId' => 'ff635a8f-4bb3-4d70-a3ed-c7749030696c',
                'claimType' => 'ASSIGNED_DOCUMENTS_CREATE_DOCUMENT'
            ], [
                'id' => Str::uuid(36),
                'actionId' => 'cd46a3a4-ede5-4941-a49b-3df7eaa46428',
                'roleId' => 'ff635a8f-4bb3-4d70-a3ed-c7749030696c',
                'claimType' => 'DOCUMENT_CATEGORY_MANAGE_DOCUMENT_CATEGORY'
            ], [
                'id' => Str::uuid(36),
                'actionId' => '7ba630ca-a9d3-42ee-99c8-766e2231fec1',
                'roleId' => 'ff635a8f-4bb3-4d70-a3ed-c7749030696c',
                'claimType' => 'DASHBOARD_VIEW_DASHBOARD'
            ]
        ];

        $userRoles = [[
            'id' => Str::uuid(36),
            'userId' => '4d2689d8-6f72-4aa6-911d-2414c1a751af',
            'roleId' => 'f8b6ace9-a625-4397-bdf8-f34060dbd8e4'
        ]];


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
        UserRoles::insert($userRoles);
    }
}
