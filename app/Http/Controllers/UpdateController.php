<?php

namespace App\Http\Controllers;

use App\Installer\RequirementsChecker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class UpdateController extends Controller
{
    public function index(RequirementsChecker $checker)
    {
        $this->clearCache();
        $isUpdateAvailable = false;
        $installed = $this->getInstalledVersion();
        $currentVerion = $this->getCurrentVersion();

        if (version_compare(
            $currentVerion,
            $installed
        ) != 0) {
            $isUpdateAvailable = true;
        }

        $requirements = collect([]);

        if ($isUpdateAvailable) {
            $requirementsCheck   = $checker->check();
            $extensions = $requirementsCheck['results']['php'];
            foreach ($extensions as $extension => $enabled) {
                if (!$enabled) {
                    $requirements[] =   [
                        'path' => $extension,
                        'result' => false,
                        'errorMessage' => 'PHP ' . $extension . ' extension is required.',
                    ];
                }
            }

            $directories = [
                '',
                'storage',
                'storage/app',
                'storage/logs',
                'storage/framework',
                'public',
            ];

            $baseDir = base_path();
            foreach ($directories as $directory) {
                $path = rtrim("$baseDir/$directory", '/');
                $writable = is_writable($path);
                if (!$writable) {
                    $result = [
                        'path' => $path,
                        'result' => false,
                        'errorMessage' => '',
                    ];
                    $result['errorMessage'] = is_dir($path)
                        ? 'Make this directory writable by giving it 755 or 777 permissions via file manager.'
                        : 'Make this directory writable by giving it 644 permissions via file manager.';
                    $requirements[] = $result;
                }
            }
        }

        return view('update.update')->with([
            'requirements' => $requirements,
            'isRequirementsErrors' => $requirements->count() > 0,
            'isUpdateAvailable' => $isUpdateAvailable
        ]);
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:cache');
    }

    public function update()
    {
        $currentVerion = $this->getCurrentVersion();

        // migrate database
        Artisan::call('migrate', [
            '--force' => true,
        ]);

        $this->RunSeeder();

        $this->clearCache();

        $this->setEnv('APP_VERSION', $currentVerion);

        return view('update.finish');
    }

    private function RunSeeder()
    {
        Artisan::call('db:seed', [
            '--class' => \LanguageSeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => \PermissionSeederV2::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => \PermissionSeederV21::class,
            '--force' => true,
        ]);
    }

    private function getCurrentVersion(): string
    {
        return Config::get('constants.APP_VERSION');
    }

    private function getInstalledVersion(): string
    {
        return env('APP_VERSION', '1.0.0');
    }

    private function setEnv($key, $value)
    {
        $path = base_path('.env');

        if (is_bool(env($key))) {
            $old = env($key) ? 'true' : 'false';
        } else {
            $old = env($key);
        }

        if (file_exists($path)) {
            if ($old != null) {
                $newContent = str_replace(
                    "$key=" . $old,
                    "$key=" . $value,
                    file_get_contents($path)
                );
                file_put_contents($path, $newContent);
            } else {

                $newContent = file_get_contents($path);
                file_put_contents($path, $newContent . $key . "=" . $value);
            }
        }
    }
}
