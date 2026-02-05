<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Installer\RequirementsChecker;
use Illuminate\Contracts\View\View;
use App\Installer\EnvironmentManager;
use App\Installer\FinishesInstallation;
use App\Installer\PermissionsChecker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use App\Installer\PrivilegesChecker;
use App\Installer\PrivilegeNotGrantedException;
use App\Models\Users;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class InstallController extends Controller
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    use FinishesInstallation;
    /**
     * Shows the requirements page
     */
    public function index(RequirementsChecker $checker): View
    {
        $installed = env('INSTALLED', false);
        if ($installed == true) {
            return view('angular');
        }

        $step           = 1;
        $requirements   = $checker->check();
        $php            = $checker->checkPHPversion();
        $memoryLimitMB  = EnvironmentManager::getMemoryLimitInMegabytes();
        $memoryLimitRaw = ini_get('memory_limit');

        return view('installer.requirements', compact(
            'step',
            'php',
            'requirements',
            'memoryLimitMB',
            'memoryLimitRaw'
        ));
    }

    /**
     * Shows the permissions page
     */
    public function permissions(PermissionsChecker $checker): View
    {
        $step        = 2;
        $permissions = $checker->check();

        return view('installer.permissions', compact('step', 'permissions'));
    }


    /**
     * Application setup
     */
    public function setup(): View
    {
        $step = 3;

        $guessedUrl = EnvironmentManager::guessUrl();

        return view('installer.setup', compact(
            'step',
            'guessedUrl',
        ));
    }

    /**
     * Store the environmental variables
     */
    public function setupStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'app_url'           => 'required|url',
            'app_name'          => 'required',
            'database_hostname' => 'required',
            'database_port'     => 'required',
            'database_name'     => 'required',
            'database_username' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('install/setup')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $connection = $this->testDatabaseConnection($request);
            (new PrivilegesChecker($connection))->check();
        } catch (\Exception $e) {
            $this->setDatabaseTestsErrors($validator, $e);

            return redirect('install/setup')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->setEnv('APP_NAME', $request->app_name);
            $this->setEnv('APP_URL', $request->app_url);
            $this->setEnv('DB_HOST', $request->database_hostname);
            $this->setEnv('DB_PORT', $request->database_port);
            $this->setEnv('DB_DATABASE', "'" . $request->database_name . "'");
            $this->setEnv('DB_USERNAME', "'" . $request->database_username . "'");
            $this->setEnv('DB_PASSWORD', $request->database_password ? "'" . $request->database_password . "'"  : '');
        } catch (\Throwable $th) {
            return redirect('install/setup')
                ->withErrors([
                    'general' => 'Failed to write .env file, make sure that the files permissions and ownership are correct. Check documentation on how to setup the permissions and ownership.',
                ]);
        }

        return redirect(rtrim($request->app_url, '/') . '/install/database');
    }

    private function setEnv($key, $value)
    {
        $path = base_path('.env');

        if (is_bool(env($key))) {
            $old = env($key) ? 'true' : 'false';
        } elseif (env($key) === null) {
            $old = 'null';
        } else {
            $old = env($key);
        }

        if (file_exists($path)) {
            $newContent = str_replace(
                "$key=" . $old,
                "$key=" . $value,
                file_get_contents($path)
            );
            file_put_contents($path, $newContent);
        }
    }


    /**
     * Migrate the database
     */
    public function database(): RedirectResponse
    {
        $this->migrate();
        return redirect('install/user');
    }

    /**
     * Display the user step
     */
    public function user(): View
    {
        $step = 4;
        return view('installer.user', compact('step'));
    }

    /**
     * Store the user
     */
    public function userStore(Request $request): RedirectResponse
    {

        $validator = Validator::make($request->all(), [
            'firstName'     => 'required|string|max:191',
            'lastName'     => 'required|string|max:191',
            'email'    => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect('install/user')
                ->withErrors($validator)
                ->withInput();
        }

        $request['password'] = Hash::make($request->password);
        $request['roleIds'] = [];
        $request['userName'] = $request->email;
        $this->userRepository->createUser($request->all());
        $this->setEnv('INSTALLED', true);
        return redirect('install/finalize');
    }

    /**
     * Finalize the installation with redirect
     */
    public function finalize(): RedirectResponse
    {
        $errors = ['general' => ''];

        $user = Users::first();

        if ($user == null) {
            $errors['general'] .= 'Admin User is not setup yet. Please try to setup the Admin User First.';
        } else {
            $this->seed();
        }

        $finalErrors = empty($errors['general']) ? [] : $errors;

        $route = URL::temporarySignedRoute('install.finished', now()->addMinutes(60));

        return redirect($route)->withErrors($finalErrors);
    }

    /**
     * Display the finish step or apply patches
     */
    public function finished(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            return $this->patch($request);
        }

        $step = 5;
        $user = Users::first();

        return view('installer.finish', [
            'step'          => $step,
            'user'          => $user,
        ]);
    }

    /**
     * Set the database tests errors
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param \Exception $e
     *
     * @return void
     */
    protected function setDatabaseTestsErrors($validator, $e)
    {
        if (strstr($e->getMessage(), 'Unknown character set')) {
            $validator->getMessageBag()->add('general', 'At least MySQL 5.6 version is required.');
        } elseif ($e instanceof PrivilegeNotGrantedException) {
            $validator->getMessageBag()->add('privilege', 'The ' . $e->getPriviligeName() . ' privilige is not granted to the database user, the following error occured during tests: ' . $e->getMessage());
        } else {
            $validator->getMessageBag()->add('general', 'Could not establish database connection: ' . $e->getMessage());
            $validator->getMessageBag()->add('database_hostname', 'Please check entered value.');
            $validator->getMessageBag()->add('database_port', 'Please check entered value.');
            $validator->getMessageBag()->add('database_name', 'Please check entered value.');
            $validator->getMessageBag()->add('database_username', 'Please check entered value.');
            $validator->getMessageBag()->add('database_password', 'Please check entered value.');
        }
    }

    /**
     * Test the database connection
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Database\Connection
     */
    protected function testDatabaseConnection($request)
    {
        $params = [
            'driver'    => 'mysql',
            'host'      => $request->database_hostname,
            'database'  => $request->database_name,
            'username'  => $request->database_username,
            'password'  => $request->database_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];

        $hash = md5(json_encode($params));

        Config::set('database.connections.install' . $hash, $params);

        /**
         * @var \Illuminate\Database\Connection
         */
        $connection = DB::connection('install' . $hash);

        // Triggers PDO init, in case of errors, will fail and throw exception
        $connection->getPdo();

        return $connection;
    }
}
