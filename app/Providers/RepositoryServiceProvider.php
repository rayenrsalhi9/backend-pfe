<?php

namespace App\Providers;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Implementation\CategoryRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Implementation\PagesRepository;
use App\Repositories\Contracts\PagesRepositoryInterface;
use App\Repositories\Implementation\ActionsRepository;
use App\Repositories\Contracts\ActionsRepositoryInterface;
use App\Repositories\Contracts\CompanyProfileRepositoryInterface;
use App\Repositories\Implementation\RoleRepository;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Implementation\UserRepository;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Implementation\UserRoleRepository;
use App\Repositories\Contracts\UserClaimRepositoryInterface;
use App\Repositories\Implementation\UserClaimRepository;
use App\Repositories\Contracts\UserRoleRepositoryInterface;
use App\Repositories\Implementation\EmailSMTPSettingRepository;
use App\Repositories\Contracts\EmailSMTPSettingRepositoryInterface;
use App\Repositories\Implementation\DocumentRepository;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Implementation\DocumentMetaDataRepository;
use App\Repositories\Contracts\DocumentMetaDataRepositoryInterface;
use App\Repositories\Implementation\DocumentVersionsRepository;
use App\Repositories\Contracts\DocumentVersionsRepositoryInterface;
use App\Repositories\Implementation\DocumentPermissionRepository;
use App\Repositories\Contracts\DocumentPermissionRepositoryInterface;
use App\Repositories\Implementation\DocumentCommentRepository;
use App\Repositories\Contracts\DocumentCommentRepositoryInterface;
use App\Repositories\Implementation\DocumentAuditTrailRepository;
use App\Repositories\Contracts\DocumentAuditTrailRepositoryInterface;
use App\Repositories\Implementation\RoleUsersRepository;
use App\Repositories\Contracts\RoleUsersRepositoryInterface;
use App\Repositories\Implementation\LoginAuditRepository;
use App\Repositories\Contracts\LoginAuditRepositoryInterface;
use App\Repositories\Implementation\ReminderRepository;
use App\Repositories\Contracts\ReminderRepositoryInterface;
use App\Repositories\Implementation\DashboardRepository;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Implementation\UserNotificationRepository;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use App\Repositories\Implementation\SendEmailRepository;
use App\Repositories\Contracts\SendEmailRepositoryInterface;
use App\Repositories\Contracts\NotificationScheduleInterface;
use App\Repositories\Implementation\NotificationScheduleRepository;
use App\Repositories\Contracts\ConnectionMappingRepositoryInterface;
use App\Repositories\Implementation\ConnectionMappingRepository;
use App\Repositories\Contracts\DocumentTokenRepositoryInterface;
use App\Repositories\Implementation\DocumentTokenRepository;
use App\Repositories\Contracts\EmailRepositoryInterface;
use App\Repositories\Implementation\CompanyProfileRepository;
use App\Repositories\Implementation\EmailRepository;



class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(PagesRepositoryInterface::class, PagesRepository::class);
        $this->app->bind(ActionsRepositoryInterface::class, ActionsRepository::class);
        $this->app->bind(ScreenOperationRepositoryInterface::class, ScreenOperationRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserRoleRepositoryInterface::class, UserRoleRepository::class);
        $this->app->bind(UserClaimRepositoryInterface::class, UserClaimRepository::class);
        $this->app->bind(EmailSMTPSettingRepositoryInterface::class, EmailSMTPSettingRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(DocumentMetaDataRepositoryInterface::class, DocumentMetaDataRepository::class);
        $this->app->bind(DocumentVersionsRepositoryInterface::class, DocumentVersionsRepository::class);
        $this->app->bind(DocumentPermissionRepositoryInterface::class, DocumentPermissionRepository::class);
        $this->app->bind(DocumentCommentRepositoryInterface::class, DocumentCommentRepository::class);
        $this->app->bind(DocumentAuditTrailRepositoryInterface::class, DocumentAuditTrailRepository::class);
        $this->app->bind(RoleUsersRepositoryInterface::class, RoleUsersRepository::class);
        $this->app->bind(LoginAuditRepositoryInterface::class, LoginAuditRepository::class);
        $this->app->bind(ReminderRepositoryInterface::class, ReminderRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(UserNotificationRepositoryInterface::class, UserNotificationRepository::class);
        $this->app->bind(SendEmailRepositoryInterface::class, SendEmailRepository::class);
        $this->app->bind(NotificationScheduleInterface::class, NotificationScheduleRepository::class);
        $this->app->bind(ConnectionMappingRepositoryInterface::class, ConnectionMappingRepository::class);
        $this->app->bind(DocumentTokenRepositoryInterface::class, DocumentTokenRepository::class);
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);
        $this->app->bind(CompanyProfileRepositoryInterface::class, CompanyProfileRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
