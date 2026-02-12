<?php

use Pusher\Pusher;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Events\ConversationEvent;
use Illuminate\Support\Facades\Auth;
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

// use App\Http\Controllers\PublicArticlesController;

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

Route::get('document/{id}/officeviewer', [DocumentController::class, 'officeviewer']);
Route::get('/companyProfile', [CompanyProfileController::class, 'getCompanyProfile']);
Route::post('/companyProfile', [CompanyProfileController::class, 'updateCompanyProfile']);

Route::middleware(['auth'])->group(function () {

    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    /* Route::get('/user/{id}', [UserController::class, 'edit']); */

    Route::post('pusher/auth', function (Request $request) {

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channelName = $request->input('channel_name');
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => false
            ]
        );

        $socketId = $request->input('socket_id');
        $channelData = json_encode(['user_id' => $user->id]);

        $auth = $pusher->authorizeChannel($channelName, $socketId, $channelData);

        return response($auth);
    });

    Route::group(['middleware' => ['hasToken:USER_VIEW_USERS']], function () {
        Route::get('/user', [UserController::class, 'index']);
    });

    Route::get('/user-dropdown', [UserController::class, 'dropdown']);

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
        Route::get('/employes', [UserController::class, 'getEmployes']);
    });


    Route::middleware('hasToken:USER_RESET_PASSWORD')->group(function () {
        Route::post('/user/resetpassword', [UserController::class, 'submitResetPassword']);
    });

    Route::post('/user/changepassword', [UserController::class, 'changePassword']);

    Route::put('/users/profile', [UserController::class, 'updateUserProfile']);

    Route::middleware('hasToken:USER_ASSIGN_PERMISSION')->group(function () {
        Route::put('/userClaim/{id}', [UserClaimController::class, 'update']);
    });

    Route::middleware('hasToken:DASHBOARD_VIEW_DASHBOARD')->group(function () {
        Route::get('/dashboard/dailyreminder/{month}/{year}', [DashboardController::class, 'getDailyReminders']);
        Route::get('/dashboard/weeklyreminder/{month}/{year}', [DashboardController::class, 'getWeeklyReminders']);
        Route::get('/dashboard/monthlyreminder/{month}/{year}', [DashboardController::class, 'getMonthlyReminders']);
        Route::get('/dashboard/quarterlyreminder/{month}/{year}', [DashboardController::class, 'getQuarterlyReminders']);
        Route::get('/dashboard/halfyearlyreminder/{month}/{year}', [DashboardController::class, 'getHalfYearlyReminders']);
        Route::get('/dashboard/yearlyreminder/{month}/{year}', [DashboardController::class, 'getYearlyReminders']);
        Route::get('/dashboard/onetimereminder/{month}/{year}', [DashboardController::class, 'getOneTimeReminder']);
        Route::get('/Dashboard/GetDocumentByCategory', [DocumentController::class, 'getDocumentsByCategoryQuery']);
        Route::get('/documents/transactions', [DocumentAuditTrailController::class, 'documentsTransactions']);
        Route::get('/documents/extension', [DocumentController::class, 'countByExtension']);
        Route::get('/user', [UserController::class, 'index']);
    });

    Route::get('/category/dropdown', [CategoryController::class, 'GetAllCategoriesForDropDown']);
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
        Route::get('/emailSMTPSetting', [EmailSMTPSettingController::class, 'index']);
        Route::post('/emailSMTPSetting', [EmailSMTPSettingController::class, 'create']);
        Route::put('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'update']);
        Route::delete('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'destroy']);
        Route::get('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'edit']);
    });

    Route::get('/document/{id}/download/{isVersion}', [DocumentController::class, 'downloadDocument']);
    Route::get('/document/{id}/readText/{isVersion}', [DocumentController::class, 'readTextDocument']);
    Route::middleware('hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS')->group(function () {
        Route::get('/documents', [DocumentController::class, 'getDocuments']);
    });

    // Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_EDIT_DOCUMENT,ASSIGNED_DOCUMENTS_EDIT_DOCUMENT']], function () {
    //     Route::post('/document', [DocumentController::class, 'saveDocument']);
    // });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_CREATE_DOCUMENT,ASSIGNED_DOCUMENTS_CREATE_DOCUMENT']], function () {
        Route::post('/document', [DocumentController::class, 'saveDocument']);
    });

    Route::get('/document/assignedDocuments', [DocumentController::class, 'assignedDocuments']);

    Route::get('/document/{id}', [DocumentController::class, 'getDocumentbyId']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_EDIT_DOCUMENT,ASSIGNED_DOCUMENTS_EDIT_DOCUMENT']], function () {
        Route::get('/document/{id}/getMetatag', [DocumentController::class, 'getDocumentMetatags']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_EDIT_DOCUMENT,ASSIGNED_DOCUMENTS_EDIT_DOCUMENT']], function () {
        Route::put('/document/{id}', [DocumentController::class, 'updateDocument']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_DELETE_DOCUMENT,ASSIGNED_DOCUMENTS_DELETE_DOCUMENT']], function () {
        Route::delete('/document/{id}', [DocumentController::class, 'deleteDocument']);
    });

    Route::middleware('hasToken:DOCUMENT_AUDIT_TRAIL_VIEW_DOCUMENT_AUDIT_TRAIL')->group(function () {
        Route::get('/documentAuditTrail', [DocumentAuditTrailController::class, 'getDocumentAuditTrails']);
    });

    Route::post('/documentAuditTrail', [DocumentAuditTrailController::class, 'saveDocumentAuditTrail']);

    Route::get('/documentComment/{documentId}', [DocumentCommentController::class, 'index']);

    Route::delete('/documentComment/{id}', [DocumentCommentController::class, 'destroy']);

    Route::post('/documentComment', [DocumentCommentController::class, 'saveDocumentComment']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::get('/DocumentRolePermission/{id}', [DocumentPermissionController::class, 'edit']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/documentRolePermission', [DocumentPermissionController::class, 'addDocumentRolePermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/documentUserPermission', [DocumentPermissionController::class, 'addDocumentUserPermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::post('/documentRolePermission/multiple', [DocumentPermissionController::class, 'multipleDocumentsToUsersAndRoles']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::delete('/documentUserPermission/{id}', [DocumentPermissionController::class, 'deleteDocumentUserPermission']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_SHARE_DOCUMENT,ASSIGNED_DOCUMENTS_SHARE_DOCUMENT']], function () {
        Route::delete('/documentRolePermission/{id}', [DocumentPermissionController::class, 'deleteDocumentRolePermission']);
    });

    Route::get('/document/{id}/isDownloadFlag/isPermission/{isPermission}', [DocumentPermissionController::class, 'getIsDownloadFlag']);

    Route::get('/documentversion/{documentId}', [DocumentVersionController::class, 'index']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION']], function () {
        Route::post('/documentversion', [DocumentVersionController::class, 'saveNewVersionDocument']);
    });

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_UPLOAD_NEW_VERSION']], function () {
        Route::post('/documentversion/{id}/restore/{versionId}', [DocumentVersionController::class, 'restoreDocumentVersion']);
    });

    Route::get('/documentToken/{documentId}/token', [DocumentTokenController::class, 'getDocumentToken']);
    Route::delete('/documentToken/{token}', [DocumentTokenController::class, 'deleteDocumentToken']);
    Route::post('/reminder/document', [ReminderController::class, 'addReminder']);
    Route::get('/reminder/{id}/myreminder', [ReminderController::class, 'edit']);

    Route::middleware('hasToken:USER_ASSIGN_USER_ROLE')->group(function () {
        Route::get('/roleusers/{roleId}', [RoleUsersController::class, 'getRoleUsers']);
    });

    Route::middleware('hasToken:USER_ASSIGN_USER_ROLE')->group(function () {
        Route::put('/roleusers/{roleId}', [RoleUsersController::class, 'updateRoleUsers']);
    });

    Route::middleware('hasToken:LOGIN_AUDIT_VIEW_LOGIN_AUDIT_LOGS')->group(function () {
        Route::get('/loginAudit', [LoginAuditController::class, 'getLoginAudit']);
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

    Route::get('/reminder/all/currentuser', [ReminderController::class, 'getReminderForLoginUser']);

    Route::delete('/reminder/currentuser/{id}', [ReminderController::class, 'deleteReminderCurrentUser']);

    Route::middleware('hasToken:EMAIL_MANAGE_SMTP_SETTINGS')->group(function () {
        Route::put('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'update']);
        Route::delete('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'destroy']);
        Route::get('/emailSMTPSetting/{id}', [EmailSMTPSettingController::class, 'edit']);
    });

    Route::get('/userNotification/notification', [UserNotificationController::class, 'index']);
    Route::get('/userNotification/notifications', [UserNotificationController::class, 'getNotifications']);
    Route::post('/userNotification/MarkAsRead', [UserNotificationController::class, 'markAsRead']);
    Route::post('/UserNotification/MarkAllAsRead', [UserNotificationController::class, 'markAllAsRead']);

    Route::group(['middleware' => ['hasToken:ALL_DOCUMENTS_VIEW_DOCUMENTS,ASSIGNED_DOCUMENTS_SEND_EMAIL']], function () {
        Route::post('/email', [EmailController::class, 'sendEmail']);
    });

    //languages
    Route::post('/languages', [LanguageController::class, 'saveLanguage']);
    Route::delete('/languages/{id}', [LanguageController::class, 'deleteLanguage']);
    Route::get('/languages', [LanguageController::class, 'getLanguages']);
    Route::get('/defaultlanguage', [LanguageController::class, 'defaultlanguage']);
    Route::get('/languageById/{id}', [LanguageController::class, 'getFileContentById']);

    //chat
    Route::prefix('conversations')->controller(ConversationController::class)->group(function () {

        Route::get('', 'conversationsByUser');
        Route::get('{id}/messages', 'conversationMessages');
        Route::get('{id}/users', 'conversationUsers');
        Route::delete('delete/{id}', 'conversationDelete');
        Route::put('update/{id}', 'conversationUpdate');


        Route::post('addUser', 'conversationAddUser');
        Route::post('create', 'conversationCreate');

        Route::post('message', 'messageSend');
        Route::put('message/{id}/seen', 'messageSeen');
        Route::put('message/{id}/reaction', 'messageReaction');
    });

    
        Route::get('/public-articles', [UserController::class, 'getAllPublic'])->withoutMiddleware('auth');
   
    
    

    Route::prefix('articles')->group(function () {

        Route::controller(ArticlesController::class)->group(function () {
            Route::get('', 'getAll');
            Route::get('/get/{id}', 'getOne');
            Route::put('update/{id}', 'update');
            Route::post('create', 'create');
            Route::delete('delete/{id}', 'delete');
        });

        Route::prefix('categories')->controller(CategoriesController::class)->group(function () {
            Route::get('', 'getAll');
            Route::get('/get/{id}', 'getOne');
            Route::put('update/{id}', 'update');
            Route::post('create', 'create');
            Route::delete('delete/{id}', 'delete');
        });
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



// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
