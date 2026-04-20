<?php

use Pusher\Pusher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ActionsController;
use App\Http\Controllers\Articles\CategoriesController;
use App\Http\Controllers\Articles\ArticlesController;
use App\Http\Controllers\Blogs\BlogCategoriesController;
use App\Http\Controllers\Blogs\BlogsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleUsersController;
use App\Http\Controllers\UserClaimController;
use App\Http\Controllers\LoginAuditController;
use App\Http\Controllers\DocumentTokenController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\DocumentCommentController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\EmailSMTPSettingController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\DocumentAuditTrailController;
use App\Http\Controllers\DocumentPermissionController;
use App\Http\Controllers\Forums\ForumCategoriesController;
use App\Http\Controllers\Forums\ForumsController;
use App\Http\Controllers\ResponseAuditTrailController;
use App\Http\Controllers\SurverysController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login');
    Route::post('auth/logout', 'logout');
    Route::post('auth/register', 'register');
    Route::post('auth/subscribe', 'subscribe');
    Route::post('auth/forgot', 'forgot');
    Route::post('auth/verify', 'verifyPin');
    Route::post('auth/reset-password', 'resetPassword');
});

Route::get('document/{id}/office-viewer', [DocumentController::class, 'officeviewer']);
Route::get('document/{id}/officeviewer', [DocumentController::class, 'officeviewer']);
Route::get('/company-profile', [CompanyProfileController::class, 'getCompanyProfile']);

Route::middleware(['auth', 'checkBlacklist'])->group(function () {

    Route::post('/company-profile', [CompanyProfileController::class, 'updateCompanyProfile'])
        ->middleware('can:updateCompanyProfile');

    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    Route::post('pusher/auth', function (Request $request) {

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channelName = $request->input('channel_name');

        $cleanChannelName = preg_replace('/^(private-|presence-)/', '', $channelName);

        if (preg_match('/^App\.Models\.User\.(\d+)$/', $cleanChannelName, $matches)) {
            $channelUserId = (int) $matches[1];
            if ((int) $user->id !== $channelUserId) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        } elseif (preg_match('/^conversation\.(\d+)$/', $cleanChannelName, $matches)) {
            $conversationId = $matches[1];
            $conversation = \App\Models\Conversation::find($conversationId);
            if (!$conversation || !$conversation->users()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        } elseif ($channelName !== $cleanChannelName) {
            // Has prefix but didn't match patterns - reject
        } else {
            // No prefix and no match - reject unknown channels
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => config('broadcasting.connections.pusher.options.useTLS', true)
                ]
            );

            $socketId = $request->input('socket_id');
            $channelData = json_encode(['user_id' => $user->id]);

            $auth = $pusher->authorizeChannel($channelName, $socketId, $channelData);

            return response($auth);
        } catch (\Throwable $th) {
            Log::error('Pusher authorization failed: ' . $th->getMessage());
            return response()->json(['error' => 'Pusher authorization failed'], 500);
        }
    });

    Route::group(['middleware' => ['hasToken:USER_VIEW_USERS']], function () {
        Route::get('/user', [UserController::class, 'index']);
    });

    Route::get('/user-dropdown', [UserController::class, 'dropdown']);

    Route::get('/user-with-claim', [UserController::class, 'getUsersWithClaim'])
        ->middleware('hasToken:USER_VIEW_USERS');

    Route::middleware('hasToken:USER_CREATE_USER')->group(function () {
        Route::post('/user', [UserController::class, 'create']);
    });

    Route::middleware('hasToken:USER_EDIT_USER')->group(function () {
        Route::put('/user/{id}', [UserController::class, 'update']);
    });

    Route::middleware('hasToken:USER_DELETE_USER')->group(function () {
        Route::delete('/user/{id}', [UserController::class, 'destroy']);
    });

    Route::middleware('hasToken:USER_EDIT_USER')->group(function () {
        Route::get('/user/{id}', [UserController::class, 'edit']);
    });

    Route::middleware('hasToken:USER_EDIT_USER')->group(function () {
        Route::get('/employees', [UserController::class, 'getEmployes']);
    });


    Route::middleware('hasToken:USER_RESET_PASSWORD')->group(function () {
        Route::post('/user/reset-password', [UserController::class, 'submitResetPassword']);
    });

    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    Route::put('/users/profile', [UserController::class, 'updateUserProfile']);

    Route::middleware('hasToken:USER_ASSIGN_PERMISSION')->group(function () {
        Route::put('/user-claim/{id}', [UserClaimController::class, 'update']);
    });

    Route::middleware('hasToken:DASHBOARD_VIEW_DASHBOARD')->group(function () {
        Route::get('/dashboard/daily-reminder/{month}/{year}', [DashboardController::class, 'getDailyReminders']);
        Route::get('/dashboard/weekly-reminder/{month}/{year}', [DashboardController::class, 'getWeeklyReminders']);
        Route::get('/dashboard/monthly-reminder/{month}/{year}', [DashboardController::class, 'getMonthlyReminders']);
        Route::get('/dashboard/quarterly-reminder/{month}/{year}', [DashboardController::class, 'getQuarterlyReminders']);
        Route::get('/dashboard/half-yearly-reminder/{month}/{year}', [DashboardController::class, 'getHalfYearlyReminders']);
        Route::get('/dashboard/yearly-reminder/{month}/{year}', [DashboardController::class, 'getYearlyReminders']);
        Route::get('/dashboard/one-time-reminder/{month}/{year}', [DashboardController::class, 'getOneTimeReminder']);
        Route::get('/dashboard/get-document-by-category', [DocumentController::class, 'getDocumentsByCategoryQuery']);
        Route::get('/documents/transactions', [DocumentAuditTrailController::class, 'documentsTransactions']);
        Route::get('/documents/extension', [DocumentController::class, 'countByExtension']);
        Route::get('/user', [UserController::class, 'index']);
    });

    Route::get('/category/dropdown', [CategoryController::class, 'getAllCategoriesForDropDown']);
    Route::middleware('hasToken:DOCUMENT_CATEGORY_MANAGE_DOCUMENT_CATEGORY')->group(function () {
        Route::get('category', [CategoryController::class, 'index']);
        Route::post('/category', [CategoryController::class, 'create']);
        Route::put('/category/{id}', [CategoryController::class, 'update']);
        Route::delete('/category/{id}', [CategoryController::class, 'destroy']);
        Route::get('/category/{id}/subcategories', [CategoryController::class, 'subcategories']);
    });

    Route::get('/pages', [PagesController::class, 'index']);
    Route::post('/pages', [PagesController::class, 'create']);
    Route::put('/pages/{id}', [PagesController::class, 'update']);
    Route::delete('/pages/{id}', [PagesController::class, 'destroy']);

    Route::get('/actions', [ActionsController::class, 'index']);
    Route::post('/actions', [ActionsController::class, 'create']);
    Route::put('/actions/{id}', [ActionsController::class, 'update']);
    Route::delete('/actions/{id}', [ActionsController::class, 'destroy']);

    Route::get('/role-dropdown', [RoleController::class, 'dropdown']);

    Route::group(['middleware' => ['hasToken:ROLE_VIEW_ROLES']], function () {
        Route::get('/role', [RoleController::class, 'index']);
    });

    Route::middleware('hasToken:ROLE_CREATE_ROLE')->group(function () {
        Route::post('/role', [RoleController::class, 'create']);
    });

    Route::middleware('hasToken:ROLE_EDIT_ROLE')->group(function () {
        Route::put('/role/{id}', [RoleController::class, 'update']);
    });

    Route::middleware('hasToken:ROLE_DELETE_ROLE')->group(function () {
        Route::delete('/role/{id}', [RoleController::class, 'destroy']);
    });

    Route::middleware('hasToken:ROLE_EDIT_ROLE')->group(function () {
        Route::get('/role/{id}', [RoleController::class, 'edit']);
    });

    Route::middleware('hasToken:EMAIL_MANAGE_SMTP_SETTINGS')->group(function () {
        Route::get('/email-smtp-setting', [EmailSMTPSettingController::class, 'index']);
        Route::post('/email-smtp-setting', [EmailSMTPSettingController::class, 'create']);
        Route::put('/email-smtp-setting/{id}', [EmailSMTPSettingController::class, 'update']);
        Route::delete('/email-smtp-setting/{id}', [EmailSMTPSettingController::class, 'destroy']);
        Route::get('/email-smtp-setting/{id}', [EmailSMTPSettingController::class, 'edit']);
    });

    Route::get('/document/{id}/download/{isVersion}', [DocumentController::class, 'downloadDocument']);
    Route::get('/document/{id}/read-text/{isVersion}', [DocumentController::class, 'readTextDocument']);
    Route::middleware('hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS')->group(function () {
        Route::get('/documents', [DocumentController::class, 'getDocuments']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_CREATE_DOCUMENT,ASSIGNED_DOCUMENTS_CREATE_DOCUMENT']], function () {
        Route::post('/document', [DocumentController::class, 'saveDocument']);
    });

    Route::get('/document/assigned-documents', [DocumentController::class, 'assignedDocuments']);

    Route::get('/document/{id}', [DocumentController::class, 'getDocumentbyId']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_EDIT_DOCUMENT,ASSIGNED_DOCUMENTS_EDIT_DOCUMENT']], function () {
        Route::get('/document/{id}/get-metatag', [DocumentController::class, 'getDocumentMetatags']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_EDIT_DOCUMENT,ASSIGNED_DOCUMENTS_EDIT_DOCUMENT']], function () {
        Route::put('/document/{id}', [DocumentController::class, 'updateDocument']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_DELETE_DOCUMENT,ASSIGNED_DOCUMENTS_DELETE_DOCUMENT']], function () {
        Route::delete('/document/{id}', [DocumentController::class, 'deleteDocument']);
    });

    Route::middleware('hasToken:DOCUMENT_AUDIT_TRAIL_VIEW_DOCUMENT_AUDIT_TRAIL')->group(function () {
        Route::get('/document-audit-trail', [DocumentAuditTrailController::class, 'getDocumentAuditTrails']);
    });

    Route::post('/document-audit-trail', [DocumentAuditTrailController::class, 'saveDocumentAuditTrail']);

    Route::get('/document-comment/{documentId}', [DocumentCommentController::class, 'index']);

    Route::delete('/document-comment/{id}', [DocumentCommentController::class, 'destroy']);

    Route::post('/document-comment', [DocumentCommentController::class, 'saveDocumentComment']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::get('/document-role-permission/{id}', [DocumentPermissionController::class, 'edit']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/document-role-permission', [DocumentPermissionController::class, 'addDocumentRolePermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/document-user-permission', [DocumentPermissionController::class, 'addDocumentUserPermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/document-role-permission/multiple', [DocumentPermissionController::class, 'multipleDocumentsToUsersAndRoles']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::delete('/document-user-permission/{id}', [DocumentPermissionController::class, 'deleteDocumentUserPermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::delete('/document-role-permission/{id}', [DocumentPermissionController::class, 'deleteDocumentRolePermission']);
    });

    Route::get('/document/{id}/is-download-flag/is-permission/{isPermission}', [DocumentPermissionController::class, 'getIsDownloadFlag']);

    Route::get('/document-version/{documentId}', [DocumentVersionController::class, 'index']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION']], function () {
        Route::post('/document-version', [DocumentVersionController::class, 'saveNewVersionDocument']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION']], function () {
        Route::post('/document-version/{id}/restore/{versionId}', [DocumentVersionController::class, 'restoreDocumentVersion']);
    });

    Route::get('/document-token/{documentId}/token', [DocumentTokenController::class, 'getDocumentToken']);
    Route::delete('/document-token/{token}', [DocumentTokenController::class, 'deleteDocumentToken']);
    Route::get('/reminder/{id}/my-reminder', [ReminderController::class, 'edit']);

    Route::middleware('hasToken:USER_ASSIGN_USER_ROLE')->group(function () {
        Route::get('/role-users/{roleId}', [RoleUsersController::class, 'getRoleUsers']);
    });

    Route::middleware('hasToken:USER_ASSIGN_USER_ROLE')->group(function () {
        Route::put('/role-users/{roleId}', [RoleUsersController::class, 'updateRoleUsers']);
    });

    Route::middleware('hasToken:LOGIN_AUDIT_VIEW_LOGIN_AUDIT_LOGS')->group(function () {
        Route::get('/login-audit', [LoginAuditController::class, 'getLoginAudit']);
    });

    Route::middleware('hasToken:REMINDER_VIEW_REMINDERS')->group(function () {
        Route::get('/reminder/all', [ReminderController::class, 'getReminders']);
    });

    Route::middleware('hasToken:REMINDER_CREATE_REMINDER')->group(function () {
        Route::post('/reminder', [ReminderController::class, 'addReminder']);
    });

    Route::middleware('hasToken:REMINDER_EDIT_REMINDER')->group(function () {
        Route::get('/reminder/{id}', [ReminderController::class, 'edit']);
    });

    Route::middleware('hasToken:REMINDER_EDIT_REMINDER')->group(function () {
        Route::put('/reminder/{id}', [ReminderController::class, 'updateReminder']);
    });

    Route::middleware('hasToken:REMINDER_DELETE_REMINDER')->group(function () {
        Route::delete('/reminder/{id}', [ReminderController::class, 'deleteReminder']);
    });

    Route::get('/reminder/all/current-user', [ReminderController::class, 'getReminderForLoginUser']);

    Route::get('/reminder/all/currentuser', [ReminderController::class, 'getReminderForLoginUser']);

    Route::delete('/reminder/current-user/{id}', [ReminderController::class, 'deleteReminderCurrentUser']);

    Route::delete('/reminder/currentuser/{id}', [ReminderController::class, 'deleteReminderCurrentUser']);

    Route::get('/user-notifications', [UserNotificationController::class, 'getNotifications']);
    Route::post('/user-notification/mark-as-read', [UserNotificationController::class, 'markAsRead']);
    Route::post('/user-notification/mark-all-as-read', [UserNotificationController::class, 'markAllAsRead']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_SEND_EMAIL']], function () {
        Route::post('/email', [EmailController::class, 'sendEmail']);
    });

    Route::post('/languages', [LanguageController::class, 'saveLanguage']);
    Route::delete('/languages/{id}', [LanguageController::class, 'deleteLanguage']);
    Route::get('/languages', [LanguageController::class, 'getLanguages']);
    Route::get('/default-language', [LanguageController::class, 'defaultlanguage']);
    Route::get('/language-by-id/{id}', [LanguageController::class, 'getFileContentById']);

    Route::prefix('conversations')->controller(ConversationController::class)->group(function () {

        Route::get('', 'conversationsByUser');
        Route::get('{id}/messages', 'conversationMessages');
        Route::get('{id}/users', 'conversationUsers');
        Route::delete('delete/{id}', 'conversationDelete');
        Route::put('update/{id}', 'conversationUpdate');


        Route::post('add-user', 'conversationAddUser');
        Route::post('remove-user', 'conversationRemoveUser');
        Route::post('create', 'conversationCreate');

        Route::post('message', 'messageSend');
        Route::put('message/{id}/seen', 'messageSeen');
        Route::put('message/{id}/reaction', 'messageReaction');
    });
});

Route::get('/public-articles', [UserController::class, 'getAllPublic']);

Route::prefix('articles')->group(function () {
    Route::controller(ArticlesController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');

        Route::middleware(['auth'])->post('comments/{id}', 'addComment');
        Route::middleware(['auth'])->delete('comments/delete/{commentId}', 'deleteComment');
    });

    Route::prefix('categories')->controller(CategoriesController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');
    });
});

Route::prefix('blogs')->group(function () {

    Route::controller(BlogsController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');

        Route::middleware(['auth'])->post('comments/{id}', 'addComment');
        Route::middleware(['auth'])->delete('comments/delete/{commentId}', 'deleteComment');
        Route::middleware(['auth'])->post('reactions/{id}', 'addReaction');
    });

    Route::prefix('categories')->controller(BlogCategoriesController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');
    });
});

Route::prefix('forums')->group(function () {

    Route::controller(ForumsController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');

        Route::middleware(['auth'])->post('comments/{id}', 'addComment');
        Route::middleware(['auth'])->post('reactions/{id}', 'addReaction');
        Route::middleware(['auth'])->delete('comments/delete/{commentId}', 'deleteComment');
    });

    Route::prefix('categories')->controller(ForumCategoriesController::class)->group(function () {
        Route::get('', 'getAll');
        Route::get('/get/{id}', 'getOne');
        Route::middleware(['auth'])->put('update/{id}', 'update');
        Route::middleware(['auth'])->post('create', 'create');
        Route::middleware(['auth'])->delete('delete/{id}', 'delete');
    });
});

Route::prefix('response-audit')->controller(ResponseAuditTrailController::class)->group(function () {
    Route::middleware(['auth', 'hasToken:RESPONSE_AUDIT_TRAIL_VIEW_RESPONSE_AUDIT_TRAIL'])->group(function () {
        Route::get('', 'getResponseAuditTrails');
        Route::get('get/{id}', 'getResponseAuditTrail');
        Route::get('forums-dropdown', 'getForumsDropdown');
        Route::get('users-dropdown', 'getUsersDropdown');
        Route::get('transactions', 'responseTransactions');
    });
    Route::middleware(['auth'])->post('create', 'createResponseAuditTrail');
    Route::middleware(['auth'])->put('update/{id}', 'updateResponseAuditTrail');
    Route::middleware(['auth'])->delete('delete/{id}', 'deleteResponseAuditTrail');
});

Route::prefix('surveys')->controller(SurverysController::class)->group(function () {
    Route::get('', 'getAll');
    Route::get('get/{id}', 'getOne');
    Route::get('latest', 'getLast');
    Route::get('statistics/{id}', 'statistics');
    Route::middleware(['auth'])->put('update/{id}', 'update');
    Route::middleware(['auth'])->post('create', 'create');
    Route::middleware(['auth'])->post('answer/{id}', 'answer');
    Route::middleware(['auth'])->delete('delete/{id}', 'delete');
});

Route::get('/i18n/{fileName}', [LanguageController::class, 'downloadFile']);