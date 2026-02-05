<?php

namespace App\Installer;

use App\Innoclapps\Facades\Innoclapps;
use Illuminate\Support\Facades\Artisan;
use App\Innoclapps\Facades\ChangeLogger;

trait FinishesInstallation
{
    /**
     * Finalize the installation
     *
     * @return array
     */
    protected function finalizeInstallation()
    {
        $errors = ['general' => ''];

        ChangeLogger::disable();

        try {
            Artisan::call('storage:link');
        } catch (\Exception $e) {
            $errors['general'] .= "Failed to create storage symlink.\n";
        }

        $this->seed();

        if (! Innoclapps::createInstalledFile()) {
            $errors['general'] .= 'Failed to create the installed file. (' . Innoclapps::installedFileLocation() . ").\n";
        }

        $this->optimize();

        return empty($errors['general']) ? [] : $errors;
    }

    /**
     * Migrate the database
     *
     * @return void
     */
    protected function migrate()
    {
        Artisan::call('migrate', [
            '--force' => true,
        ]);
    }

    /**
     * Seed the application database
     *
     * @return void
     */
    protected function seed()
    {
        Artisan::call('db:seed', [
            '--class' => \DatabaseSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * Cache the application bootstrap files
     *
     * @return void
     */
    protected function optimize()
    {
        Artisan::call('docmgt:optimize');
    }
   
}
